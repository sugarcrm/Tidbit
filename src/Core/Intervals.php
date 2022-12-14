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
    public array $assembleIdCache = [];

    /**
     * Intervals constructor.
     * Should be called once, when application is initialising
     */
    public function __construct(protected Config $config)
    {
    }

    /**
     * Generate Tidbit like ID for record bean
     */
    public function generateTidbitID(int $counter, string $module): string
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

    public function decodeTidbitID(string $id, string $module): int
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
    public function generateRelatedTidbitID(int $counter, string $curModule, string $relModule): string
    {
        $relUpID = $this->getRelatedId($counter, $curModule, $relModule);
        $relModule = $this->getAlias($relModule);
        return $this->assembleId($relModule, $relUpID);
    }

    private function ensureIdPrefixCache(string $module): void
    {
        if (empty($this->assembleIdCache[$module])) {
            $this->assembleIdCache[$module] = (($module == 'Users') || ($module == 'Teams'))
                ? static::PREFIX . '-' . $module
                : static::PREFIX . '-' . $module . $GLOBALS['baseTime'];
        }
    }

    /**
     * Assemble Bean id string by module and related/count IDs
     */
    public function assembleId(string $module, string $id, bool $quotes = true): string
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
     */
    public function getRelatedId(int $counter, string $curModule, string $relModule, int $shift = 0): int
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
     */
    public function getAlias(string $moduleName): string
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
