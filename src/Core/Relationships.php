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

    protected $module;
    protected $dataTool;
    protected $currentDateTime;

    /**
     * Relationships constructor.
     */
    public function __construct(string $module, DataTool $dataTool)
    {
        $this->module = $module;
        $this->dataTool = $dataTool;
        $this->coreIntervals = Factory::getComponent('Intervals');
        $this->currentDateTime = "'" . date('Y-m-d H:i:s') . "'";
    }

    /**
     * @param $relTable
     * @return string
     */
    public function generateRelID($count, $relModule, $relIntervalID, $shift, $multiply)
    {
        static $modules;
        if (!$modules) {
            $modules = array_flip(array_keys($GLOBALS['modules']));
        }

        $bin = $suffix = \pack(
            "CLCLCC",
            $modules[$this->module],
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
    public function generate($n, $baseID)
    {
        $tidbitRelationships = $GLOBALS['tidbit_relationships'];
        if (empty($tidbitRelationships[$this->module])) {
            return [];
        }

        $result = [];
        foreach ($tidbitRelationships[$this->module] as $relModule => $relationship) {
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
            $thisToRelatedRatio = $this->calculateRatio($relationship, $relModule);
            for ($j = 0; $j < $thisToRelatedRatio; $j++) {
                $multiply = isset($relationship['repeat']) ? $relationship['repeat'] : 1;

                /* Normally $multiply == 1 */
                while ($multiply--) {
                    $youN = $this->coreIntervals->getRelatedId($n, $this->module, $relModule, $j);
                    $youAlias = $this->coreIntervals->getAlias($relModule);
                    $youID = $this->coreIntervals->assembleId($youAlias, $youN, false);

                    $relID = $this->generateRelID($n, $relModule, $youN, $j, $multiply);
                    $installData = [
                        'id'                  => "'" . $relID . "'",
                        $relationship['self'] => "'" . $baseID . "'",
                        $relationship['you']  => "'" . $youID . "'",
                        'date_modified'       => $this->currentDateTime,
                    ];

                    $relationTable = $relationship['table'];
                    $installData = $this->enrichRow($relationTable, $installData);
                    $result[$relationTable][] = $installData;
                }
            }
        }

        return $result;
    }

    /**
     * Some relationships have additional fields defined in the config
     */
    public function enrichRow(string $table, array $row): array
    {
        if (!empty($GLOBALS['dataTool'][$table])) {
            foreach ($GLOBALS['dataTool'][$table] as $field => $typeData) {
                $row[$field] = $this->dataTool->handleType($typeData, '', $field);
            }
        }
        return $row;
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
    protected function calculateRatio($relationship, $relModule)
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
            $thisToRelatedRatio = $modules[$relModule] / $modules[$this->module];
        }

        return $thisToRelatedRatio;
    }
}
