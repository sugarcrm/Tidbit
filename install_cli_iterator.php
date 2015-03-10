#!/usr/local/bin/php
<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application.  
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

ini_set('memory_limit', '8096M');
if(!defined('sugarEntry'))define('sugarEntry', true);

chdir('..');
require_once('include/entryPoint.php');
set_time_limit(0);

/* Are we going to use this? */
$recordsPerPage = 1000;
$relQueryCount = 0;

require_once('install_config.php');

// TODO: This loads additional definitions into beanList and beanFiles for
// custom modules
if(file_exists('custom/application/Ext/Include/modules.ext.php')) {
	require_once('custom/application/Ext/Include/modules.ext.php');
}
if (file_exists('include/modules_override.php')) {
	require_once('include/modules_override.php');
}

if(!isset($argc))
{
	header("Location: install.php");
}

$usageStr = "Usage: ".$_SERVER['PHP_SELF']." [-l loadFactor] [-u userCount] [-x txBatchSize] [-e] [-c] [-t] [-h] [-v]\n";
$versionStr = "Tidbit v2.0 -- Compatible with SugarCRM 5.5 through 6.0.\n";
$helpStr = <<<EOS
$versionStr
This script populates your instance of SugarCRM with realistic demo data. 

$usageStr
Options
    -l loadFactor   	The number of Accounts to create.  The ratio between
                    	Accounts and the other modules is fixed in
                    	install_config.php, so the loadFactor determines the number
                    	of each type of module to create.
                    	If not specified, defaults to 1000.

    -u userCount    	The number of Users to create.  If not specified,
                    	but loadFactor is, number of users is 1/10th of
                    	loadFactor.  Otherwise, default is 100.

    -o              	Turn Obliterate Mode on.  All existing records and
                    	relationships in the tables populated by this script will
                    	be emptied.  This includes any custom data in those tables.
                    	The administrator account will not be deleted.

    -c              	Turn Clean Mode on.  All existing demo data will be
                    	removed.  No Data created within the app will be affected,
                    	and the administrator account will not be deleted.  Has no
                    	effect if Obliterate Mode is enabled.
                    
    -t              	Turn Turbo Mode on.  Records are produced in groups of 1000
                    	duplicates.  Users and teams are not affected.
                    	Useful for testing duplicate checking or quickly producing
                    	a large volume of test data.

    -e              	Turn Existing Users Mode on.  Regardless of other settings,
                    	no Users or Teams will be created or modified.  Any new
                    	data created will be assigned and associated with existing
                    	Users and Teams.  The number of users that would normally
                    	be created is assumed to be the number of existing users.
                    	Useful for appending data onto an existing data set.

    --as_populate       Populate ActivityStream records for each user and module

    --as_last_rec <N>   Works with "--as_populate" key only. Populate last N
                        records of each module (default: all available)

    --as_number <N>     Works with "--as_populate" key only. Number of
                        ActivityStream records for each module record (default 10)

    --as_buffer <N>     Works with "--as_populate" key only. Size of ActivityStream
                        insertion buffer (default 1000)

    -d              	Turn Debug Mode on.  With Debug Mode, all queries will be
                    	logged in a file called 'executedQueries.txt' in your
                    	Tidbit folder.

    -v              	Display version information.
    
    -h              	Display this help text.

    -x count            How often to commit module records - important on DBs like DB2. Default is no batches.

    -s             	Specify the number of teams per team set and per record.
    
    --allmodules	Automatically detect all installed modules and generate data for them.
    
    --allrelationships	Automatically detect all relationships and generate data for them.

    --iterator count This will only insert in the DB tha last (count) records specified, meanwhile the iterator will continue running in the loop
    
    "Powered by SugarCRM"
    

EOS;


// TODO: changed command line arg handling to detect --allmodules & --allrelationships
if(function_exists('getopt'))
{
	$opts = getopt('l:u:s:x:ecothvd', array('allmodules', 'allrelationships', 'as_populate', 'as_number:', 'as_buffer:', 'as_last_rec:','iterator:'));
	if($opts === false)
	{
		die($usageStr);
	}
	if(isset($opts['v']))
	{
		die($versionStr);
	}
	if(isset($opts['h']))
	{
		die($helpStr);
	}
}
else
{
	$opts = array();
	$nextData = false;
	foreach($argv as $arg)
	{
		if(($arg === '-v'))
		{
			die($versionStr);
		}
		if($arg === '-h')
		{
			die($helpStr);
		}
		if($nextData !== false)
		{
			$opts[$nextData] = $arg;
			$nextData = false;
		}
		elseif($arg === '-l')
		{
			$nextData = 'l';
		}
		elseif($arg === '-u')
		{
			$nextData = 'u';
		}
		elseif($arg === '-e')
		{
			$opts['e'] = true;
		}
		elseif($arg === '-o')
		{
			$opts['o'] = true;
		}
		elseif($arg === '-c')
		{
			$opts['c'] = true;
		}
		elseif($arg === '-x')
		{
			$nextData = 'x';
		}
		elseif($arg === '-t')
		{
			$opts['t'] = true;
		}
		elseif($arg === '-d')
		{
			$opts['d'] = true;
		}
		elseif($arg === '-s')
		{
			$nextData = 's';
		}
		elseif($arg == '--allmodules') {
			$opts['allmodules'] = true;
		}
		elseif($arg == '--allrelationships') {
			$opts['allrelationships'] = true;
		} elseif($arg === '--as_populate') {
            $opts['as_populate'] = true;
        } elseif($arg === '--as_number') {
            $nextData = 'as_number';
        } elseif($arg === '--as_buffer') {
            $nextData = 'as_buffer';
        } elseif($arg === '--as_last_rec') {
            $nextData = 'as_last_rec';
        } elseif($arg === '--iterator') {
            $nextData = 'iterator';
        }
	}
}

//var_dump($opts);
$allrelationships = false;
if(isset($opts['allmodules'])) {
	echo "automatically detecting installed modules\n"; 
	foreach($GLOBALS['moduleList'] as $candidate_module) {
		if(!isset($modules[$candidate_module])) {
			// TODO: Load for modules not defined in install_config
			// is the same as for Contacts (4000)
			$modules[$candidate_module] = 4000;
		} 
	}
}
if (isset($opts['allrelationships'])) {
	echo "automatically generating relationships\n"; 
	$allrelationships = true;
}
if(isset($opts['l']))
{
	if(!is_numeric($opts['l']))
	{
		die($usageStr);
	}
	$factor = $opts['l']/$modules['Accounts'];
	foreach($modules as $m=>$n){
		$modules[$m] *= $factor;
	}
}
if(isset($opts['u']))
{
	if(!is_numeric($opts['u']))
	{
		die($usageStr);
	}
	$modules['Teams'] = $opts['u']*($modules['Teams']/$modules['Users']);
	$modules['Users'] = $opts['u'];
}

if(isset($opts['x']))
{
	if(!is_numeric($opts['x']) || $opts['x'] < 1)
	{
		die($usageStr);
	}
    $_SESSION['txBatchSize'] = $opts['x'];

}

if (file_exists(dirname(__FILE__) . '/../ini_setup.php')) {
	require_once dirname(__FILE__) . '/../ini_setup.php';
	set_include_path(
	INSTANCE_PATH . PATH_SEPARATOR .
	TEMPLATE_PATH . PATH_SEPARATOR .
	get_include_path()
	);
}
require_once('include/utils.php');
require_once('config.php');
require_once('include/modules.php');
require_once('include/database/DBManagerFactory.php');
require_once('include/SugarTheme/SugarTheme.php');
require_once('Tidbit/Data/DefaultData.php');
require_once('Tidbit/DataTool.php');
require_once('Tidbit/install_functions.php');
require_once('Tidbit/Data/contactSeedData.php');
$_SESSION['modules'] = $modules;
$_SESSION['startTime'] = microtime();
$_SESSION['baseTime'] = time();
$_SESSION['totalRecords'] = 0;



foreach($modules as $records){
	$_SESSION['totalRecords'] += $records;
}
if(isset($opts['e']))
{
	$_SESSION['UseExistUsers'] = true;
}
if(isset($opts['c']))
{
	$_SESSION['clean'] = true;
}
if(isset($opts['o']))
{
	$_SESSION['obliterate'] = true;
}
if(isset($opts['t']))
{
	$_SESSION['turbo'] = true;
}
if(isset($opts['d']))
{
	$_SESSION['debug'] = true;
}
if(isset($opts['as_populate'])) {
    $_SESSION['as_populate'] = true;
    if(isset($opts['as_number'])) {
        $_SESSION['as_number'] = $opts['as_number'];
    }
    if(isset($opts['as_buffer'])) {
        $_SESSION['as_buffer'] = $opts['as_buffer'];
    }
    if(isset($opts['as_last_rec'])) {
        $_SESSION['as_last_rec'] = $opts['as_last_rec'];
    }
}
if(isset($opts['iterator'])) {
	$_SESSION['iterator'] = $opts['iterator'];
}
$_SESSION['processedRecords'] = 0;
$_SESSION['allProcessedRecords'] = 0;

if(isset($_SESSION['debug']))
{
	$GLOBALS['queryFP'] = fopen('Tidbit/executedQueries.txt', 'w');
}



class FakeLogger { public function __call($m, $a) { } }
$GLOBALS['log']= new FakeLogger();
$GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
$GLOBALS['db'] = DBManagerFactory::getInstance(); // get default sugar db
startTransaction();

//When creating module_keys variable, ensure that Teams is the first element in the Array
$teams = $modules['Teams'];
unset($modules['Teams']);

$module_keys = array_keys($modules);
array_unshift($module_keys, 'Teams');
$modules['Teams'] = $teams;

echo "Constructing\n";
foreach($module_keys as $module)
{
	echo "{$modules[$module]} {$module}\n";
}
echo "With Clean Mode ".(isset($_SESSION['clean'])?"ON":"OFF")."\n";
echo "With Turbo Mode ".(isset($_SESSION['turbo'])?"ON":"OFF")."\n";
echo "With Transaction Batch Mode ".(isset($_SESSION['txBatchSize'])?$_SESSION['txBatchSize']:"OFF"). "\n";
echo "With Obliterate Mode ".(isset($_SESSION['obliterate'])?"ON":"OFF")."\n";
echo "With Existing Users Mode ".(isset($_SESSION['UseExistUsers'])?"ON - {$modules['Users']} users":"OFF")."\n";
echo "With ActivityStream Polulating Mode " . (isset($_SESSION['as_populate']) ? "ON" : "OFF") . "\n";
$obliterated = array();
//DataTool::generateTeamSets();
foreach($module_keys as $module)
{
	if(!is_dir('modules/' . $module))continue;
	if((($module == 'Users') || ($module == 'Teams')) && isset($_SESSION['UseExistUsers']))
	{
		echo "Skipping $module\n";
		continue;
	}
	
	// TODO: fixing emails
//	if ($module == 'Emails') {
//		echo "Skipping $module\n";
//		continue;
//	}

	echo "Processing Module $module\n";
	$total = $modules[$module];
	$total_iterator = 0;
	if(isset($_SESSION['iterator'])){
		$total_iterator = $total - 	$_SESSION['iterator']
	}
	

	$GLOBALS['relatedQueries'] = array();
	$GLOBALS['queries'] = array();
	$GLOBALS['relatedQueriesCount'] = 0;

	// TODO: ignoring modules who aren't keys in $beanList
	// and  classes who aren't keys in $beanFiles
	// Not sure if this is the right thing to do
	if (!isset($beanList[$module])) {
		echo "skipping module: " . $module . "\n";
		continue;
	}
	$class = $beanList[$module];
	if (!isset($beanFiles[$class])) {
		echo "skipping module: " . $module . "\n";
		continue;
	}
	require_once($beanFiles[$class]);
	$bean = new $class();
	
	// TODO: if allrelationships is true, pull from relationships
	// table and add to $GLOBALS['tidbit_relationships']
	if ($allrelationships && $module != 'Teams' && $module != 'Emails') { // Teams & Emails module relationships handled separately
		$result = $GLOBALS['db']->query(
		"SELECT * FROM relationships WHERE lhs_module='$module'");
		global $tidbit_relationships;
		while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
			if (!isset($row['join_table']) || !isset($row['join_key_lhs']) 
				|| !isset($row['join_key_rhs'])) {
					continue;
			}
			$rhs_module = $row['rhs_module'];
			$table = $row['join_table'];
			$self = $row['join_key_lhs'];
			$you = $row['join_key_rhs'];
			if ($rhs_module == 'Teams' || $rhs_module == 'Emails') { // Teams & Emails module relationships handled separately
				continue;
			}
			if (!isset($tidbit_relationships[$module])) {
				$tidbit_relationships[$module] = array();
			}
			if (!isset($tidbit_relationships[$module][$rhs_module])) {
				$tidbit_relationships[$module][$rhs_module] = array();
			}
			$tidbit_relationships[$module][$rhs_module]['table'] = $table;
			$tidbit_relationships[$module][$rhs_module]['self'] = $self;
			$tidbit_relationships[$module][$rhs_module]['you'] = $you;
		}
	}

	if(isset($_SESSION['obliterate'])){
		echo "\tObliterating all existing data ... ";
		/* Make sure not to delete the admin! */
		if($module == 'Users') {
			$GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1'");
			$GLOBALS['db']->query("DELETE FROM user_preferences WHERE 1=1");
		} else if($module == 'Teams') {
			$GLOBALS['db']->query("DELETE FROM teams WHERE id != '1'");
			$GLOBALS['db']->query("DELETE FROM team_sets");
			$GLOBALS['db']->query("DELETE FROM team_sets_teams");
			$GLOBALS['db']->query("DELETE FROM team_sets_modules");
		} else {
			$GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1 = 1");
		}
		if(!empty($tidbit_relationships[$module])){
			foreach($tidbit_relationships[$module] as $rel){
				if(!empty($obliterated[$rel['table']]))continue;
				$obliterated[$rel['table']]= true;
				$GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1 = 1");
			}
		}
		echo "DONE\n";
	}elseif(isset($_SESSION['clean'])){
		echo "\tCleaning up demo data ... ";
		/* Make sure not to delete the admin! */
		if($module == 'Users'){
			$GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1' AND id LIKE 'seed-%'");
		}else if ($module == 'Teams') {
			$GLOBALS['db']->query("DELETE FROM teams WHERE id != '1' AND id LIKE 'seed-%'");
			$GLOBALS['db']->query("DELETE FROM team_sets");
			$GLOBALS['db']->query("DELETE FROM team_sets_teams");
			$GLOBALS['db']->query("DELETE FROM team_sets_modules");
		}else {
			$GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1=1 AND id LIKE 'seed-%'");
		}
		if(!empty($tidbit_relationships[$module])){
			foreach($tidbit_relationships[$module] as $rel){
				if(!empty($obliterated[$rel['table']]))continue;
				$obliterated[$rel['table']]= true;
				$GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1=1 AND id LIKE 'seed-%'");
			}
		}
		echo "DONE\n";
	}

	if(file_exists('Tidbit/Data/' . $bean->module_dir . '.php')){
		require_once('Tidbit/Data/' . $bean->module_dir . '.php');
	}
	$ibfd = new DataTool();
	$ibfd->fields = $bean->field_defs;
	
	$ibfd->table_name = $bean->table_name;
	$ibfd->module = $module;

	unset($GLOBALS['queryHead']);
	unset($GLOBALS['queries']);
	/* We need to insert $total records
	 * into the DB.  We are using the module and table-name given by
	 * $module and $bean->table_name. */


	for($i = 0; $i < $total; $i++){
		if(isset($_SESSION['iterator']) && ($i <= $total_iterator)){
			continue;
		}
		$ibfd->count = $i;
		/* Don't turbo Users or Teams */
		if(!isset($_SESSION['turbo']) || !($i % $recordsPerPage) || ($module != 'Users') || ($module != 'Teams')){
			$ibfd->clean();
			$ibfd->count = $i;
			$ibfd->generateData();
		}
		/*
		 if ($i == 0) {
		 foreach($ibfd->installData as $key => $val) {
		 echo $key . " => " . $val . "\n";
		 }
		 }
		 */
		$ibfd->generateId();
		//$ibfd->generateTeamSetId();
		$ibfd->createInserts();
		$ibfd->generateRelationships();

		$_SESSION['processedRecords']++;

		//flush the relatedQueries every 2000, and at the end of each page.
		if(($relQueryCount >= 2000) || ($i == $total-1))
		{
			echo '.';
			foreach($GLOBALS['relatedQueries'] as $data){
				$head = $data['head'];
				unset($data['head']);
				processQueries($head, $data);
			}
			$GLOBALS['relatedQueries'] = array();
			$relQueryCount = 0;
		}

		if(!empty($GLOBALS['queryHead']) && !empty($GLOBALS['queries']) && $i != 0 && $i%19 ==0)
		{
			$dbStart = microtime();
			processQueries($GLOBALS['queryHead'], $GLOBALS['queries']);
			/* Clear queries */
			unset($GLOBALS['queries']);
		}

        if (isset($_SESSION['txBatchSize']) && $i%$_SESSION['txBatchSize'] == 0) {
            $GLOBALS['db']->commit();
            echo "#";
        }

		if($i%1000 == 0) echo '*';

	} //for

	if(!empty($GLOBALS['queryHead']) && !empty($GLOBALS['queries']))
	{
		$dbStart = microtime();
		echo "\n\tHitting DB... ";
		processQueries($GLOBALS['queryHead'], $GLOBALS['queries']);
		/* Clear queries */
		unset($GLOBALS['queryHead']);
		unset($GLOBALS['queries']);
		echo microtime_diff($dbStart, microtime())."s ";
	}

//	if ($module == 'Users') {
//		loggedQuery(file_get_contents(dirname(__FILE__) . '/sql/update-user-preferences.sql'));
//	}

    if ($module == 'Users') {
        $content = 'YTo0OntzOjg6InRpbWV6b25lIjtzOjE1OiJBbWVyaWNhL1Bob2VuaXgiO3M6MjoidXQiO2k6MTtzOjI0OiJIb21lX1RFQU1OT1RJQ0VfT1JERVJfQlkiO3M6MTA6ImRhdGVfc3RhcnQiO3M6MTI6InVzZXJQcml2R3VpZCI7czozNjoiYTQ4MzYyMTEtZWU4OS0wNzE0LWE0YTItNDY2OTg3YzI4NGY0Ijt9';
        $result = $GLOBALS['db']->query("SELECT id from users where id LIKE 'seed-Users%'");
        while($row = $GLOBALS['db']->fetchByAssoc($result)){
            $hashed_id = md5($row['id']);

            $curdt = $datetime = date('Y-m-d H:i:s') ;
            $stmt = "INSERT INTO user_preferences(id,category,date_entered,date_modified,assigned_user_id,contents) values ('" . $hashed_id . "', 'global', '" . $curdt . "', '" . $curdt . "', '" . $row['id'] . "', '" . $content . "')";
            loggedQuery($stmt);
        }
    }

	if ($module == 'Teams') {
		require_once('modules/Teams/TeamSet.php');
        require_once('modules/Teams/TeamSetManager.php');
		TeamSetManager::flushBackendCache();
		$teams_data = array();
		$result = $GLOBALS['db']->query("SELECT id FROM teams");
		while($row = $GLOBALS['db']->fetchByAssoc($result)){
			$teams_data[$row['id']] = $row['id'];
		}

		sort($teams_data);
		//Now generate the random team_sets
		$results = array();

		$max_teams_per_set = 10;
		if(isset($opts['s']) && $opts['s'] > 0){
			$max_teams_per_set = $opts['s'];
		}

		foreach($teams_data as $team_id) {
			//If there are more than 20 teams, a reasonable number of teams for a maximum team set is 10
			if($max_teams_per_set == 1){
				generate_team_set($team_id, array($team_id));
			}elseif(count($teams_data) > $max_teams_per_set) {
				generate_team_set($team_id, get_random_array($teams_data, $max_teams_per_set));
			}else {
				generate_team_set($team_id, $teams_data);
			}
		}

		$result = $GLOBALS['db']->query("SELECT team_set_id, team_id FROM team_sets_teams");
		$team_sets = array();
		while($row = $GLOBALS['db']->fetchByAssoc($result)){
			$team_sets[$row['team_set_id']][] = $row['team_id'];
		}
		DataTool::$team_sets_array = $team_sets;
	}

	echo "DONE\n";
}

endTransaction();

if(!empty($GLOBALS['queryFP']))
{
	fclose($GLOBALS['queryFP']);
}


echo "Total Time: " . microtime_diff($_SESSION['startTime'], microtime()) . "\n";
echo "Core Records Inserted: ".$_SESSION['processedRecords']."\n";
echo "Total Records Inserted: ".$_SESSION['allProcessedRecords']."\n";

// BEGIN Activity Stream populating
if (!empty($_SESSION['as_populate'])) {

    $activityStreamOptions = array(
        'activities_per_module_record' => !empty($_SESSION['as_number']) ? $_SESSION['as_number'] : 10,
        'insertion_buffer_size' => !empty($_SESSION['as_buffer']) ? $_SESSION['as_buffer'] : 1000,
        'last_n_records' => !empty($_SESSION['as_last_rec']) ? $_SESSION['as_last_rec'] : 0,
    );

    if(!empty($GLOBALS['beanList']['Activities'])) {

        echo "\nPopulating Activity Stream\n";
        $timer = microtime(1);

        require_once 'Tidbit/Generator/ActivityGenerator.php';
        $tga = new TidbitActivityGenerator();        
        $tga->userCount = $GLOBALS['modules']['Users'];
        $tga->activitiesPerModuleRecord = $activityStreamOptions['activities_per_module_record'];
        $tga->modules = $GLOBALS['modules'];
        $tga->db = $GLOBALS['db'];
        $tga->insertionBufferSize = $activityStreamOptions['insertion_buffer_size'];
        $tga->lastNRecords = $activityStreamOptions['last_n_records'];
        if(isset($_SESSION['iterator'])){
			$tga->iterator = $_SESSION['iterator'];
			if($tga->lastNRecords >= $_SESSION['iterator']){
				$tga->lastNRecords = $_SESSION['iterator'];
			}
		}

        $tga->init();

        echo " - user activities per module record: {$tga->activitiesPerModuleRecord}\n";
        echo " - max number of records for each module: " . ($tga->lastNRecords ? $tga->lastNRecords : 'all') . "\n";
        echo " - users: {$tga->userCount}\n";
        echo " - modules: {$tga->activityModuleCount}\n";
        echo " - total activities to insert: " . ($tga->activitiesPerUser * $tga->userCount) . "\n";
        echo " - activities per user: {$tga->activitiesPerUser}\n";
        echo " - insertion buffer size: {$tga->insertionBufferSize} records\n";
        if (isset($_SESSION['obliterate'])) {
            echo "\tObliterating existing Activity Stream data ... ";
            echo ($tga->obliterateActivities() ? "OK" : "FAIL") . "\n";
        }
        echo "\tPopulating .";

        $progressStep = 10;
        $percentage = $progressStep;
        $insertedActivities = 0;

        do {
            if ($result = $tga->createDataset()) {
                if (!$tga->flushDataset()) {
                    echo "FAILED\n";
                    break;
                }
                if ($tga->insertedActivities > $insertedActivities) {
                    echo ".";
                    $insertedActivities= $tga->insertedActivities;
                    if ($tga->progress > $percentage) {
                        $percentage += ceil(($tga->progress - $percentage) / $progressStep) * $progressStep;
                        echo "{$tga->progress}%";
                    }
                }
            }
        } while ($result);

        if (!$tga->flushDataset(true)) {
            echo "FAILED\n";
        }

        echo "\nInserted activities: {$tga->insertedActivities}\n";
        $timer = round(microtime(1) - $timer, 2);
        echo "SQL queries: {$tga->countQuery}, fetch requests: {$tga->countFetch}, time spent: {$timer} seconds\n\n";
        echo "END Activity Stream populating\n\n";

    } else {
        echo "Activity Stream doesn't support by SugarCRM instance\n\n";
    }
}
// END Activity Stream populating


echo "Done\n\n\n";

?>
