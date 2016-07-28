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

$GLOBALS['dataTool']['default'] = array();
$GLOBALS['dataTool']['default']['int'] = array('range' => array('min' => 0, 'max' => 100));
$GLOBALS['dataTool']['default']['uint'] = array('range' => array('min' => 0, 'max' => 100));
$GLOBALS['dataTool']['default']['double'] = array('range' => array('min' => 0, 'max' => 1000), 'multiply' => 1.7);
$GLOBALS['dataTool']['default']['float'] = array('range' => array('min' => 0, 'max' => 1000), 'multiply' => 2.3);
$GLOBALS['dataTool']['default']['decimal'] = array('range' => array('min' => 0, 'max' => 1000), 'multiply' => 3.2);
$GLOBALS['dataTool']['default']['decimal2'] = array('range' => array('min' => 0, 'max' => 1000), 'multiply' => 4.3);
$GLOBALS['dataTool']['default']['short'] = array('range' => array('min' => 0, 'max' => 10));
$GLOBALS['dataTool']['default']['varchar'] = array('list' => 'last_name_array');
$GLOBALS['dataTool']['default']['text'] = array('gibberish' => -1);
$GLOBALS['dataTool']['default']['date'] = array(
    'range' => array('min' => -30, 'max' => 30),
    'type' => 'date',
    'basetime' => time()
);
//NEEDS THE DROPDOWN LIST TO GET THE PROPER VALUE
$GLOBALS['dataTool']['default']['enum'] = array('set' => false);
$GLOBALS['dataTool']['default']['datetime'] = array(
    'range' => array('min' => -30, 'max' => 30),
    'type' => 'datetime',
    'basetime' => time()
);
$GLOBALS['dataTool']['default']['time'] = array(
    'range' => array('min' => -30, 'max' => 30),
    'type' => 'time',
    'basetime' => time()
);
$GLOBALS['dataTool']['default']['bool'] = array('range' => array('min' => 0, 'max' => 1));
$GLOBALS['dataTool']['default']['email'] = array('list' => 'last_name_array', 'suffix' => '@example.com');
$GLOBALS['dataTool']['default']['phone'] = array('phone' => true);
$GLOBALS['dataTool']['default']['meeting_probability'] = array('set' => false);
$GLOBALS['dataTool']['default']['team_id'] = array('related' => array('module' => 'Teams'));
$GLOBALS['dataTool']['default']['created_by'] = array('related' => array('module' => 'Users'));
$GLOBALS['dataTool']['default']['assigned_user_id'] = array('related' => array('module' => 'Users'));
$GLOBALS['dataTool']['default']['modified_user_id'] = array('related' => array('module' => 'Users'));

// Tags generation, cause it fills to many data
$GLOBALS['dataTool']['default']['tag'] = array('list' => 'last_name_array');
$GLOBALS['dataTool']['default']['tag_lower'] = array('same' => 'tag', 'toLower' => true);
