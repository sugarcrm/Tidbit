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

    /**
     * Relationships constructor.
     */
    public function __construct()
    {
        $this->coreIntervals = Factory::getComponent('Intervals');
    }

    /**
     * @param $relTable
     * @return string
     */
    public function generateRelID($module, $count, $relModule, $relIntervalID, $shift, $multiply)
    {
        static $modules;
        if (!$modules) {
            $modules = array_flip(array_keys($GLOBALS['modules']));
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
     * @param int $n
     * @param array $installData
     */
    public function generate($module, $n, $baseId)
    {
        $tidbitRelationships = $GLOBALS['tidbit_relationships'];
        if (empty($tidbitRelationships[$module])) {
            return [];
        }

        $result = [];
        foreach ($tidbitRelationships[$module] as $relModule => $relationship) {
            // skip typed relationships as they are processed with corresponding decorators
            if (isset($relationship['type'])) {
                continue;
            }

            $modules = $GLOBALS['modules'];
            if (empty($modules[$relModule])) {
                continue;
            }

            /* According to $relationship['ratio'],
             * we attach that many of the related object to the current object
             * through $relationship['table']
             */
            $thisToRelatedRatio = $this->calculateRatio($module, $relationship, $relModule);
            for ($j = 0; $j < $thisToRelatedRatio; $j++) {
                $multiply = isset($relationship['repeat']) ? $relationship['repeat'] : 1;

                /* Normally $multiply == 1 */
                while ($multiply--) {
                    $youN = $this->coreIntervals->getRelatedId($n, $module, $relModule, $j);
                    $youAlias = $this->coreIntervals->getAlias($relModule);
                    $youID = $this->coreIntervals->assembleId($youAlias, $youN, false);

                    $dataTool = $this->getDataTool($module, $n);
                    $date = $dataTool->getConvertDatetime();
                    $relID = $this->generateRelID($module, $n, $relModule, $youN, $j, $multiply);
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

                    $result[$relationTable][] = $installData;
                }
            }
        }

        return $result;
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
            $modules = $GLOBALS['modules'];
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
        $dTool = new DataTool($GLOBALS['storageType']);
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
