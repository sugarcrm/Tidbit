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


class Tidbit_Generator_TBA
{
    /**
     * @var DBManager
     */
    protected $db;

    /**
     * Counter of inserting objects.
     *
     * @var int
     */
    protected $insertCounter = 0;

    /**
     * Constructor.
     *
     * @param DBManager $db
     */
    public function __construct(DBManager $db)
    {
        $this->db = $db;
    }

    /**
     * @return int
     */
    public function getInsertCounter()
    {
        return $this->insertCounter;
    }

    /**
     * Generate TBA Rules
     *
     * @param $roleActions
     * @param $tbaRestrictionLevel
     * @param $tbaFieldAccess
     */
    function generate($roleActions, $tbaRestrictionLevel, $tbaFieldAccess)
    {
        // Cache ACLAction IDs
        $queryACL = "SELECT id, category, name FROM acl_actions where category in ('"
            . implode("','", array_values($roleActions)) . "')";
        $resultACL = $this->db->query($queryACL);

        $actionsIds = array();

        // $actionsIds will contain keys like %category%_%name%
        while ($row = $this->db->fetchByAssoc($resultACL)) {
            $actionsIds[$row['category'] . '_' . $row['name']] = $row['id'];
        }

        $result = $this->db->query("SELECT id FROM acl_roles WHERE id LIKE 'seed-ACLRoles%'");
        $date_modified = $this->db->convert("'" . $GLOBALS['timedate']->nowDb() . "'", 'datetime');

        while ($row = $this->db->fetchByAssoc($result)) {
            foreach ($roleActions as $moduleName) {
                $this->generateACLRoleActions($moduleName, $row['id'], $actionsIds, $date_modified, $tbaRestrictionLevel);
                if ($tbaRestrictionLevel[$_SESSION['tba_level']]['fields']) {
                    $this->generateACLFields($moduleName, $row['id'], $date_modified, $tbaFieldAccess, $tbaRestrictionLevel);
                }
            }
        }
    }

    /**
     * Generate and save queries for 'acl_roles_actions' table
     *
     * @param $moduleName
     * @param $id
     * @param $actionsIds
     * @param $date_modified
     * @param $tbaRestrictionLevel
     */
    private function generateACLRoleActions($moduleName, $id, $actionsIds, $date_modified, $tbaRestrictionLevel) {
        foreach ($tbaRestrictionLevel[$_SESSION['tba_level']]['modules'] as $action => $access_override) {
            $actionId = isset($actionsIds[$moduleName . '_' . $action])
                ? $actionsIds[$moduleName . '_' . $action]
                : null;
            if (!empty($actionId)) {
                $relationship_data = array(
                    'role_id' => $id,
                    'action_id' => $actionId,
                    'access_override' => $access_override
                );
                $query = "INSERT INTO acl_roles_actions (id, " . implode(',', array_keys($relationship_data))
                    . ", date_modified) VALUES ('" . create_guid() . "', " . "'"
                    . implode("', '", $relationship_data) . "', " . $date_modified . ")";
                loggedQuery($query);
            }
        }
    }

    /**
     * Generate and save queries for 'acl_fields' table
     *
     * @param $moduleName
     * @param $id
     * @param $date_modified
     * @param $tbaFieldAccess
     * @param $tbaRestrictionLevel
     */
    private function generateACLFields($moduleName, $id, $date_modified, $tbaFieldAccess, $tbaRestrictionLevel) {
        $beanACLFields = BeanFactory::getBean('ACLFields');
        $roleFields = $beanACLFields->getFields($moduleName, '', $id);
        foreach ($roleFields as $fieldName => $fieldValues) {
            $date = trim($date_modified, "'");
            $insert_data = array(
                'id' => md5($moduleName . $id . $fieldName),
                'date_entered' => $date,
                'date_modified' => $date,
                'name' => $fieldName,
                'category' => $moduleName,
                'aclaccess' => $tbaFieldAccess,
                'role_id' => $id
            );
            $query = "INSERT INTO acl_fields (" . implode(',', array_keys($insert_data))
                . ") VALUES (" . "'" . implode("', '", $insert_data) . "')";

            if ($tbaRestrictionLevel[$_SESSION['tba_level']]['fields'] === 'required_only') {
                if ($fieldValues['required']) {
                    loggedQuery($query);
                }
            } else {
                loggedQuery($query);
            }
        }
    }
}
