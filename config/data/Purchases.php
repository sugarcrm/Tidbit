<?php
// aliases
$GLOBALS['aliases']['PurchasesFavorites'] = 'PurFav';
$GLOBALS['aliases']['PurchasesSubscription'] = 'PurSub';

// fields
$GLOBALS['dataTool']['Purchases']['account_id'] = [
    'related' => [
        'module' => 'Accounts',
    ]
];

// relationships
$GLOBALS['tidbit_relationships']['Purchases'] = [
    'Documents' => [
        'self' => 'purchase_id',
        'you' => 'document_id',
        'table' => 'documents_purchases',
    ],
    'Emails' => [
        'self' => 'bean_id',
        'you' => 'email_id',
        'table' => 'emails_beans',
        'ratio' => 1,
    ],
    'Tags' => [
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'ratio' => 2,
        'random_id' => true,
    ],
    'Accounts' => [
        'self' => 'purchase_id',
        'you' => 'account_id',
        'table' => 'accounts_purchases',
    ],
    'Cases' => [
        'self' => 'purchase_id',
        'you' => 'case_id',
        'table' => 'cases_purchases',
    ],
    'Contacts' => [
        'self' => 'purchase_id',
        'you' => 'contact_id',
        'table' => 'contacts_purchases',
    ],
];
