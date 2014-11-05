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
class TidbitActivityEntity
{
    public $moduleId1;
    public $moduleId2;
    public $moduleName2;
    public $moduleBean2;
    public $activityType;
    public $activityData;
    public $changedFields = array();

    protected $fields = array();

    protected static $index;

    public function __construct($fields)
    {
        foreach ($fields as $field => $data) {
            $this->setField($field, null);
        }
        self::$index++;
    }

    public function setField($name, $value)
    {
        $this->fields[$name] = $value;
    }

    public function initialize()
    {
        $this->initializeFields();
    }

    public function getData()
    {
        return $this->fields;
    }

    public function getRelationshipsData()
    {
        $rel1 = array(
            'id' => "actr1-" . self::$index,
            'activity_id' => $this->fields['id'],
            'parent_type' => $this->moduleName1,
            'parent_id' => $this->moduleId1,
            'fields' => json_encode($this->changedFields),
            'date_modified' => $this->fields['date_modified'],
            'deleted' => 0,
        );
        $rel2 = array(
            'id' => "actr2-" . self::$index,
            'activity_id' => $this->fields['id'],
            'parent_type' => $this->moduleName2,
            'parent_id' => $this->moduleId2,
            'fields' => '',
            'date_modified' => $this->fields['date_modified'],
            'deleted' => 0,
        );
        return array($rel1, $rel2);
    }

    protected function initializeFields()
    {
        $this->setField('id', $this->generateId());
        $this->setField('date_entered', $this->generateDate());
        $this->setField('date_modified', $this->generateDate());
        $this->setField('modified_user_id', 1);
        $this->setField('created_by', $this->moduleId1);
        $this->setField('deleted', 0);
        $this->setField('parent_id', $this->moduleId2);
        $this->setField('parent_type', $this->moduleName2);
        $this->setField('activity_type', $this->activityType);
        $this->setField('data', $this->generateActivityData());

        $this->setField('comment_count', 0);
    }

    protected function generateActivityData()
    {
        $activityData = array(
            'object' => array(
                'type' => $this->moduleBean2->object_name,
                'module' => $this->moduleName2,
                'id' => $this->activityData['id'],
            ),
        );

        if (isset($this->activityData['name'])) {
            $activityData['object']['name'] = $this->activityData['name'];
        } else {
            $activityData['object']['name'] = $this->activityData['id'];
        }

        if ($this->activityType == 'update') {
            if(isset($activityData['object']['name'])) {
                $activityData['changes'] = array(
                    'name' => array(
                        'field_name' => 'name',
                        'data_type' => 'string',
                        'before' => $activityData['object']['name'] . '_old_ver',
                        'after' => $activityData['object']['name'],
                    )
                );
                $this->changedFields[] = 'name';
            } elseif (isset($this->activityData['last_name'])) {
                $activityData['changes'] = array(
                    'last_name' => array(
                        'field_name' => 'last_name',
                        'data_type' => 'string',
                        'before' => $this->activityData['last_name'] . '_old_ver',
                        'after' => $this->activityData['last_name'],
                    )
                );
                $this->changedFields[] = 'last_name';
            }
        }

        return json_encode($activityData);
    }


    protected function generateId()
    {
        return "act-" . self::$index;
    }

    protected function generateDate()
    {
        return date('Y-m-d H:i:s');
    }
}
