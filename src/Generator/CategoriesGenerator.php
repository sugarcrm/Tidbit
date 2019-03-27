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

namespace Sugarcrm\Tidbit\Generator;

use \Sugarcrm\Tidbit\Core\Factory;

class CategoriesGenerator extends ModuleGenerator
{
    const CHILDREN = 5;

    private $cache = [];
    private $rootID;

    public function __construct(\SugarBean $bean)
    {
        parent::__construct($bean);
        $idGenerator = Factory::getComponent('intervals');
        $this->rootID = $idGenerator->generateTidbitID(0, $this->bean()->getModuleName());
    }

    public function generateRecord($n)
    {
        $total = $GLOBALS['modules'][$this->bean()->getModuleName()];
        $level = $this->level($n);
        $totalLevel = $this->level($total - 1);
        $deltaLevel = $totalLevel - $level;

        $leftNodes = $this->leftSubtreeNodeCount($n);
        $left = $leftNodes * 2 + ($level + 1);
        $right = $left + ((1 - pow(self::CHILDREN, $deltaLevel + 1)) / (1 - self::CHILDREN)) * 2 - 1;

        $data = parent::generateRecord($n);
        $data['data'][$this->bean()->getTableName()][0]['lft'] = $left;
        $data['data'][$this->bean()->getTableName()][0]['rgt'] = $right;
        $data['data'][$this->bean()->getTableName()][0]['lvl'] = $level;
        $data['data'][$this->bean()->getTableName()][0]['root'] = $this->rootID;
        return $data;
    }

    private function level(int $n): int
    {
        return (int) floor(log(self::CHILDREN * $n - $n + 1) / log(self::CHILDREN));
    }

    private function leftSubtreeNodeCount($n): int
    {
        if (isset($this->cache[$n])) {
            return $this->cache[$n];
        }

        $parent = (int) floor(($n - 1) / self::CHILDREN);
        if ($parent < 0) {
            return 0;
        }

        $firstSibling = $parent * self::CHILDREN + 1;
        $level = $this->level($n);
        $total = $GLOBALS['modules'][$this->bean()->getModuleName()];
        $totalLevel = $this->level($total);
        $deltaLevel = $totalLevel - $level;

        $result = ($n - $firstSibling) * (1 - pow(self::CHILDREN, $deltaLevel)) / (1 - self::CHILDREN);
        $result += $this->leftSubtreeNodeCount($parent);

        $this->cache[$n] = $result;
        return $result;
    }
}
