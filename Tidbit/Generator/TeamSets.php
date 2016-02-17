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
     * @var Tidbit_InsertBuffer
     */
    protected $insertBufferTeamSets;

    /**
     * @var Tidbit_InsertBuffer
     */
    protected $insertBufferTeamSetsTeams;

    /**
     * @var array
     */
    protected $teamIds = array();

    /**
     * Array of inserting team_md5's.
     *
     * @var array
     */
    protected $teamMd5Array = array();

    /**
     * Array of TeamSets for DataTool
     *
     * @var array
     */
    protected $teamSets = array();

    /**
     * Flag to use db or not
     *
     * @var bool
     */
    private $storageTypeDb = true;

    /**
     * Constructor.
     *
     * @param DBManager $db
     * @param Tidbit_StorageAdapter_Storage_Abstract $storageAdapter
     * @param int $insertBatchSize
     * @param array $teamIds
     */
    public function __construct(DBManager $db, Tidbit_StorageAdapter_Storage_Abstract $storageAdapter, $insertBatchSize, $teamIds)
    {
        $this->db = $db;
        $this->insertBufferTeamSets = new Tidbit_InsertBuffer('team_sets', $storageAdapter, $insertBatchSize);
        $this->insertBufferTeamSetsTeams = new Tidbit_InsertBuffer('team_sets_teams', $storageAdapter, $insertBatchSize);
        $this->teamIds = $teamIds;
        $this->storageTypeDb = $storageAdapter::STORE_TYPE != Tidbit_StorageAdapter_Factory::OUTPUT_TYPE_CSV;
        $this->loadTeamIds();
        $this->loadTeamMd5();
    }

    /**
     * Generate TBA Rules
     */
    public function generate()
    {
        TeamSetManager::flushBackendCache();

        $max_teams_per_set = 10;
        if (isset($opts['s']) && $opts['s'] > 0) {
            $max_teams_per_set = $opts['s'];
        }

        if (isset($_SESSION['fullteamset'])) {
            for ($i = 0, $max = count($this->teamIds); $i < $max; $i++) {
                for ($j = 1; $j <= $max; $j++) {
                    $set = array_slice($this->teamIds, $i, $j);
                    $this->generateFullTeamset($set, $this->teamIds);
                }
            }
        } else {
            foreach ($this->teamIds as $team_id) {
                //If there are more than 20 teams, a reasonable number of teams for a maximum team set is 10
                if ($max_teams_per_set == 1) {
                    $this->generateTeamSet($team_id, array($team_id));
                } elseif (count($this->teamIds) > $max_teams_per_set) {
                    $this->generateTeamSet($team_id, $this->getRandomArray($this->teamIds, $max_teams_per_set));
                } else {
                    $this->generateTeamSet($team_id, $this->teamIds);
                }
            }
        }

        // If number of teams is bigger than max teams in team set,
        // also generate TeamSet with all Teams inside, for relate records
        if (count($this->teamIds) > $max_teams_per_set) {
            $this->addTeamsToCreatedTeamSet($this->teamIds);
        }

        DataTool::$team_sets_array = $this->teamSets;

        // Calculate TeamSet with maximum teams inside
        $maxTeamSet = 0;
        foreach ($this->teamSets as $teamSetId => $teams) {
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
    private function generateFullTeamset($set, $teams)
    {
        $team_count = count($teams);
        for ($i = 0; $i < $team_count; $i++) {
            $this->addTeamsToCreatedTeamSet(array_unique(array_merge($set, array($teams[$i]))));
        }
    }

    /**
     * generate_team_set
     * Helper function to recursively create team sets
     *
     * @param $primary string The primary team
     * @param $teams string The teams to use
     */
    private function generateTeamSet($primary, $teams)
    {
        if (!in_array($primary, $teams)) {
            array_push($teams, $primary);
        }
        $teams = array_reverse($teams);
        $team_count = count($teams);
        for ($i = 0; $i < $team_count; $i++) {
            $this->addTeamsToCreatedTeamSet($teams);
            array_pop($teams);
        }
    }

    /**
     * Adds teams as described in Beans function addTeams()
     *
     * @param $teams
     */
    private function addTeamsToCreatedTeamSet($teams)
    {
        $stats = $this->_getStatistics($teams);
        $team_md5 = $stats['team_md5'];

        if (!in_array($team_md5, $this->teamMd5Array)) {
            if (count($teams) == 1) {
                $id = $teams[0];
            } else {
                $id = create_guid();
            }
            $date_modified = $this->db->convert("'" . $GLOBALS['timedate']->nowDb() . "'", 'datetime');

            $installDataTS = array(
                'id' => "'" . $id . "'",
                'name' => "'" . $team_md5 . "'",
                'team_md5' => "'" . $team_md5 . "'",
                'team_count' => $stats['team_count'],
                'date_modified' => $date_modified,
            );

            $this->insertBufferTeamSets->addInstallData($installDataTS);
            $this->teamMd5Array[] = $team_md5;

            foreach ($teams as $team_id) {
                $installDataTST = array(
                    'id' => "'" . create_guid() . "'",
                    'team_set_id' => "'" . $id . "'",
                    'team_id' => "'" . $team_id . "'",
                    'date_modified' => $date_modified,
                );

                $this->insertBufferTeamSetsTeams->addInstallData($installDataTST);
                $this->teamSets[$id][] = $team_id;
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
    private function getRandomArray($array, $num)
    {
        $rand = array_rand($array, $num);
        $result = array();

        for ($i = 0; $i < $num; $i++) {
            $result[$i] = $array[$rand[$i]];
        }
        return $result;
    }

    /**
     * Get array of team ids from db
     *
     */
    private function loadTeamIds()
    {
        if ($this->storageTypeDb) {
            $result = $this->db->query("SELECT id FROM teams");
            while ($row = $this->db->fetchByAssoc($result)) {
                $this->teamIds[] = $row['id'];
            }
        }
        $this->teamIds = array_unique($this->teamIds);
        sort($this->teamIds);
    }

    /**
     * Load team md5 from db
     */
    private function loadTeamMd5()
    {
        if (!$this->storageTypeDb) {
            return;
        }

        $sql = "SELECT team_md5 FROM team_sets";
        $result = $this->db->query($sql);
        while ($row = $this->db->fetchByAssoc($result)) {
            array_push($this->teamMd5Array, $row['team_md5']);
        }
    }
}
