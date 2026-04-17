<?php

return [
    'test_user' => [
        'username' => 'RobertBerto',
        'email' => 'robertberto@test.com',
        'first_name' => 'Robert',
        'last_name' => 'Berto',
        'phone' => '0612345678',
        'address' => '123 Test Street',
        'city' => 'Toulon',
        'postal_code' => '83000',
        'password' => 'robertberto',
    ],
    'admin_user' => [
        'username' => 'ma',
        'password' => 'password',
    ],
    'test_cards' => [
        'valid' => '4242424242424242',
        'declined' => '4000000000000002',
        'insufficient_funds' => '4000000000009995',
    ],
    'cart' => [
        [
            'id' => 1,
            'name' => 'Margherita',
            'price' => 10.90,
            'size' => 'moyenne',
            'quantity' => 2,
        ],
        [
            'id' => 3,
            'name' => 'Quattro Formaggi',
            'price' => 20.86,
            'size' => 'grande',
            'quantity' => 1,
        ],
    ],
    'payment' => [
        'method' => 'especes',
        'expiry' => '12/25',
        'cvv' => '123',
        'card_name' => 'Test User',
    ],
    'order' => [
        'notes' => '',
    ],
];
