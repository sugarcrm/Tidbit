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

namespace Sugarcrm\Tidbit\Generator;

use Sugarcrm\Tidbit\StorageAdapter\Factory;

class TBA extends Common
{
    /**
     * @var array
     */
    private $aclRoleIds = array();

    /**
     * @var array
     */
    private $roleActions = array();

    /**
     * @var array
     */
    private $tbaRestrictionLevel = array();

    /**
     * @var int
     */
    private $tbaFieldAccess;

    /**
     * @param array $aclRoleIds
     */
    public function setAclRoleIds($aclRoleIds)
    {
        $this->aclRoleIds = $aclRoleIds;
    }

    /**
     * @param array $roleActions
     */
    public function setRoleActions($roleActions)
    {
        $this->roleActions = $roleActions;
    }

    /**
     * @param array $tbaRestrictionLevel
     */
    public function setTbaRestrictionLevel($tbaRestrictionLevel)
    {
        $this->tbaRestrictionLevel = $tbaRestrictionLevel;
    }

    /**
     * @param int $tbaFieldAccess
     */
    public function setTbaFieldAccess($tbaFieldAccess)
    {
        $this->tbaFieldAccess = $tbaFieldAccess;
    }

    /**
     * Generate TBA Rules
     *
     * @throws Exception
     */
    public function generate()
    {
        if (!$this->aclRoleIds
            || !$this->roleActions
            || !$this->tbaRestrictionLevel
            || !$this->tbaFieldAccess
        ) {
            throw new Exception(
                "One or more of needed settings isn't set (aclRoleIds, roleActions, tbaRestrictionLevel, tbaFieldAccess"
            );
        }

        $actionsIds = $this->getActionIds();
        $this->loadAclRoleIds();

        $dateModified = $this->db->convert("'" . $GLOBALS['timedate']->nowDb() . "'", 'datetime');

        foreach ($this->aclRoleIds as $roleId) {
            foreach ($this->roleActions as $moduleName) {
                $this->generateACLRoleActions($moduleName, $roleId, $actionsIds, $dateModified);
                if ($this->tbaRestrictionLevel[$GLOBALS['tba_level']]['fields']) {
                    $this->generateACLFields($moduleName, $roleId, $dateModified);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearDB()
    {
        $this->db->query("DELETE FROM acl_roles_actions WHERE role_id LIKE 'seed-%'");
        $this->db->query("DELETE FROM acl_fields WHERE role_id LIKE 'seed-%'");
    }

    /**
     * {@inheritdoc}
     */
    public function obliterateDB()
    {
        $this->db->query("DELETE FROM acl_roles_actions WHERE role_id LIKE 'seed-%'");
        $this->db->query($this->getTruncateTableSQL('acl_fields'));
    }

    /**
     * Generate and save queries for 'acl_roles_actions' table
     *
     * @param $moduleName
     * @param $id
     * @param $actionsIds
     * @param $dateModified
     */
    private function generateACLRoleActions($moduleName, $id, $actionsIds, $dateModified)
    {
        foreach ($this->tbaRestrictionLevel[$GLOBALS['tba_level']]['modules'] as $action => $access_override) {
            if (!isset($actionsIds[$moduleName . '_' . $action])) {
                continue;
            }

            $relationshipData = array(
                'id' => "'" . create_guid() . "'",
                'role_id' => "'" . $id . "'",
                'action_id' => "'" . $actionsIds[$moduleName . '_' . $action] . "'",
                'access_override' => $access_override,
                'date_modified' => $dateModified,
            );

            $this->getInsertBuffer('acl_roles_actions')->addInstallData($relationshipData);
            $this->insertCounter++;
        }
    }

    /**
     * Generate and save queries for 'acl_fields' table
     *
     * @param $moduleName
     * @param $id
     * @param $dateModified
     */
    private function generateACLFields($moduleName, $id, $dateModified)
    {
        $beanACLFields = \BeanFactory::getBean('ACLFields');
        $roleFields = $beanACLFields->getFields($moduleName, '', $id);
        foreach ($roleFields as $fieldName => $fieldValues) {
            if ($this->tbaRestrictionLevel[$GLOBALS['tba_level']]['fields'] === 'required_only'
                && !$fieldValues['required']
            ) {
                continue;
            }

            $insertData = array(
                'id' => "'" . md5($moduleName . $id . $fieldName) . "'",
                'date_entered' => $dateModified,
                'date_modified' => $dateModified,
                'name' => "'" .$fieldName .  "'",
                'category' => "'" . $moduleName .  "'",
                'aclaccess' => $this->tbaFieldAccess,
                'role_id' => "'" . $id .  "'",
            );

            $this->getInsertBuffer('acl_fields')->addInstallData($insertData);
            $this->insertCounter++;
        }
    }

    /**
     * Loads and return action ids
     *
     * @return array
     */
    private function getActionIds()
    {
        // Cache ACLAction IDs
        $queryACL = "SELECT id, category, name FROM acl_actions where category in ('"
            . implode("','", array_values($this->roleActions)) . "')";
        $resultACL = $this->db->query($queryACL);

        $actionsIds = array();

        // $actionsIds will contain keys like %category%_%name%
        while ($row = $this->db->fetchByAssoc($resultACL)) {
            $actionsIds[$row['category'] . '_' . $row['name']] = $row['id'];
        }
        return $actionsIds;
    }

    /**
     * Load AclRole ids from db
     */
    private function loadAclRoleIds()
    {
        // if storage isn't db we use just setted in constructor ids
        if ($this->storageType == Factory::OUTPUT_TYPE_CSV) {
            return;
        }
        $this->aclRoleIds = array();
        $result = $this->db->query("SELECT id FROM acl_roles WHERE id LIKE 'seed-ACLRoles%'");
        while ($row = $this->db->fetchByAssoc($result)) {
            $this->aclRoleIds[] = $row['id'];
        }
    }
}
