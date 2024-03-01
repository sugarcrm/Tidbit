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

$GLOBALS['dataTool']['Users']['modified_user_id'] = ['same' => 'id'];
$GLOBALS['dataTool']['Users']['status'] = ['value' => "'Active'"];
$GLOBALS['dataTool']['Users']['employee_status'] = ['value' => "'Active'"];
$GLOBALS['dataTool']['Users']['system_generated_password'] = ['value' => "0"];
$GLOBALS['dataTool']['Users']['pwd_last_changed'] = ['skip' => true];
$GLOBALS['dataTool']['Users']['authenticate_id'] = ['skip' => true];
$GLOBALS['dataTool']['Users']['sugar_login'] = ['value' => '1'];
$GLOBALS['dataTool']['Users']['picture'] = ['skip' => true];
$GLOBALS['dataTool']['Users']['is_admin'] = ['value' => "0"];
$GLOBALS['dataTool']['Users']['external_auth_only'] = ['value' => "0"];
$GLOBALS['dataTool']['Users']['receive_notifications'] = ['value' => "0"];
$GLOBALS['dataTool']['Users']['default_team'] = ['related' => ['module' => 'Teams']];
$GLOBALS['dataTool']['Users']['user_name'] = ['incname' => 'user'];
$GLOBALS['dataTool']['Users']['user_hash'] = ['same_sugar_hash' => 'user_name'];
$GLOBALS['dataTool']['Users']['user_preferences'] = ['skip' => true];
$GLOBALS['dataTool']['Users']['portal_only'] = ['skip' => true];
$GLOBALS['dataTool']['Users']['is_group'] = ['value' => "0"];
$GLOBALS['dataTool']['Users']['preferred_language'] = ['value' => "'en_us'"];
$GLOBALS['dataTool']['Users']['phone_fax'] = ['phone' => true];
$GLOBALS['dataTool']['Users']['phone_work'] = ['phone' => true];
$GLOBALS['dataTool']['Users']['phone_other'] = ['phone' => true];
$GLOBALS['dataTool']['Users']['phone_mobile'] = ['phone' => true];
$GLOBALS['dataTool']['Users']['phone_home'] = ['phone' => true];
$GLOBALS['dataTool']['Users']['cookie_consent'] = ['value' => "1"];
$GLOBALS['dataTool']['Users']['license_type'] = ['value' => "'[\"SUGAR_SERVE\",\"SUGAR_SELL_ADVANCED_BUNDLE\"]'"];
