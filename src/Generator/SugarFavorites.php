<?php

namespace Sugarcrm\Tidbit\Generator;

use Sugarcrm\Tidbit\StorageAdapter\Storage\Common as StorageCommon;
use Sugarcrm\Tidbit\Core\Factory;

/**
 * Class SugarFavorites
 * @package Sugarcrm\Tidbit\Generator
 */
class SugarFavorites extends Common
{
    /** @var string Sugar DB table name */
    protected $table = 'sugarfavorites';

    /** @var string Generator Module Name */
    protected $moduleName = 'SugarFavorites';

    /** @var array Modules to generate */
    protected $generatedModules = array();

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
        global $sugarFavoritesModules, $module_keys;

        $modulesToGenerate = array_intersect(array_keys($sugarFavoritesModules), $module_keys);

        foreach ($modulesToGenerate as $module) {
            $this->generatedModules[$module] = $sugarFavoritesModules[$module];
        }

        parent::__construct($db, $storageAdapter, $insertBatchSize, $recordsNumber);
    }

    /**
     * Generate SugarFavorites records associated with modules defined in config
     */
    public function generate()
    {
        $coreIntervals = Factory::getComponent('Intervals');

        foreach ($this->generatedModules as $module => $records) {
            parent::updateModulesCount($this->moduleName, $records);

            $moduleCounter = 0;
            $dateModified = $this->db->convert("'" . $GLOBALS['timedate']->nowDb() . "'", 'datetime');

            for ($i = 0; $i < $records; $i++) {
                $assignedUser = $coreIntervals->generateRelatedTidbitID($moduleCounter, $this->moduleName, 'Users');

                $insertData = array(
                    'id'               => $coreIntervals->generateTidbitID($this->insertCounter, $this->moduleName),
                    'date_entered'     => $dateModified,
                    'date_modified'    => $dateModified,
                    'name'             => "''",
                    'module'           => "'" . $module .  "'",
                    'record_id'        => $coreIntervals->generateRelatedTidbitID(
                        $moduleCounter,
                        $this->moduleName,
                        $module
                    ),
                    'assigned_user_id' => $assignedUser,
                    'created_by'       => $assignedUser,
                    'modified_user_id' => $assignedUser,
                );

                $moduleCounter++;

                $this->getInsertBuffer($this->table)
                     ->addInstallData($insertData);

                $this->insertCounter++;
            }
        }
    }

    /**
     * Clean up mode, deletes previously generated records by Tidbit and Demo data
     */
    public function clearDB()
    {
        $this->db->query("DELETE FROM {$this->table} WHERE id LIKE 'seed-%'");
    }

    /**
     * Obliterate table
     */
    public function obliterateDB()
    {
        $this->db->query($this->getTruncateTableSQL($this->table));
    }
}
