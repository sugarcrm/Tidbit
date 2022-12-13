<?php

namespace Sugarcrm\Tidbit\Core;

/**
 * Class Config
 * @package Sugarcrm\Tidbit\Core
 */
class Config
{
    /** @var array */
    protected $config = array();

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
     *
     * @param string $item
     * @return mixed|null
     */
    public function get($item)
    {
        return $this->config[$item] ?? null;
    }

    /**
     * For some generators we need to dynamically set number of
     * Records that will be generated for Module
     *
     * @param string $module
     * @param int $count
     */
    public function setModuleCount($module, $count)
    {
        global $modules;

        $this->config['modules'][$module] = $count;
        $modules[$module] = $count;
    }
}
