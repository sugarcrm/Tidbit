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

$GLOBALS['dataTool']['Tasks']['contact_id'] = array('related' => array('module' => 'Contacts'));
$GLOBALS['dataTool']['Tasks']['parent_id'] = array('related' => array('module' => 'Accounts'));
$GLOBALS['dataTool']['Tasks']['parent_type'] = array('value' => "'Accounts'");
$GLOBALS['dataTool']['Tasks']['status'] = array('meeting_probability' => true);
$GLOBALS['dataTool']['Tasks']['contact_phone'] = array('phone' => true);

/* We want tasks to be in the past 90% of the time. */
$GLOBALS['dataTool']['Tasks']['date_start'] = array(
    'range'    => array('min' => -400, 'max' => 36),
    'type'     => 'datetime',
    'basetime' => $GLOBALS['baseTime']
);

$GLOBALS['dataTool']['Tasks']['date_due'] = array(
    'same_datetime' => 'date_start',
    'modify' => array(
        'days' => array(
            'field' => 'duration_days'
        ),
        'days' => '30'
    )
);
