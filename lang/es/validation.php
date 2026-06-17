<?php

/*
|--------------------------------------------------------------------------
| Spanish validation lines
|--------------------------------------------------------------------------
| Standard Laravel Spanish messages plus a custom :attribute map so errors
| reference business-friendly field names. Only the messages actually used
| by the wizard rules are included; extend as needed.
*/

return [

    'accepted'        => 'Debe aceptar :attribute.',
    'before'          => ':attribute debe ser una fecha anterior a hoy.',
    'confirmed'       => 'La confirmación de :attribute no coincide.',
    'date'            => ':attribute no es una fecha válida.',
    'email'           => ':attribute debe ser un correo electrónico válido.',
    'file'            => ':attribute debe ser un archivo.',
    'in'              => ':attribute seleccionado no es válido.',
    'max'             => [
        'string' => ':attribute no debe exceder :max caracteres.',
        'file'   => ':attribute no debe exceder :max kilobytes.',
    ],
    'mimes'           => ':attribute debe ser un archivo de tipo: :values.',
    'regex'           => 'El formato de :attribute no es válido.',
    'required'        => 'El campo :attribute es obligatorio.',
    'required_if'     => 'El campo :attribute es obligatorio.',
    'size'            => [
        'string' => ':attribute debe tener :size caracteres.',
        'array'  => ':attribute debe contener :size elementos.',
    ],
    'array'           => ':attribute debe ser una lista.',
    'string'          => ':attribute debe ser texto.',
    'url'             => ':attribute debe ser una URL válida.',

    /*
    | Custom messages for specific field+rule pairs.
    */
    'custom' => [
        'terms_accepted' => [
            'accepted' => 'Debe aceptar los Términos y Condiciones para continuar.',
        ],
        'rfc' => [
            'regex' => 'El RFC no tiene un formato válido.',
            'size'  => 'El RFC debe tener :size caracteres.',
        ],
        'website' => [
            'regex' => 'Ingrese solo el dominio, por ejemplo: www.sistemascontino.com.mx (sin https://, no poner /).',
        ],
    ],

    /*
    | Friendly attribute names.
    */
    'attributes' => [
        'rfc'                  => 'RFC',
        'first_name'           => 'nombre(s)',
        'last_name'            => 'apellido(s)',
        'website'              => 'sitio web',
        'legal_name'           => 'razón social',
        'commercial_name'      => 'nombre comercial',

        'street'               => 'calle y número',
        'postal_code'          => 'código postal',
        'colonia'              => 'colonia',
        'city'                 => 'ciudad / municipio',
        'state'                => 'estado',
        'country'              => 'país',

        'id_type'              => 'tipo de identificación',
        'id_front'             => 'imagen frontal de la identificación',
        'id_back'              => 'imagen posterior de la identificación',
        'proof_of_address'     => 'comprobante de domicilio',
        'tax_certificate'      => 'constancia de situación fiscal',
        'articles_of_incorporation' => 'acta constitutiva',
        'power_of_attorney'    => 'poder notarial',

        'sales_rep_email'      => 'correo del representante de ventas',
        'contact_first_name'   => 'nombre del contacto',
        'contact_last_name'    => 'apellido del contacto',
        'contact_email'        => 'correo del contacto',
        'contact_phone'        => 'teléfono del contacto',

        'rep_first_name'       => 'nombre del representante',
        'rep_last_name'        => 'apellido del representante',
        'rep_email'            => 'correo del representante',
        'rep_phone'            => 'teléfono del representante',
        'rep_dob'              => 'fecha de nacimiento',

        'references'           => 'referencias',
        'references.*.company' => 'empresa de la referencia',
        'references.*.phone'   => 'teléfono de la referencia',

        'terms_accepted'       => 'los Términos y Condiciones',
    ],

];
