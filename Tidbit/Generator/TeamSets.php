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

require_once('modules/Teams/TeamSet.php');

class Tidbit_Generator_TeamSets extends TeamSet
{
    /**
     * @var DBManager
     */
    public $db;

    /**
     * Array of inserting team_md5's.
     *
     * @var array
     */
    protected $team_md5_array = array();

    /**
     * Array of TeamSets for DataTool
     *
     * @var array
     */
    protected $team_sets = array();

    /**
     * Constructor.
     *
     * @param DBManager $db
     */
    public function __construct(DBManager $db)
    {
        $this->db = $db;
    }

    /**
     * Generate TBA Rules
     */
    function generate()
    {
        TeamSetManager::flushBackendCache();
        $teams_data = array();
        $result = $GLOBALS['db']->query("SELECT id FROM teams");
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $teams_data[$row['id']] = $row['id'];
        }

        $max_teams_per_set = 10;
        if (isset($opts['s']) && $opts['s'] > 0) {
            $max_teams_per_set = $opts['s'];
        }
        sort($teams_data);
        if (isset($_SESSION['fullteamset'])) {
            for ($i = 0, $max = count($teams_data); $i < $max; $i++) {
                for ($j = 1; $j <= $max; $j++) {
                    $set = array_slice($teams_data, $i, $j);
                    $this->generate_full_teamset($set, $teams_data);
                }
            }
        } else {
            foreach ($teams_data as $team_id) {
                //If there are more than 20 teams, a reasonable number of teams for a maximum team set is 10
                if ($max_teams_per_set == 1) {
                    $this->generate_team_set($team_id, array($team_id));
                } elseif (count($teams_data) > $max_teams_per_set) {
                    $this->generate_team_set($team_id, $this->get_random_array($teams_data, $max_teams_per_set));
                } else {
                    $this->generate_team_set($team_id, $teams_data);
                }
            }
        }

        // If number of teams is bigger than max teams in team set,
        // also generate TeamSet with all Teams inside, for relate records
        if (count($teams_data) > $max_teams_per_set) {
            $this->add_teams($teams_data);
        }

        DataTool::$team_sets_array = $this->team_sets;

        // Calculate TeamSet with maximum teams inside
        $maxTeamSet = 0;
        foreach ($this->team_sets as $teamSetId => $teams) {
            if (count($teams) > $maxTeamSet) {
                $maxTeamSet = count($teams);
                DataTool::$max_team_set_id = $teamSetId;
            }
        }
    }

    /**
     * Helper function to generate full team sets
     *
     * @param $set
     * @param $teams
     */
    private function generate_full_teamset($set, $teams)
    {
        $team_count = count($teams);
        for ($i = 0; $i < $team_count; $i++) {
            $this->add_teams(array_unique(array_merge($set, array($teams[$i]))));
        }
    }

    /**
     * generate_team_set
     * Helper function to recursively create team sets
     *
     * @param $primary string The primary team
     * @param $teams string The teams to use
     */
    private function generate_team_set($primary, $teams)
    {
        if (!in_array($primary, $teams)) {
            array_push($teams, $primary);
        }
        $teams = array_reverse($teams);
        $team_count = count($teams);
        for ($i = 0; $i < $team_count; $i++) {
            $this->add_teams($teams);
            array_pop($teams);
        }
    }

    /**
     * Adds teams as described in Beans function addTeams()
     *
     * @param $teams
     */
    private function add_teams($teams)
    {
        $stats = $this->_getStatistics($teams);
        $team_md5 = $stats['team_md5'];

        if (!in_array($team_md5, $this->team_md5_array)) {
            if (count($teams) == 1) {
                $id = $teams[0];
            } else {
                $id = create_guid();
            }
            $date_modified = $this->db->convert("'" . $GLOBALS['timedate']->nowDb() . "'", 'datetime');
            $team_count = $stats['team_count'];
            $name = $team_md5;
            $query = "INSERT INTO team_sets (id, name, team_md5, team_count, date_modified) VALUES ('" . $id . "', '"
                . $name . "', '" . $team_md5 . "', '" . $team_count . "', " . $date_modified . ")";
            loggedQuery($query);

            foreach ($teams as $team_id) {
                $guid = create_guid();
                $query = "INSERT INTO team_sets_teams (id,team_set_id,team_id,date_modified) VALUES ('" . $guid . "', '"
                    . $id . "', '" . $team_id . "', " . $date_modified . ")";
                loggedQuery($query);
                array_push($this->team_md5_array, $team_md5);
                $this->team_sets[$id][] = $team_id;
            }
        }
    }

    /**
     * Given an array return random array elements from the array
     *
     * @param array $array
     * @param int $num
     * @return array
     */
    private function get_random_array($array, $num)
    {
        $rand = array_rand($array, $num);
        $result = array();

        for ($i = 0; $i < $num; $i++) {
            $result[$i] = $array[$rand[$i]];
        }
        return $result;
    }
}
