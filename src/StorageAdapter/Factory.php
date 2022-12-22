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

namespace Sugarcrm\Tidbit\StorageAdapter;

use Sugarcrm\Tidbit\Exception;
use Sugarcrm\Tidbit\StorageAdapter\Storage\Common;

class Factory
{
    public const OUTPUT_TYPE_MYSQL = 'mysql';
    public const OUTPUT_TYPE_ORACLE = 'oracle';
    public const OUTPUT_TYPE_CSV = 'csv';
    public const OUTPUT_TYPE_DB2 = 'db2';

    /**
     * List of storage types
     *
     * @var array
     */
    private static $availableTypes = [
        self::OUTPUT_TYPE_CSV,
        self::OUTPUT_TYPE_MYSQL,
        self::OUTPUT_TYPE_ORACLE,
        self::OUTPUT_TYPE_DB2,
    ];

    /**
     * Storage Adapter Creator
     *
     * @throws Exception
     *
     */
    public static function getAdapterInstance(
        string $storageType,
        $storageResource,
        string $logQueryPath = ''
    ): Common
    {
        if (!in_array($storageType, self::$availableTypes)) {
            throw new Exception('Unsupported storage type');
        }

        $storageAdapterName = self::getAdapterClassName($storageType);
        return new $storageAdapterName($storageResource, $logQueryPath);
    }

    /**
     * Determine full storage-adapter name and include it
     */
    private static function getAdapterClassName(string $storageType): string
    {
        $adapterSuffixName = ucfirst($storageType);
        return '\Sugarcrm\Tidbit\StorageAdapter\Storage\\' . $adapterSuffixName;
    }
}
