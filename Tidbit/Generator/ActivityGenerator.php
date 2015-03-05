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

require_once 'Entities/ActivityEntity.php';

class TidbitActivityGenerator
{
    public $userCount;
    public $activitiesPerModuleRecord;
    public $activitiesPerUser;
    public $activityModules;
    public $activityModuleCount;
    public $modules;
    public $db;
    public $insertionBufferSize = 100;
    public $fetchBuffer = 1000;
    public $lastNRecords = 0;
    public $insertedActivities = 0;
    public $activityBean;
    public $progress = 0;
    public $countQuery = 0;
    public $countFetch = 0;
    protected $beans = array();
    protected $activityFields = array();
    protected $totalModulesRecords = 0;

    /**
     * Constant. Activities won't be created for these modules
     * @var array
     */
    protected $moduleBlackList = array(
        'Users',
        'Teams',
        'ProductBundles',
        'EmailAddresses',
        'Documents',
        'Notes',
        'Emails',
        'Bugs'
    );

    /**
     * Dynamic variable using to pass values to another iteration of createDataSet -> flushDataSet
     * @var array
     */
    protected $currentOffsets = array();
    protected $currentModuleName;

    protected $fetchQueryPatterns = array(
        'default' => "SELECT id%s FROM %s ORDER BY date_modified DESC LIMIT %d, %d",
    );
    protected $insertQueryPattern = "INSERT INTO %s (%s) VALUES %s";
    protected $fetchedData = array();
    protected $fullyLoadedModules = array();
    protected $currentUser;
    protected $currentModuleRecord;
    protected $nextActivityModule = 0;
    protected $dataSet = array();
    protected $dataSetRelationships = array();
    protected $dataSetLength = 0;
    protected $relationshipsTable = 'activities_users';

    /**
     * @var array Generating activity types and percentage of their share in total count. Array sum must be 100!
     */
    protected $activityTypeShares = array(
        'create' => 40,
        'delete' => 30,
        'attach' => 0,
        'update' => 30,
        'link' => 0,
        'unlink' => 0,
        'post' => 0,
    );

    public function init()
    {
        foreach ($this->modules as $module => $moduleRecordCount) {
            $this->beans[$module] = BeanFactory::getBean($module);
            $this->currentOffsets[$module] = array(
                'offset' => 0,
                'next' => 0,
                'total' => $moduleRecordCount,
            );
        }
        $this->activityModules = array_values(array_diff(array_keys($this->modules), $this->moduleBlackList));
        foreach ($this->activityModules as $module) {
            // apply lastNRecords option
            $this->currentOffsets[$module]['total'] = $this->lastNRecords > 0 && $this->modules[$module] > $this->lastNRecords
                ? $this->lastNRecords
                : $this->modules[$module];
            $this->totalModulesRecords += $this->currentOffsets[$module]['total'];
        }
        $this->activitiesPerUser = $this->totalModulesRecords * $this->activitiesPerModuleRecord;
        $this->activityModuleCount = count($this->activityModules);
        $this->activityBean = BeanFactory::getBean('Activities');
        foreach ($this->activityBean->field_defs as $field => $data) {
            if (empty($data['source'])) {
                $this->activityFields[$field] = $data;
            }
        }
        $this->initNextModuleName();
    }

    public function obliterateActivities()
    {
        return $this->query("DELETE FROM {$this->activityBean->table_name}")
        && $this->query("DELETE FROM {$this->relationshipsTable}");
    }

    public function createDataSet()
    {
        $this->currentUser = $this->currentUser ? $this->currentUser : $this->nextUser();

        while ($this->currentModuleRecord = $this->nextModuleRecord($this->currentModuleName)) {
            $this->createActivitiesForRecord();
            if ($this->dataSetLength >= $this->insertionBufferSize) {
                // flush current buffers
                return true;
            }
        }

        // iterate again with next module
        $nextModuleNameExists = $this->initNextModuleName();
        if ($nextModuleNameExists) {
            $this->currentOffsets[$this->currentModuleName]['next'] = 0;
            return true;
        }

        // iterate again with next user
        $this->currentUser = $this->nextUser();
        if ($this->currentUser) {
            $this->cleanOffsets();
            $this->nextActivityModule = 0;
            $this->initNextModuleName();
            return true;
        }

        // we have no next module nor next user, so process finished
        return false;
    }

    protected function createActivitiesForRecord()
    {
        for ($index = 0; $index < $this->activitiesPerModuleRecord; $index++) {
            $this->createActivity($index);
        }
    }

    protected function cleanOffsets()
    {
        foreach ($this->currentOffsets as $module => $offsetData) {
            if ($module !== 'Users') {
                $this->currentOffsets[$module]['next'] = 0;
            }
        }
    }

    protected function createActivity($index)
    {
        $activityEntity = new TidbitActivityEntity($this->activityFields);
        $activityEntity->moduleId1 = $this->currentUser['id'];
        $activityEntity->moduleId2 = $this->currentModuleRecord['id'];
        $activityEntity->moduleName1 = 'Users';
        $activityEntity->moduleName2 = $this->currentModuleName;
        $activityEntity->moduleBean2 = $this->beans[$this->currentModuleName];
        $fillPercentage = round(($index / $this->activitiesPerModuleRecord) * 100);
        $activityEntity->activityType = $this->getNextActivityType($fillPercentage);
        $activityEntity->activityData = $this->currentModuleRecord;
        $activityEntity->initialize();
        $this->dataSet[] = $activityEntity->getData();
        $this->dataSetLength++;
        $relationships = $activityEntity->getRelationshipsData();
        foreach ($relationships as $rel) {
            $this->dataSetRelationships[] = $rel;
        }

    }

    public function flushDataSet($final = false)
    {
        if (!empty($this->dataSet) && (($this->dataSetLength >= $this->insertionBufferSize) || $final)) {
            // activities
            $result = $this->insertDataSet($this->dataSet, $this->activityBean->table_name);
            if ($result) {
                // relationships
                $result = $this->insertDataSet($this->dataSetRelationships, $this->relationshipsTable);

                if ($result) {
                    $this->insertedActivities += $this->dataSetLength;
                    $this->progress = round(($this->currentOffsets['Users']['next'] / ($this->currentOffsets['Users']['total'] + 1)) * 100);

                    $this->dataSet = $this->dataSetRelationships = array();
                    $this->dataSetLength = 0;

                    return true;
                }
            }
            // incorrect query or db error is fatal error
            return false;
        } else {
            // empty dataSet is not an error
            return true;
        }
    }

    protected function insertDataSet(array $dataSet, $tableName)
    {
        if (!empty($dataSet)) {
            $columns = array_keys($dataSet[0]);
            $dataRows = array();
            foreach ($dataSet as $row) {
                $dataRows[] = "(" . implode(", ", array_map(array($this->db, 'quoted'), $row)) . ")";
            }
            $sql = sprintf(
                $this->insertQueryPattern,
                $tableName,
                implode(', ', $columns),
                implode(', ', $dataRows)
            );
            return $this->query($sql);
        } else {
            return false;
        }
    }

    protected function initNextModuleName()
    {
        $moduleName = isset($this->activityModules[$this->nextActivityModule]) ? $this->activityModules[$this->nextActivityModule++] : null;
        if ($moduleName) {
            $this->currentModuleName = $moduleName;
            return true;
        }
        return false;
    }

    protected function nextModuleRecord($moduleName)
    {
        if (empty($this->fetchedData[$moduleName][$this->currentOffsets[$moduleName]['next']])) {
            if (empty($this->fullyLoadedModules[$moduleName])) {
                $this->fetchedData[$moduleName] = isset($this->fetchedData[$moduleName]) ? $this->fetchedData[$moduleName] : array();
                if ($data = $this->fetchNextModuleRecords($moduleName)) {
                    foreach ($data as $k => $v) {
                        $this->fetchedData[$moduleName][$k] = $v;
                    }
                }
            }
        }
        if (!empty($this->fetchedData[$moduleName][$this->currentOffsets[$moduleName]['next']])) {
            return $this->fetchedData[$moduleName][$this->currentOffsets[$moduleName]['next']++];
        }
        return null;
    }

    protected function nextUser()
    {
        $user = $this->nextModuleRecord('Users');
        if (!empty($user['id']) && $user['id'] == 1) { // skip admin
            $user = $this->nextModuleRecord('Users');
        }
        return $user;
    }

    protected function fetchNextModuleRecords($moduleName)
    {
        $queryPattern = isset($this->fetchQueryPatterns[$moduleName]) ? $this->fetchQueryPatterns[$moduleName] : $this->fetchQueryPatterns['default'];

        $extraFields = array();
        $hasName = !empty($this->beans[$moduleName]->field_defs['name']) && empty($this->beans[$moduleName]->field_defs['name']['source']);
        if ($hasName) {
            $extraFields[] = 'name';
        }
        $hasLastName = !empty($this->beans[$moduleName]->field_defs['last_name']) && empty($this->beans[$moduleName]->field_defs['last_name']['source']);
        if ($hasLastName) {
            $extraFields[] = 'last_name';
        }

        $data = null;

        $limit = $this->fetchBuffer;
        if ($this->currentOffsets[$moduleName]['offset'] + $limit > $this->currentOffsets[$moduleName]['total']) {
            $limit = $this->currentOffsets[$moduleName]['total'] - $this->currentOffsets[$moduleName]['offset'];
            $this->fullyLoadedModules[$moduleName] = true;
        }

        if ($limit > 0) {
            $sql = sprintf(
                $queryPattern,
                empty($extraFields) ? '' : ', ' . implode(', ', $extraFields),
                $this->beans[$moduleName]->table_name,
                $this->currentOffsets[$moduleName]['offset'],
                $limit
            );
            $data = $this->fetch($sql, $this->currentOffsets[$moduleName]['offset']);
        }
        if ($data) {
            $this->currentOffsets[$moduleName]['offset'] += $limit;
        } else {
            $this->fullyLoadedModules[$moduleName] = true;
        }
        return $data;
    }

    protected function fetch($sql, $startIndex = 0)
    {
        $out = array();
        $res = $this->query($sql);
        while ($row = $this->db->fetchByAssoc($res)) {
            $this->countFetch++;
            $out[$startIndex++] = $row;
        }
        return $out;
    }

    protected function getNextActivityType($fillPercentage)
    {
        $percentage = 0;
        foreach ($this->activityTypeShares as $at => $ats) {
            $percentage += $ats;
            if ($fillPercentage <= $percentage) {
                return $at;
            }
        }
    }

    protected function query($sql)
    {
        $this->countQuery++;
        return $this->db->query($sql);
    }
}
