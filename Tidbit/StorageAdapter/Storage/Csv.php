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

require_once('Tidbit/Tidbit/StorageAdapter/Storage/Abstract.php');

class Tidbit_StorageAdapter_Storage_Csv extends Tidbit_StorageAdapter_Storage_Abstract {

    /**
     * @var string
     */
    const STORE_TYPE = Tidbit_StorageAdapter_Factory::OUTPUT_TYPE_CSV;

    /**
     * {@inheritdoc}
     *
     */
    public function save($tableName, array $installData)
    {

        if (!$tableName || !$installData) {
            throw new Tidbit_Exception("Csv adapter error: wrong data to save");
        }

        $fileName = $this->getCurrentFilePathName($tableName);
        $needHeader = !file_exists($fileName);
        $storeFile = fopen($fileName, 'a');

        if ($needHeader) {
            $head = $this->prepareCsvString(array_keys($installData[0]), "'");
            fwrite($storeFile, $head);
        }

        foreach ($installData as $data) {
            fwrite($storeFile, $this->prepareCsvString($data));
        }

        fclose($storeFile);
    }

    /**
     * Remove spaces and wrap to quotes values in the list
     *
     * @param array $values
     * @param string $quote
     * @return string
     */
    protected function prepareCsvString(array $values, $quote = '')
    {
        foreach ($values as $k => $v) {
            if (strtolower($v) == 'null') {
                $values[$k] = '';
            } else {
                $values[$k] = $quote . trim($v) . $quote;
            }
        }
        return join(',', $values) . "\n";
    }

    /**
     * Return full path to file for data storing
     *
     * @param string $tableName
     * @return string
     * @throws Tidbit_Exception
     */
    protected function getCurrentFilePathName($tableName)
    {
        if (!$this->storageResource
            || !is_string($this->storageResource)
            || !file_exists($this->storageResource)
        ) {
            throw new Tidbit_Exception(
                "For csv generation storageResource must be string with path to saving directory"
            );
        }
        return $this->storageResource . '/' . $tableName . '.csv';
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
    protected function executeQuery($query, $quote = true)
    {

    }
}
