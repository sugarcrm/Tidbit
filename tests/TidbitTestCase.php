<?php

namespace Sugarcrm\Tidbit\Tests;

use Sugarcrm\Tidbit\PHPUnit\IsQuotedValueConstraint;

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

    /**
     * Custom assertion that value is quoted
     *
     * @param $value
     * @param string $message
     */
    public static function assertIsQuoted($value, $message = '')
    {
        self::assertThat($value, self::isQuotedValue(), $message);
    }

    /**
     * Instance of IsQuotedValueConstraint class
     *
     * @return IsQuotedValueConstraint
     */
    public static function isQuotedValue()
    {
        return new IsQuotedValueConstraint();
    }

    /**
     * Helper function to remove quotes from string
     *
     * @param string $value
     * @return string
     */
    public function removeQuotes($value)
    {
        return trim($value, "'");
    }
}
