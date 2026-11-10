<?php

return [
    'currency' => 'SAR',
    'duplicate_purchase_policy' => 'prevent',
    'certificate' => [
        'requires_quiz_pass' => true,
        'requires_admin_approval' => true,
    ],
    'bank_transfer' => [
        'bank_name' => 'الراجحي',
        'account_number' => '126000010006089801223',
        'iban' => 'SA9280000126608019801223',
    ],
    'coupon' => [
        'allow_promotions' => false,
    ],
];
