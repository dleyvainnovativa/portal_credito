<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| PostalCode
|--------------------------------------------------------------------------
| Static catalog row. One per colonia. Queried by codigo_postal.
*/

class PostalCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'codigo_postal',
        'colonia',
        'municipio',
        'ciudad',
        'estado',
    ];

    /** Scope: rows for a given 5-digit postal code. */
    public function scopeForCode(Builder $query, string $code): Builder
    {
        return $query->where('codigo_postal', $code);
    }
}
