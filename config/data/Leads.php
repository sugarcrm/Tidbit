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

$GLOBALS['dataTool']['Leads']['first_name'] = array('list' => 'first_name_array');
$GLOBALS['dataTool']['Leads']['primary_address_street'] = array(
    'range'      => array('min' => 1, 'max' => 1500),
    'suffixlist' => array('last_name_array', 'streetTypes'),
    'isQuoted'   => true
);
$GLOBALS['dataTool']['Leads']['alt_address_street'] = $GLOBALS['dataTool']['Leads']['primary_address_street'];
$GLOBALS['dataTool']['Leads']['primary_address_city'] = array('list' => 'city_array');
$GLOBALS['dataTool']['Leads']['alt_address_city'] = $GLOBALS['dataTool']['Leads']['primary_address_city'];
$GLOBALS['dataTool']['Leads']['primary_address_state'] = array('list' => 'state_array');
$GLOBALS['dataTool']['Leads']['alt_address_state'] = $GLOBALS['dataTool']['Leads']['primary_address_state'];
$GLOBALS['dataTool']['Leads']['primary_address_postalcode'] = array(
    'range' => array('min' => 15000, 'max' => 99999),
    'isQuoted' => true
);
$GLOBALS['dataTool']['Leads']['alt_address_postalcode'] = $GLOBALS['dataTool']['Leads']['primary_address_postalcode'];
$GLOBALS['dataTool']['Leads']['primary_address_country'] = array('value' => "USA");
$GLOBALS['dataTool']['Leads']['alt_address_country'] = array('value' => "USA");
$GLOBALS['dataTool']['Leads']['account_id'] = array('related' => array('module' => 'Accounts'));
$GLOBALS['dataTool']['Leads']['contact_id'] = array('related' => array('module' => 'Contacts'));
// $GLOBALS['dataTool']['Leads']['lead_source_description'] = array('gibberish' => 8);
$GLOBALS['dataTool']['Leads']['phone_fax'] = array('phone' => true);
$GLOBALS['dataTool']['Leads']['phone_work'] = array('phone' => true);
$GLOBALS['dataTool']['Leads']['phone_other'] = array('phone' => true);
$GLOBALS['dataTool']['Leads']['phone_mobile'] = array('phone' => true);
$GLOBALS['dataTool']['Leads']['phone_home'] = array('phone' => true);
$GLOBALS['dataTool']['Leads']['picture'] = ['skip' => true];
$GLOBALS['dataTool']['Leads']['subscriptions'] = ['probability' => 60];
