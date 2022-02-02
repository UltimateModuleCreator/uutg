<?php
return [
    'default' => [
        'namespace_strategy' => [
            2 => 'Test',
            3 => 'Unit'
        ],
        'default_uses' => [
            'PHPUnit\Framework\TestCase',
            'PHPUnit\Framework\MockObject\MockObject'
        ],
        'header' => [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            ''
        ],
        'non_testable' => [
            '__construct'
        ],
        'strong_type' => true,
        'non_mockable' => [
            'string' => '""',
            'array' => '[]',
            'int' => '1',
            'bool' => 'true',
            'float' =>  '1.00',
            'callable' => 'function () {
            }',
            '\Closure' => 'function () {
            }'
        ]
    ],
];
