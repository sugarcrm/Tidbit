<?php

$GLOBALS['dataTool']['RevenueLineItems']['opportunity_id'] = [
    'related' => [
        'module' => 'Opportunities',
    ]
];

$GLOBALS['dataTool']['RevenueLineItems']['account_id'] = [
    'related' => [
        'module' => 'Accounts',
    ]
];

$GLOBALS['dataTool']['RevenueLineItems']['product_template_id'] = [
    'related' => [
        'module' => 'ProductTemplates',
    ]
];

$GLOBALS['dataTool']['RevenueLineItems']['category_id'] = [
    'related' => [
        'module' => 'ProductCategories',
    ]
];

$GLOBALS['dataTool']['RevenueLineItems']['probability'] = [
    'range' => [
        'min' => 10, 'max' => 100
    ]
];

$GLOBALS['dataTool']['RevenueLineItems']['date_closed'] = [
    'range' => ['min' => -365, 'max' => 182],
    'type' => 'date',
    'units' => 'days',
];
