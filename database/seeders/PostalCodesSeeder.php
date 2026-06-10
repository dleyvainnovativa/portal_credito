<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| PostalCodesSeeder
|--------------------------------------------------------------------------
| Loads storage/app/postal/codigos_postales.json into the postal_codes table.
|
| The file is ~28.6 MB. Rather than json_decode the whole thing (which would
| hold the entire decoded array in memory at once), we stream it object-by-
| object with a tiny incremental parser and flush in batches. Memory stays
| flat regardless of file size.
|
| Field mapping (source -> column):
|   codigo_postal -> codigo_postal
|   colonia       -> colonia
|   municipio     -> municipio   (used as the primary "city" downstream)
|   ciudad        -> ciudad      (often empty in the source; kept as fallback)
|   estado        -> estado
*/

class PostalCodesSeeder extends Seeder
{
    private const SOURCE = 'postal/codigos_postales.json';
    private const BATCH  = 1000;

    public function run(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::SOURCE)) {
            $this->command?->error('Postal JSON not found at storage/app/' . self::SOURCE);
            return;
        }

        // Fresh load each run.
        DB::table('postal_codes')->truncate();

        $path = $disk->path(self::SOURCE);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->command?->error('Could not open postal JSON for reading.');
            return;
        }

        $batch = [];
        $total = 0;

        foreach ($this->streamObjects($handle) as $row) {
            $batch[] = [
                'codigo_postal' => substr((string) ($row['codigo_postal'] ?? ''), 0, 5),
                'colonia'       => $this->clean($row['colonia'] ?? '', 180),
                'municipio'     => $this->clean($row['municipio'] ?? null, 180),
                'ciudad'        => $this->clean($row['ciudad'] ?? null, 180),
                'estado'        => $this->clean($row['estado'] ?? '', 180),
            ];

            if (count($batch) >= self::BATCH) {
                DB::table('postal_codes')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('postal_codes')->insert($batch);
            $total += count($batch);
        }

        fclose($handle);
        $this->command?->info("Seeded {$total} postal code rows.");
    }

    private function clean(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return mb_substr($value, 0, $max);
    }

    /**
     * Stream a JSON array of flat objects without loading the whole file.
     * Yields each object as an associative array.
     *
     * Works for the known shape: a top-level array of objects whose values
     * are strings (no nested objects/arrays). Reads in chunks and emits each
     * complete {...} block via json_decode.
     *
     * @param resource $handle
     * @return \Generator<int, array<string,mixed>>
     */
    private function streamObjects($handle): \Generator
    {
        $buffer = '';
        $scan = 0;              // index in $buffer scanned up to
        $depth = 0;
        $inString = false;
        $escaped = false;
        $objectStart = null;

        while (! feof($handle)) {
            $chunk = fread($handle, 1 << 16); // 64 KB
            if ($chunk === false) {
                break;
            }
            $buffer .= $chunk;
            $len = strlen($buffer);

            while ($scan < $len) {
                $ch = $buffer[$scan];

                if ($inString) {
                    if ($escaped) {
                        $escaped = false;
                    } elseif ($ch === '\\') {
                        $escaped = true;
                    } elseif ($ch === '"') {
                        $inString = false;
                    }
                } else {
                    if ($ch === '"') {
                        $inString = true;
                    } elseif ($ch === '{') {
                        if ($depth === 0) {
                            $objectStart = $scan;
                        }
                        $depth++;
                    } elseif ($ch === '}') {
                        $depth--;
                        if ($depth === 0 && $objectStart !== null) {
                            $json = substr($buffer, $objectStart, $scan - $objectStart + 1);
                            $decoded = json_decode($json, true);
                            if (is_array($decoded)) {
                                yield $decoded;
                            }
                            $objectStart = null;
                        }
                    }
                }
                $scan++;
            }

            // Compact: discard the processed prefix so memory stays bounded,
            // adjusting the scan cursor to match the trimmed buffer.
            if ($depth === 0) {
                $buffer = '';
                $scan = 0;
            } elseif ($objectStart !== null && $objectStart > 0) {
                $buffer = substr($buffer, $objectStart);
                $scan -= $objectStart;
                $objectStart = 0;
            }
        }
    }
}
