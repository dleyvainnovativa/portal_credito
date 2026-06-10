<?php

namespace App\Services\Pdf;

use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| AnnexGuard
|--------------------------------------------------------------------------
| Authoritative backend check for annex files. Splitting happens in the
| browser (pdf-lib) so the big original never has to traverse PHP upload
| limits — but the client is untrusted, so every file that arrives is
| re-validated here:
|   - allowed mime/extension (pdf/jpg/jpeg/png)
|   - within the 15 MB annex limit
|
| A file that fails (e.g. a single PDF page that alone exceeds 15 MB and
| therefore couldn't be split) is rejected so the caller can surface the
| "please upload a smaller file" error.
*/

class AnnexGuard
{
    private int $maxBytes;
    private array $mimes;

    public function __construct()
    {
        $this->maxBytes = ((int) config('contisign.annex.max_size_kb', 15360)) * 1024;
        $this->mimes    = array_map('strtolower', config('contisign.annex.mimes', ['pdf', 'jpg', 'jpeg', 'png']));
    }

    public function maxBytes(): int
    {
        return $this->maxBytes;
    }

    /** @return true|string  true if OK, else a translatable error message key */
    public function check(UploadedFile $file): bool|string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (! in_array($ext, $this->mimes, true)) {
            return __('Unsupported file type. Allowed: PDF, JPG, PNG.');
        }

        if ($file->getSize() > $this->maxBytes) {
            return __('This file exceeds the :mb MB limit and could not be split automatically. Please upload a smaller file.', [
                'mb' => round($this->maxBytes / 1024 / 1024),
            ]);
        }

        return true;
    }

    /** Validate an already-stored file by absolute path + size. */
    public function checkStored(string $absolutePath, int $sizeBytes, string $ext): bool|string
    {
        if (! in_array(strtolower($ext), $this->mimes, true)) {
            return __('Unsupported file type. Allowed: PDF, JPG, PNG.');
        }
        if ($sizeBytes > $this->maxBytes) {
            return __('This file exceeds the :mb MB limit and could not be split automatically. Please upload a smaller file.', [
                'mb' => round($this->maxBytes / 1024 / 1024),
            ]);
        }
        return true;
    }
}
