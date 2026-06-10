<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PostalCodeService;
use Illuminate\Http\JsonResponse;

/*
|--------------------------------------------------------------------------
| PostalCodeController
|--------------------------------------------------------------------------
| Read-only lookup consumed by the address step. Returns 200 with found=false
| (not 404) when a code isn't in the catalog, so the front end can switch to
| manual entry cleanly.
*/

class PostalCodeController extends Controller
{
    public function __construct(private readonly PostalCodeService $service) {}

    public function show(string $code): JsonResponse
    {
        return response()->json($this->service->lookup($code));
    }
}
