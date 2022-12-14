<?php
$GLOBALS['aliases']['PurchasedLineItems'] = 'PLIs';
$GLOBALS['aliases']['PurchasedLineItemsFavorites'] = 'PLIsFav';
$GLOBALS['aliases']['PurchasedLineItemsSubscription'] = 'PLIsSub';

$GLOBALS['dataTool']['PurchasedLineItems']['subscriptions'] = ['probability' => 0];

// fields
$GLOBALS['dataTool']['PurchasedLineItems']['account_id'] = [
    'related' => [
        'module' => 'Accounts',
    ]
];
$GLOBALS['dataTool']['PurchasedLineItems']['purchase_id'] = [
    'related' => [
        'module' => 'Purchases',
    ]
];

// relationships
$GLOBALS['tidbit_relationships']['PurchasedLineItems'] = array(
    'Documents' => array(
        'self' => 'purchasedlineitem_id',
        'you' => 'document_id',
        'table' => 'documents_purchasedlineitems',
    ),
    'Emails' => array(
        'self' => 'bean_id',
        'you' => 'email_id',
        'table' => 'emails_beans',
        'ratio' => 1,
    ),
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'ratio' => 2,
        'random_id' => true,
    ),
);
