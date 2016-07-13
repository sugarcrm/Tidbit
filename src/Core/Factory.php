<?php

namespace Sugarcrm\Tidbit\Core;

use Sugarcrm\Tidbit\Exception;

/**
 * Class Factory
 * @package Sugarcrm\Tidbit\Core
 *
 * Available components:
 *  - Intervals
 *  - Relationships
 *  - Config
 */
class Factory
{
    /** @var array */
    protected static $instances = array();

    public static function getComponent($component)
    {
        if (!isset(static::$instances[$component])) {
            $methodName = 'configure' . ucfirst($component);
            if (method_exists('Sugarcrm\Tidbit\Core\Factory', 'configure' . $component)) {
                static::$instances[$component] = Factory::$methodName();
            } else {
                throw new Exception('Component "' . $component . '" is not defined in core classes');
            }
        }

        return static::$instances[$component];
    }

    /**
     * Configure Core Config class
     */
    protected static function configureConfig()
    {
        return new Config();
    }

    /**
     * Configure Core Intervals class
     */
    protected static function configureIntervals()
    {
        /** @var Config $config */
        $config = Factory::getComponent('Config');
        return new Intervals($config);
    }

    /**
     * Configure Core Intervals class
     */
    protected static function configureRelationships()
    {
        /** @var Config $config */
        $config = Factory::getComponent('Config');
        return new Relationships($config);
    }
}
