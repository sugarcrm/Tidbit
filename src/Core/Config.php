<?php

namespace Sugarcrm\Tidbit\Core;

/**
 * Class Config
 * @package Sugarcrm\Tidbit\Core
 */
class Config
{
    /** @var array */
    protected $config = [];

    public function __construct()
    {
        global $modules, $aliases, $tidbit_relationships, $storageType;

        $this->config['modules'] = $modules;
        $this->config['aliases'] = $aliases;
        $this->config['tidbit_relationships'] = $tidbit_relationships;
        $this->config['storageType'] = $storageType;
    }

    /**
     * Get config item
     */
    public function get(string $item)
    {
        return $this->config[$item] ?? null;
    }

    /**
     * For some generators we need to dynamically set number of
     * Records that will be generated for Module
     */
    public function setModuleCount(string $module, int $count): void
    {
        global $modules;

        $this->config['modules'][$module] = $count;
        $modules[$module] = $count;
    }
}
