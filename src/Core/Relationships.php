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
    const PREFIX = 'seed-rel';

    /** @var array relationships counters */
    public $relationshipCounters = array();

    /** @var mixed - temp storage for relation config settings */
    protected $restoreSettings;

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
    public function generateRelID($relTable)
    {
        $this->relationshipCounters[$relTable] = isset($this->relationshipCounters[$relTable]) ?
            $this->relationshipCounters[$relTable] + 1 :
            1
        ;

        return sprintf('%s-%d%d', static::PREFIX, $GLOBALS['baseTime'], $this->relationshipCounters[$relTable]);
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
            // TODO: remove this check or replace with something else
            if (!is_dir('modules/' . $relModule)) {
                continue;
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
                $relIntervalID = (!empty($relationship['random_id']))
                    ? $this->coreIntervals->getRandomInterval($relModule)
                    : $this->getRelatedLinkId($count, $module, $relModule);

                $currentRelModule = $this->coreIntervals->getAlias($relModule);
                $relId = $this->coreIntervals->assembleId($currentRelModule, $relIntervalID, false);

                $multiply = $this->calculateBodyMultiplier($relationship['table']);

                /* Normally $multiply == 1 */
                while ($multiply--) {
                    $data = $this->getRelationshipInstallData($relationship, $module, $count, $baseId, $relId);
                    $this->relatedModules[$relationship['table']][] = $data;
                }

                $GLOBALS['allProcessedRecords']++;

                $this->restoreRelationConfig($relationship['table']);

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
     * Calculate body multiplier, in relation configs, we can set ["repeat"]["factor"] that will
     * indicate how many SAME relations will be generated for $bean
     *
     * @see "quotes_accounts" relation config
     *
     * TODO: remove GLOBALS
     *
     * @param string $relTable
     * @return int
     */
    protected function calculateBodyMultiplier($relTable)
    {
        /* If a repeat factor is specified, then we will process the body multiple times. */
        if (!empty($GLOBALS['dataTool'][$relTable]) &&
            !empty($GLOBALS['dataTool'][$relTable]['repeat'])) {
            $multiply = $GLOBALS['dataTool'][$relTable]['repeat']['factor'];

            // We don't want 'repeat' to get into the DB, but we'll put it back into the globals later.
            $this->restoreSettings = $GLOBALS['dataTool'][$relTable];
            unset($GLOBALS['dataTool'][$relTable]['repeat']);
        } else {
            $multiply = 1;
        }

        return $multiply;
    }

    /**
     * Restore relationships config
     *
     * @param $relTable
     */
    protected function restoreRelationConfig($relTable)
    {
        if ($this->restoreSettings) {
            $GLOBALS['dataTool'][$relTable] = $this->restoreSettings;
            // clean up settings
            $this->restoreSettings = null;
        }
    }


    /**
     * Generate relationship data to insert
     *
     * TODO: remove $GLOBALS
     *
     * @param array $relationship
     * @param string $module
     * @param int $count
     * @param string $baseId
     * @param string $relId
     * @return array
     */
    protected function getRelationshipInstallData(array $relationship, $module, $count, $baseId, $relId)
    {
        $relationTable = $relationship['table'];

        $dataTool = $this->getDataTool($module, $count);

        $date = $dataTool->getConvertDatetime();

        $installData = array(
            'id'                  => "'" . $this->generateRelID($relationTable) . "'",
            $relationship['self'] => "'" . $baseId . "'",
            $relationship['you']  => "'" . $relId . "'",
            'deleted'             => 0,
            'date_modified'       => $date,
        );

        if (!empty($GLOBALS['dataTool'][$relationTable])) {
            foreach ($GLOBALS['dataTool'][$relationTable] as $field => $typeData) {
                $seed = $dataTool->generateSeed($module, $field, $count);
                $installData[$field] = $dataTool->handleType($typeData, '', $field, $seed);
            }
        }

        return $installData;
    }

    /**
     * Get related module id for current module
     *
     * @param int $count
     * @param string $module
     * @param string $relModule
     *
     * @return string
     */
    public function getRelatedLinkId($count, $module, $relModule)
    {
        /* The baseModule needs to be Accounts normally
         * but we need to keep Quotes inclusive
         * and Teams and Users, which are above Accounts,
         * need to have themselves as the base.
         */
        if ($relModule == 'Teams') {
            $baseModule = 'Teams';
        } elseif ($module == 'ACLRoles') {
            $baseModule = 'ACLRoles';
        } elseif ($module == 'Users') {
            $baseModule = 'Users';
        } elseif ($module == 'ProductBundles') {
            $baseModule = 'Quotes';
        } else {
            $baseModule = 'Accounts';
        }

        return $this->coreIntervals->getRelatedId($count, $module, $relModule, $baseModule);
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

    /**
     * Getter for restoreSettings
     *
     * @return mixed
     */
    public function getRestoreSettings()
    {
        return $this->restoreSettings;
    }
}
