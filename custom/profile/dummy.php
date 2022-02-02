<?php
return [
    'dummy' => [ //For testing purposes
        'extends' => 'magento',
        'non_mockable' => [
            'string' => 'dummy'
        ],
        'replacements' => [
            'Other\Dummy' => '\Replacement'
        ]
    ]
];
