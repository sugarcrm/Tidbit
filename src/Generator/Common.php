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

use Sugarcrm\Tidbit\Core\Config;
use Sugarcrm\Tidbit\Core\Factory as CoreFactory;
use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\InsertBuffer;
use Sugarcrm\Tidbit\StorageAdapter\Factory;
use \Sugarcrm\Tidbit\StorageAdapter\Storage\Common as StorageCommon;

abstract class Common
{
    /**
     * @var \DBManager
     */
    protected $db;

    /**
     * @var \Sugarcrm\Tidbit\StorageAdapter\Storage\Common
     */
    protected $storageAdapter;

    /**
     * Type of output storage
     *
     * @var string
     */
    protected $storageType;

    /**
     * @var int
     */
    protected $insertBatchSize;

    /**
     * Counter of inserting objects.
     *
     * @var int
     */
    protected $insertCounter = 0;

    /**
     * Insert counters by modules
     *
     * @var array
     */
    private $insertCounterModules = array();


    /**
     * @var Activity
     */
    protected $activityStreamGenerator;

    /**
     * Number of records what generator should create
     *
     * @var int
     */
    protected $recordsNumber = 0;

    /**
     * Object of module-class for what we'll create AS
     *
     * @var \SugarBean
     */
    protected $activityBean = null;

    /**
     * List of InsertBuffer's instances
     *
     * @var array
     */
    private $insertBuffers = array();

    /**
     * Constructor.
     *
     * @param \DBManager $db
     * @param StorageCommon $storageAdapter
     * @param int $insertBatchSize
     * @param int $recordsNumber
     */
    public function __construct(\DBManager $db, StorageCommon $storageAdapter, $insertBatchSize, $recordsNumber = 0)
    {
        $this->db = $db;
        $this->storageAdapter = $storageAdapter;
        $this->storageType = $storageAdapter::STORE_TYPE;
        $this->insertBatchSize = $insertBatchSize;
        $this->recordsNumber = $recordsNumber;
    }

    /**
     * Return object of module-class for what we'll create AS
     *
     * @return null|\SugarBean
     */
    public function getActivityBean()
    {
        return $this->activityBean;
    }

    /**
     * @param string $moduleName
     * @return int
     */
    public function getInsertCounterForModule($moduleName)
    {
        if (!isset($this->insertCounterModules[$moduleName])) {
            return 0;
        }

        return $this->insertCounterModules[$moduleName];
    }

    /**
     * @param string $moduleName
     */
    public function incrementInsertCounterForModule($moduleName)
    {
        $this->insertCounterModules[$moduleName] = $this->getInsertCounterForModule($moduleName) + 1;
    }

    /**
     * @param Activity $activityStreamGenerator
     */
    public function setActivityStreamGenerator($activityStreamGenerator)
    {
        $this->activityStreamGenerator = $activityStreamGenerator;
    }

    /**
     * Data generator.
     *
     */
    abstract public function generate();

    /**
     * Remove generated data from DB.
     */
    abstract public function clearDB();

    /**
     * Remove all data from the tables of DB affected by generator.
     */
    abstract public function obliterateDB();

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
     * @return int
     */
    public function getInsertCounter()
    {
        return $this->insertCounter;
    }

    /**
     * Lazy InsertBuffer creator
     *
     * @param string $tableName
     * @return InsertBuffer
     */
    public function getInsertBuffer($tableName)
    {
        if (empty($this->insertBuffers[$tableName])) {
            $this->insertBuffers[$tableName] = new InsertBuffer(
                $tableName,
                $this->storageAdapter,
                $this->insertBatchSize
            );
        }

        return $this->insertBuffers[$tableName];
    }

    /**
     * Flush all insertBuffers
     */
    public function flushInsertBuffers()
    {
        foreach ($this->insertBuffers as $bufferForFlush) {
            $bufferForFlush->flush();
        }
    }

    /**
     * Update generated records count in $modules array
     *
     * @param $module
     * @param $count
     */
    protected function updateModulesCount($module, $count)
    {
        /** @var Config $config */
        $config = CoreFactory::getComponent('Config');
        $config->setModuleCount($module, $count);
    }

    /**
     * Generate DataTool object with data for model.
     *
     * @param string $modelName
     * @param int $modelCounter
     * @return DataTool
     */
    protected function getDataToolForModel($modelName, $modelCounter)
    {
        $bean = \BeanFactory::getBean($modelName);

        $dataTool = new DataTool($this->storageType);
        $dataTool->setFields($bean->field_defs);
        $dataTool->table_name = $bean->table_name;
        $dataTool->module = $modelName;
        $dataTool->count = $modelCounter;
        $dataTool->generateId();
        $dataTool->generateData();

        if ($this->activityStreamGenerator) {
            $tailRecords = $this->recordsNumber - $this->activityStreamGenerator->getLastNRecords();
            if ($this->activityStreamGenerator->getLastNRecords() == 0
                || $this->recordsNumber < $this->activityStreamGenerator->getLastNRecords()
                || $this->getInsertCounterForModule($modelName) >= $tailRecords
            ) {
                $this->activityStreamGenerator->createActivityForRecord($dataTool, $bean);
            }
        }

        $this->incrementInsertCounterForModule($modelName);

        return $dataTool;
    }

    /**
     * Create/update insert object.
     *
     * @param DataTool $dataTool
     */
    protected function addInsertData($dataTool)
    {
        $this->getInsertBuffer($dataTool->table_name)->addInstallData($dataTool->installData);
        $this->insertCounter++;
    }

    /**
     * Log generator message.
     *
     * @param string $message
     */
    protected function log($message)
    {
        echo $message . "\n";
    }
}
