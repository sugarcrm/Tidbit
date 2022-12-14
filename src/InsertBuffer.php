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

namespace Sugarcrm\Tidbit;

use Sugarcrm\Tidbit\StorageAdapter\Storage\Common;

/**
 * Class-container of generated data to adopt it for Storage Adapters
 */
class InsertBuffer
{
    protected array $installData = [];

    public function __construct(protected string $tableName, protected Common $storage)
    {
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        if (!empty($this->installData)) {
            echo "\nNOTICE: It would be better to explicitly call flush() method for saving data chunks for table: '"
                . $this->tableName
                . "'.";
        }
        $this->flush();
    }

    /**
     * @param array $installData
     */
    public function addInstallData($installData): void
    {
        $this->installData[] = $installData;
        if (count($this->installData) >= $GLOBALS['insertBatchSize']) {
            $this->makeSave();
        }
    }

    /**
     * Clear data to save
     */
    public function clear(): void
    {
        $this->installData = [];
    }

    /**
     * Flush is used for insert final chunks of data from buffer
     *
     */
    public function flush(): void
    {
        if ($this->tableName && $this->installData) {
            $this->makeSave();
        }
    }

    /**
     * rtfn
     */
    protected function makeSave(): void
    {
        $this->storage->save($this->tableName, $this->installData);
        $this->clear();
    }
}
