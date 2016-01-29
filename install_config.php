<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2010 SugarCRM Inc.
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

$modules = array(
    'Tags' => 1000,
	'EmailAddresses'=>12000,
    'ACLRoles' => 10,
    'Users' => 100,
    'Teams' => 20,
    'Accounts' => 1000,
    'Quotes' => 1000,
    'ProductBundles' => 2000,
    'Products' => 4000,
    'Calls' => 24000,
    'Emails' => 16000,
    'Contacts' => 4000,
    'Leads' => 4000,
    'Opportunities' => 2000,
    'Cases' => 4000,
    'Bugs' => 3000,
    'Meetings' => 8000,
    'Tasks' => 4000,
    'Notes' => 4000,
    'Documents'=>1000,
    'Categories' => 600,
    'KBContents' => 1000,
);

$aliases = array(
    'EmailAddresses' => 'Emadd',
    'ProductBundles' => 'Prodb',
    'Opportunities'  => 'Oppty'
);

$activityModulesBlackList = array(
    'Users',
    'Teams',
    'ProductBundles',
    'EmailAddresses',
    'Documents'
);

$tidbit_relationships['ACLRoles'] = array(
    'Users' => array(
        'self' => 'role_id',
        'you' => 'user_id',
        'table' => 'acl_roles_users'
    ),
);

$tidbit_relationships['Users'] = array(
    'Teams' => array(
        'self' => 'user_id',
        'you' => 'team_id',
        'table' => 'team_memberships'
    ) ,
    'Calls' => array(
        'self' => 'user_id',
        'you' => 'call_id',
        'table' => 'calls_users'
    ) ,
    'Contacts' => array(
        'self' => 'user_id',
        'you' => 'contact_id',
        'table' => 'contacts_users'
    ) ,
    'Meetings' => array(
        'self' => 'user_id',
        'you' => 'meeting_id',
        'table' => 'meetings_users'
    ) ,
);
$tidbit_relationships['Accounts'] = array(
	'EmailAddresses' => array(
		'you' => 'email_address_id',
		'self' => 'bean_id',
		'table' => 'email_addr_bean_rel',
		'ratio'=>1,
	),
    'Contacts' => array(
        'self' => 'account_id',
        'you' => 'contact_id',
        'table' => 'accounts_contacts'
    ) ,
    'Opportunities' => array(
        'self' => 'account_id',
        'you' => 'opportunity_id',
        'table' => 'accounts_opportunities'
    ) ,
    'Bugs' => array(
        'self' => 'account_id',
        'you' => 'bug_id',
        'table' => 'accounts_bugs'
    ) ,
    'Cases' => array(
        'self' => 'account_id',
        'you' => 'case_id',
        'table' => 'accounts_cases'
    ) ,
    'Quotes' => array(
        'self' => 'account_id',
        'you' => 'quote_id',
        'table' => 'quotes_accounts'
    ) ,
);
$tidbit_relationships['Contacts'] = array(
	'EmailAddresses' => array(
		'you' => 'email_address_id',
		'self' => 'bean_id',
		'table' => 'email_addr_bean_rel',
		'ratio'=>1,
	),
    'Opportunities' => array(
        'self' => 'contact_id',
        'you' => 'opportunity_id',
        'table' => 'opportunities_contacts'
    ) ,
    'Cases' => array(
        'self' => 'contact_id',
        'you' => 'case_id',
        'table' => 'contacts_cases'
    ) ,
    'Bugs' => array(
        'self' => 'contact_id',
        'you' => 'bug_id',
        'table' => 'contacts_bugs'
    ) ,
    'Meetings' => array(
        'self' => 'contact_id',
        'you' => 'meeting_id',
        'table' => 'meetings_contacts'
    ) ,
    'Calls' => array(
        'self' => 'contact_id',
        'you' => 'call_id',
        'table' => 'calls_contacts'
    ) ,
    'Quotes' => array(
        'self' => 'contact_id',
        'you' => 'quote_id',
        'table' => 'quotes_contacts'
    ) ,

);
$tidbit_relationships['Opportunities'] = array(
    'Quotes' => array(
        'self' => 'opportunity_id',
        'you' => 'quote_id',
        'table' => 'quotes_opportunities'
    ) ,
);
$tidbit_relationships['Cases'] = array(
    'Bugs' => array(
        'self' => 'case_id',
        'you' => 'bug_id',
        'table' => 'cases_bugs'
    ) ,
);
$tidbit_relationships['Quotes'] = array(
    'ProductBundles' => array(
        'self' => 'quote_id',
        'you' => 'bundle_id',
        'table' => 'product_bundle_quote'
    ) ,
);
$tidbit_relationships['ProductBundles'] = array(
    'Products' => array(
        'self' => 'bundle_id',
        'you' => 'product_id',
        'table' => 'product_bundle_product'
    ) ,
);
$tidbit_relationships['Products'] = array(
    /* Ratio MUST be set for this to not point at itself. */
    'Products' => array(
        'self' => 'parent_id',
        'you' => 'child_id',
        'table' => 'product_product',
        'ratio' => 4
    ) ,
);
$tidbit_relationships['Leads']=array(
'EmailAddresses' => array(
		'you' => 'email_address_id',
		'self' => 'bean_id',
		'table' => 'email_addr_bean_rel',
		'ratio'=>1,
	)
);
//$tidbit_relationships['EmailAddresses'] = array(
//	'Contacts' => array(
//		'self' => 'email_address_id',
//		'you' => 'bean_id',
//		'table' => 'email_addr_bean_rel',
//
//	),
//	
//);

$tbaModuleAccess = 72; // This value related to ACL_ALLOW_SELECTED_TEAMS(Owner & Selected Teams) constant
$tbaFieldAccess = 68; // This value related to ACL_SELECTED_TEAMS_READ_OWNER_WRITE((Owner & Selected Teams) Read/Owner Write) constant
$tbaRestrictionLevelDefault = 'medium';
$tbaRestrictionLevel = array(
    'minimum' => array(
        'modules' => array(
            'delete' => $tbaModuleAccess
        ),
        'fields' => false
    ),
    'medium' => array(
        'modules' => array(
            'create' => $tbaModuleAccess,
            'view' => $tbaModuleAccess,
            'list' => $tbaModuleAccess,
            'edit' => $tbaModuleAccess,
            'delete' => $tbaModuleAccess
        ),
        'fields' => false
    ),
    'maximum' => array(
        'modules' => array(
            'create' => $tbaModuleAccess,
            'view' => $tbaModuleAccess,
            'list' => $tbaModuleAccess,
            'edit' => $tbaModuleAccess,
            'delete' => $tbaModuleAccess
        ),
        'fields' => 'required_only'
    ),
    'full' => array(
        'modules' => array(
            'create' => $tbaModuleAccess,
            'view' => $tbaModuleAccess,
            'list' => $tbaModuleAccess,
            'edit' => $tbaModuleAccess,
            'delete' => $tbaModuleAccess
        ),
        'fields' => true
    ),
);
$roleActions = array(
    'Accounts',
    'Contacts',
    'Leads',
    'Quotes',
    'Opportunities',
    'Bugs',
    'Cases',
    'KBContents'
);

$kbCategoriesNestingLevel = 5;
// Temporary disable generation of Notes for KB because of request
// 'Notes Account RelatedTo Filter' in Jmeter tests failing
$kbNumberOfArticlesWithNotes = 0;
$kbNumberOfArticlesWithRevision = 5;

$kbLanguage = array(
    'list' => array(
        'en' => 'English',
        'de' => 'Deutsch',
        'ru' => 'Russian',
    ),
    'primary' => 'en',
);
