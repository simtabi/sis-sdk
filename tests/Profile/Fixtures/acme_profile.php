<?php

declare(strict_types=1);

// A profile fixture consumed by SisProfile::fromFile() — a plain PHP array, no framework, no classes.
return [
    'issuer' => 'ACME',
    'separator' => '-',
    'serials' => [
        'global_start' => 500000,
        'scoped_start' => 1,
        'min_width' => 6,
        'max_width' => 9,
        'default_width' => 6,
    ],
    'aliases' => [
        'grammar' => ['min' => 4, 'max' => 6],
        'reserved' => ['ACME', 'ROOT'],
    ],
    'capacity_threshold' => 0.75,
    'classes' => [
        ['code' => 'CST', 'label' => 'Customer', 'uses_alias' => true],
        ['code' => 'ORD', 'label' => 'Order', 'scoped' => true],
        ['code' => 'STD', 'label' => 'Standard', 'serial_start' => 1],
    ],
];
