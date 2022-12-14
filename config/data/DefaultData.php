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

$GLOBALS['dataTool']['default'] = [];
$GLOBALS['dataTool']['default']['int'] = ['range' => ['min' => 0, 'max' => 100]];
$GLOBALS['dataTool']['default']['uint'] = ['range' => ['min' => 0, 'max' => 100]];
$GLOBALS['dataTool']['default']['tinyint'] = ['range' => ['min' => 0, 'max' => 100]];
$GLOBALS['dataTool']['default']['long'] = ['range' => ['min' => 0, 'max' => 100]];
$GLOBALS['dataTool']['default']['ulong'] = ['range' => ['min' => 0, 'max' => 100]];
$GLOBALS['dataTool']['default']['double'] = ['range' => ['min' => 0, 'max' => 1000], 'multiply' => 1.7];
$GLOBALS['dataTool']['default']['float'] = ['range' => ['min' => 0, 'max' => 1000], 'multiply' => 2.3];
$GLOBALS['dataTool']['default']['decimal'] = ['range' => ['min' => 0, 'max' => 1000], 'multiply' => 3.2];
$GLOBALS['dataTool']['default']['decimal2'] = ['range' => ['min' => 0, 'max' => 1000], 'multiply' => 4.3];
$GLOBALS['dataTool']['default']['short'] = ['range' => ['min' => 0, 'max' => 10]];
$GLOBALS['dataTool']['default']['varchar'] = ['list' => 'last_name_array'];
// $GLOBALS['dataTool']['default']['text'] = array('gibberish' => -1);
$GLOBALS['dataTool']['default']['text'] = ['value' => "''"];
$GLOBALS['dataTool']['default']['longtext'] = $GLOBALS['dataTool']['default']['text'];
$GLOBALS['dataTool']['default']['url'] = [
    'list' => 'last_name_array',
    'prefix' => "http://www.",
    'suffix' => '.com',
    'toLower' => true,
];
$GLOBALS['dataTool']['default']['date'] = [
    'range' => ['min' => -30, 'max' => 30],
    'type' => 'date',
    'units' => 'days',
];
$GLOBALS['dataTool']['default']['datetime'] = [
    'range' => ['min' => -30 * 24 * 60 * 60, 'max' => 30 * 24 * 60 * 60],
    'type' => 'datetime',
    'units' => 'seconds',
];
$GLOBALS['dataTool']['default']['datetimecombo'] = $GLOBALS['dataTool']['default']['datetime'];
$GLOBALS['dataTool']['default']['time'] = [
    'range' => ['min' => -12 * 60 * 60, 'max' => 12 * 60 * 60],
    'type' => 'time',
    'units' => 'seconds',
];
$GLOBALS['dataTool']['default']['date_entered'] = [
    'range' => ['min' => -90 * 24 * 60 * 60, 'max' => 0],
    'type' => 'datetime',
    'units' => 'seconds',
];
$GLOBALS['dataTool']['default']['date_modified'] = [
    'range' => ['min' => -90 * 24 * 60 * 60, 'max' => 0],
    'type' => 'datetime',
    'units' => 'seconds',
];
//NEEDS THE DROPDOWN LIST TO GET THE PROPER VALUE
$GLOBALS['dataTool']['default']['enum'] = ['set' => false];
$GLOBALS['dataTool']['default']['multienum'] = ['set' => false];
$GLOBALS['dataTool']['default']['bool'] = ['range' => ['min' => 0, 'max' => 1]];
$GLOBALS['dataTool']['default']['email'] = ['list' => 'last_name_array', 'suffix' => '@example.com'];
$GLOBALS['dataTool']['default']['phone'] = ['phone' => true];
$GLOBALS['dataTool']['default']['meeting_probability'] = ['set' => false];
$GLOBALS['dataTool']['default']['team_id'] = ['related' => ['module' => ['Users', 'Teams']]];
$GLOBALS['dataTool']['default']['team_set_id'] = ['related' => ['module' => ['Users', 'TeamSets']]];
$GLOBALS['dataTool']['default']['created_by'] = ['related' => ['module' => 'Users']];
$GLOBALS['dataTool']['default']['assigned_user_id'] = ['related' => ['module' => 'Users']];
$GLOBALS['dataTool']['default']['modified_user_id'] = ['related' => ['module' => 'Users']];
$GLOBALS['dataTool']['default']['currency'] = ['range' => ['min' => 0, 'max' => 100000], 'multiply' => 0.01];

// Tags generation, cause it fills to many data
$GLOBALS['dataTool']['default']['tag'] = ['list' => 'last_name_array'];
$GLOBALS['dataTool']['default']['tag_lower'] = ['same' => 'tag', 'toLower' => true];
$GLOBALS['dataTool']['default']['id'] = [];
$GLOBALS['dataTool']['default']['deleted'] = ['value' => 0];
$GLOBALS['dataTool']['default']['dp_business_purpose'] = ['value' => "''"];
$GLOBALS['dataTool']['default']['_erased_fields'] = ['probability' => 3];
$GLOBALS['dataTool']['default']['subscriptions'] = ['probability' => 2];
$GLOBALS['dataTool']['default']['favorites'] = ['probability' => 3];
$GLOBALS['dataTool']['default']['billing_address_country'] = $GLOBALS['dataTool']['default']['shipping_address_country'] = ['value' => "'USA'"];
$GLOBALS['dataTool']['default']['billing_address_street'] = $GLOBALS['dataTool']['default']['shipping_address_street'] = [
    'range' => ['min' => 1, 'max' => 1500],
    'suffixlist' => ['last_name_array', 'streetTypes'],
    'isQuoted' => true
];
$GLOBALS['dataTool']['default']['billing_address_city'] = $GLOBALS['dataTool']['default']['shipping_address_city'] = ['list' => 'city_array'];
$GLOBALS['dataTool']['default']['billing_address_state'] = $GLOBALS['dataTool']['default']['shipping_address_state'] = ['list' => 'state_array'];
$GLOBALS['dataTool']['default']['billing_address_postalcode'] = $GLOBALS['dataTool']['default']['shipping_address_postalcode'] = [
    'range' => ['min' => 15000, 'max' => 99999],
    'isQuoted' => true
];

// skip special fields
$GLOBALS['dataTool']['default']['sync_key'] = ['skip' => true];
