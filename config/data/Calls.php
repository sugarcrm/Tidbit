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

$GLOBALS['dataTool']['Calls']['contact_id'] = array('related' => array('module' => 'Contacts'));
$GLOBALS['dataTool']['Calls']['parent_id'] = array('related' => array('module' => 'Accounts'));
$GLOBALS['dataTool']['Calls']['parent_type'] = array('value' => "'Accounts'");

// Durations in Minutes will be 30 mins, hours from 0 to 8 (selected random)
$GLOBALS['dataTool']['Calls']['duration_minutes'] = array('value' => "'30'");
$GLOBALS['dataTool']['Calls']['duration_hours'] = array('range' => array('min' => 0, 'max' => 8));
/* We want calls to be in the past 90% of the time. */
/* Start should be always bigger than end */
$GLOBALS['dataTool']['Calls']['date_start'] = array(
    'range' => array('min' => -400, 'max' => 36),
    'type' => 'datetime',
    'units' => 'days',
);
$GLOBALS['dataTool']['Calls']['date_end'] = array(
    'same_datetime' => 'date_start',
    'modify' => array(
        'hours' => array(
            'field' => 'duration_hours'
        ),
        'minutes' => '30'
    )
);

$GLOBALS['dataTool']['Calls']['status'] = array('meeting_probability' => true);
$GLOBALS['dataTool']['Calls']['reminder_time'] = array('value' => -1);
$GLOBALS['dataTool']['Calls']['email_reminder_time'] = array('value' => -1);
$GLOBALS['dataTool']['Calls']['subscriptions'] = ['probability' => 75];
$GLOBALS['dataTool']['Calls']['dri_workflow_id'] = array('value' => null);
$GLOBALS['dataTool']['Calls']['dri_workflow_template_id'] = array('value' => null);
$GLOBALS['dataTool']['Calls']['dri_subworkflow_id'] = array('value' => null);
$GLOBALS['dataTool']['Calls']['dri_subworkflow_template_id'] = array('value' => null);
$GLOBALS['dataTool']['Calls']['dri_workflow_task_template_id'] = array('value' => null);
