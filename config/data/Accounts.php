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

$GLOBALS['dataTool']['Accounts']['billing_address_street'] = array(
    'range' => array('min' => 1, 'max' => 1500),
    'suffixlist' => array('last_name_array', 'streetTypes'),
    'isQuoted' => true
);
$GLOBALS['dataTool']['Accounts']['shipping_address_street'] =
    $GLOBALS['dataTool']['Accounts']['billing_address_street'];
$GLOBALS['dataTool']['Accounts']['billing_address_city'] = array('list' => 'city_array');
$GLOBALS['dataTool']['Accounts']['shipping_address_city'] = $GLOBALS['dataTool']['Accounts']['billing_address_city'];
$GLOBALS['dataTool']['Accounts']['billing_address_state'] = array('list' => 'state_array');
$GLOBALS['dataTool']['Accounts']['shipping_address_state'] = $GLOBALS['dataTool']['Accounts']['billing_address_state'];
$GLOBALS['dataTool']['Accounts']['billing_address_postalcode'] = array(
    'range' => array('min' => 15000, 'max' => 99999),
    'isQuoted' => true
);
$GLOBALS['dataTool']['Accounts']['shipping_address_postalcode'] =
    $GLOBALS['dataTool']['Accounts']['billing_address_postalcode'];
$GLOBALS['dataTool']['Accounts']['billing_address_country'] = array('value' => "'USA'");
$GLOBALS['dataTool']['Accounts']['shipping_address_country'] = array('value' => "'USA'");
$GLOBALS['dataTool']['Accounts']['annual_revenue'] = array('range' => array('min' => 10000, 'max' => 500000000));
$GLOBALS['dataTool']['Accounts']['employees'] = array('range' => array('min' => 3, 'max' => 50000));
$GLOBALS['dataTool']['Accounts']['website'] = array('value' => "'www.example.com'");
$GLOBALS['dataTool']['Accounts']['name'] = array(
    'list' => 'last_name_array',
    'prefixlist' => array('companyPre'),
    'suffixlist' => array('companyExt')
);
$GLOBALS['dataTool']['Accounts']['phone_alternate'] = array('phone' => true);
$GLOBALS['dataTool']['Accounts']['phone_fax'] = array('phone' => true);
$GLOBALS['dataTool']['Accounts']['phone_office'] = array('phone' => true);
