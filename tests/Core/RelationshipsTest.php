<?php

namespace Sugarcrm\Tidbit\Tests\Core;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\Core\Relationships;
use Sugarcrm\Tidbit\Core\Config;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class RelationshipsTest
 * @package Sugarcrm\Tidbit\Tests\Core
 * @coversDefaultClass Sugarcrm\Tidbit\Core\Relationships
 */
class RelationshipsTest extends TidbitTestCase
{
    public function getConfig()
    {
        return new Config();
    }

    /**
     * @dataProvider dataTestCalculateRatioProvider
     * @covers ::calculateRatio
     *
     * @param string $module
     * @param array $relationship
     * @param string $relModule
     * @param string $expected
     */
    public function testCalculateRatio($module, $relationship, $relModule, $expected)
    {
        $GLOBALS['modules'] = array(
            'Calls'    => 1000,
            'Contacts' => 400,
            'Accounts' => 100,
        );

        $relationships = new Relationships($this->getConfig());
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\Core\Relationships', 'calculateRatio');

        $actual = $method->invokeArgs($relationships, array($module, $relationship, $relModule));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @see testCalculateRatio
     * @return array
     */
    public function dataTestCalculateRatioProvider()
    {
        return array(
            array( // Based on modules rel
                'Contacts',
                array(),
                'Accounts',
                0.25
            ),
            array( // Based on modules rel
                'Calls',
                array(),
                'Accounts',
                0.1
            ),
            array( // Rel definition contains "ratio"
                'Calls',
                array('ratio' => 5),
                'Accounts',
                5
            ),
            array( // Rel definition contains "random_ratio", for test put min and max the same
                'Calls',
                array('random_ratio' => array('min' => 2, 'max' => 2)),
                'Accounts',
                2
            ),
        );
    }
}
