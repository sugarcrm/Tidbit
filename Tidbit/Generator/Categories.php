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

require_once('Tidbit/Data/Categories.php');
require_once('Tidbit/Tidbit/Generator/Insert/Object.php');
require_once('Tidbit/Tidbit/Generator/Abstract.php');

class Tidbit_Generator_Categories extends Tidbit_Generator_Abstract
{
    const MODEL_NAME = 'Categories';

    /**
     * @var int
     */
    private $nestingLevel = 1;

    /**
     * Generated model count
     *
     * @var int
     */
    private $modelCounter = 0;

    /**
     * @var Tidbit_Generator_Insert_Object
     */
    private $insertObject;

    /**
     * Constructor.
     *
     * @param DBManager $db
     */
    public function __construct(DBManager $db)
    {
        global $kbCategoriesNestingLevel;
        if ($kbCategoriesNestingLevel) {
            $this->nestingLevel = $kbCategoriesNestingLevel;
        }
        parent::__construct($db);
    }

    /**
     * Data generator.
     *
     * @param int $number
     */
    public function generate($number)
    {
        if ($number < $this->nestingLevel) {
            $this->log(
                'Warn: generated number of categories is less than required number of levels, so set levels=categories'
            );
            $this->nestingLevel = $number;
        }

        $categoriesCounter = 1;
        $root = new stdClass();
        $root->lvl = 0;
        $categoryByLevels = array(array($root));
        while ($categoriesCounter < $number) {
            for ($i = 1; $i < $this->nestingLevel; $i++) {
                if ($categoriesCounter >= $number) {
                    continue;
                }
                $categoriesCounter++;
                $category = new stdClass();
                $category->lvl = $i;
                $categoryByLevels[$i][] = $category;
                $parent = $categoryByLevels[$i - 1][0];
                $parent->children[] = $category;
            }
        }

        $commonCounter = 0;
        $this->countTree($categoryByLevels[0], $commonCounter);

        $rootId = $this->createInsertRecord($root);
        for ($i = 1; $i < $this->nestingLevel; $i++) {
            foreach ($categoryByLevels[$i] as $category) {
                $this->createInsertRecord($category, $rootId);
            }
        }

        processQueries($this->insertObject->getHead(), $this->insertObject->getValues());
        $this->insertCounter += count($this->insertObject->getValues());
        $this->setCategoryRootInConfig($rootId);
    }

    /**
     * Remove generated data from DB.
     */
    public function clearDB()
    {
        $this->db->query("DELETE FROM categories WHERE id LIKE 'seed-%'");
    }

    /**
     * Remove all data from the tables of DB affected by generator.
     */
    public function obliterateDB()
    {
        $this->db->truncateTableSQL('categories');
    }

    /**
     * Count tree indexes.
     *
     * @param array $categoryByLevels
     * @param int $commonCounter
     */
    private function countTree(array $categoryByLevels, &$commonCounter)
    {
        foreach ($categoryByLevels as $category) {
            $category->lft = ++$commonCounter;
            if (!empty($category->children)) {
                $this->countTree($category->children, $commonCounter);
            }
            $category->rft = ++$commonCounter;
        }
    }

    /**
     * Add insert record and return id of it.
     *
     * @param stdClass $category
     * @param string $rootId
     */
    private function createInsertRecord(stdClass $category, $rootId = '') {
        $dataTool = $this->getDataToolForModel(static::MODEL_NAME, $this->modelCounter++);
        $dataTool->installData['root'] = $rootId ? $rootId : $dataTool->installData['id'];
        $dataTool->installData['lft'] = $category->lft;
        $dataTool->installData['rgt'] = $category->rft;
        $dataTool->installData['lvl'] = $category->lvl;

        if (!$this->insertObject) {
            $this->insertObject = new Tidbit_Generator_Insert_Object(
                $dataTool->createInsertHead($dataTool->table_name),
                array($dataTool->createInsertBody())
            );
        } else {
            $this->insertObject->addValues($dataTool->createInsertBody());
        }

        return $dataTool->installData['id'];
    }

    /**
     * Set KB categories root in config table
     *
     * @param $rootId
     */
    private function setCategoryRootInConfig($rootId)
    {
        $rootId = $this->db->quoted($rootId);
        $this->db->query("UPDATE config SET value={$rootId} WHERE category='KBContents' AND name='category_root'");
    }
}
