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

namespace Sugarcrm\Tidbit\StorageAdapter\Storage;

use Sugarcrm\Tidbit\Exception;
use Sugarcrm\Tidbit\StorageAdapter\Factory;

class Csv extends Common
{
    public const STORE_TYPE = Factory::OUTPUT_TYPE_CSV;

    protected string $filenameSuffix = '';

    public function __construct($storageResource, $logQueryPath = '')
    {
        parent::__construct($storageResource, $logQueryPath);
        if (!$this->storageResource
            || !is_string($this->storageResource)
            || !file_exists($this->storageResource)
        ) {
            throw new Exception(
                "For csv generation storageResource must be string with path to saving directory"
            );
        }
    }

    public function setFilenameSuffix(string $suffix)
    {
        $this->filenameSuffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $tableName, array $installData)
    {
        if (!$tableName || !$installData) {
            throw new Exception("Csv adapter error: wrong data to save");
        }

        $fileName = $this->getFileName($tableName);
        $needHeader = !file_exists($fileName);
        $storeFile = fopen($fileName, 'a');

        $dataToWrite = '';
        if ($needHeader) {
            $dataToWrite = $this->prepareCsvString(array_keys($installData[0]), "'");
        }

        foreach ($installData as $data) {
            $dataToWrite .= $this->prepareCsvString($data);
        }

        fwrite($storeFile, $dataToWrite);
        fclose($storeFile);
    }

    /**
     * Remove spaces and wrap to quotes values in the list
     *
     * @param string $quote
     * @return string
     */
    protected function prepareCsvString(array $values, $quote = '')
    {
        foreach ($values as $k => $v) {
            if ($v === 'null' || $v === 'NULL') {
                $values[$k] = '';
            } else {
                $values[$k] = $quote . trim($v) . $quote;
            }
        }
        return join(',', $values) . "\n";
    }

    /**
     * Return full path to file for data storing
     */
    protected function getFileName(string $tableName): string
    {
        return $this->storageResource . '/' . $tableName . $this->filenameSuffix . '.csv';
    }

    /**
     * Stubbed for csv
     */
    public function commitQuery()
    {

    }

    /**
     * Stubbed for csv
     *
     * @param string $query
     * @param bool $quote
     */
    public function executeQuery($query, $quote = true)
    {

    }
}
