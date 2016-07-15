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

class Db2 extends Common
{
    /**
     * @var string
     */
    const STORE_TYPE = Factory::OUTPUT_TYPE_DB2;

    /**
     * {@inheritdoc}
     *
     */
    public function save($tableName, array $installData)
    {
        $sql = $this->prepareQuery($tableName, $installData);
        $this->logQuery($sql);
        $this->storageResource->query($sql, true, "INSERT QUERY FAILED");
        $this->commitQuery();
    }

    /**
     * rtfn
     *
     * @param string $tableName
     * @param array $installData
     * @return string
     * @throws \Sugarcrm\Tidbit\Exception
     */
    protected function prepareQuery($tableName, array $installData)
    {
        if (!$tableName || !$installData) {
            throw new Exception("DB2 adapter error: wrong data to insert");
        }

        $columns = " (" . implode(", ", array_keys($installData[0])) . ")";
        $sql = 'INSERT INTO ' . $tableName . $columns;

        $this->patchSequenceValues($installData);
        $insertRecords = count($installData);

        for ($i = 0; $i < $insertRecords; $i++) {
            $sql .= ' VALUES ' . "(" . implode(", ", $installData[$i]) . ")";
            $sql .= ($i + 1 < $insertRecords) ? ' UNION ALL' : '';
        }

        return $sql;
    }

    /**
     * Patch inserted value if it look like sequence
     *
     * @param array $installData
     */
    protected function patchSequenceValues(array &$installData)
    {
        if (!$sequence = $this->getSequenceFromValues($installData[0])) {
            return;
        }

        $installDataCount = count($installData);
        $currentValue = $this->getCurrentSequenceValue($sequence['name']);

        for ($i=0; $i <$installDataCount; $i++) {
            $installData[$i][$sequence['field']] = ++$currentValue;
        }

        $this->setNewSequenceValue($sequence['name'], $installDataCount);
    }

    /**
     * Check array of values on containing sequence value
     *
     * @param array $values
     * @return array
     */
    protected function getSequenceFromValues(array $values)
    {
        foreach ($values as $k => $v) {
            if (substr($v, -12) == '_SEQ.NEXTVAL') {
                return array('field' => $k, 'name' => substr($v, 0, -8));
            }
        }
        return array();
    }

    /**
     * rtfn
     *
     * @param string $sequenceName
     * @return int
     */
    protected function getCurrentSequenceValue($sequenceName)
    {
        $sql = sprintf(
            "SELECT lastassignedval AS current_val FROM SYSIBM.SYSSEQUENCES WHERE seqname = '%s'",
            $sequenceName
        );
        
        $result = $this->storageResource->query($sql);
        $row = $this->storageResource->fetchByAssoc($result);

        return intval($row['current_val']);
    }

    /**
     * rtfn
     *
     * @param string $sequenceName
     * @param int $incrementOn
     */
    protected function setNewSequenceValue($sequenceName, $incrementOn)
    {
        // set increment value to our batch_size
        $sql = sprintf("ALTER SEQUENCE %s INCREMENT BY %d", $sequenceName, $incrementOn);
        $this->storageResource->query($sql);

        // do increment
        $sql = sprintf("SELECT %s.nextval FROM SYSIBM.SYSDUMMY1;", $sequenceName);
        $this->storageResource->query($sql);

        // return increment value to 1
        $sql = sprintf("ALTER SEQUENCE %s INCREMENT BY 1", $sequenceName);
        $this->storageResource->query($sql);
    }
}
