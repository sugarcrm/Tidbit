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

require_once 'bootstrap.php';

//When creating module_keys variable, ensure that Teams and Tags are first in the modules list
$module_keys = array_keys($modules);
array_unshift($module_keys, 'Teams');
$module_keys = array_unique($module_keys);

echo "Constructing\n";
foreach ($module_keys as $module) {
    echo "{$modules[$module]} {$module}\n";
}

echo "\n";
echo "With Clean Mode " . (isset($GLOBALS['clean']) ? "ON" : "OFF") . "\n";
echo "With Turbo Mode " . (isset($GLOBALS['turbo']) ? "ON" : "OFF") . "\n";
echo "With Transaction Batch Mode " . (isset($_GLOBALS['txBatchSize']) ? $_GLOBALS['txBatchSize'] : "OFF") . "\n";
echo "With Obliterate Mode " . (isset($GLOBALS['obliterate']) ? "ON" : "OFF") . "\n";
echo "With Existing Users Mode " . (isset($GLOBALS['UseExistUsers']) ? "ON - {$modules['Users']} users" : "OFF") . "\n";
echo "With ActivityStream Populating Mode " . (isset($GLOBALS['as_populate']) ? "ON" : "OFF") . "\n";
echo "With Team-based ACL Mode " . (isset($GLOBALS['tba']) ? "ON" : "OFF") . "\n";
echo "With Team-based Restriction Level " . (isset($GLOBALS['tba_level']) ? strtoupper($GLOBALS['tba_level']) : "OFF") . "\n";
echo "\n";

// creating storage adapter
$storageType = empty($opts['storage']) ? $storageType : $opts['storage'];
if ($storageType == 'csv') {
    $storage = TIDBIT_DIR . '/' . $tidbitCsvDir;
    clearCsvDir($storage);
} else {
    $storage = $GLOBALS['db'];
}

$obliterated = array();
$relationStorageBuffers = array();
$storageAdapter = \Sugarcrm\Tidbit\StorageAdapter\Factory::getAdapterInstance($storageType, $storage, $logQueriesPath);
$prefsGenerator = new \Sugarcrm\Tidbit\Generator\UserPreferences($GLOBALS['db'], $storageAdapter);

foreach ($module_keys as $module) {

    $GLOBALS['time_spend'][$module] = microtime();

    // Check module class exists in bean factory
    // For old versions - getBeanName is used
    // For new versions - getBeanClass, cause getBeanName is deprecated
    if ((method_exists('BeanFactory', 'getBeanClass') && !BeanFactory::getBeanClass($module))
        || method_exists('BeanFactory', 'getBeanName') && !BeanFactory::getBeanName($module)) {
        echo "Module $module is not found in 'modules/' folder or \$beanList, \$beanFiles global variables do not contain it\n";
        echo "Skipping module: " . $module . "\n";
        continue;
    }

    if ((($module == 'Users') || ($module == 'Teams')) && isset($GLOBALS['UseExistUsers'])) {
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
        if (isset($GLOBALS['obliterate'])) {
            echo "\tObliterating all existing data ... ";
            $generator->obliterateDB();
            echo "DONE";
        } elseif (isset($GLOBALS['clean'])) {
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
    if (isset($GLOBALS['iterator']) && ($total > $GLOBALS['iterator'])) {
        $total_iterator = $total - $GLOBALS['iterator'];
        echo $total_iterator . " records will be skipped from generation.\n";
    }

    $bean = BeanFactory::getBean($module);

    if (isset($GLOBALS['obliterate'])) {
        echo "\tObliterating all existing data ... ";
        /* Make sure not to delete the admin! */
        if ($module == 'Users') {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1'");
            $prefsGenerator->obliterate();
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
    } elseif (isset($GLOBALS['clean'])) {
        echo "\tCleaning up demo data ... ";
        /* Make sure not to delete the admin! */
        if ($module == 'Users') {
            $prefsGenerator->clean();
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
        if (isset($GLOBALS['iterator']) && ($i <= $total_iterator)) {
            continue;
        }
        $dTool->count = $i;
        /* Don't turbo Users or Teams */
        if (!isset($GLOBALS['turbo']) || !($i % $recordsPerPage) || ($module != 'Users') || ($module != 'Teams')) {
            $dTool->clean();
            $dTool->count = $i;
            $dTool->generateData();
        }

        $generatedIds[] = $dTool->generateId();
        $GLOBALS['allProcessedRecords']++;
        $dTool->generateRelationships();

        $GLOBALS['processedRecords']++;
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

        if (isset($GLOBALS['clean'])) {
            $tbaGenerator->clearDB();
        } elseif (isset($GLOBALS['obliterate'])) {
            $tbaGenerator->obliterateDB();
        }

        if (!empty($GLOBALS['tba'])) {
            $tbaGenerator->setAclRoleIds($generatedIds);
            $tbaGenerator->setRoleActions($roleActions);
            $tbaGenerator->setTbaFieldAccess($tbaFieldAccess);
            $tbaGenerator->setTbaRestrictionLevel($tbaRestrictionLevel);
            $tbaGenerator->generate();
        }
    }

    if ($module == 'Users') {
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
echo "Total Time: " . microtime_diff($GLOBALS['startTime'], microtime()) . "\n";
echo "Core Records Inserted: " . $GLOBALS['processedRecords'] . "\n";
echo "Total Records Inserted: " . $GLOBALS['allProcessedRecords'] . "\n";

// BEGIN Activity Stream populating
if (!empty($GLOBALS['as_populate'])) {

    $activityStreamOptions = array(
        'activities_per_module_record' => !empty($GLOBALS['as_number']) ? $GLOBALS['as_number'] : 10,
        'insertion_buffer_size' => !empty($GLOBALS['as_buffer']) ? $GLOBALS['as_buffer'] : 1000,
        'last_n_records' => !empty($GLOBALS['as_last_rec']) ? $GLOBALS['as_last_rec'] : 0,
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
        if (isset($GLOBALS['iterator'])) {
            $tga->iterator = $GLOBALS['iterator'];
            if ($tga->lastNRecords >= $GLOBALS['iterator']) {
                $tga->lastNRecords = $GLOBALS['iterator'];
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
        if (isset($GLOBALS['obliterate'])) {
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
    if (isset($GLOBALS['UseExistUsers'])) {
        $converter->convert('user_preferences');
    }
}

echo "Done\n\n\n";
