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

namespace Sugarcrm\Tidbit\Generator;

use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\Generator\Activity\Entity;
use Sugarcrm\Tidbit\InsertBuffer;
use Sugarcrm\Tidbit\StorageAdapter\Storage\Common as StorageCommon;

class Activity
{
    const ACTIVITY_TABLE = 'activities';
    const RELATIONSHIP_TABLE = 'activities_users';

    /** @var  int */
    protected $activitiesPerModuleRecord;

    /** @var int  */
    protected $insertedActivitiesCount = 0;

    /** @var \DBManager  */
    protected $db;

    /** @var array  */
    protected $userIds = array();

    /** @var  string */
    protected $convertedDateTime;

    /** @var  InsertBuffer */
    protected $insertBufferActivities;

    /** @var  InsertBuffer */
    protected $insertBufferActivitiesRelationships;

    /** @var array  */
    protected $activityModulesBlackList = array();

    /**
     * Setting to generate activity just for last N records
     *
     * @var int
     */
    protected $lastNRecords = 0;

    /**
     * @var array Generating activity types and percentage of their share in total count. Array sum must be 100!
     */
    protected $activityTypeShares = array(
        'create' => 40,
        'delete' => 30,
        'attach' => 0,
        'update' => 30,
        'link'   => 0,
        'unlink' => 0,
        'post'   => 0,
    );

    /**
     * Constructor
     *
     * @param \DBManager $db
     * @param StorageCommon $adapter
     * @param int $activitiesPerModule
     * @param int $lastNRecords
     */
    public function __construct(
        \DBManager $db,
        StorageCommon $adapter,
        $activitiesPerModule,
        $lastNRecords
    ) {
        $this->db = $db;
        $this->activitiesPerModuleRecord = $activitiesPerModule;
        $this->lastNRecords = $lastNRecords;
        $this->insertBufferActivities = new InsertBuffer(self::ACTIVITY_TABLE, $adapter);
        $this->insertBufferActivitiesRelationships = new InsertBuffer(
            self::RELATIONSHIP_TABLE,
            $adapter
        );
    }

    /**
     * @param array $activityModulesBlackList
     */
    public function setActivityModulesBlackList($activityModulesBlackList)
    {
        $this->activityModulesBlackList = $activityModulesBlackList;
    }

    /**
     * @param array $userIds
     */
    public function setUserIds($userIds)
    {
        $this->userIds = $userIds;
    }

    /**
     * @return int
     */
    public function getInsertedActivitiesCount()
    {
        return $this->insertedActivitiesCount;
    }

    /**
     * @return int
     */
    public function getLastNRecords()
    {
        return $this->lastNRecords;
    }

    /**
     * Checker of possibility of activity generation
     *
     * @param \SugarBean $bean
     * @return bool
     */
    public function willGenerateActivity(\SugarBean $bean)
    {
        return !empty($this->userIds)
            && !empty($GLOBALS['beanList']['Activities']) // Check that used sugar instance supports AS
            && $bean->isActivityEnabled()
            && !in_array($bean->module_name, $this->activityModulesBlackList)
        ;
    }

    /**
     * @param int $totalModuleRecords
     * @return int
     */
    public function calculateActivitiesToCreate($totalModuleRecords)
    {
        $affectedModuleRecords = empty($this->lastNRecords) || ($totalModuleRecords < $this->lastNRecords) ?
            $totalModuleRecords :
            $this->lastNRecords
        ;

        return $affectedModuleRecords * $this->activitiesPerModuleRecord * count($this->userIds);
    }

    /**
     * @param DataTool $dTool
     * @param \SugarBean $bean
     */
    public function createActivityForRecord(DataTool $dTool, \SugarBean $bean)
    {
        if (!$this->willGenerateActivity($bean)) {
            return;
        }

        foreach ($this->userIds as $uid) {
            for ($index = 0; $index < $this->activitiesPerModuleRecord; $index++) {
                $this->createActivity($index, $uid, $dTool, $bean->object_name);
            }
        }
    }

    /**
     * Write to storage activities for record in DataTool.
     *
     * @param int $index
     * @param string $uid
     * @param DataTool $dTool
     * @param string $beanObjectName
     */
    protected function createActivity($index, $uid, DataTool $dTool, $beanObjectName)
    {
        $fillPercentage = round(($index / $this->activitiesPerModuleRecord) * 100);
        $activityType = $this->getNextActivityType($fillPercentage);

        $activityEntity = new Entity($uid, $dTool, $beanObjectName, $activityType);
        $activityData = $activityEntity->getData();
        $relationshipsData = $activityEntity->getRelationshipsData();

        $this->insertBufferActivities->addInstallData($activityData);
        foreach ($relationshipsData as $rel) {
            $this->insertBufferActivitiesRelationships->addInstallData($rel);
        }

        $this->insertedActivitiesCount++;
    }

    /**
     * Clear activities db.
     *
     * @return bool
     */
    public function obliterateActivities()
    {
        return $this->db->query($this->getTruncateTableSQL(self::ACTIVITY_TABLE))
        && $this->db->query($this->getTruncateTableSQL(self::RELATIONSHIP_TABLE));
    }

    /**
     * Contains truncate db table logic for different DB Managers
     *
     * @param $tableName
     * @return string
     */
    protected function getTruncateTableSQL($tableName)
    {
        return ($this->db->dbType == 'ibm_db2')
            ? sprintf('ALTER TABLE %s ACTIVATE NOT LOGGED INITIALLY WITH EMPTY TABLE', $tableName)
            : $this->db->truncateTableSQL($tableName);
    }

    /**
     * @param $fillPercentage
     * @return int|string
     */
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
}
