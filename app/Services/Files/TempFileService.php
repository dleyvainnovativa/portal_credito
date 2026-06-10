<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| TempFileService
|--------------------------------------------------------------------------
| Owns the lifecycle of uploaded documents while an application is in
| progress. Files are NOT a repository — they live under a per-session token
| directory and are purged after a successful Contisign submission (Phase 7)
| or swept when abandoned.
|
| Layout on the configured temp disk:
|   {temp_dir}/{token}/{documentKey}.{ext}
|
| The service returns/accepts a metadata array per stored document so the
| rest of the app (review screen, payload mapper) never touches disk paths
| directly:
|   [
|     'key'           => 'proof_of_address',
|     'original_name' => 'comprobante.pdf',
|     'stored_path'   => 'onboarding/tmp/<token>/proof_of_address.pdf',
|     'mime'          => 'application/pdf',
|     'size'          => 482113,
|   ]
|
| The owning token is held in WizardState; this service is told the token by
| the caller so it stays free of session coupling.
*/

class TempFileService
{
    public function __construct(
        private readonly string $disk,
        private readonly string $baseDir,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('documents.temp_disk', 'local'),
            trim((string) config('documents.temp_dir', 'onboarding/tmp'), '/'),
        );
    }

    /** Generate a fresh, URL-safe session token for a new application. */
    public function newToken(): string
    {
        return Str::lower(Str::random(32));
    }

    private function dirFor(string $token): string
    {
        // Token is validated to alphanumerics to keep the path safe.
        $safe = preg_replace('/[^a-z0-9]/i', '', $token);
        return "{$this->baseDir}/{$safe}";
    }

    private function disk()
    {
        return Storage::disk($this->disk);
    }

    /**
     * Store (or replace) a document for a given key. Any prior file for the
     * same key is removed first so a key holds exactly one file.
     *
     * @return array<string,mixed> metadata for the stored file
     */
    public function store(string $token, string $key, UploadedFile $file): array
    {
        $this->deleteKey($token, $key);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = "{$key}.{$ext}";
        $dir = $this->dirFor($token);

        $path = $file->storeAs($dir, $filename, ['disk' => $this->disk]);

        return [
            'key'           => $key,
            'original_name' => $file->getClientOriginalName(),
            'stored_path'   => $path,
            'mime'          => $file->getClientMimeType(),
            'size'          => $file->getSize(),
        ];
    }

    /**
     * Store one or more parts for a document key. A document may arrive as
     * multiple parts when a large PDF was split client-side. Prior parts for
     * the key are cleared first.
     *
     * Returns logical-document metadata:
     *   [
     *     'key' => 'acta_constitutiva',
     *     'original_name' => 'acta.pdf',     // base name of the original
     *     'parts' => [ ['stored_path'=>..,'mime'=>..,'size'=>..,'name'=>..], ... ],
     *   ]
     *
     * @param UploadedFile[] $files
     */
    public function storeParts(string $token, string $key, array $files): array
    {
        $this->deleteKey($token, $key);

        $dir = $this->dirFor($token);
        $parts = [];
        $i = 0;

        foreach (array_values($files) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $i++;
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            // Single part keeps the bare key; multiple parts get a suffix.
            $filename = (count($files) > 1) ? "{$key}_part{$i}.{$ext}" : "{$key}.{$ext}";
            $path = $file->storeAs($dir, $filename, ['disk' => $this->disk]);

            $parts[] = [
                'stored_path' => $path,
                'mime'        => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'name'        => $file->getClientOriginalName(),
            ];
        }

        // Derive a clean logical name from the first part.
        $first = $parts[0]['name'] ?? $key;
        $baseName = preg_replace('/-parte\d+(?=\.pdf$)/i', '', $first);

        return [
            'key'           => $key,
            'original_name' => $baseName,
            'parts'         => $parts,
        ];
    }

    /** Remove any file(s) currently stored under a key (incl. split parts). */
    public function deleteKey(string $token, string $key): void
    {
        $dir = $this->dirFor($token);
        $disk = $this->disk();

        if (! $disk->exists($dir)) {
            return;
        }

        foreach ($disk->files($dir) as $file) {
            $stem = pathinfo($file, PATHINFO_FILENAME);
            // matches "key" exactly or "key_partN"
            if ($stem === $key || str_starts_with($stem, "{$key}_part")) {
                $disk->delete($file);
            }
        }
    }

    public function exists(string $token, string $key): bool
    {
        $dir = $this->dirFor($token);
        $disk = $this->disk();

        if (! $disk->exists($dir)) {
            return false;
        }

        foreach ($disk->files($dir) as $file) {
            $stem = pathinfo($file, PATHINFO_FILENAME);
            if ($stem === $key || str_starts_with($stem, "{$key}_part")) {
                return true;
            }
        }
        return false;
    }

    /** Absolute filesystem path for a stored document (for upload to Contisign). */
    public function absolutePath(string $storedPath): string
    {
        return $this->disk()->path($storedPath);
    }

    /** Read raw contents of a stored document. */
    public function get(string $storedPath): ?string
    {
        return $this->disk()->exists($storedPath) ? $this->disk()->get($storedPath) : null;
    }

    /** Remove every file + the directory for a session token. */
    public function purge(string $token): void
    {
        $dir = $this->dirFor($token);
        $disk = $this->disk();
        if ($disk->exists($dir)) {
            $disk->deleteDirectory($dir);
        }
    }

    /**
     * Sweep token directories older than $maxAgeMinutes. Used by the
     * scheduled cleanup command (Phase 7) to reclaim abandoned sessions.
     *
     * @return int number of directories removed
     */
    public function sweepOlderThan(int $maxAgeMinutes): int
    {
        $disk = $this->disk();
        if (! $disk->exists($this->baseDir)) {
            return 0;
        }

        $cutoff = now()->subMinutes($maxAgeMinutes)->getTimestamp();
        $removed = 0;

        foreach ($disk->directories($this->baseDir) as $dir) {
            // lastModified of the directory isn't reliable across drivers;
            // use the newest file inside it instead.
            $files = $disk->files($dir);
            $newest = 0;
            foreach ($files as $f) {
                $newest = max($newest, $disk->lastModified($f));
            }
            // Empty or stale directory → remove.
            if ($newest === 0 || $newest < $cutoff) {
                $disk->deleteDirectory($dir);
                $removed++;
            }
        }

        return $removed;
    }
}
