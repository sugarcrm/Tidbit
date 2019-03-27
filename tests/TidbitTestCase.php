<?php

namespace Sugarcrm\Tidbit\Tests;

class TidbitTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $globals;

    public function setUp()
    {
        parent::setUp();
        $this->globals = $GLOBALS;
    }

    public function tearDown()
    {
        $GLOBALS = $this->globals;
        parent::tearDown();
    }

    /**
     * In order to be able to access non-public method of a $class,
     * we need to use reflection of it. Otherwise, stub class could be used in test
     *
     * @param string $className
     * @param string $method
     * @return \ReflectionMethod
     */
    public static function accessNonPublicMethod($className, $method)
    {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Returns value of non-public property
     *
     * @param $className
     * @param $propertyName
     * @param $classObject
     *
     * @return mixed
     */
    public static function getNonPublicProperty($className, $propertyName, $classObject)
    {
        $class = new \ReflectionClass($className);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($classObject);
    }
}
