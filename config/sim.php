<?php

declare(strict_types=1);

use Simtabi\SIS\Enums\Environment;
use Simtabi\SIS\Spec;

/**
 * The built-in SIM profile as data — the reference vocabulary of SIM-STD-0001:2026, encoding the 22 classes,
 * reserved aliases, serial policy, and alias-derivation vocabulary that the core once hardcoded. Every value
 * here is lifted VERBATIM from the original SIM core, so `new Sis()` is byte-identical to the pre-profile
 * engine. `SimProfile::create()` loads this file through `SisProfile::fromFile()`.
 *
 * This is runtime data, not a dev-only file: it ships in the dist tarball (never export-ignored).
 */
return [
    'issuer' => 'SIM',
    'separator' => '-',

    'serials' => [
        'global_start' => 100001,
        'scoped_start' => 1,
        'min_width' => 6,
        'max_width' => 9,
        'default_width' => 6,
    ],

    'aliases' => [
        'grammar' => ['min' => 4, 'max' => 6],
        'reserved' => [
            'SIMT', 'PROS', 'TEST', 'NULL', 'VOID', 'TEMP',
            'DEMO', 'NONE', 'ADMIN', 'ROOT', 'SYST',
        ],
        'derivation' => [
            'legal_suffixes' => [
                'LLC', 'INC', 'INCORPORATED', 'LTD', 'LIMITED', 'CORP', 'CORPORATION',
                'CO', 'COMPANY', 'GMBH', 'PLC', 'SA', 'SAS', 'BV', 'NV', 'AB', 'AG',
                'OY', 'AS', 'PTY', 'LLP', 'LP', 'PC', 'PLLC', 'SARL', 'SRL', 'SPA',
                'KK', 'PBC',
            ],
            'generic_words' => [
                'HOLDINGS', 'GROUP', 'PARTNERS', 'VENTURES', 'SOLUTIONS', 'SERVICES',
                'TECHNOLOGIES', 'TECHNOLOGY', 'CONSULTING', 'SYSTEMS', 'LABS', 'STUDIO',
                'INDUSTRIES', 'INTERNATIONAL', 'GLOBAL', 'AND',
            ],
            'padding' => 'X',
            'vowels' => ['A', 'E', 'I', 'O', 'U'],
            'min' => 4,
            'max' => 6,
        ],
    ],

    'capacity_threshold' => 0.80,

    'spec' => Spec::DOCUMENT,
    'edition' => Spec::EDITION,

    'classes' => [
        // Party and organisation (§3.1)
        ['code' => 'CLT', 'label' => 'Client', 'uses_alias' => true],
        ['code' => 'PRS', 'label' => 'Person', 'subtypes' => ['ENG', 'DES', 'PM', 'OPS', 'BIZ', 'EXE']],
        ['code' => 'VND', 'label' => 'Vendor'],
        ['code' => 'DPT', 'label' => 'Department', 'uses_alias' => true, 'subtypes' => ['ENG', 'DES', 'OPS', 'BIZ', 'FIN', 'EXE']],

        // Commercial (§3.2)
        ['code' => 'PRJ', 'label' => 'Project', 'scoped' => true],
        ['code' => 'SOW', 'label' => 'Statement of Work', 'scoped' => true],
        ['code' => 'CHG', 'label' => 'Change Order', 'scoped' => true],
        ['code' => 'MIL', 'label' => 'Milestone', 'scoped' => true],
        ['code' => 'QUO', 'label' => 'Quote', 'scoped' => true],
        ['code' => 'INV', 'label' => 'Invoice', 'scoped' => true],
        ['code' => 'CRN', 'label' => 'Credit Note', 'scoped' => true],

        // Product (§3.3)
        ['code' => 'PRD', 'label' => 'Product', 'uses_alias' => true],
        ['code' => 'SVC', 'label' => 'Service', 'uses_alias' => true],
        ['code' => 'CMP', 'label' => 'Component', 'uses_alias' => true],
        ['code' => 'REL', 'label' => 'Release'],

        // Asset and governance (§3.4)
        ['code' => 'AST', 'label' => 'Asset', 'subtypes' => ['LAP', 'MON', 'PHN', 'SRV', 'LIC', 'DOM', 'KEY', 'MSC']],
        ['code' => 'DOC', 'label' => 'Document', 'scoped' => true, 'subtypes' => ['ICA', 'MSA', 'SOW', 'NDA', 'CHG', 'DPA', 'IPA', 'EMP', 'QUO']],
        ['code' => 'STD', 'label' => 'Standard', 'serial_start' => 1],
        ['code' => 'ADR', 'label' => 'Decision Record'],

        // Operations (§3.5)
        ['code' => 'TKT', 'label' => 'Ticket', 'scoped' => true],
        ['code' => 'INC', 'label' => 'Incident'],
        ['code' => 'ENV', 'label' => 'Environment', 'scoped' => true, 'subtypes' => Environment::codes()],
    ],
];
