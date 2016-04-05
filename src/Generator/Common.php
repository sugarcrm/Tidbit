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
use Sugarcrm\Tidbit\InsertBuffer;

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
     * List of InsertBuffer's instances
     *
     * @var array
     */
    private $insertBuffers = array();

    /**
     * Constructor.
     *
     * @param \DBManager $db
     * @param \Sugarcrm\Tidbit\StorageAdapter\Storage\Common $storageAdapter
     * @param int $insertBatchSize
     */
    public function __construct(\DBManager $db, \Sugarcrm\Tidbit\StorageAdapter\Storage\Common $storageAdapter, $insertBatchSize)
    {
        $this->db = $db;
        $this->storageAdapter = $storageAdapter;
        $this->storageType = $storageAdapter::STORE_TYPE;
        $this->insertBatchSize = $insertBatchSize;
    }

    /**
     * Data generator.
     *
     * @param int $number
     */
    abstract public function generate($number);

    /**
     * Remove generated data from DB.
     */
    abstract public function clearDB();

    /**
     * Remove all data from the tables of DB affected by generator.
     */
    abstract public function obliterateDB();

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
                $this->insertBatchSize);
        }

        return $this->insertBuffers[$tableName];
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
        $dataTool->fields = $bean->field_defs;
        $dataTool->table_name = $bean->table_name;
        $dataTool->module = $modelName;
        $dataTool->count = $modelCounter;
        $dataTool->generateId();
        $dataTool->generateData();

        return $dataTool;
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
