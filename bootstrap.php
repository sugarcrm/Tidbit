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

$usageStr = "Usage: " . $_SERVER['PHP_SELF'] .
    " [-l loadFactor] [-u userCount] [-x txBatchSize] [-e] [-c] [-t] [-h] [-v]\n";

$versionStr = "Tidbit v2.0 -- Compatible with SugarCRM 5.5 and up.\n";
$helpStr = <<<EOS
$versionStr
This script populates your instance of SugarCRM with realistic demo data.

$usageStr
Options
    -l loadFactor   	The number of Accounts to create.  The ratio between
                    	Accounts and the other modules is fixed in
                    	configs, so the loadFactor determines the number
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

    -t              	DEPRECATED: Turn Turbo Mode on.  Records are produced in groups of 1000
                    	duplicates.  Users and teams are not affected.
                    	Useful for testing duplicate checking or quickly producing
                    	a large volume of test data.

    --allmodules        All Modules. Scans the Sugar system for all out-of-box
                        and custom modules and will insert records to populate
                        all. If modules are already configured, those
                        configurations are not overridden, only appended-to. The
                        number of records created is specified by config. variable
                        \$all_modules_default_count, which is set to 5000 unless
                        overridden in custom configuration. It is recommended
                        that this option still be used with custom configuration
                        to handle custom fields, one/many relationships and any
                        customization like custom indexes or auto-incrementing
                        fields.

    --allrelationships  All Relationships. Scans the Sugar system for all out-of-box
                        and custom relationships. If relationships are already
                        configured, those configurations are not overridden but
                        only appended-to.

    --as_populate       Populate ActivityStream records for each user and module

    --as_last_rec <N>   Works with "--as_populate" key only. Populate last N
                        records of each module (default: all available)

    --as_number <N>     Works with "--as_populate" key only. Number of
                        ActivityStream records for each module record (default 10)

    --as_buffer <N>     Works with "--as_populate" key only. Size of ActivityStream
                        insertion buffer (by default equals to insert_batch_size)

    --storage name      Database Type.  Tidbit will try to auto-detect your database based
                        on your sugar configuration, otherwise you can specify the storage type
                        with one of the following options:
                        - mysql
                        - oracle
                        - db2
                        - csv

    --sugar_path        Path to Sugar installation directory

    -d                  Turn Debug Mode on.  With Debug Mode, all queries will be
                        logged in a file called 'executedQueries.txt' in your
                        Tidbit folder.

    -v                  Display version information.

    -h                  Display this help text.

    -x count            How often to commit module records - important on DBs like DB2. Default is no batches.

    -s                  Specify the number of teams per team set and per record.

    --tba               Turn Team-based ACL Mode on.

    --tba_level         Specify restriction level for Team-based ACL. Could be (minimum/medium/maximum/full).
                        Default level is medium.

    --fullteamset       DEPRECATED: Build fully intersected teamset list.

    --insert_batch_size Number of VALUES to be added to one INSERT statement for bean data.
                        Does Not include relations for now

    --with-tags         Turn on Tags and Tags Relations generation. If you do not specify this option,
                        default will be false.

    --with-favorites    Turn on Sugar Favorites generation. Will generate records in "sugarfavorites" table for modules
                        describes in config as \$sugarFavoritesModules, \$sugarFavoritesModules will be multiplied with
                        "load factor" (-l) argument
    --profile           Name of file in folder config/profiles (without .php) or path to php-config-file with profile data.
                        File can contain php-arrays
                            - modules -- counts of beans to create
                            - profile_opts -- redefines of settings listed here
                        In case of setting profile (this setting) setting -l (load factor) will be ignored.

    --base_time         Unix timestamp that is used as a custom base time value for all data fields that are related to it.
                        Defaults to current timestamp. When provided also used as a seed for Random Number Generator.

    "Powered by SugarCRM"


EOS;

require_once __DIR__ . '/install_functions.php';

if (!defined('TIDBIT_CLI_START')) {
    exitWithError('Tidbit should be run with "./bin/tidbit" or "./vendor/bin/tidbit" instead');
}

if (!function_exists('getopt')) {
    exitWithError('"getopt" function not found. Please make sure you are running PHP 5.3+ version');
}

$opts = getopt(
    'l:u:s:x:ecothvd',
    array(
        'fullteamset',
        'tba_level:',
        'tba',
        'with-tags',
        'with-favorites',
        'allmodules',
        'allrelationships',
        'as_populate',
        'as_number:',
        'as_buffer:',
        'storage:',
        'sugar_path:',
        'as_last_rec:',
        'insert_batch_size:',
        'profile:',
        'base_time:'
    )
);

if ($opts === false) {
    exitWithError($usageStr);
}

if (isset($opts['v'])) {
    die($versionStr);
}
if (isset($opts['h'])) {
    die($helpStr);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '8096M');
set_time_limit(0);

define('TIDBIT_DIR', __DIR__);
define('CONFIG_DIR', __DIR__ . '/config');
define('PROFILES_DIR', CONFIG_DIR . '/profiles');
define('DATA_DIR', CONFIG_DIR . '/data');
define('RELATIONSHIPS_DIR', CONFIG_DIR . '/relationships');

$GLOBALS['baseTime'] = time();
if (isset($opts['base_time'])) {
    if (!is_numeric($opts['base_time'])) {
        exitWithError('base_time value should be an integer');
    }
    $GLOBALS['baseTime'] = intval($opts['base_time']);
    mt_srand($GLOBALS['baseTime']);
}

// load general config
require_once CONFIG_DIR . '/config.php';

if (isset($opts['profile'])) {
    if (is_file($opts['profile'])) {
        require_once $opts['profile'];
    } elseif (is_file(PROFILES_DIR . '/' . $opts['profile'] . '.php')) {
        require_once PROFILES_DIR . '/' . $opts['profile'] . '.php';
    } else {
        exitWithError('Given profile ' . $opts['profile'] . ' does not exist');
    }

    if (isset($profile_opts)) {
        $opts = array_merge($opts, $profile_opts);
    }
}

if (isset($opts['sugar_path'])) {
    $sugarPath = $opts['sugar_path'];
}
if (!is_file($sugarPath . '/include/entryPoint.php')) {
    // Check current user directory
    $currentDirectory = getcwd();

    if (is_file($currentDirectory . '/include/entryPoint.php')) {
        $sugarPath = $currentDirectory;
    } else {
        exitWithError("Where is no Sugar on path " . $sugarPath . " ... exiting");
    }
}

define('SUGAR_PATH', $sugarPath);

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

chdir(SUGAR_PATH); // needed because we have check in entryPoint.php (if file_exists('config.php'))

require_once SUGAR_PATH . '/include/entryPoint.php';
require_once SUGAR_PATH . '/config.php';
require_once SUGAR_PATH . '/include/modules.php';
require_once SUGAR_PATH . '/include/utils.php';
require_once SUGAR_PATH . '/include/database/DBManagerFactory.php';
require_once SUGAR_PATH . '/include/SugarTheme/SugarTheme.php';
require_once SUGAR_PATH . '/include/utils/db_utils.php';
require_once SUGAR_PATH . '/modules/Teams/TeamSet.php';

// TODO: This loads additional definitions into beanList and beanFiles for
// custom modules
if (file_exists(SUGAR_PATH . '/custom/application/Ext/Include/modules.ext.php')) {
    require_once(SUGAR_PATH . '/custom/application/Ext/Include/modules.ext.php');
}
if (file_exists(SUGAR_PATH . '/include/modules_override.php')) {
    require_once(SUGAR_PATH . '/include/modules_override.php');
}

if (file_exists(dirname(__FILE__) . '/../ini_setup.php')) {
    require_once dirname(__FILE__) . '/../ini_setup.php';
    set_include_path(INSTANCE_PATH . PATH_SEPARATOR . TEMPLATE_PATH . PATH_SEPARATOR . get_include_path());
}

// load user's modules data files
require_once DATA_DIR . '/contactSeedData.php'; // must be loaded here
includeDataInDir(DATA_DIR);

// Load custom fields for relationships
includeDataInDir(RELATIONSHIPS_DIR);

// Load user's configs
require_once __DIR__ . '/custom/config.php';

$GLOBALS['log'] = new Sugarcrm\Tidbit\FakeLogger();
$GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
$GLOBALS['db'] = DBManagerFactory::getInstance(); // get default sugar db
// Do not cache DateTime values, DataTool will do this
$GLOBALS['timedate']->allow_cache = false;

$GLOBALS['processedRecords'] = 0;
$GLOBALS['allProcessedRecords'] = 0;

/*
 * if user wants all modules, append system modules to existing config
 */
if (isset($opts['allmodules'])) {
    global $moduleList;
    foreach ($moduleList as $aModule) {
        if (!isset($modules[$aModule])) {
            $modules[$aModule] = $all_modules_default_count;
        }
    }
    ksort($modules);
}

/*
 * if user wants all relationships, append to existing config
 */
if (isset($opts['allrelationships'])) {
    global $tidbit_relationships;
    $tidbit_relationships = generate_m2m_relationship_list($tidbit_relationships);
}

$GLOBALS['modules'] = $modules;
$GLOBALS['startTime'] = microtime();
$GLOBALS['totalRecords'] = 0;
$GLOBALS['time_spend'] = array();


$recordsPerPage = 1000;     // Are we going to use this?
$insertBatchSize = 0;       // zero means use default value provided by storage adapter
$moduleUsingGenerators = array('KBContents', 'Categories', 'SugarFavorites', 'ProductCategories');


if (isset($opts['l']) && !isset($opts['profile'])) {
    if (!is_numeric($opts['l'])) {
        exitWithError($usageStr);
    }
    $factor = $opts['l'] / $modules['Accounts'];
    foreach ($modules as $m => $n) {
        $modules[$m] *= $factor;
    }

    // Multiple favorites with $factor too
    if (isset($opts['with-favorites'])) {
        foreach ($sugarFavoritesModules as $m => $n) {
            $sugarFavoritesModules[$m] *= $factor;
        }
    }
}
if (isset($opts['u'])) {
    if (!is_numeric($opts['u'])) {
        exitWithError($usageStr);
    }
    $modules['Teams'] = $opts['u'] * ($modules['Teams'] / $modules['Users']);
    $modules['Users'] = $opts['u'];
    if (isset($opts['tba'])) {
        $modules['ACLRoles'] = ceil($modules['Users'] / $modules['ACLRoles']);
    }
}

if (isset($opts['x'])) {
    if (!is_numeric($opts['x']) || $opts['x'] < 1) {
        exitWithError($usageStr);
    }
    $_GLOBALS['txBatchSize'] = $opts['x'];
} else {
    $_GLOBALS['txBatchSize'] = 0;
}

if (isset($opts['c'])) {
    $GLOBALS['clean'] = true;
}
if (isset($opts['o'])) {
    $GLOBALS['obliterate'] = true;
}
if (isset($opts['t'])) {
    trigger_error('Turbo mode is deprecated and will be removed in future version');
    $GLOBALS['turbo'] = true;
}
if (isset($opts['d'])) {
    $GLOBALS['debug'] = true;
}

if (!isset($opts['with-favorites'])) {
    unset($modules['SugarFavorites']);
}

$maxTeamsPerSet = (!empty($opts['s'])) ? $opts['s'] : $defaultMaxTeamsPerSet;

if (isset($opts['tba'])) {
    if (version_compare($GLOBALS['sugar_config']['sugar_version'], '7.8.0', '>=')) {
        $GLOBALS['tba'] = true;
    } else {
        echo "!!! WARNING !!!\n";
        echo "Team Based ACL Settings could not be enabled for SugarCRM version less than 7.8 \n";
        echo "!!! WARNING !!!\n";
        echo "\n";
    }
}

if (isset($GLOBALS['tba']) && $GLOBALS['tba'] == true) {
    $GLOBALS['tba_level'] = in_array($opts['tba_level'], array_keys($tbaRestrictionLevel))
        ? strtolower($opts['tba_level'])
        : $tbaRestrictionLevelDefault;
}

if (isset($opts['fullteamset'])) {
    trigger_error('Full Team Set mode is deprecated and will be removed in future version');
    $GLOBALS['fullteamset'] = true;
}

if (isset($opts['as_populate'])) {
    $GLOBALS['as_populate'] = true;
    if (isset($opts['as_number'])) {
        $GLOBALS['as_number'] = $opts['as_number'];
    }
    if (isset($opts['as_buffer'])) {
        $GLOBALS['as_buffer'] = $opts['as_buffer'];
    }
    if (isset($opts['as_last_rec'])) {
        $GLOBALS['as_last_rec'] = $opts['as_last_rec'];
    }
}

if (isset($GLOBALS['debug'])) {
    $GLOBALS['queryFP'] = fopen('Tidbit/executedQueries.txt', 'w');
}

if (!empty($opts['insert_batch_size']) && $opts['insert_batch_size'] > 0) {
    $insertBatchSize = ((int)$opts['insert_batch_size']);
}

// Remove Tags module, if it's not turned in CLI options
if (!isset($opts['with-tags'])) {
    unset($modules['Tags']);
}

// Do not populate KBContent and KBCategories for versions less that 7.7.0.0
if (isset($modules['Categories']) && version_compare($GLOBALS['sugar_config']['sugar_version'], '7.7.0', '<')) {
    echo "Knowledge Base Tidbit Data population is available only for 7.7.0.0 and newer versions of SugarCRM\n";
    echo "\n";

    unset($modules['Categories']);
    unset($modules['KBContents']);
}

foreach ($modules as $records) {
    $GLOBALS['totalRecords'] += $records;
}

$activityStreamOptions = array(
    'activities_per_module_record' => !empty($GLOBALS['as_number']) ? $GLOBALS['as_number'] : 10,
    'insertion_buffer_size' => !empty($GLOBALS['as_buffer']) ? $GLOBALS['as_buffer'] : $insertBatchSize,
    'last_n_records' => !empty($GLOBALS['as_last_rec']) ? $GLOBALS['as_last_rec'] : 0,
);
