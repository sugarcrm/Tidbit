<?php

namespace Sugarcrm\Tidbit\Core;

/**
 * Class Relations
 *
 * Provides relationships and ID calculation logic
 * for Generators and DataTool classes
 *
 * @package Sugarcrm\Tidbit\Core
 */
class Intervals
{
    /** default quoted ID size */
    const TIDBIT_ID_LENGTH = 38;

    /** tidbit ID prefix */
    const PREFIX = 'seed';

    /** Indicates how many chars from beginning and end of module name */
    const MODULE_NAME_PART = 5;

    /** @var array counters array */
    public $counters = array();

    /** @var array cache for Rel Modules IDs */
    public $assembleIdCache = array();

    /** @var Config */
    protected $config;

    /**
     * Intervals constructor.
     * Should be called once, when application is initialising
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generate Tidbit like ID for record bean
     *
     * @param integer $counter
     * @param string $module
     * @return string
     */
    public function generateTidbitID($counter, $module)
    {
        $currentModule = $this->getAlias($module);
        $id = $this->assembleId($currentModule, $counter);

        // 36 is max ID length + 2 chars for quotes;
        if (strlen($id) > static::TIDBIT_ID_LENGTH) {
            $moduleLength = strlen($currentModule);
            // example seed-Calls146161708310000
            $id = sprintf("'%s-%s%s'", static::PREFIX, $currentModule, substr(md5($id), 0, -($moduleLength + 1)));
        }
        
        return $id;
    }
    
    /**
     * Generate Tidbit like related ID $relModule
     *
     * @param int $counter
     * @param string $curModule
     * @param string $relModule
     * @return string
     */
    public function generateRelatedTidbitID($counter, $curModule, $relModule)
    {
        $relUpID   = $this->getRelatedUpId($counter, $curModule, $relModule);
        $relModule = $this->getAlias($relModule);
        $relatedId = $this->assembleId($relModule, $relUpID);

        return $relatedId;
    }

    /**
     * Assemble Bean id string by module and related/count IDs
     *
     * @param string $module
     * @param int $id
     * @param bool $quotes
     *
     * @return string
     */
    public function assembleId($module, $id, $quotes = true)
    {
        if (empty($this->assembleIdCache[$module])) {
            $this->assembleIdCache[$module] = (($module == 'Users') || ($module == 'Teams'))
                ? static::PREFIX . '-' . $module
                : static::PREFIX . '-' . $module . $GLOBALS['baseTime'];
        }

        $seedId = $this->assembleIdCache[$module] . $id;

        // should return id be quoted or not
        if ($quotes) {
            $seedId = "'" . $seedId . "'";
        }

        return $seedId;
    }

    /**
     * Generate a 'parent' id for use
     * by handleType:'parent'
     *
     * @param int $counter
     * @param string $curModule
     * @param string $relModule
     *
     * @return string
     */
    public function getRelatedUpId($counter, $curModule, $relModule)
    {
        /* The relModule that we point up to should be the base */
        return $this->getRelatedId($counter, $curModule, $relModule, $relModule);
    }

    /**
     * Generate a 'related' id for use
     * by handleType:'related' and 'generateRelationships'
     *
     * @param int $counter
     * @param string $curModule
     * @param string $relModule
     * @param string $baseModule
     *
     * @return integer
     */
    public function getRelatedId($counter, $curModule, $relModule, $baseModule)
    {
        if (empty($this->counters[$curModule . $relModule])) {
            $this->counters[$curModule . $relModule] = 0;
        }

        $c = $this->counters[$curModule . $relModule];
        $result = 0;

        $modules = $this->config->get('modules');

        /*
         * All ratios can be determined simply by looking
         * at the relative counts in $GLOBALS['modules']
         *
         * Such a module must exist.
         */
        
        if (!empty($modules[$relModule]) && array_key_exists($curModule, $modules)) {
            $baseToRelatedRatio = $modules[$relModule] / $modules[$baseModule];
            $baseToThisRatio = $modules[$curModule] / $modules[$baseModule];

            /*
             * floor($this->count/$acctToThisRatio) determines what 'group'
             * this record is a part of.  Multiplying by $acctToRelatedRatio
             * gives us the starting record for the group of the related type.
             */
            $n = floor(floor($counter / $baseToThisRatio) * $baseToRelatedRatio);

            $this->counters[$curModule . $relModule]++;

            /*
             * There are $acctToRelatedRatio of the related types
             * in the group, so we can just pick one of them.
             */
            $result = $n + ($c % ceil($baseToRelatedRatio));
        }

        return $result;
    }

    /**
     * Returns an alias to be used for id generation. Always honor the
     * configured alias if one exists, otherwise for longer module names (over
     * 10 chars), use the first and last 5 characters of the passed-in name
     * (even if they overlap).
     *
     * @param string $moduleName
     * @return string
     */
    public function getAlias($moduleName)
    {
        $aliases = $this->config->get('aliases');

        if (isset($aliases[$moduleName])) {
            return $aliases[$moduleName];
        } elseif (strlen($moduleName) > (static::MODULE_NAME_PART * 2)) {
            return substr($moduleName, 0, static::MODULE_NAME_PART) . substr($moduleName, -(static::MODULE_NAME_PART));
        } else {
            return $moduleName;
        }
    }

    /**
     * Get Random Related Module counter.
     * Returns random value from $relModule generation interval
     * f.e. if you generating 1000 Accounts, relatedId will be returned from 1 to 1000
     *
     * @param $module
     * @return int
     */
    public function getRandomInterval($module)
    {
        $modules = $this->config->get('modules');
        return mt_rand(0, $modules[$module] - 1);
    }
}
