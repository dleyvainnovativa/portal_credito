<?php

/*
|--------------------------------------------------------------------------
| Wizard flow definitions
|--------------------------------------------------------------------------
| Single source of truth for the step sequence of each applicant type.
| The WizardController, stepper, and navigation are all driven by this
| array, so adding / reordering / relabeling steps never requires touching
| controller code.
|
| Each step:
|   key   — stable slug (used in routes, session keys, validation mapping)
|   label — short label shown in the stepper
|   view  — Blade partial under resources/views/wizard/steps/
|
| The Individual flow has 7 steps; the Company flow has 8.
*/

return [

    'individual' => [
        'label' => 'Individual',          // Persona Física
        'steps' => [
            ['key' => 'personal',     'label' => 'Información Personal',     'view' => 'individual.personal'],
            ['key' => 'address',      'label' => 'Domicilio Fiscal',           'view' => 'individual.address'],
            ['key' => 'identification', 'label' => 'Documentacón de Soporte',   'view' => 'individual.identification'],
            ['key' => 'contact',      'label' => 'Contacto Principal',   'view' => 'individual.contact'],
            ['key' => 'references',   'label' => 'Referencias de Proveedores',         'view' => 'individual.references'],
            ['key' => 'authorization', 'label' => 'Autorización',     'view' => 'individual.authorization'],
            ['key' => 'review',       'label' => 'Resumen de la Solicitud',            'view' => 'individual.review'],
        ],
    ],

    'company' => [
        'label' => 'Empresa',             // Persona Moral
        'steps' => [
            ['key' => 'business',     'label' => 'Información de la Empresa',     'view' => 'company.business'],
            ['key' => 'address',      'label' => 'Domicilio Fiscal',  'view' => 'company.address'],
            ['key' => 'documents',    'label' => 'Documentos Corporativos',    'view' => 'company.documents'],
            ['key' => 'representative', 'label' => 'Representante Legal',       'view' => 'company.representative'],
            ['key' => 'contact',      'label' => 'Contacto Principal',   'view' => 'company.contact'],
            ['key' => 'references',   'label' => 'Referencias de Proveedores',         'view' => 'company.references'],
            ['key' => 'authorization', 'label' => 'Autorización',     'view' => 'company.authorization'],
            ['key' => 'review',       'label' => 'Resumen de la Solicitud',            'view' => 'company.review'],
        ],
    ],

];
