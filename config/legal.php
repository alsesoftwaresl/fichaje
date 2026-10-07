<?php

return [
    // Versiona los documentos legales para poder llevar histórico de qué
    // versión aceptó cada empresa. Súbelas cuando el contenido cambie.
    'version_terminos' => '1.0',
    'version_encargo' => '1.0',

    // Datos del titular de la web (obligatorios en España por el artículo 10 de la
    // LSSI). Se leen de .env: lo que esté vacío simplemente no se muestra, así que
    // nunca aparece un dato falso ni un hueco por rellenar en la web pública.
    // Los valores por defecto son datos públicos de la sociedad (BORME / Registro Mercantil);
    // cámbialos en .env si algo no coincide con la escritura.
    'titular' => [
        'nombre' => env('LEGAL_NOMBRE', 'ALSE SOFTWARE, S.L.'),
        'nif' => env('LEGAL_NIF', 'B88656269'),
        'domicilio' => env('LEGAL_DOMICILIO', 'Calle País Valenciano, 17, 03178 Benijófar (Alicante)'),
        'email' => env('LEGAL_EMAIL', 'info@achrono.es'),
        'telefono' => env('LEGAL_TELEFONO'),
        'registro_mercantil' => env('LEGAL_REGISTRO_MERCANTIL', 'Registro Mercantil de Alicante'),
    ],
];
