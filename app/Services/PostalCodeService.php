<?php

namespace App\Services;

use App\Models\PostalCode;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| PostalCodeService
|--------------------------------------------------------------------------
| Resolves a 5-digit postal code into { estado, city, colonias[] } from the
| local postal_codes table. No external services.
|
| City mapping: the source data fills `municipio` and often leaves `ciudad`
| empty, so we prefer municipio and fall back to ciudad. For Mexican
| addresses the municipio is the expected "Ciudad / Municipio" value.
|
| Results are cached briefly since the same codes are queried repeatedly
| during a session.
*/

class PostalCodeService
{
    private const CACHE_TTL = 86400; // 24h — static reference data

    /**
     * @return array{found:bool, estado:?string, city:?string, colonias:array<int,string>}
     */
    public function lookup(string $code): array
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== 5) {
            return $this->empty();
        }

        return Cache::remember("postal:$code", self::CACHE_TTL, function () use ($code) {
            $rows = PostalCode::forCode($code)
                ->orderBy('colonia')
                ->get(['colonia', 'municipio', 'ciudad', 'estado']);

            if ($rows->isEmpty()) {
                return $this->empty();
            }

            $first = $rows->first();
            $city = $first->municipio ?: $first->ciudad;

            $colonias = $rows
                ->pluck('colonia')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'found'    => true,
                'estado'   => $first->estado,
                'city'     => $city,
                'colonias' => $colonias,
            ];
        });
    }

    private function empty(): array
    {
        return ['found' => false, 'estado' => null, 'city' => null, 'colonias' => []];
    }
}
