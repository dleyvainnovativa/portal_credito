<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| postal_codes
|--------------------------------------------------------------------------
| One row per colonia (matches the source JSON shape). Lookups always hit
| codigo_postal, so it's indexed. A single postal code maps to one
| estado/municipio but many colonias.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postal_codes', function (Blueprint $table) {
            $table->id();
            $table->char('codigo_postal', 5)->index();
            $table->string('colonia', 180);
            $table->string('municipio', 180)->nullable();
            $table->string('ciudad', 180)->nullable();
            $table->string('estado', 180);
            // No timestamps: this is static reference data.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_codes');
    }
};
