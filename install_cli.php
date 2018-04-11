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

require_once 'bootstrap.php';

/*
 * impose a hard limit of one hundred billion on all modules to ensure unique IDs
 */
foreach ($modules as $module => $count) {
    if ($count > 100000000000) {
        $modules[$module] = 100000000000;
    }
}

//When creating module_keys variable, ensure that Teams and Users are first in the modules list
$module_keys = array_keys($modules);
array_unshift($module_keys, 'Users');
array_unshift($module_keys, 'Teams');
$module_keys = array_unique($module_keys);

echo "Constructing\n";
foreach ($module_keys as $module) {
    echo "{$modules[$module]} {$module}\n";
}

if (isset($opts['with-favorites'])) {
    echo "\nSugar Favorites Generated:\n";
    foreach ($sugarFavoritesModules as $favModule => $favCount) {
        echo "\t$favCount {$favModule}\n";
    }
}

echo "\n";
echo "With Clean Mode " . (isset($GLOBALS['clean']) ? "ON" : "OFF") . "\n";
echo "With Transaction Batch Mode " . (isset($_GLOBALS['txBatchSize']) ? $_GLOBALS['txBatchSize'] : "OFF") . "\n";
echo "With Obliterate Mode " . (isset($GLOBALS['obliterate']) ? "ON" : "OFF") . "\n";
echo "With ActivityStream Populating Mode " . (isset($GLOBALS['as_populate']) ? "ON" : "OFF") . "\n";
echo "With Team-based ACL Mode " . (isset($GLOBALS['tba']) ? "ON" : "OFF") . "\n";
echo "With Team-based Restriction Level " .
    (isset($GLOBALS['tba_level']) ? strtoupper($GLOBALS['tba_level']) : "OFF") . "\n";
echo "\n";

// creating storage adapter
//if no storage flags are passed, try to autodetect storage from the sugar install
if (empty($opts['storage'])) {
    $sugarStorageType = $sugar_config['dbconfig']['db_type'];
    switch ($sugarStorageType) {
        case 'oci8':
            $storageType = 'oracle';
            break;
        case 'db2':
            $storageType = 'db2';
            break;
        default:
            $storageType = 'mysql';
    }
} else {
    $storageType = $opts['storage'];
}

if ($storageType == 'csv') {
    $storage = TIDBIT_DIR . '/' . $tidbitCsvDir;
    clearCsvDir($storage);
} else {
    $storage = $GLOBALS['db'];
}

$obliterated = array();
$relationStorageBuffers = array();

$storageAdapter = \Sugarcrm\Tidbit\StorageAdapter\Factory::getAdapterInstance($storageType, $storage, $logQueriesPath);
$activityGenerator = new \Sugarcrm\Tidbit\Generator\Activity(
    $GLOBALS['db'],
    $storageAdapter,
    $activityStreamOptions['insertion_buffer_size'],
    $activityStreamOptions['activities_per_module_record'],
    $activityStreamOptions['last_n_records']
);
$activityGenerator->setActivityModulesBlackList($activityModulesBlackList);
if (isset($GLOBALS['obliterate'])) {
    echo "Obliterating activities ... \n";
    $activityGenerator->obliterateActivities();
}

foreach ($module_keys as $module) {
    $moduleTimeStart = microtime();

    // Check module class exists in bean factory
    // For old versions - getBeanName is used
    // For new versions - getBeanClass, cause getBeanName is deprecated
    if ((method_exists('BeanFactory', 'getBeanClass') && !BeanFactory::getBeanClass($module))
        || method_exists('BeanFactory', 'getBeanName') && !BeanFactory::getBeanName($module)) {
        echo "Module $module is not found in 'modules/' folder or \$beanList, \$beanFiles global" .
            " variables do not contain it\n";
        echo "Skipping module: " . $module . "\n";
        continue;
    }

    $total = $modules[$module];
    if ($total == 0) {
        echo "Skipping module: $module as it's configured to generate 0 records\n";
        continue;
    }

    echo "\nProcessing Module $module"
        .(isset($tidbit_relationships[$module])
            ? " with relationships to ".implode(", ", array_keys($tidbit_relationships[$module]))
            :"")
        .":\n";

    if (in_array($module, $moduleUsingGenerators)) {
        $generatorName = '\Sugarcrm\Tidbit\Generator\\' . $module;
        /** @var \Sugarcrm\Tidbit\Generator\Common $generator */
        $generator = new $generatorName($GLOBALS['db'], $storageAdapter, $modules[$module]);

        if (isset($GLOBALS['obliterate'])) {
            echo "\tObliterating all existing data ... ";
            $generator->obliterateDB();
            echo "DONE";
        } elseif (isset($GLOBALS['clean'])) {
            echo "\tCleaning up Tidbit and demo data ... ";
            $generator->clearDB();
            echo "DONE";
        }

        if (!empty($GLOBALS['as_populate'])) {
            $generator->setActivityStreamGenerator($activityGenerator);
            $activityBean = $generator->getActivityBean();
            if ($activityBean && $activityGenerator->willGenerateActivity($activityBean)) {
                echo "\n\tWill create " . $activityGenerator->calculateActivitiesToCreate($modules[$module])
                    . " activity records";
            }
        }

        echo "\n\tHitting DB... ";
        $generator->generate();
        $total = $generator->getInsertCounter();
        showProgress($modules[$module], $modules[$module]);
        echo "\n\tTime spend... " . microtime_diff($moduleTimeStart, microtime()) . "s\n";
        continue;
    }

    echo "Inserting ${total} records.\n";
    $bean = BeanFactory::getBean($module);

    $generatorClass = "\Sugarcrm\Tidbit\Generator\\{$module}Generator";
    if (!class_exists($generatorClass)) {
        $generatorClass = \Sugarcrm\Tidbit\Generator\ModuleGenerator::class;
    }
    $g = new $generatorClass($bean, $activityGenerator);
    if (isset($tidbit_relationships[$module])) {
        foreach ($tidbit_relationships[$module] as $relationship) {
            if (!isset($relationship['type'])) {
                continue;
            }

            $relationship['self_total'] = $total;
            $relationship['you_total'] = $modules[$relationship['you_module']];
            $rdc = "\Sugarcrm\Tidbit\Generator\\" . ucfirst($relationship['type']) . "Relationship";
            $g = new $rdc($g, $relationship);
        }
    }

    if (method_exists($bean, 'hasPiiFields') && $bean->hasPiiFields()) {
        $d = new Sugarcrm\Tidbit\Generator\ErasedFieldsDecorator($g);
        if ($d->isUsefull()) {
            $g = $d;
        }
    }
    if ($bean->isActivityEnabled()) {
        $d = new Sugarcrm\Tidbit\Generator\FollowingDecorator($g);
        if ($d->isUsefull()) {
            $g = $d;
        }
    }
    $c = new \Sugarcrm\Tidbit\Generator\Controller($g, $bean, $activityGenerator);

    if (isset($GLOBALS['obliterate'])) {
        echo "\tObliterating all existing data ... ";
        $g->obliterate();
        echo "DONE\n";
    } elseif (isset($GLOBALS['clean'])) {
        echo "\tCleaning up demo data ... ";
        $g->clean();
        echo "DONE\n";
    }

    if (!empty($GLOBALS['as_populate']) && $activityGenerator->willGenerateActivity($bean)) {
        echo "\tWill create " . $activityGenerator->calculateActivitiesToCreate($total) . " activity records\n";
    }

    $c->generate($total);
    echo "\tTime spend... " . microtime_diff($moduleTimeStart, microtime()) . "s\n";
}

// Update enabled Modules Tabs
\Sugarcrm\Tidbit\Helper\ModuleTabs::updateEnabledTabs($GLOBALS['db'], $module_keys, $GLOBALS['moduleList']);

// force immediately destructors work
$totalInsertedActivities = $activityGenerator->getInsertedActivitiesCount();
unset($activityGenerator);

echo "\n";
echo "Total Time: " . microtime_diff($GLOBALS['startTime'], microtime()) . "\n";
echo "Core Records Inserted: " . $GLOBALS['processedRecords'] . "\n";
echo "Total Records Inserted: " . $GLOBALS['allProcessedRecords'] . "\n";

if (!empty($GLOBALS['as_populate'])) {
    echo "Activities: \n";
    echo " - user activities per module record: " . $activityStreamOptions['activities_per_module_record'] . "\n";
    echo " - users: " . $modules['Users'] . "\n";
    echo " - max number of records for each module: " .
        ($activityStreamOptions['last_n_records'] ? $activityStreamOptions['last_n_records'] : 'all') . "\n";
    echo " - total records inserted: " . $totalInsertedActivities . "\n";
}

if ($storageType == 'csv') {
    // Save table-dictionaries
    $converter = new \Sugarcrm\Tidbit\CsvConverter($GLOBALS['db'], $storageAdapter);
    $converter->convert('config');
    $converter->convert('acl_actions');
}

echo "Done\n";
