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


class Tidbit_Generator_UserPreferences
{
    /** @var DBManager */
    private $db;

    /** @var  Tidbit_StorageAdapter_Storage_Abstract */
    private $storageAdapter;

    /** @var array  */
    private $defaultContentsArr = array(
        'timezone' => "America/Phoenix",
        'ut' => 1,
        'Home_TEAMNOTICE_ORDER_BY' => 'date_start',
        'userPrivGuid' => 'a4836211-ee89-0714-a4a2-466987c284f4',
    );

    /**
     * Tidbit_Generator_UserPreferences constructor.
     *
     * @param DBManager $db
     * @param Tidbit_StorageAdapter_Storage_Abstract $storageAdapter
     */
    public function __construct(DBManager $db, Tidbit_StorageAdapter_Storage_Abstract $storageAdapter)
    {
        $this->db = $db;
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * @param array $userIds
     */
    public function generate(array $userIds)
    {
        if ($insertData = $this->prepareInsertData($userIds)) {
            $this->storageAdapter->save('user_preferences', $insertData);
        }
    }

    /**
     * @param array $userIds
     * @return array
     */
    private function prepareInsertData(array $userIds)
    {
        $insertData = array();
        if (!$userIds) {
            return $insertData;
        }

        $defaultContents = "'" . base64_encode(serialize($this->defaultContentsArr)) . "'";
        $currentDateTime = "'" . date('Y-m-d H:i:s') . "'";

        foreach ($userIds as $id) {
            $insertData[] = array(
                'id' => "'" . md5($id) . "'",
                'category' => "'global'",
                'date_entered' => $currentDateTime,
                'date_modified' => $currentDateTime,
                'assigned_user_id' => "'" . $id . "'",
                'contents' => $defaultContents,
            );
        }

        return $insertData;
    }
}
