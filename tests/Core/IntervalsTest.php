<?php

namespace Sugarcrm\Tidbit\Tests\Core;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\Core\Intervals;
use Sugarcrm\Tidbit\Core\Config;

/**
 * Class IntervalsTest
 * @package Sugarcrm\Tidbit\Tests\Core
 * @coversDefaultClass \Sugarcrm\Tidbit\Core\Intervals
 */
class IntervalsTest extends TidbitTestCase
{
    /**
     * @return Config
     */
    protected function getConfig()
    {
        return new Config();
    }

    /**
     * @covers Sugarcrm\Tidbit\Core\Intervals::getAlias
     * @dataProvider dataGetAliasProvider
     *
     * @param string $module
     * @param string $expected
     */
    public function testGetAlias($module, $expected)
    {
        $GLOBALS['aliases'] = array(
            'EmailAddresses' => 'Emadd',
            'ProductBundles' => 'Prodb',
            'Opportunities'  => 'Oppty',
        );

        $instance = new Intervals($this->getConfig());

        $actual = $instance->getAlias($module);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @see testGetAlias
     * @return array
     */
    public function dataGetAliasProvider()
    {
        return array(
            array( // Generic module, length is less than 10
                'Contacts',
                'Contacts'
            ),
            array( // Module in aliases list
                'EmailAddresses',
                'Emadd'
            ),
            array( // Long name module, not listed in aliases
                'SugarFavorites',
                'Sugarrites'
            ),
            array( // 10 length name should not be truncated
                'Sugar12345',
                'Sugar12345'
            ),
        );
    }

    /**
     * @covers Sugarcrm\Tidbit\Core\Intervals::assembleId
     * @dataProvider dataTestAssembleIdProvider
     *
     * @param string $module
     * @param int $id
     * @param string $expected
     * @param array $cache
     */
    public function testAssembleId($module, $id, $expected, $cache = array())
    {
        $GLOBALS['baseTime'] = '1000';

        $instance = new Intervals($this->getConfig());
        $instance->assembleIdCache = $cache;

        $actual = $instance->assembleId($module, $id);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @see testAssembleId
     * @return array
     */
    public function dataTestAssembleIdProvider()
    {
        return array(
            array( // generic module case
                'Contacts',
                10,
                'seed-Contacts100010'
            ),
            array( // Users cases
                'Users',
                10,
                'seed-Users10'
            ),
            array( // Teams cases
                'Teams',
                100,
                'seed-Teams100'
            ),
            array( // Cached case
                'Leads',
                100,
                'seed-LeadsBLABLA100',
                array('Leads' => 'seed-LeadsBLABLA')
            ),
            array( // Cached case
                'Leads',
                100,
                'seed-LeadsBLABLA100',
                array('Leads' => 'seed-LeadsBLABLA')
            ),
        );
    }
}
