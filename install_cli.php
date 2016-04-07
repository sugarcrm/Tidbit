#!/usr/local/bin/php
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

ini_set('memory_limit', '8096M');
if (!defined('sugarEntry')) define('sugarEntry', true);
define('SUGAR_DIR', __DIR__ . '/..');
define('DATA_DIR', __DIR__ . '/Data');
define('RELATIONSHIPS_DIR', __DIR__ . '/Relationships');


require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/install_config.php';

chdir('..');
require_once('include/entryPoint.php');
set_time_limit(0);

/* Are we going to use this? */
$recordsPerPage = 1000;

// TODO: This loads additional definitions into beanList and beanFiles for
// custom modules
if (file_exists(SUGAR_DIR . '/custom/application/Ext/Include/modules.ext.php')) {
    require_once(SUGAR_DIR . '/custom/application/Ext/Include/modules.ext.php');
}
if (file_exists(SUGAR_DIR . '/include/modules_override.php')) {
    require_once(SUGAR_DIR . '/include/modules_override.php');
}

$usageStr = "Usage: " . $_SERVER['PHP_SELF'] . " [-l loadFactor] [-u userCount] [-x txBatchSize] [-e] [-c] [-t] [-h] [-v]\n";
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

    --storage name       Storage name, you have next options:
                        - mysql
                        - oracle
                        - csv

    -d              	Turn Debug Mode on.  With Debug Mode, all queries will be
                    	logged in a file called 'executedQueries.txt' in your
                    	Tidbit folder.

    -v              	Display version information.
    
    -h              	Display this help text.

    -x count            How often to commit module records - important on DBs like DB2. Default is no batches.

    -s             	Specify the number of teams per team set and per record.
    
    --tba               Turn Team-based ACL Mode on.

    --tba_level         Specify restriction level for Team-based ACL. Could be (minimum/medium/maximum/full).
                        Default level is medium.
    --fullteamset       Build fully intersected teamset list.

    --allmodules	Automatically detect all installed modules and generate data for them.

    --allrelationships	Automatically detect all relationships and generate data for them.

    --iterator count    This will only insert in the DB the last (count) records specified, meanwhile the
                        iterator will continue running in the loop. Used to check for orphaned records.

    --insert_batch_size Number of VALUES to be added to one INSERT statement for bean data.
                        Does Not include relations for now

    --with-tags         Turn on Tags and Tags Relations generation. If you do not specify this option,
                        default will be false.
    
    "Powered by SugarCRM"
    

EOS;

if (!function_exists('getopt')) {
    die('"getopt" function not found. Please make sure you are running PHP 5+ version');
}

$opts = getopt('l:u:s:x:ecothvd', array('fullteamset', 'tba_level:', 'tba', 'with-tags', 'allmodules', 'allrelationships', 'as_populate', 'as_number:', 'as_buffer:', 'storage:', 'as_last_rec:', 'iterator:', 'insert_batch_size:'));

if ($opts === false) {
    die($usageStr);
}

if (isset($opts['v'])) {
    die($versionStr);
}

if (isset($opts['h'])) {
    die($helpStr);
}

$allrelationships = false;
if (isset($opts['allmodules'])) {
    echo "automatically detecting installed modules\n";
    foreach ($GLOBALS['moduleList'] as $candidate_module) {
        if (!isset($modules[$candidate_module])) {
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
if (isset($opts['l'])) {
    if (!is_numeric($opts['l'])) {
        die($usageStr);
    }
    $factor = $opts['l'] / $modules['Accounts'];
    foreach ($modules as $m => $n) {
        $modules[$m] *= $factor;
    }
}
if (isset($opts['u'])) {
    if (!is_numeric($opts['u'])) {
        die($usageStr);
    }
    $modules['Teams'] = $opts['u'] * ($modules['Teams'] / $modules['Users']);
    $modules['Users'] = $opts['u'];
    if (isset($opts['tba'])) {
        $modules['ACLRoles'] = ceil($modules['Users'] / $modules['ACLRoles']);
    }
}

if (isset($opts['x'])) {
    if (!is_numeric($opts['x']) || $opts['x'] < 1) {
        die($usageStr);
    }
    $_GLOBALS['txBatchSize'] = $opts['x'];
} else {
    $_GLOBALS['txBatchSize'] = 0;
}

if (file_exists(dirname(__FILE__) . '/../ini_setup.php')) {
    require_once dirname(__FILE__) . '/../ini_setup.php';
    set_include_path(
        INSTANCE_PATH . PATH_SEPARATOR .
        TEMPLATE_PATH . PATH_SEPARATOR .
        get_include_path()
    );
}

require_once SUGAR_DIR . '/config.php';
require_once SUGAR_DIR . '/include/modules.php';
require_once SUGAR_DIR . '/include/utils.php';
require_once SUGAR_DIR . '/include/database/DBManagerFactory.php';
require_once SUGAR_DIR . '/include/SugarTheme/SugarTheme.php';
require_once SUGAR_DIR . '/include/utils/db_utils.php';
require_once SUGAR_DIR . '/modules/Teams/TeamSet.php';
require_once DATA_DIR . '/DefaultData.php';
require_once DATA_DIR . '/contactSeedData.php';
require_once __DIR__ . '/install_functions.php';

// Do not populate KBContent and KBCategories for versions less that 7.7.0.0
if (isset($modules['Categories']) && version_compare($GLOBALS['sugar_config']['sugar_version'], '7.7.0', '<')) {
    echo "Knowledge Base Tidbit Data population is available only for 7.7.0.0 and newer versions of SugarCRM\n";
    echo "\n";

    unset($modules['Categories']);
    unset($modules['KBContents']);
}

$_SESSION['modules'] = $modules;
$_SESSION['startTime'] = microtime();
$_SESSION['baseTime'] = time();
$_SESSION['totalRecords'] = 0;
$GLOBALS['time_spend'] = array();

foreach ($modules as $records) {
    $_SESSION['totalRecords'] += $records;
}
if (isset($opts['e'])) {
    $_SESSION['UseExistUsers'] = true;
}
if (isset($opts['c'])) {
    $_SESSION['clean'] = true;
}
if (isset($opts['o'])) {
    $_SESSION['obliterate'] = true;
}
if (isset($opts['t'])) {
    $_SESSION['turbo'] = true;
}
if (isset($opts['d'])) {
    $_SESSION['debug'] = true;
}

if (isset($opts['tba'])) {
    if (version_compare($GLOBALS['sugar_config']['sugar_version'], '7.8.0', '>=')) {
        $_SESSION['tba'] = true;
    } else {
        echo "!!! WARNING !!!\n";
        echo "Team Based ACL Settings could not be enabled for SugarCRM version less than 7.8 \n";
        echo "!!! WARNING !!!\n";
        echo "\n";
    }
}

if (isset($_SESSION['tba']) && $_SESSION['tba'] == true) {
    $_SESSION['tba_level'] = in_array($opts['tba_level'], array_keys($tbaRestrictionLevel)) ? strtolower($opts['tba_level']) : $tbaRestrictionLevelDefault;
}

if(isset($opts['fullteamset']))
{
    $_SESSION['fullteamset'] = true;
}

if (isset($opts['as_populate'])) {
    $_SESSION['as_populate'] = true;
    if (isset($opts['as_number'])) {
        $_SESSION['as_number'] = $opts['as_number'];
    }
    if (isset($opts['as_buffer'])) {
        $_SESSION['as_buffer'] = $opts['as_buffer'];
    }
    if (isset($opts['as_last_rec'])) {
        $_SESSION['as_last_rec'] = $opts['as_last_rec'];
    }
}
if (isset($opts['iterator'])) {
    $_SESSION['iterator'] = $opts['iterator'];
}
$_SESSION['processedRecords'] = 0;
$_SESSION['allProcessedRecords'] = 0;

if (isset($_SESSION['debug'])) {
    $GLOBALS['queryFP'] = fopen('Tidbit/executedQueries.txt', 'w');
}


// zero means use default value provided by storage adapter
$insertBatchSize = 0;
if (!empty($opts['insert_batch_size']) && $opts['insert_batch_size'] > 0) {
    $insertBatchSize = ((int)$opts['insert_batch_size']);
}

$moduleUsingGenerators = array('KBContents', 'Categories');

class FakeLogger
{
    public function __call($m, $a)
    {
    }
}

$GLOBALS['log'] = new FakeLogger();
$GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
$GLOBALS['db'] = DBManagerFactory::getInstance(); // get default sugar db

// Remove Tags module, if it's not turned in CLI options
if (!isset($opts['with-tags'])) {
    unset($modules['Tags']);
}

//When creating module_keys variable, ensure that Teams and Tags are first in the modules list
$module_keys = array_keys($modules);
array_unshift($module_keys, 'Teams');
$module_keys = array_unique($module_keys);

echo "Constructing\n";
foreach ($module_keys as $module) {
    echo "{$modules[$module]} {$module}\n";
}

echo "\n";
echo "With Clean Mode " . (isset($_SESSION['clean']) ? "ON" : "OFF") . "\n";
echo "With Turbo Mode " . (isset($_SESSION['turbo']) ? "ON" : "OFF") . "\n";
echo "With Transaction Batch Mode " . (isset($_GLOBALS['txBatchSize']) ? $_GLOBALS['txBatchSize'] : "OFF") . "\n";
echo "With Obliterate Mode " . (isset($_SESSION['obliterate']) ? "ON" : "OFF") . "\n";
echo "With Existing Users Mode " . (isset($_SESSION['UseExistUsers']) ? "ON - {$modules['Users']} users" : "OFF") . "\n";
echo "With ActivityStream Populating Mode " . (isset($_SESSION['as_populate']) ? "ON" : "OFF") . "\n";
echo "With Team-based ACL Mode " . (isset($_SESSION['tba']) ? "ON" : "OFF") . "\n";
echo "With Team-based Restriction Level " . (isset($_SESSION['tba_level']) ? strtoupper($_SESSION['tba_level']) : "OFF") . "\n";
echo "\n";

// creating storage adapter
$storageType = empty($opts['storage']) ? $storageType : $opts['storage'];
if ($storageType == 'csv') {
    $storage = $dirToSaveCsv;
    clearCsvDir($storage);
} else {
    $storage = $GLOBALS['db'];
}
$storageAdapter = \Sugarcrm\Tidbit\StorageAdapter\Factory::getAdapterInstance($storageType, $storage, $logQueriesPath);
$relationStorageBuffers = array();

$obliterated = array();
foreach ($module_keys as $module) {

    $GLOBALS['time_spend'][$module] = microtime();

    if (!is_dir('modules/' . $module)) {
        echo "Module not found $module\n";
        continue;
    }

    if ((($module == 'Users') || ($module == 'Teams')) && isset($_SESSION['UseExistUsers'])) {
        echo "Skipping $module\n";
        continue;
    }

    echo "\nProcessing Module $module\n";
    $total = $modules[$module];

    if (file_exists(DATA_DIR . '/' . $module . '.php')) {
        require_once(DATA_DIR . '/' . $module . '.php');
    }

    if (in_array($module, $moduleUsingGenerators)) {
        $generatorName = '\Sugarcrm\Tidbit\Generator\\' . $module;
        /** @var \Sugarcrm\Tidbit\Generator\Common $generator */
        $generator = new $generatorName($GLOBALS['db'], $storageAdapter, $insertBatchSize);
        if (isset($_SESSION['obliterate'])) {
            echo "\tObliterating all existing data ... ";
            $generator->obliterateDB();
            echo "DONE";
        } elseif (isset($_SESSION['clean'])) {
            echo "\tCleaning up Tidbit and demo data ... ";
            $generator->clearDB();
            echo "DONE";
        }

        echo "\n\tHitting DB... ";
        $generator->generate($modules[$module]);
        $total = $generator->getInsertCounter();
        echo " DONE";

        $GLOBALS['time_spend'][$module] = microtime_diff($GLOBALS['time_spend'][$module], microtime());
        echo "\n\tTime spend... " . $GLOBALS['time_spend'][$module] . "s\n";

        continue;
    }

    echo "Inserting ${total} records.\n";
    $total_iterator = 0;
    if (isset($_SESSION['iterator']) && ($total > $_SESSION['iterator'])) {
        $total_iterator = $total - $_SESSION['iterator'];
        echo $total_iterator . " records will be skipped from generation.\n";
    }

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
                || !isset($row['join_key_rhs'])
            ) {
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

    if (isset($_SESSION['obliterate'])) {
        echo "\tObliterating all existing data ... ";
        /* Make sure not to delete the admin! */
        if ($module == 'Users') {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1'");
            $GLOBALS['db']->query("DELETE FROM user_preferences WHERE 1=1");
        } else if ($module == 'Teams') {
            $GLOBALS['db']->query("DELETE FROM teams WHERE id != '1'");
            $GLOBALS['db']->query("DELETE FROM team_sets");
            $GLOBALS['db']->query("DELETE FROM team_sets_teams");
            $GLOBALS['db']->query("DELETE FROM team_sets_modules");
        } else {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1 = 1");
        }
        if (!empty($tidbit_relationships[$module])) {
            foreach ($tidbit_relationships[$module] as $rel) {
                if (!empty($obliterated[$rel['table']])) continue;
                $obliterated[$rel['table']] = true;
                $GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1 = 1");
            }
        }
        echo "DONE";
    } elseif (isset($_SESSION['clean'])) {
        echo "\tCleaning up demo data ... ";
        /* Make sure not to delete the admin! */
        if ($module == 'Users') {
            $GLOBALS['db']->query(
                "DELETE FROM user_preferences WHERE id IN " .
                "(SELECT md5(id) FROM $bean->table_name WHERE id != '1' AND id LIKE 'seed-%')"
            );
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1' AND id LIKE 'seed-%'");
        } else if ($module == 'Teams') {
            $GLOBALS['db']->query("DELETE FROM teams WHERE id != '1' AND id LIKE 'seed-%'");
            $GLOBALS['db']->query("DELETE FROM team_sets");
            $GLOBALS['db']->query("DELETE FROM team_sets_teams");
            $GLOBALS['db']->query("DELETE FROM team_sets_modules");
        } else {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1=1 AND id LIKE 'seed-%'");
        }
        if (!empty($tidbit_relationships[$module])) {
            foreach ($tidbit_relationships[$module] as $rel) {
                if (!empty($obliterated[$rel['table']])) continue;
                $obliterated[$rel['table']] = true;
                $GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1=1 AND id LIKE 'seed-%'");
            }
        }
        echo "DONE";
    }

    $dTool = new \Sugarcrm\Tidbit\DataTool($storageType);
    $dTool->fields = $bean->field_defs;

    $dTool->table_name = $bean->table_name;
    $dTool->module = $module;

    echo "\n\tHitting DB... ";
    $beanInsertBuffer = new \Sugarcrm\Tidbit\InsertBuffer($dTool->table_name, $storageAdapter, $insertBatchSize);

    /* We need to insert $total records
     * into the DB.  We are using the module and table-name given by
     * $module and $bean->table_name. */
    $generatedIds = array();
    for ($i = 0; $i < $total; $i++) {
        if (isset($_SESSION['iterator']) && ($i <= $total_iterator)) {
            continue;
        }
        $dTool->count = $i;
        /* Don't turbo Users or Teams */
        if (!isset($_SESSION['turbo']) || !($i % $recordsPerPage) || ($module != 'Users') || ($module != 'Teams')) {
            $dTool->clean();
            $dTool->count = $i;
            $dTool->generateData();
        }

        $generatedIds[] = $dTool->generateId();
        $_SESSION['allProcessedRecords']++;
        $dTool->generateRelationships();

        $_SESSION['processedRecords']++;
        $beanInsertBuffer->addInstallData($dTool->installData);

        if ($dTool->getRelatedModules()) {
            foreach ($dTool->getRelatedModules() as $table => $installDataArray) {
                if (empty($relationStorageBuffers[$table])) {
                    $relationStorageBuffers[$table] = new \Sugarcrm\Tidbit\InsertBuffer(
                        $table,
                        $storageAdapter,
                        $insertBatchSize
                    );
                }
                foreach ($installDataArray as $data) {
                    $relationStorageBuffers[$table]->addInstallData($data);
                }
            }
        }
        $dTool->clearRelatedModules();

        if ($_GLOBALS['txBatchSize'] && $i % $_GLOBALS['txBatchSize'] == 0) {
            $storageAdapter->commitQuery();
            echo "#";
        }
        if ($i % 1000 == 0) echo '*';
    } //for

    if ($module == 'Teams') {
        $teamGenerator = new \Sugarcrm\Tidbit\Generator\TeamSets(
            $GLOBALS['db'], $storageAdapter, $insertBatchSize, $generatedIds
        );
        $teamGenerator->generate();
    }

    // Apply TBA Rules for some modules
    // $roleActions are defined in install_config.php
    if ($module == 'ACLRoles') {
        $tbaGenerator = new \Sugarcrm\Tidbit\Generator\TBA($GLOBALS['db'], $storageAdapter, $insertBatchSize);

        if (isset($_SESSION['clean'])) {
            $tbaGenerator->clearDB();
        } elseif (isset($_SESSION['obliterate'])) {
            $tbaGenerator->obliterateDB();
        }

        if (!empty($_SESSION['tba'])) {
            $tbaGenerator->setAclRoleIds($generatedIds);
            $tbaGenerator->setRoleActions($roleActions);
            $tbaGenerator->setTbaFieldAccess($tbaFieldAccess);
            $tbaGenerator->setTbaRestrictionLevel($tbaRestrictionLevel);
            $tbaGenerator->generate();
        }
    }

    if ($module == 'Users') {
        $prefsGenerator = new \Sugarcrm\Tidbit\Generator\UserPreferences($GLOBALS['db'], $storageAdapter);
        $prefsGenerator->generate($generatedIds);
    }

    echo " DONE";

    $GLOBALS['time_spend'][$module] = microtime_diff($GLOBALS['time_spend'][$module], microtime());
    echo "\n\tTime spend... " . $GLOBALS['time_spend'][$module] . "s\n";
}

// force immediately destructors work
unset($relationStorageBuffers);

if (!empty($GLOBALS['queryFP'])) {
    fclose($GLOBALS['queryFP']);
}

echo "\n";
echo "Total Time: " . microtime_diff($_SESSION['startTime'], microtime()) . "\n";
echo "Core Records Inserted: " . $_SESSION['processedRecords'] . "\n";
echo "Total Records Inserted: " . $_SESSION['allProcessedRecords'] . "\n";

// BEGIN Activity Stream populating
if (!empty($_SESSION['as_populate'])) {

    $activityStreamOptions = array(
        'activities_per_module_record' => !empty($_SESSION['as_number']) ? $_SESSION['as_number'] : 10,
        'insertion_buffer_size' => !empty($_SESSION['as_buffer']) ? $_SESSION['as_buffer'] : 1000,
        'last_n_records' => !empty($_SESSION['as_last_rec']) ? $_SESSION['as_last_rec'] : 0,
    );

    if (!empty($GLOBALS['beanList']['Activities'])) {

        echo "\nPopulating Activity Stream\n";
        $timer = microtime(1);

        $tga = new \Sugarcrm\Tidbit\Generator\Activity($GLOBALS['db'], $storageAdapter, $insertBatchSize);
        $asModules = array();
        foreach ($GLOBALS['modules'] as $moduleName => $recordsCount) {
            /** @var SugarBean $bean */
            $bean = BeanFactory::getBean($moduleName);
            if ($bean && ($bean->isActivityEnabled() || $moduleName == 'Users')) {
                $asModules[$moduleName] = $recordsCount;
            }
        }
        $tga->userCount = $GLOBALS['modules']['Users'];
        $tga->activitiesPerModuleRecord = $activityStreamOptions['activities_per_module_record'];
        $tga->modules = $asModules;
        $tga->insertionBufferSize = $activityStreamOptions['insertion_buffer_size'];
        $tga->lastNRecords = $activityStreamOptions['last_n_records'];
        if (isset($_SESSION['iterator'])) {
            $tga->iterator = $_SESSION['iterator'];
            if ($tga->lastNRecords >= $_SESSION['iterator']) {
                $tga->lastNRecords = $_SESSION['iterator'];
            }
        }

        $tga->init();

        echo " - user activities per module record: {$tga->activitiesPerModuleRecord}\n";
        echo " - max number of records for each module: " . ($tga->lastNRecords ? $tga->lastNRecords : 'all') . "\n";
        echo " - users: {$tga->userCount}\n";
        echo " - modules: ({$tga->activityModuleCount}) " . implode(',', $tga->activityModules) . "\n";
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
                $tga->flushDataset();
                if ($tga->insertedActivities > $insertedActivities) {
                    echo ".";
                    $insertedActivities = $tga->insertedActivities;
                    if ($tga->progress > $percentage) {
                        $percentage += ceil(($tga->progress - $percentage) / $progressStep) * $progressStep;
                        echo "{$tga->progress}%";
                    }
                }
            }
        } while ($result);

        $tga->flushDataset(true);

        echo "\nInserted activities: {$tga->insertedActivities}\n";
        $timer = round(microtime(1) - $timer, 2);
        echo "SQL queries: {$tga->countQuery}, fetch requests: {$tga->countFetch}, time spent: {$timer} seconds\n\n";
        echo "END Activity Stream populating\n\n";

    } else {
        echo "Activity Stream doesn't support by SugarCRM instance\n\n";
    }
}
// END Activity Stream populating

if ($storageType == 'csv') {
    // Save table-dictionaries
    $converter = new \Sugarcrm\Tidbit\CsvConverter($GLOBALS['db'], $storageAdapter, $insertBatchSize);
    $converter->convert('config');
    $converter->convert('acl_actions');
    if (isset($_SESSION['UseExistUsers'])) {
        $converter->convert('user_preferences');
    }
}

echo "Done\n\n\n";
