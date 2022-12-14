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

$GLOBALS['dataTool']['KBContents']['kbdocument_id'] = ['related' => ['module' => 'KBDocuments']];
$GLOBALS['dataTool']['KBContents']['kbarticle_id'] = ['related' => ['module' => 'KBArticles']];
$GLOBALS['dataTool']['KBContents']['team_id'] = ['related' => ['module' => 'Teams']];
$GLOBALS['dataTool']['KBContents']['category_id'] = ['related' => ['module' => 'Categories']];
$GLOBALS['dataTool']['KBContents']['active_rev'] = ['value' => 1];
$GLOBALS['dataTool']['KBContents']['revision'] = ['value' => 1];
$GLOBALS['dataTool']['KBContents']['language'] = ['list' => 'lang_array'];
$GLOBALS['dataTool']['KBContents']['kbdocument_body'] = ['gibberish' => 20];
$GLOBALS['dataTool']['KBContents']['kbsapprover_id'] = ['related' => ['module' => 'Users']];
$GLOBALS['dataTool']['KBContents']['kbscase_id'] = ['related' => ['module' => 'Cases']];
$GLOBALS['dataTool']['KBContents']['description'] = ['value' => ''];

$maxVotes = empty($GLOBALS['modules']['Users']) ? 1 : $GLOBALS['modules']['Users'];
$GLOBALS['dataTool']['KBContents']['useful'] = ['range' => ['min' => 0, 'max' => $maxVotes]];
$GLOBALS['dataTool']['KBContents']['notuseful'] = ['range' => ['min' => 0, 'max' => $maxVotes]];
$GLOBALS['dataTool']['KBContents']['is_external'] = ['value' => 1];
