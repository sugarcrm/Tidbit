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
$module_keys = array_values(array_unique($module_keys));

echo "Constructing\n";
foreach ($module_keys as $module) {
    echo "{$modules[$module]} {$module}\n";
}

echo "\n";
echo "With Clean Mode " . (isset($GLOBALS['clean']) ? "ON" : "OFF") . "\n";
echo "\n";

// creating storage adapter
//if no storage flags are passed, try to autodetect storage from the sugar install
if (empty($opts['storage'])) {
    $sugarStorageType = $sugar_config['dbconfig']['db_type'];
    switch ($sugarStorageType) {
        case 'oci8':
            $storageType = 'oracle';
            break;
        case 'ibm_db2':
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

$relationStorageBuffers = array();

$storageAdapter = \Sugarcrm\Tidbit\StorageAdapter\Factory::getAdapterInstance($storageType, $storage, $logQueriesPath);

$mc = count($module_keys);
for ($mn = 1; $mn <= $mc; $mn++) {
    $module = $module_keys[$mn-1];
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

    $progressLogPrefix = "$module [$mn/$mc]";

    echo "\nProcessing Module $module"
        .(isset($tidbit_relationships[$module])
            ? " with relationships to ".implode(", ", array_keys($tidbit_relationships[$module]))
            :"")
        .":\n";

    if (in_array($module, $moduleUsingGenerators)) {
        $generatorName = '\Sugarcrm\Tidbit\Generator\\' . $module;
        /** @var \Sugarcrm\Tidbit\Generator\Common $generator */
        $generator = new $generatorName($GLOBALS['db'], $storageAdapter, $modules[$module]);

        if (isset($GLOBALS['clean'])) {
            echo "\tCleaning up Tidbit and demo data ... ";
            $generator->clearDB();
            echo "DONE";
        }

        echo "\n\tHitting DB... ";
        $generator->generate();
        $total = $generator->getInsertCounter();
        showProgress($progressLogPrefix, $modules[$module], $modules[$module]);
        echo "\n\tTime spend... " . microtime_diff($moduleTimeStart, microtime()) . "s\n";
        continue;
    }

    echo "Inserting ${total} records.\n";
    $bean = BeanFactory::getBean($module);

    $generatorClass = "\Sugarcrm\Tidbit\Generator\\{$module}Generator";
    if (!class_exists($generatorClass)) {
        $generatorClass = \Sugarcrm\Tidbit\Generator\ModuleGenerator::class;
    }
    $g = new $generatorClass($bean);
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
    if ($bean->isFavoritesEnabled()) {
        $d = new Sugarcrm\Tidbit\Generator\FavoritesDecorator($g);
        if ($d->isUsefull()) {
            $g = $d;
        }
    }
    if ($GLOBALS['parallel']) {
        $c = new \Sugarcrm\Tidbit\Generator\ForkingController($g, $GLOBALS['parallel']);
    } else {
        $c = new \Sugarcrm\Tidbit\Generator\Controller($g);
    }
    $c->setProgressLogPrefix($progressLogPrefix);

    if (isset($GLOBALS['clean'])) {
        echo "\tCleaning up demo data ... ";
        $g->clean();
        echo "DONE\n";
    }

    $c->generate($total);
    echo "\tTime spend... " . microtime_diff($moduleTimeStart, microtime()) . "s\n";
}

// Update enabled Modules Tabs
\Sugarcrm\Tidbit\Helper\ModuleTabs::updateEnabledTabs($GLOBALS['db'], $module_keys, $GLOBALS['moduleList']);

if ($storageType != 'csv') {
    echo "Creating implicit team memberships based on the users hierarchy\n";
    \Sugarcrm\Tidbit\Helper\RepairTeams::repair();

    echo "Rebuild team security denormalized table\n";
    if (\Sugarcrm\Tidbit\Helper\TeamSecurityDenorm::denorm() != 0) {
        exit(11);
    }
}

echo "\n";
echo "Total Time: " . microtime_diff($GLOBALS['startTime'], microtime()) . "\n";
echo "Core Records Inserted: " . $GLOBALS['processedRecords'] . "\n";
echo "Total Records Inserted: " . $GLOBALS['allProcessedRecords'] . "\n";

echo "Done\n";
