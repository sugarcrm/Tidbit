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

use Sugarcrm\Tidbit\DataTool;

class ModuleGenerator implements Generator
{
    /**
     * Sugar Bean
     *
     * @var \SugarBean
     */
    protected $bean;

    /**
     * DataTool
     *
     * @var DataTool
     */
    protected $dTool;

    protected $activityGenerator;

    public function __construct(\SugarBean $bean, Activity $activityGenerator)
    {
        $this->bean = $bean;
        $dTool = new DataTool($GLOBALS['storageType']);
        $dTool->table_name = $bean->getTableName();
        $dTool->module = $bean->getModuleName();
        $dTool->setFields($bean->field_defs);
        $this->dTool = $dTool;
        $this->activityGenerator = $activityGenerator;
    }

    public function obliterate()
    {
        $bean = $this->bean;
        $module = $bean->getModuleName();

        $query = "DELETE FROM `{$bean->getTableName()}` WHERE {$this->getDeleteWhereCondition()}";
        $GLOBALS['db']->query($query, true);
        if ($bean->hasCustomFields()) {
            $query = "DELETE FROM `{$bean->get_custom_table_name()}` WHERE {$this->getDeleteWhereConditionCstm()}";
            $GLOBALS['db']->query($query, true);
        }

        if (empty($GLOBALS['tidbit_relationships'][$module])) {
            return;
        }

        foreach ($GLOBALS['tidbit_relationships'][$module] as $rel) {
            if (!empty($GLOBALS['obliterated'][$rel['table']])) {
                continue;
            }
            $GLOBALS['obliterated'][$rel['table']] = true;
            $GLOBALS['db']->query("DELETE FROM `{$rel['table']}` WHERE 1 = 1", true);
        }
    }

    public function clean()
    {
        $bean = $this->bean;
        $module = $bean->getModuleName();
        $idColumnName = 'id';

        // TODO: EmailText generation has to be implemented as 1-1 relationship to Emails module
        if ($module == 'EmailText') {
            $idColumnName = 'email_id';
        }

        $query = "DELETE FROM `{$bean->getTableName()}` "
            . "WHERE {$this->getDeleteWhereCondition()} AND `$idColumnName` LIKE 'seed-%'";
        $GLOBALS['db']->query($query, true);

        if ($bean->hasCustomFields()) {
            $query = "DELETE FROM `{$bean->get_custom_table_name()}` "
                . "WHERE {$this->getDeleteWhereConditionCstm()} AND `{$idColumnName}_c` LIKE 'seed-%'";
            $GLOBALS['db']->query($query, true);
        }

        if (empty($GLOBALS['tidbit_relationships'][$module])) {
            return;
        }

        foreach ($GLOBALS['tidbit_relationships'][$module] as $rel) {
            if (!empty($GLOBALS['obliterated'][$rel['table']])) {
                continue;
            }
            $GLOBALS['obliterated'][$rel['table']] = true;
            $GLOBALS['db']->query("DELETE FROM `{$rel['table']}` WHERE `id` LIKE 'seed-%'", true);
        }
    }

    protected function getDeleteWhereCondition()
    {
        return '1 = 1';
    }

    protected function getDeleteWhereConditionCstm()
    {
        return '1 = 1';
    }

    public function generateRecord($n)
    {
        $dTool = $this->dTool;
        $dTool->clean();
        $dTool->count = $n;
        $beanId = $dTool->generateId($this->bean->hasCustomFields());
        $dTool->generateData();

        $result = [
            'id' => $beanId,
            'data' => [
                $this->bean->getTableName() => [$dTool->installData],
            ],
        ];
        if ($dTool->installDataCstm) {
            $result['data'][$this->bean->get_custom_table_name()] = [$dTool->installDataCstm];
        }

        if (!$beanId) {
            return $result;
        }

        /** @var \Sugarcrm\Tidbit\Core\Relationships $relationships */
        $relationships = \Sugarcrm\Tidbit\Core\Factory::getComponent('Relationships');
        $relationships->generateRelationships($dTool->module, $dTool->count, $dTool->installData);
        foreach ($relationships->getRelatedModules() as $table => $rows) {
            $result['data'][$table] = $rows;
        }
        $relationships->clearRelatedModules();

        if (!empty($GLOBALS['as_populate']) && (
            empty($GLOBALS['activityStreamOptions']['last_n_records'])
            || $total < $GLOBALS['activityStreamOptions']['last_n_records']
            || $i >= $total - $GLOBALS['activityStreamOptions']['last_n_records']
        )) {
            $this->activityGenerator->createActivityForRecord($dTool, $this->bean);
        }

        return $result;
    }

    public function bean()
    {
        return $this->bean;
    }
}
