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
echo "With " . $maxTeamsPerSet ." teams in Team Sets \n";
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
$prefsGenerator = new \Sugarcrm\Tidbit\Generator\UserPreferences($GLOBALS['db'], $storageAdapter);
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

    echo "\nProcessing Module $module"
        .(isset($tidbit_relationships[$module])
            ? " with relationships to ".implode(", ", array_keys($tidbit_relationships[$module]))
            :"")
        .":\n";
    $total = $modules[$module];

    if (in_array($module, $moduleUsingGenerators)) {
        $generatorName = '\Sugarcrm\Tidbit\Generator\\' . $module;
        /** @var \Sugarcrm\Tidbit\Generator\Common $generator */
        $generator = new $generatorName($GLOBALS['db'], $storageAdapter, $insertBatchSize, $modules[$module]);

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

    // If the module has custom fields, write to the _cstm table
    $useCustomTable = $bean->hasCustomFields();

    if (isset($GLOBALS['obliterate'])) {
        echo "\tObliterating all existing data ... ";
        /* Make sure not to delete the admin! */
        if ($module == 'Users') {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1'");
            $prefsGenerator->obliterate();
        } elseif ($module == 'Teams') {
            $GLOBALS['db']->query("DELETE FROM teams WHERE id != '1'");
            $GLOBALS['db']->query("DELETE FROM team_sets");
            $GLOBALS['db']->query("DELETE FROM team_sets_teams");
            $GLOBALS['db']->query("DELETE FROM team_sets_modules");
        } else {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1 = 1");

            // if module has custom table obliterate up custom table too
            if ($useCustomTable) {
                $GLOBALS['db']->query("DELETE FROM " . $bean->get_custom_table_name() . " WHERE 1 = 1");
            }
        }
        if (!empty($tidbit_relationships[$module])) {
            foreach ($tidbit_relationships[$module] as $rel) {
                if (!empty($obliterated[$rel['table']])) {
                    continue;
                }
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
        } elseif ($module == 'Teams') {
            //TBD: Following 3 queries only tested with Mysql database,
            //  if you are using database such as Oracle, DB2, MSSQL, you might need to refactor those 3 queries.
            $GLOBALS['db']->query(
                "DELETE a FROM team_sets_teams a JOIN teams b ON b.id=a.team_id "
                ."WHERE b.id != '1' AND b.id LIKE 'seed-%'"
            );
            $GLOBALS['db']->query(
                "DELETE a FROM team_sets a LEFT JOIN (SELECT DISTINCT team_set_id FROM team_sets_teams"
                . " WHERE deleted=0) b ON a.id=b.team_set_id WHERE b.team_set_id is null"
            );
            $GLOBALS['db']->query(
                "DELETE a FROM team_sets_modules a left JOIN team_sets b ON a.team_set_id=b.id"
                . " WHERE b.id is null AND a.team_set_id is not null"
            );
            $GLOBALS['db']->query("DELETE FROM teams WHERE id != '1' AND id LIKE 'seed-%'");
        } else {
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1=1 AND id LIKE 'seed-%'");

            // if module has custom table clean up custom table too
            if ($useCustomTable) {
                $GLOBALS['db']->query(
                    "DELETE FROM " . $bean->get_custom_table_name()
                    . " WHERE 1=1 AND id_c LIKE 'seed-%'"
                );
            }
        }
        if (!empty($tidbit_relationships[$module])) {
            foreach ($tidbit_relationships[$module] as $rel) {
                if (!empty($obliterated[$rel['table']])) {
                    continue;
                }
                $obliterated[$rel['table']] = true;
                $GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1=1 AND id LIKE 'seed-%'");
            }
        }
        echo "DONE";
    }

    $dTool = new \Sugarcrm\Tidbit\DataTool($storageType);
    $dTool->table_name = $bean->getTableName();
    $dTool->module = $module;
    $dTool->setFields($bean->field_defs);

    if (!empty($GLOBALS['as_populate']) && $activityGenerator->willGenerateActivity($bean)) {
        echo "\n\tWill create " . $activityGenerator->calculateActivitiesToCreate($total) . " activity records";
    }
    echo "\n";

    $beanInsertBuffer = new \Sugarcrm\Tidbit\InsertBuffer($dTool->table_name, $storageAdapter, $insertBatchSize);

    if ($useCustomTable) {
        $beanInsertBufferCustom = new \Sugarcrm\Tidbit\InsertBuffer(
            $bean->get_custom_table_name(),
            $storageAdapter,
            $insertBatchSize
        );
    }

    /* We need to insert $total records
     * into the DB.  We are using the module and table-name given by
     * $module and $bean->table_name. */
    $generatedIds = array();
    for ($i = 0; $i < $total; $i++) {
        $dTool->clean();
        $dTool->count = $i;
        $beanId = $dTool->generateId($useCustomTable);
        $dTool->generateData();

        // Generate relationships if $bean has an "id" field
        if ($beanId) {
            $generatedIds[] = $beanId;

            /** @var \Sugarcrm\Tidbit\Core\Relationships $relationships */
            $relationships = \Sugarcrm\Tidbit\Core\Factory::getComponent('Relationships');
            $relationships->generateRelationships($dTool->module, $dTool->count, $dTool->installData);

            if ($relationships->getRelatedModules()) {
                foreach ($relationships->getRelatedModules() as $table => $installDataArray) {
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

            $relationships->clearRelatedModules();
        }

        $beanInsertBuffer->addInstallData($dTool->installData);

        // if module has custom table, write custom install data into buffer
        if (isset($beanInsertBufferCustom) && $useCustomTable && !empty($dTool->installDataCstm)) {
            $beanInsertBufferCustom->addInstallData($dTool->installDataCstm);
        }

        // Increase counters and insert generated data to Buffer
        $GLOBALS['allProcessedRecords']++;
        $GLOBALS['processedRecords']++;

        if (!empty($GLOBALS['as_populate'])
            && (
                empty($activityStreamOptions['last_n_records'])
                || $total < $activityStreamOptions['last_n_records']
                || $i >= $total - $activityStreamOptions['last_n_records']
            )
        ) {
            $activityGenerator->createActivityForRecord($dTool, $bean);
        }

        if ($_GLOBALS['txBatchSize'] && $i % $_GLOBALS['txBatchSize'] == 0) {
            $storageAdapter->commitQuery();
        }
        if ($i % (int)(max(1, min($total/100, 1000))) == 0) {
            showProgress($i, $total);
        }
    } //for

    if ($module == 'Teams') {
        $teamGenerator = new \Sugarcrm\Tidbit\Generator\TeamSets(
            $GLOBALS['db'],
            $storageAdapter,
            $insertBatchSize,
            $generatedIds,
            $maxTeamsPerSet
        );
        $teamGenerator->generate();
    }

    // Apply TBA Rules for some modules
    // $roleActions are defined in configs
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
        if (!empty($GLOBALS['as_populate'])) {
            $activityGenerator->setUserIds($generatedIds);
        }
    }

    // Flushing insertBuffer
    if (isset($beanInsertBuffer)) {
        $beanInsertBuffer->flush();
    }
    if (isset($beanInsertBufferCustom)) {
        $beanInsertBufferCustom->flush();
    }
    // Flushing insertBuffer from relationships
    if (isset($relationStorageBuffers)) {
        foreach ($relationStorageBuffers as $relationStorageBufferForFlush) {
            $relationStorageBufferForFlush->flush();
        }
    }
    showProgress($total, $total);
    echo "\tTime spend... " . microtime_diff($moduleTimeStart, microtime()) . "s\n";
}

// Update enabled Modules Tabs
\Sugarcrm\Tidbit\Helper\ModuleTabs::updateEnabledTabs($GLOBALS['db'], $module_keys, $GLOBALS['moduleList']);

// force immediately destructors work
unset($relationStorageBuffers);
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
    $converter = new \Sugarcrm\Tidbit\CsvConverter($GLOBALS['db'], $storageAdapter, $insertBatchSize);
    $converter->convert('config');
    $converter->convert('acl_actions');
}

echo "Done\n";
