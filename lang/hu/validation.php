<?php

declare(strict_types=1);

/**
 * Hungarian validation lines. Only the rules used by the app's forms are
 * defined here; anything missing falls back to the English fallback locale.
 * Messages avoid a leading "a/az" article (which depends on the following
 * word in Hungarian) and use capitalized attribute names instead.
 */
return [
    'required' => ':attribute megadása kötelező.',
    'email' => ':attribute formátuma érvénytelen.',
    'string' => ':attribute szöveg kell, hogy legyen.',

    'min' => [
        'string' => ':attribute legalább :min karakter legyen.',
    ],

    'max' => [
        'string' => ':attribute legfeljebb :max karakter lehet.',
    ],

    'attributes' => [
        'name' => 'Név',
        'email' => 'E-mail cím',
        'message' => 'Üzenet',
    ],
];
