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

$GLOBALS['dataTool']['Users']['modified_user_id'] = array('same' => 'id');
$GLOBALS['dataTool']['Users']['status'] = array('value' => "'Active'");
$GLOBALS['dataTool']['Users']['employee_status'] = array('value' => "'Active'");
$GLOBALS['dataTool']['Users']['system_generated_password'] = array('value' => "0");
$GLOBALS['dataTool']['Users']['pwd_last_changed'] = array('skip' => true);
$GLOBALS['dataTool']['Users']['authenticate_id'] = array('skip' => true);
$GLOBALS['dataTool']['Users']['sugar_login'] = array('value' => '1');
$GLOBALS['dataTool']['Users']['picture'] = array('skip' => true);
$GLOBALS['dataTool']['Users']['is_admin'] = array('value' => "0");
$GLOBALS['dataTool']['Users']['external_auth_only'] = array('value' => "0");
$GLOBALS['dataTool']['Users']['receive_notifications'] = array('value' => "0");
$GLOBALS['dataTool']['Users']['default_team'] = array('related' => array('module'=>'Teams'));
$GLOBALS['dataTool']['Users']['user_name'] = array('incname' => 'user');
$GLOBALS['dataTool']['Users']['user_hash'] = array('same_hash' => 'user_name');
$GLOBALS['dataTool']['Users']['user_preferences'] = array('skip' => true);
$GLOBALS['dataTool']['Users']['portal_only'] = array('skip' => true);
$GLOBALS['dataTool']['Users']['is_group'] = array('value' => "0");
