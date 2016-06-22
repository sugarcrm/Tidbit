<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2016 SugarCRM Inc.
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

namespace Sugarcrm\Tidbit\Generator\Activity;

use Sugarcrm\Tidbit\DataTool;

class Entity
{
    const USERS_MODULE_NAME = 'Users';

    /**
     * Current DataTool object with record info
     *
     * @var DataTool
     */
    protected $dataToolObject;
    
    /** @var  int */
    protected $userId;

    /** @var  string */
    protected $beanObjectName;
    
    /** @var array  */
    protected $changedFields = array();

    /** @var array  */
    protected $activityFields = array();

    /** @var int  */
    protected static $index = 0;


    /**
     * Entity constructor.
     *
     * @param $userId
     * @param $dataToolObject
     * @param $beanObjectName
     * @param $activityType
     */
    public function __construct($userId, $dataToolObject, $beanObjectName, $activityType)
    {
        $this->userId = $userId;
        $this->dataToolObject = $dataToolObject;
        $this->beanObjectName = $beanObjectName;
        $this->activityType = $activityType;

        self::$index++;
        $this->initializeFields();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->activityFields;
    }

    /**
     * Generate and return relationship data.
     *
     * @return array
     */
    public function getRelationshipsData()
    {
        $rel1 = array(
            'id' => "'actr1-" . self::$index . "'",
            'activity_id' => $this->activityFields['id'],
            'parent_type' => "'" . self::USERS_MODULE_NAME . "'",
            'parent_id' => "'" . $this->userId . "'",
            'fields' => "'" . json_encode($this->changedFields) . "'",
            'date_modified' => $this->activityFields['date_modified'],
            'deleted' => 0,
        );
        $rel2 = array(
            'id' => "'actr2-" . self::$index . "'",
            'activity_id' => $this->activityFields['id'],
            'parent_type' => "'" . $this->dataToolObject->module . "'",
            'parent_id' => $this->dataToolObject->installData['id'],
            'fields' => "''",
            'date_modified' => $this->activityFields['date_modified'],
            'deleted' => 0,
        );
        return array($rel1, $rel2);
    }

    /**
     * Initialize activity fields.
     */
    protected function initializeFields()
    {
        $activityBean = \BeanFactory::getBean('Activities');
        foreach ($activityBean->field_defs as $field => $data) {
            if (empty($data['source'])) {
                $this->activityFields[$field] = null;
            }
        }

        $this->activityFields['id'] = "'" . $this->generateId() . "'";
        $dateTime = $this->dataToolObject->getConvertDatetime();
        $this->activityFields['date_entered'] = $dateTime;
        $this->activityFields['date_modified'] = $dateTime;
        $this->activityFields['modified_user_id'] = 1;
        $this->activityFields['created_by'] = "'" . $this->userId . "'";
        $this->activityFields['deleted'] = 0;
        $this->activityFields['parent_id'] = $this->dataToolObject->installData['id'];
        $this->activityFields['parent_type'] = "'" . $this->dataToolObject->module . "'";
        $this->activityFields['activity_type'] = "'" . $this->activityType . "'";
        $this->activityFields['data'] = "'" . $this->generateActivityData() . "'";
        $this->activityFields['comment_count'] = 0;
        $this->activityFields['last_comment'] = "''";
    }

    /**
     * @return string
     */
    protected function generateActivityData()
    {
        $objectId = substr($this->dataToolObject->installData['id'], 1, -1);
        $activityData = array(
            'object' => array(
                'type' => $this->beanObjectName,
                'module' => $this->dataToolObject->module,
                'id' => $objectId,
            ),
        );

        if (isset($this->dataToolObject->installData['name'])) {
            $activityData['object']['name'] = substr($this->dataToolObject->installData['name'], 1, -1);
        } else {
            $activityData['object']['name'] = $objectId;
        }

        if ($this->activityType == 'update') {
            if (isset($activityData['object']['name'])) {
                $activityData['changes'] = array(
                    'name' => array(
                        'field_name' => 'name',
                        'data_type' => 'string',
                        'before' => $activityData['object']['name'] . '_old_ver',
                        'after' => $activityData['object']['name'],
                    )
                );
                $this->changedFields[] = 'name';
            } elseif (isset($this->dataToolObject->installData['last_name'])) {
                $lastName = substr($this->dataToolObject->installData['last_name'], 1, -1);
                $activityData['changes'] = array(
                    'last_name' => array(
                        'field_name' => 'last_name',
                        'data_type' => 'string',
                        'before' => $lastName . '_old_ver',
                        'after' => $lastName,
                    )
                );
                $this->changedFields[] = 'last_name';
            }
        }

        return json_encode($activityData);
    }

    /**
     * Activity id generator.
     *
     * @return string
     */
    protected function generateId()
    {
        return "act-" . self::$index;
    }
}
