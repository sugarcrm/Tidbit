<?php

namespace Sugarcrm\Tidbit\Tests\Core;

use Sugarcrm\Tidbit\Core\Config;
use Sugarcrm\Tidbit\Core\Relationships;
use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

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
        $GLOBALS['modules'] = [
            'Calls' => 1000,
            'Contacts' => 400,
            'Accounts' => 100,
        ];

        $relationships = new Relationships($module, new DataTool('storageType'));
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\Core\Relationships', 'calculateRatio');

        $actual = $method->invokeArgs($relationships, [$relationship, $relModule]);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     * @see testCalculateRatio
     */
    public function dataTestCalculateRatioProvider()
    {
        return [
            [ // Based on modules rel
                'Contacts',
                [],
                'Accounts',
                0.25
            ],
            [ // Based on modules rel
                'Calls',
                [],
                'Accounts',
                0.1
            ],
            [ // Rel definition contains "ratio"
                'Calls',
                ['ratio' => 5],
                'Accounts',
                5
            ],
            [ // Rel definition contains "random_ratio", for test put min and max the same
                'Calls',
                ['random_ratio' => ['min' => 2, 'max' => 2]],
                'Accounts',
                2
            ],
        ];
    }
}
