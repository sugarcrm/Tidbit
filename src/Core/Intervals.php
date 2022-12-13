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
    public const TIDBIT_ID_LENGTH = 38;

    /** tidbit ID prefix */
    public const PREFIX = 'seed';

    /** Indicates how many chars from beginning and end of module name */
    public const MODULE_NAME_PART = 5;

    /** @var array cache for Rel Modules IDs */
    public $assembleIdCache = array();

    /** @var Config */
    protected $config;

    /**
     * Intervals constructor.
     * Should be called once, when application is initialising
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

    public function decodeTidbitID($id, $module)
    {
        $module = $this->getAlias($module);
        $this->ensureIdPrefixCache($module);
        $id = substr($id, 1, -1);
        $prefix = $this->assembleIdCache[$module];
        if (substr($id, 0, strlen($prefix)) != $prefix) {
            throw new \Exception("id $id of module $module doesn't start with $prefix");
        }
        $id = substr($id, strlen($prefix));
        return (int) $id;
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
        $relUpID   = $this->getRelatedId($counter, $curModule, $relModule);
        $relModule = $this->getAlias($relModule);
        $relatedId = $this->assembleId($relModule, $relUpID);

        return $relatedId;
    }

    private function ensureIdPrefixCache($module)
    {
        if (empty($this->assembleIdCache[$module])) {
            $this->assembleIdCache[$module] = (($module == 'Users') || ($module == 'Teams'))
                ? static::PREFIX . '-' . $module
                : static::PREFIX . '-' . $module . $GLOBALS['baseTime'];
        }
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
        $this->ensureIdPrefixCache($module);
        $seedId = $this->assembleIdCache[$module] . $id;

        // should return id be quoted or not
        if ($quotes) {
            $seedId = "'" . $seedId . "'";
        }

        return $seedId;
    }

    /**
     * Calculate a 'related' id
     *
     * @param int $counter
     * @param string $curModule
     * @param string $relModule
     * @param int $shift
     *
     * @return integer
     */
    public function getRelatedId($counter, $curModule, $relModule, $shift = 0)
    {
        $modules = $this->config->get('modules');
        $n = floor($counter * $modules[$relModule] / $modules[$curModule]);
        $result = ($n + $shift) % $modules[$relModule];

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
}
