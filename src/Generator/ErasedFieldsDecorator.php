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

class ErasedFieldsDecorator extends Decorator
{
    protected $piiFields = [];

    protected $piiFieldsEncoded;

    protected $config = [];

    protected $tableNameEncoded;

    public function __construct(Generator $g)
    {
        parent::__construct($g);
        foreach ([$g->bean()->getModuleName(), 'default'] as $module) {
            if (isset($GLOBALS['dataTool'][$module]['_erased_fields'])) {
                $this->config = $GLOBALS['dataTool'][$module]['_erased_fields'];
            }
        }

        foreach ($this->bean()->field_defs as $field => $fieldDef) {
            $isDB = empty($fieldDef['source']) || $fieldDef['source'] == 'db';
            if (!empty($fieldDef['pii']) && $isDB) {
                $this->piiFields[] = $field;
            }
        }
        $this->piiFieldsEncoded = "'".json_encode($this->piiFields)."'";
        $this->tableNameEncoded = "'".$this->bean()->getTableName()."'";
    }

    public function isUsefull()
    {
        return $this->config['probability'] > 0 && count($this->piiFields) > 0;
    }

    public function clean()
    {
        parent::clean();
        $tableName = $this->bean()->getTableName();
        $GLOBALS['db']->query("DELETE FROM erased_fields WHERE table_name = '$tableName' AND bean_id LIKE 'seed-%'");
    }

    public function generateRecord($n)
    {
        $data = parent::generateRecord($n);
        if (mt_rand(0, 99) < $this->config['probability']) {
            foreach ($this->piiFields as $field) {
                $data['data'][$this->bean()->getTableName()][0][$field] = 'NULL';
            }

            $data['data']['erased_fields'][] = [
                'bean_id' => "'".$data['id']."'",
                'table_name' => $this->tableNameEncoded,
                'data' => $this->piiFieldsEncoded,
            ];
        }
        return $data;
    }
}
