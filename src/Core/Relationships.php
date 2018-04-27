<?php

namespace Sugarcrm\Tidbit\Core;

use Sugarcrm\Tidbit\DataTool;

/**
 * Class Relationships
 *
 * Core relationships components, used to generated relationship data for current DataTool bean
 *
 * @package Sugarcrm\Tidbit\Core
 */
class Relationships
{
    const PREFIX = 'seed-r';

    /** @var Config */
    protected $config;

    /**
     * Storage for relationship install data, will be used
     * on install_cli to put this into Buffers
     *
     * @var array
     */
    protected $relatedModules = array();

    /**
     * Relationships constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->coreIntervals = Factory::getComponent('Intervals');
    }

    /**
     * Related modules getter
     *
     * @return array
     */
    public function getRelatedModules()
    {
        return $this->relatedModules;
    }

    /**
     * Clear related modules
     */
    public function clearRelatedModules()
    {
        $this->relatedModules = array();
    }

    /**
     * @param $relTable
     * @return string
     */
    public function generateRelID($module, $count, $relModule, $relIntervalID, $shift, $multiply)
    {
        static $modules;
        if (!$modules) {
            $modules = array_flip(array_keys($this->config->get('modules')));
        }

        $bin = $suffix = \pack(
            "CLCLCC",
            $modules[$module],
            $count,
            $modules[$relModule],
            $relIntervalID,
            $shift,
            $multiply
        );
        $suffix = \bin2hex($suffix);

        return static::PREFIX . $suffix;
    }

    /**
     * Generates and saves queries to create relationships in the Sugar app, based
     * on the contents of the global array $tidbit_relationships.
     *
     * @param string $module
     * @param int $count
     * @param array $installData
     */
    public function generateRelationships($module, $count, $installData)
    {
        global $relQueryCount;

        $baseId = trim($installData['id'], "'");

        $tidbitRelationships = $this->config->get('tidbit_relationships');

        if (empty($tidbitRelationships[$module])) {
            return;
        }

        foreach ($tidbitRelationships[$module] as $relModule => $relationship) {
            // skip typed relationships as they are processed with corresponding decorators
            if (isset($relationship['type'])) {
                continue;
            }

            // TODO: remove this check or replace with something else
            if (!is_dir('modules/' . $relModule)) {
                throw new Exception("Unknown module $relModule");
            }

            $modules = $this->config->get('modules');

            if (empty($modules[$relModule])) {
                continue;
            }

            $thisToRelatedRatio = $this->calculateRatio($module, $relationship, $relModule);

            /* According to $relationship['ratio'],
             * we attach that many of the related object to the current object
             * through $relationship['table']
             */
            for ($j = 0; $j < $thisToRelatedRatio; $j++) {
                $multiply = isset($relationship['repeat']) ? $relationship['repeat'] : 1;

                /* Normally $multiply == 1 */
                while ($multiply--) {
                    $youN = $this->coreIntervals->getRelatedId($count, $module, $relModule, $j);
                    $youAlias = $this->coreIntervals->getAlias($relModule);
                    $youID = $this->coreIntervals->assembleId($youAlias, $youN, false);

                    $dataTool = $this->getDataTool($module, $count);
                    $date = $dataTool->getConvertDatetime();
                    $relID = $this->generateRelID($module, $count, $relModule, $youN, $j, $multiply);
                    $installData = [
                        'id'                  => "'" . $relID . "'",
                        $relationship['self'] => "'" . $baseId . "'",
                        $relationship['you']  => "'" . $youID . "'",
                        'deleted'             => 0,
                        'date_modified'       => $date,
                    ];

                    $relationTable = $relationship['table'];
                    if (!empty($GLOBALS['dataTool'][$relationTable])) {
                        foreach ($GLOBALS['dataTool'][$relationTable] as $field => $typeData) {
                            $installData[$field] = $dataTool->handleType($typeData, '', $field);
                        }
                    }

                    $this->relatedModules[$relationTable][] = $installData;
                }

                $relQueryCount++;
            }
        }
    }

    /**
     * Calculate relationship ratio - how many relationships should be created for same $bean
     *
     *  - Ratio can specified in relation config
     *  - Ratio could be random from "min" to "max" with "random_ratio" key
     *  - Fallback: RelModule count / Current Module
     *
     * @param $module
     * @param $relationship
     * @param $relModule
     * @return float|int
     */
    protected function calculateRatio($module, $relationship, $relModule)
    {
        if (!empty($relationship['ratio'])) {
            $thisToRelatedRatio = $relationship['ratio'];
        } elseif (!empty($relationship['random_ratio'])) {
            $thisToRelatedRatio = mt_rand(
                $relationship['random_ratio']['min'],
                $relationship['random_ratio']['max']
            );
        } else {
            $modules = $this->config->get('modules');
            $thisToRelatedRatio = $modules[$relModule] / $modules[$module];
        }

        return $thisToRelatedRatio;
    }

    /**
     * Get DataTool instance for current $module and $count
     *
     * @param string $module
     * @param int $count
     * @return DataTool
     */
    protected function getDataTool($module, $count)
    {
        $storageType = $this->config->get('storageType');
        $dTool = new DataTool($storageType);
        $dTool->module = $module;
        $dTool->count = $count;

        return $dTool;
    }

    /**
     * Setter for coreIntervals
     *
     * @param Intervals $intervals
     */
    public function setCoreIntervals(Intervals $intervals)
    {
        $this->coreIntervals = $intervals;
    }
}
