<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2016 SugarCRM Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

$storageType = 'mysql';
$logQueriesPath = '';
$tidbitCsvDir = 'csv';

// Default Sugar location path, could be overridden by "--sugar_path" argument
$sugarPath = __DIR__ . '/../..';

$modules = array(
    'Tags' => 100,
    'EmailAddresses' => 12000,
    'ACLRoles' => 10,
    'Users' => 100,
    'Teams' => 20,
    'TeamSets' => 120,
    'Accounts' => 1000,
    'Quotes' => 1000,
    'ProductBundles' => 2000,
    'Products' => 4000,
    'Calls' => 24000,
    'Emails' => 16000,
    'EmailText' => 16000,
    'Contacts' => 4000,
    'Leads' => 4000,
    'Opportunities' => 2000,
    'Cases' => 4000,
    'Bugs' => 3000,
    'Meetings' => 8000,
    'Tasks' => 4000,
    'Notes' => 4000,
    'Documents' => 1000,
    'Categories' => 600,
    'KBContents' => 1000,
    'Reports' => 1000,
    'ProductCategories' => 1000,
);

/*
 * The number of product templates per level. The total number is the number of categories
 * times the number of products.
 *
 * example: 1000 categories * 10 templates per level = 10,000 templates
 */
$productTemplatesPerLevel = 3;

/*
 * When using --allmodules this is the number of records to create per-module
 * when the module is not defined in the $modules array.
 */
$all_modules_default_count = 5000;

/*
 * Add a module alias for GUID creation. All records created will have a GUID
 * that begins with the string 'seed-', then the module name or this alias, then
 * a timestamp and an auto incrementing integer. It is recommended that all
 * module names over 10 characters have a shorter alias to ensure that unique
 * GUIDs can be created.
 */
$aliases = array(
    'EmailAddresses' => 'Emadd',
    'ProductBundles' => 'Prodb',
    'Opportunities' => 'Oppty'
);

$tidbit_relationships['TeamSets'] = [
    'Teams' => [
        'type' => 'combinations',
        'degree' => 10,
        'self' => 'team_set_id',
        'you' => 'team_id',
        'you_module' => 'Teams',
        'table' => 'team_sets_teams',
    ],
];

$tidbit_relationships['ACLRoles'] = array(
    'Users' => array(
        'self' => 'role_id',
        'you' => 'user_id',
        'table' => 'acl_roles_users'
    ),
);

$tidbit_relationships['Users'] = array(
    'Calls' => array(
        'self' => 'user_id',
        'you' => 'call_id',
        'table' => 'calls_users'
    ),
    'Meetings' => array(
        'self' => 'user_id',
        'you' => 'meeting_id',
        'table' => 'meetings_users'
    ),
    'EmailAddresses' => array(
        'self'  => 'bean_id',
        'you'   => 'email_address_id',
        'table' => 'email_addr_bean_rel',
        'ratio' => 1,
    ),
);
$tidbit_relationships['Accounts'] = array(
    'EmailAddresses' => array(
        'you' => 'email_address_id',
        'self' => 'bean_id',
        'table' => 'email_addr_bean_rel',
        'ratio' => 1,
    ),
    'Bugs' => array(
        'self' => 'account_id',
        'you' => 'bug_id',
        'table' => 'accounts_bugs'
    ),
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
    'Emails' => array(
        'self' => 'bean_id',
        'you' => 'email_id',
        'table' => 'emails_beans',
        'random_ratio' => array('min' => 0, 'max' => 1), // 50% chance of having Emails Relation
    ),
);
$tidbit_relationships['Contacts'] = array(
    'Accounts' => [
        'self' => 'contact_id',
        'you' => 'account_id',
        'table' => 'accounts_contacts',
        'ratio' => 1,
    ],
    'EmailAddresses' => array(
        'you' => 'email_address_id',
        'self' => 'bean_id',
        'table' => 'email_addr_bean_rel',
        'ratio' => 1,
    ),
    'Opportunities' => array(
        'self' => 'contact_id',
        'you' => 'opportunity_id',
        'table' => 'opportunities_contacts'
    ),
    'Cases' => array(
        'self' => 'contact_id',
        'you' => 'case_id',
        'table' => 'contacts_cases'
    ),
    'Bugs' => array(
        'self' => 'contact_id',
        'you' => 'bug_id',
        'table' => 'contacts_bugs'
    ),
    'Meetings' => array(
        'self' => 'contact_id',
        'you' => 'meeting_id',
        'table' => 'meetings_contacts'
    ),
    'Calls' => array(
        'self' => 'contact_id',
        'you' => 'call_id',
        'table' => 'calls_contacts'
    ),
    'Quotes' => array(
        'self' => 'contact_id',
        'you' => 'quote_id',
        'table' => 'quotes_contacts'
    ),
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
    'Emails' => array(
        'self' => 'bean_id',
        'you' => 'email_id',
        'table' => 'emails_beans',
        'random_ratio' => array('min' => 0, 'max' => 1), // 50% chance of having Emails Relation
    ),
);

$tidbit_relationships['Opportunities'] = array(
    'Accounts' => [
        'self' => 'opportunity_id',
        'you' => 'account_id',
        'table' => 'accounts_opportunities',
        'ratio' => 1,
    ],
    'Quotes' => array(
        'self' => 'opportunity_id',
        'you' => 'quote_id',
        'table' => 'quotes_opportunities'
    ),
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Cases'] = array(
    'Accounts' => [
        'self' => 'case_id',
        'you' => 'account_id',
        'table' => 'accounts_cases',
        'ratio' => 1,
    ],
    'Bugs' => array(
        'self' => 'case_id',
        'you' => 'bug_id',
        'table' => 'cases_bugs'
    ),
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Bugs'] = array(
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Notes'] = array(
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Calls'] = array(
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Tasks'] = array(
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Meetings'] = array(
    'Tags' => array(
        'self' => 'bean_id',
        'you' => 'tag_id',
        'table' => 'tag_bean_rel',
        'random_ratio' => array('min' => 0, 'max' => 3),
        'random_id' => true,
    ),
);
$tidbit_relationships['Quotes'] = array(
    'ProductBundles' => array(
        'self' => 'quote_id',
        'you' => 'bundle_id',
        'table' => 'product_bundle_quote'
    ),
    'Accounts' => [
        'self' => 'quote_id',
        'you' => 'account_id',
        'table' => 'quotes_accounts',
        'ratio' => 1,
        'repeat' => 2,
    ],
);
$tidbit_relationships['ProductBundles'] = array(
    'Products' => array(
        'self' => 'bundle_id',
        'you' => 'product_id',
        'table' => 'product_bundle_product'
    ),
);
$tidbit_relationships['Products'] = array(
    /* Ratio MUST be set for this to not point at itself. */
    'Products' => array(
        'self' => 'parent_id',
        'you' => 'child_id',
        'table' => 'product_product',
        'ratio' => 4
    ),
);
$tidbit_relationships['Leads'] = array(
    'EmailAddresses' => array(
        'you' => 'email_address_id',
        'self' => 'bean_id',
        'table' => 'email_addr_bean_rel',
        'ratio' => 1,
    ),
    'Calls' => array(
        'you' => 'call_id',
        'self' => 'lead_id',
        'table' => 'calls_leads',
        'ratio' => 1,
    ),
    'Meetings' => array(
        'you' => 'meeting_id',
        'self' => 'lead_id',
        'table' => 'meetings_leads',
        'ratio' => 1,
    )
);

$notifications_severity_list = array(
    'alert',
    'information',
    'other',
    'success',
    'warning',
);
