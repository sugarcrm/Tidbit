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

require_once("Tidbit/Tidbit/Exception.php");

abstract class Tidbit_StorageAdapter_Storage_Abstract {

    /**
     * @var string
     */
    const STORE_TYPE = 'abstract';

    /**
     * Connector to db or dir-path for store data
     *
     * @var mixed
     */
    protected $storageResource = null;

    /**
     * Descriptor of file for query logging
     *
     * @var Resource
     */
    protected $logQueriesFile = null;

    /**
     * Unix timestamp of start query execution
     *
     * @var string
     */
    protected $queryStartUnixTS = '';

    /**
     * Constructor
     *
     * @param mixed $storageResource
     * @param string $logQueryPath
     */
    public function __construct($storageResource, $logQueryPath = '')
    {
        $this->storageResource = $storageResource;
        if ($logQueryPath) {
            $this->logQueriesFile = fopen($logQueryPath, 'a');
        }
        $this->queryStartUnixTS = microtime();
    }

    /**
     *  Saves data from tool to storage
     *
     * @param string $tableName
     * @param array $installData
     */
    abstract public function save($tableName, array $installData);

    /**
     * Getter for query exec start time
     *
     * @return string
     */
    public function getQueryExecStartTime()
    {
        return $this->queryStartUnixTS;
    }

    /**
     * Makes commit in db
     */
    public function commitQuery()
    {
        $this->storageResource->query('COMMIT');
    }

    /**
     * Straight request into storage
     *
     * @param string $query
     * @param bool $quote
     */
    protected function executeQuery($query, $quote = true)
    {
        $this->logQuery($query);
        if ($quote) {
            $query = $this->storageResource->quote($query);
        }
        $this->storageResource->query($query, true, "QUERY FAILED");
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        if ($this->logQueriesFile) {
            fclose($this->logQueriesFile);
        }
    }

    /**
     * Log query into the file if it provided
     *
     * @param string $query
     */
    protected function logQuery($query)
    {
        if (is_resource($this->logQueriesFile)) {
            fwrite($this->logQueriesFile, $query . "\n");
        }
    }
}
