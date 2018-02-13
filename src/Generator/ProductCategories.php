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

use Sugarcrm\Tidbit\StorageAdapter\Storage\Common as StorageCommon;

class ProductCategories extends Common
{

    /** @var int nesting level for the categories */
    public $nestingLevel = 10;

    /** @var int number of product templates per level */
    public $templateNumber = 3;

    /**
     * counter for all models created by this generator
     *
     * @var integer
     */
    protected $modelCounter = 0;
    
    /**
     * Data generator.
     *
     */
    public function generate()
    {
        global $productTemplatesPerLevel;
        if ($productTemplatesPerLevel) {
            $this->templateNumber = $productTemplatesPerLevel;
        }
        echo "\n\tInserting " . $this->recordsNumber . ' rows, nested up to ' . $this->nestingLevel . ' deep.';
        echo "\n\tInserting " . $this->templateNumber . ' Product Templates per category, ';
        echo $this->recordsNumber * $this->templateNumber . ' total... ';
        $categoriesCounter = 0;
        while ($categoriesCounter < $this->recordsNumber) {
            $rootId = $this->createInsertRecord('ProductCategories', 'parent_id');
            for ($pt = 0; $pt < $this->templateNumber; $pt++) {
                $this->createInsertRecord('ProductTemplates', 'category_id', $rootId);
            }
            $categoriesCounter++;
            for ($i = 1; $i < $this->nestingLevel && ($categoriesCounter < $this->recordsNumber); $i++) {
                $leafId = $this->createInsertRecord('ProductCategories', 'parent_id', $rootId);
                $categoriesCounter++;
                for ($pt = 0; $pt < $this->templateNumber; $pt++) {
                    $this->createInsertRecord('ProductTemplates', 'category_id', $leafId);
                }
            }
        }
        $this->flushInsertBuffers();
    }

    /**
     * Remove generated data from DB.
     */
    public function clearDB()
    {
        $this->db->query("DELETE FROM product_categories WHERE id LIKE 'seed-%'");
        $this->db->query("DELETE FROM product_templates WHERE id LIKE 'seed-%'");
    }

    /**
     * Remove all data from the tables of DB affected by generator.
     */
    public function obliterateDB()
    {
        $this->db->query($this->getTruncateTableSQL('product_categories'));
        $this->db->query($this->getTruncateTableSQL('product_templates'));
    }


    /**
     * Add insert record and return id of it.
     *
     * @param string $module Module name to create insert record for
     * @param string $parent_column The parent column to use
     * @param string $parent the parent ID
     */
    private function createInsertRecord($module, $parent_column, $parent = null)
    {
        $dataTool = $this->getDataToolForModel($module, $this->modelCounter++);
        if ($parent != null) {
            $dataTool->installData[$parent_column] = $parent;
        } else {
            $dataTool->installData[$parent_column] = 'null';
        }

        $this->getInsertBuffer($dataTool->table_name)->addInstallData($dataTool->installData);
        $this->insertCounter++;

        return $dataTool->installData['id'];
    }
}
