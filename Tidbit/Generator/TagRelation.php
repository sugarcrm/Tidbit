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

require_once('Tidbit/Tidbit/Generator/Abstract.php');

class Tidbit_Generator_TagRelation extends Tidbit_Generator_Abstract
{
    const MAX_ATTACHED_TAGS = 6;
    const RECORD_ID_LENGTH = 36;

    /**
     * Generated tag list
     *
     * @var array
     */
    private $tagIds = array();

    /**
     * Generator of tag links.
     *
     * @param SugarBean $bean
     */
    public function generateForBean(SugarBean $bean)
    {
        $this->loadTags();
        if (empty($this->tagIds)) {
            $this->log("WARN: no tags for binding to module " . $bean->object_name);
            return;
        }

        $result = $this->db->query("SELECT id FROM {$bean->table_name} WHERE id LIKE 'seed-%'");
        if (!$result) {
            $this->log("WARN: no records for binding tags for module " . $bean->object_name);
            return;
        }

        $this->generateAndExecLinks($bean, $result);
    }

    /**
     * Data generator (stubbed here).
     *
     * @param int $number
     * @throws Tidbit_Generator_Exception
     */
    public function generate($number)
    {
       throw new Tidbit_Generator_Exception("Method generate() stubbed for this generator, use generateForBean()");
    }

    /**
     * Remove generated data from DB.
     */
    public function clearDB()
    {
        $this->db->query("DELETE FROM tag_bean_rel WHERE id LIKE 'seed-%'");
    }

    /**
     * Remove all data from the tables of DB affected by generator.
     */
    public function obliterateDB()
    {
        $this->db->query($this->db->truncateTableSQL('tag_bean_rel'));
    }

    /**
     * Load generated tag ids
     */
    private function loadTags()
    {
        $result = $this->db->query("SELECT id FROM tags WHERE id LIKE 'seed-%'");
        if (!$result) {
            return;
        }
        while($row = $this->db->fetchByAssoc($result)) {
            $this->tagIds[] = $row['id'];
        }
    }

    /**
     * Get some random tag ids to attach
     *
     * @return array
     */
    private function getSomeTagIds()
    {
        if (!$tagCount = $this->getTagsCountForEachRecord()) {
            return array();
        }

        // array_rand with a count of 1 returns just a single key, not an array of keys
        $randomKeys = ($tagCount > 1) ?
            array_rand($this->tagIds, $tagCount) :
            array(array_rand($this->tagIds, $tagCount));
        $tagIds = array();
        foreach ($randomKeys as $key) {
            $tagIds[] = $this->tagIds[$key];
        }

        return $tagIds;
    }

    /**
     * Get random count for tags for each record.
     * @return int
     */
    private function getTagsCountForEachRecord()
    {
        return mt_rand(0, self::MAX_ATTACHED_TAGS);
    }

    /**
     * Relation id generator
     *
     * @param SugarBean $bean
     * @return string
     * @throws Tidbit_Generator_Exception
     */
    private function generateIdForModule(SugarBean $bean)
    {
        $idStr = 'seed-Tag' . ucfirst($bean->object_name);
        $recordSerial = (string)($this->insertCounter + 1);
        $currentLength = strlen($idStr) + strlen($recordSerial);
        $padLength = self::RECORD_ID_LENGTH - $currentLength;
        if ($padLength < 0) {
            throw new Tidbit_Generator_Exception("Error of generation id for tag_bean_rel");
        }
        return str_pad($idStr, self::RECORD_ID_LENGTH - $currentLength, '0', STR_PAD_RIGHT) . $recordSerial;
    }

    /**
     * Generate multi-insert and execute it
     *
     * @param SugarBean $bean
     * @param resource $beanRecords
     * @return string
     * @throws Tidbit_Generator_Exception
     */
    private function generateAndExecLinks(SugarBean $bean, $beanRecords)
    {
        $sqlParts = $this->getInsertQueryParts();
        $sql = $sqlParts['head'];
        $insertQueryArr = array();
        while ($row = $this->db->fetchByAssoc($beanRecords)) {
            if (!$tagIds = $this->getSomeTagIds()) {
                continue;
            }
            foreach ($tagIds as $tagId) {
                $moduleName = $this->db->quote(ucfirst($bean->module_name));
                $insertQueryArr[]= sprintf(
                    $sqlParts['body'],
                    $this->generateIdForModule($bean),
                    $tagId,
                    $row['id'],
                    $moduleName
                );
                $this->insertCounter++;
            }
        }

        if (empty($insertQueryArr)) {
            return '';
        }

        $sql .= implode($sqlParts['delimeter'], $insertQueryArr) . $sqlParts['end'];
        $this->db->query($sql);
    }

    /**
     * Get insert query parts according db type.
     *
     * @return array
     */
    private function getInsertQueryParts()
    {
        return ($this->db->dbType == 'oci8') ?
            array(
                'head' => 'INSERT ALL ',
                'body' => 'INTO tag_bean_rel (id, tag_id, bean_id, bean_module, date_modified, deleted) VALUES '
                    . "('%s', '%s', '%s', '%s', sysdate(), 0)",
                'end' => ' SELECT * FROM dual',
                'delimeter' => ' '
            ) :
            array(
                'head' => 'INSERT INTO tag_bean_rel (id, tag_id, bean_id, bean_module, date_modified, deleted) VALUES ',
                'body' => "('%s', '%s', '%s', '%s', now(), 0)",
                'end' => '',
                'delimeter' => ','
            );
    }
}
