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

function loggedQuery($query)
{
    if (!empty($GLOBALS['queryFP'])) {
        fwrite($GLOBALS['queryFP'], $query . "\n");
    }
    return $GLOBALS['db']->query($query, true, "INSERT QUERY FAILED");
}

/* Each DB uses it's own stupid 'preferred' syntax for transactions. */
function startTransaction()
{
}

function endTransaction()
{
}

/**
 * This function streamlines the query execution by performing all of the following:
 * 1. Attempt to insert by chunks, if the DB supports it.  So far only mysql does.
 * 2. Handling the query as a head and a body, dealing with chunks or no chunks.
 * 3. Writing to a log file.
 * @param $head
 * @param $values
 */
function processQueries($head, $values)
{
    if ($GLOBALS['sugar_config']['dbconfig']['db_type'] == 'mysql') {
        $chunks = array_chunk($values, 20, false);
    } else {
        $chunks = array_chunk($values, 1, false);
    }

    foreach ($chunks as $chunk) {
        $query = $head . implode(', ', $chunk);
        $result = loggedQuery($query);
    }
}

/**
 * Given an array return random array elements from the array
 *
 * @param array $array
 * @param int $num
 * @return array
 */
function get_random_array($array, $num)
{
    $rand = array_rand($array, $num);
    $result = array();

    for ($i = 0; $i < $num; $i++) {
        $result[$i] = $array[$rand[$i]];
    }
    return $result;
}

/**
 * generate_team_sets
 * Helper function to recursively create team sets
 *
 * @param $primary string The primary team
 * @param $teams array The teams to use
 */
function generate_team_sets($primary, $teams)
{
    if (!in_array($primary, $teams)) {
        array_push($teams, $primary);
    }
    $teams = array_reverse($teams);
    $team_count = count($teams);

    /** @var TeamSet $teamSet */
    /* ***too slow*** $teamSet = BeanFactory::getBean('TeamSets');
    for ($i = 0; $i < $team_count; $i++) {
        $teamSet->addTeams($teams);
        array_pop($teams);
    }*/
    for ($i = 0; $i < $team_count; $i++) {
        generate_team_set($teams);
        array_pop($teams);
    }
}

/**
 * generate_team_set
 * Creates team set
 *
 * @param $teams array The teams to use
 */
function generate_team_set($teams)
{
    $teamSetId = create_guid();
    $query = 'INSERT INTO team_sets_teams (id, team_set_id, team_id) VALUES ';
    $teamMD5 = '';
    foreach ($teams as $team) {
        $teamSetTeamsId = create_guid();
        $teamMD5 .= $team;
        $query .= "('{$teamSetTeamsId}', '{$teamSetId}', '{$team}'),";
        $_SESSION['allProcessedRecords']++;
    }
    $query = rtrim($query, ', ');
    $GLOBALS['db']->query($query);

    // save team set
    $md5 = md5($teamMD5);
    $cnt = count($teams);
    $query = "INSERT INTO team_sets (id, `name`, team_md5, team_count) VALUES
        ('{$teamSetId}', '{$md5}', '{$md5}', '{$cnt}');";
    $GLOBALS['db']->query($query);
}

function generate_full_teamset($set, $teams)
{
    $team_count = count($teams);
    for ($i = 0; $i < $team_count; $i++) {
        generate_team_set(array_unique(array_merge($set, array($teams[$i]))));
    }
}

/*
* This method is meant to ensure that the user is associated with the team_set. The problem was that
* records would be assigned to a specific user, have an associated team_set_id, but the user was not
* associated with the team_set_id.
*/
function add_team_to_team_set($team_set_id, $user_id)
{
//    DMK 2011/12/01 - this function is disabled to allow faster larger data load

//    if(!isset($GLOBALS['user_team_checked'][$user_id][$team_set_id])){
//        $result = $GLOBALS['db']->query("SELECT default_team FROM users WHERE id=$user_id");
//        while($row = $GLOBALS['db']->fetchByAssoc($result)){
//            $teamset = new TeamSet();
//            $teams = $teamset->getTeamIds($team_set_id);
//            $teams[] = $row['default_team'];
//            $team_set_id = $teamset->addTeams($teams);
//            $GLOBALS['user_team_checked'][$user_id][$team_set_id] = true;
//        }
//    }
    return $team_set_id;
}

/**
 * @param $user_id
 */
function add_user_to_all_teams($user_id)
{
    static $teams = array();
    if (count($teams) == 0) {
        $result = $GLOBALS['db']->query('select id from teams');
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $teams[] = BeanFactory::getBean('Teams', $row['id']);
        }
    }
    foreach ($teams as $team) {
        $team->add_user_to_team($user_id);
        $team->save();
    }
}

/**
 * @param $userId
 */
function add_user_to_teams($userId, $teamsCnt)
{
    static $teams = array();
    if (empty($teams)) {
        $result = $GLOBALS['db']->query('select id from teams');
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $teams[] = $row['id'];
        }
    }

    $dtool = new DataTool();
    $dtool->installData = array(
        'id' => '',
        'team_id' => '',
        'user_id' => '',
        'explicit_assign' => '',
        'implicit_assign' => '',
        'date_modified' => '',
        'deleted' => '',
    );
    $queryHead = $dtool->createInsertHead('team_memberships');

    $queries = array();

    foreach (get_random_array($teams, $teamsCnt) as $teamId) {
        $dtool->installData = array(
            'id' => "'" . Sugarcrm\Sugarcrm\Util\Uuid::uuid1() . "'",
            'team_id' => "'$teamId'",
            'user_id' => "'$userId'",
            'explicit_assign' => 0,
            'implicit_assign' => 0,
            'date_modified' => "null",
            'deleted' => 0,
        );
        $queries[] = $dtool->createInsertBody();
    }

    processQueries($queryHead, $queries);
}

function findMaxTeamSetId()
{
    $result = $GLOBALS['db']->query(
        "SELECT team_set_id id, count(team_id) cnt
         FROM team_sets_teams
         GROUP BY team_set_id
         ORDER BY cnt DESC
         LIMIT 1"
    );
    $res = $GLOBALS['db']->fetchByAssoc($result);

    return $res['id'];
}
