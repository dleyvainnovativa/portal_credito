<?php

namespace App\Services\Contisign;

use App\Services\Files\TempFileService;
use App\Services\Pdf\AnnexGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| ContisignAnnexUploader
|--------------------------------------------------------------------------
| Uploads annex files to Contisign's /api/uploaddocumentfile endpoint and
| returns the `annexed` entries for the datatemplate payload. Mirrors the
| provided setAnnexed.php contract:
|   POST multipart: file=<binary>, type=annexed
|   response: { ImageUrl, path }
|   → { FileName, FieldUrl, Path }
|
| A logical document may have multiple parts (split client-side). Each part
| is uploaded separately and labeled "<Name> (parte N de M)" so they stay
| grouped under one logical document for the reviewer/signer.
*/

class ContisignAnnexUploader
{
    public function __construct(
        private readonly ContisignAuthService $auth,
        private readonly TempFileService $files,
        private readonly AnnexGuard $guard,
    ) {}

    /**
     * Upload all parts of one logical document.
     *
     * @param array  $docMeta  TempFileService metadata: ['original_name','parts'=>[...]]
     * @param string $label    human label for the annex (e.g. "Acta Constitutiva")
     * @return array<int,array{FileName:string,FieldUrl:?string,Path:?string}>
     */
    public function uploadDocument(array $docMeta, string $label): array
    {
        $parts = $docMeta['parts'] ?? [];
        $total = count($parts);
        $entries = [];

        foreach ($parts as $i => $part) {
            $absolute = $this->files->absolutePath($part['stored_path']);
            $ext = pathinfo($part['stored_path'], PATHINFO_EXTENSION);

            // Backend guard: never send an oversized/invalid part to Contisign.
            $check = $this->guard->checkStored($absolute, (int) ($part['size'] ?? 0), $ext);
            if ($check !== true) {
                throw new RuntimeException(is_string($check) ? $check : 'Invalid annex file.');
            }

            $name = $total > 1
                ? sprintf('%s (parte %d de %d)', $label, $i + 1, $total)
                : $label;

            $entries[] = $this->uploadOne($absolute, $name);
        }

        return $entries;
    }

    /** Upload a single file, returning one annexed entry. */
    private function uploadOne(string $absolutePath, string $name): array
    {
        $url = rtrim((string) config('contisign.base_url'), '/')
            . config('contisign.endpoints.upload_file');

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Could not open annex file: {$absolutePath}");
        }

        try {
            $response = Http::timeout((int) config('contisign.http.timeout', 60))
                ->withHeaders(['Authorization' => $this->auth->token()])
                ->attach('file', $handle, basename($absolutePath))
                ->post($url, ['type' => 'annexed']);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if (! $response->successful()) {
            Log::error('Contisign annex upload failed.', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'name'   => $name,
            ]);
            throw new RuntimeException('Error uploading annex to Contisign.');
        }

        $data = $response->json();
        Log::debug('Contisign annex uploaded.', ['name' => $name, 'response' => $data]);

        return [
            'FileName' => $name,
            'FieldUrl' => $data['ImageUrl'] ?? null,
            'Path'     => $data['path'] ?? null,
        ];
    }
}
