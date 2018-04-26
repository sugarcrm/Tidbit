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

namespace Sugarcrm\Tidbit\Generator;

class UsersGenerator extends ModuleGenerator
{
    protected $defaultPrefs;
    protected $currentDateTime;

    public function __construct(\SugarBean $bean)
    {
        parent::__construct($bean);
        $contents = [
            'timezone' => "America/Phoenix",
            'ut' => 1,
            'Home_TEAMNOTICE_ORDER_BY' => 'date_start',
            'userPrivGuid' => 'a4836211-ee89-0714-a4a2-466987c284f4',
        ];
        $this->defaultPrefs = "'" . base64_encode(serialize($contents)) . "'";
        $this->currentDateTime = "'" . date('Y-m-d H:i:s') . "'";
    }

    protected function getDeleteWhereCondition()
    {
        return "`id` != '1'";
    }

    protected function getDeleteWhereConditionCstm()
    {
        return "`id_c` != '1'";
    }

    public function obliterate()
    {
        parent::obliterate();
        $query = ($GLOBALS['db']->dbType == 'ibm_db2')
            ? 'ALTER TABLE user_preferences ACTIVATE NOT LOGGED INITIALLY WITH EMPTY TABLE'
            : $GLOBALS['db']->truncateTableSQL('user_preferences');
        $GLOBALS['db']->query($query, true);
    }

    public function clean()
    {
        parent::clean();
        $GLOBALS['db']->query("DELETE FROM user_preferences WHERE assigned_user_id LIKE 'seed-%'", true);
    }

    public function generateRecord($n)
    {
        $data = parent::generateRecord($n);

        $userID = $data['id'];
        $data['data']['user_preferences'][] = [
            'id' => "'" . md5($userID) . "'",
            'category' => "'global'",
            'date_entered' => $this->currentDateTime,
            'date_modified' => $this->currentDateTime,
            'assigned_user_id' => "'" . $userID . "'",
            'contents' => $this->defaultPrefs,
        ];

        return $data;
    }
}
