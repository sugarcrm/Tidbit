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

use Sugarcrm\Tidbit\Core\Intervals;
use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\StorageAdapter\Storage\Common as StorageCommon;

class KBContents extends Common
{
    /**
     * @var int
     */
    private $numberOfArticlesWithNotes = 0;

    /**
     * @var int
     */
    private $kbNumberOfArticlesWithRevision = 0;

    /**
     * @var array
     */
    private $affectedTables = array(
        'kbcontents',
        'kbarticles',
        'kbdocuments',
        'kbusefulness',
        'notes',
    );

    /**
     * Common for kb tables fields,
     * (values must be the same for one
     * kb-article)
     *
     * @var array
     */
    private $kbCommonFields = array(
        'name',
        'date_entered',
        'date_modified',
        'modified_user_id',
        'created_by',
        'team_id',
        'team_set_id',
        'description',
        'kbdocument_id',
    );

    /**
     * Constructor.
     *
     * @param \DBManager $db
     * @param StorageCommon $storageAdapter
     * @param int $insertBatchSize
     * @param int $recordsNumber
     */
    public function __construct(\DBManager $db, StorageCommon $storageAdapter, $insertBatchSize, $recordsNumber)
    {
        global $kbNumberOfArticlesWithNotes;
        if ($kbNumberOfArticlesWithNotes) {
            $this->numberOfArticlesWithNotes = $kbNumberOfArticlesWithNotes;
        }

        global $kbNumberOfArticlesWithRevision;
        if ($kbNumberOfArticlesWithRevision) {
            $this->kbNumberOfArticlesWithRevision = $kbNumberOfArticlesWithRevision;
        }

        $this->activityBean = \BeanFactory::getBean('KBContents');
        
        parent::__construct($db, $storageAdapter, $insertBatchSize, $recordsNumber);
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        // spike for current realization of DataTool, because
        // we will generate data for 3 models at once
        parent::updateModulesCount('KBArticles', $this->recordsNumber);
        parent::updateModulesCount('KBDocuments', $this->recordsNumber);

        for ($i = 0; $i < $this->recordsNumber; $i++) {
            $this->createArticleInserts($i);
        }
        $this->flushInsertBuffers();

        global $kbLanguage;
        if (empty($kbLanguage)) {
            throw new Exception("KBContents languages not configured");
        }
        $this->setKBLanguagesInConfig($kbLanguage);
    }

    /**
     * {@inheritdoc}
     */
    public function clearDB()
    {
        foreach ($this->affectedTables as $table) {
            switch ($table) {
                case 'kbusefulness':
                    $this->db->query($this->getTruncateTableSQL('kbusefulness'));
                    break;
                case 'notes':
                    $this->db->query("DELETE FROM notes WHERE id LIKE 'seed-%' AND parent_type = 'KBContents'");
                    break;
                default:
                    $this->db->query("DELETE FROM {$table} WHERE id LIKE 'seed-%'");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function obliterateDB()
    {
        foreach ($this->affectedTables as $table) {
            if ($table == 'notes') {
                $this->db->query("DELETE FROM notes WHERE parent_type = 'KBContents'");
            } else {
                $this->db->query($this->getTruncateTableSQL($table));
            }
        }
    }

    /**
     * Create and save to db bean.
     *
     * @param int $modelCounter
     */
    private function createArticleInserts($modelCounter)
    {
        $documentTool = $this->getDataToolForModel('KBDocuments', $modelCounter);
        $this->addInsertData($documentTool);

        $articleTool = $this->getDataToolForModel('KBArticles', $modelCounter);
        $this->toolCopyFieldValues($documentTool, $articleTool, $this->kbCommonFields);
        $articleTool->installData['kbdocument_id'] = $documentTool->installData['id'];
        $this->addInsertData($articleTool);

        $contentTool = $this->getDataToolForModel('KBContents', $modelCounter);
        $this->toolCopyFieldValues($articleTool, $contentTool, $this->kbCommonFields);
        $contentTool->installData['kbarticle_id'] = $articleTool->installData['id'];
        $this->addInsertData($contentTool);

        if ($this->numberOfArticlesWithNotes) {
            $noteTool = $this->getDataToolForModel('Notes', $modelCounter);
            $noteTool->installData['id'] = str_replace('-Notes', '-NotesKBCont', $noteTool->installData['id']);
            $noteTool->installData['parent_type'] = "'KBContents'";
            $noteTool->installData['parent_id'] = $contentTool->installData['id'];
            $noteTool->installData['team_id'] = $contentTool->installData['team_id'];
            $noteTool->installData['team_set_id'] = $contentTool->installData['team_set_id'];
            $this->addInsertData($noteTool);
            $this->numberOfArticlesWithNotes--;
        }

        if ($this->kbNumberOfArticlesWithRevision) {
            $contentTool->installData['id'] = str_replace(
                '-KBContents',
                '-KBContentsRev',
                $contentTool->installData['id']
            );
            $contentTool->installData['active_date'] = "NULL";
            $contentTool->installData['active_rev'] = "0";
            $contentTool->installData['revision'] = "'" . (intval($contentTool->installData['revision']) + 1) . "'";
            $this->addInsertData($contentTool);
            $this->kbNumberOfArticlesWithRevision--;
        }
    }

    /**
     * Copy list of values from one DataTool object to another.
     *
     * @param DataTool $srcTool
     * @param DataTool $dstTool
     * @param array $fields
     */
    private function toolCopyFieldValues($srcTool, $dstTool, $fields)
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $srcTool->installData)) {
                $dstTool->installData[$field] = $srcTool->installData[$field];
            }
        }
    }

    /**
     * Writes lang config structure in db.
     *
     * @param array $langConfig
     */
    private function setKBLanguagesInConfig(array $langConfig)
    {
        $langArr = array();
        foreach ($langConfig['list'] as $langShort => $langFull) {
            $langArr[] = array(
                $langShort => $langFull,
                'primary' => ($langConfig['primary'] == $langShort),
            );
        }
        $langArr = $this->db->quoted(json_encode($langArr));
        $this->db->query("UPDATE config SET value={$langArr} WHERE category='KBContents' AND name='languages'");
    }
}
