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

class Mysql extends Common
{
    /**
     * @var string
     */
    const STORE_TYPE = Factory::OUTPUT_TYPE_MYSQL;

    private $stmtCache = [];

    /**
     * {@inheritdoc}
     *
     */
    public function save($tableName, array $installData)
    {
        $columns = array_keys($installData[0]);
        $rowsPerBatch = floor(5E4 / count($columns));
        for ($i = 0; $i < ceil(count($installData)/$rowsPerBatch); $i++) {
            $from = $rowsPerBatch * $i;
            $to = min($rowsPerBatch * ($i + 1), count($installData));
            $this->do($tableName, array_slice($installData, $from, $to - $from));
            echo "$tableName: $from - $to\n";
        }
    }

    private function do($tableName, array $installData)
    {
        if (!$tableName || !$installData) {
            throw new Exception("Mysql adapter error: wrong data to insert");
        }

        $stmtCacheKey = $tableName . count($installData);
        if (!isset($this->stmtCache[$stmtCacheKey])) {
            $columns = array_keys($installData[0]);
            $sql = 'INSERT INTO ' . $tableName . ' (' . implode(',', $columns) . ") VALUES \n";

            $rowPlaceholders = '('.str_repeat('?,', count($columns) - 1).'?'.')';
            $placeholders = str_repeat($rowPlaceholders.",\n", count($installData) - 1).$rowPlaceholders;
            $sql .= $placeholders;

            $stmt = $this->storageResource->getConnection()->prepare($sql);
            $this->stmtCache[$stmtCacheKey] = $stmt;
        }

        $stmt = $this->stmtCache[$stmtCacheKey];

        $n = 1;
        foreach ($installData as $row) {
            foreach ($row as $value) {
                $stmt->bindValue($n, $value);
                $n++;
            }
        }

        if (!$stmt->execute()) {
            throw new \Exception("failed to execute statement");
        }
    }
}
