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

class TeamsGenerator extends ModuleGenerator
{
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
        $GLOBALS['db']->query("DELETE FROM team_sets");
        $GLOBALS['db']->query("DELETE FROM team_sets_teams");
        $GLOBALS['db']->query("DELETE FROM team_sets_modules");
    }

    public function clean()
    {
        //TBD: Following 3 queries only tested with Mysql database,
        //  if you are using database such as Oracle, DB2, MSSQL, you might need to refactor those 3 queries.
        $GLOBALS['db']->query(
            "DELETE a FROM team_sets_teams a JOIN teams b ON b.id=a.team_id "
                . "WHERE b.id != '1' AND b.id LIKE 'seed-%'"
        );
        $GLOBALS['db']->query(
            "DELETE a FROM team_sets a LEFT JOIN (SELECT DISTINCT team_set_id FROM team_sets_teams"
                . " WHERE deleted=0) b ON a.id=b.team_set_id WHERE b.team_set_id is null"
        );
        $GLOBALS['db']->query(
            "DELETE a FROM team_sets_modules a left JOIN team_sets b ON a.team_set_id=b.id"
                . " WHERE b.id is null AND a.team_set_id is not null"
        );
        parent::clean();
    }
}
