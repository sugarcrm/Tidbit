<?php

namespace Sugarcrm\Tidbit\Tests\Core;

use Sugarcrm\Tidbit\Core\Config;
use Sugarcrm\Tidbit\Core\Intervals;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

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
     * @covers       Sugarcrm\Tidbit\Core\Intervals::getAlias
     * @dataProvider dataGetAliasProvider
     *
     * @param string $module
     * @param string $expected
     */
    public function testGetAlias($module, $expected)
    {
        $GLOBALS['aliases'] = [
            'EmailAddresses' => 'Emadd',
            'ProductBundles' => 'Prodb',
            'Opportunities' => 'Oppty',
        ];

        $instance = new Intervals($this->getConfig());

        $actual = $instance->getAlias($module);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     * @see testGetAlias
     */
    public function dataGetAliasProvider()
    {
        return [
            [ // Generic module, length is less than 10
                'Contacts',
                'Contacts'
            ],
            [ // Module in aliases list
                'EmailAddresses',
                'Emadd'
            ],
            [ // Long name module, not listed in aliases
                'SugarFavorites',
                'Sugarrites'
            ],
            [ // 10 length name should not be truncated
                'Sugar12345',
                'Sugar12345'
            ],
        ];
    }

    /**
     * @covers       Sugarcrm\Tidbit\Core\Intervals::assembleId
     * @dataProvider dataTestAssembleIdProvider
     *
     * @param string $module
     * @param int $id
     * @param bool $quoted
     * @param string $expected
     * @param array $cache
     */
    public function testAssembleId($module, $id, $quoted, $expected, $cache = [])
    {
        $GLOBALS['baseTime'] = '1000';

        $instance = new Intervals($this->getConfig());
        $instance->assembleIdCache = $cache;

        $actual = $instance->assembleId($module, $id, $quoted);

        if ($quoted) {
            $this->assertIsQuoted($actual);
            $actual = $this->removeQuotes($actual);
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     * @see testAssembleId
     */
    public function dataTestAssembleIdProvider()
    {
        return [
            [ // generic module case
                'Contacts',
                10,
                false,
                'seed-Contacts100010'
            ],
            [ // Users cases
                'Users',
                10,
                false,
                'seed-Users10'
            ],
            [ // Teams cases
                'Teams',
                100,
                false,
                'seed-Teams100'
            ],
            [ // Cached case
                'Leads',
                100,
                false,
                'seed-LeadsBLABLA100',
                ['Leads' => 'seed-LeadsBLABLA']
            ],
            [ // Cached case
                'Leads',
                100,
                true,
                'seed-LeadsBLABLA100',
                ['Leads' => 'seed-LeadsBLABLA']
            ],
        ];
    }

    /**
     * @return array
     * @see testGetRelatedIdRelAndBaseAreTheSame
     */
    public function dataTestGetRelatedIdProvider()
    {
        return [
            [ // Initial values
                0,
                ['ContactsAccounts' => 0],
                'Contacts',
                'Accounts',
                0
            ],
            [ // Less than Contacts/Accounts, 5th Contact should be linked on 1st Account
                4,
                ['ContactsAccounts' => 4],
                'Contacts',
                'Accounts',
                0
            ],
            [ // Already generated 10 records, for each 10 Contacts there should be one Account
                10,
                ['ContactsAccounts' => 10],
                'Contacts',
                'Accounts',
                1
            ],
            [ // 95th contact should be linked to 10th account
                95,
                ['ContactsAccounts' => 95],
                'Contacts',
                'Accounts',
                9
            ],
        ];
    }

    /**
     * @see testGenerateRelatedTidbitID
     */
    public function dataTestGenerateRelatedTidbitIDProvider()
    {
        return [
            [
                0,
                'Contacts',
                'Accounts',
                'seed-Accounts10000'
            ],
            [
                105, // (100 / 400) * 105
                'Contacts',
                'Accounts',
                'seed-Accounts100026'
            ],
            [
                105, // (20 / 400) * 105
                'Contacts',
                'Users',
                'seed-Users5'
            ],
            [
                105, // (40 / 400) * 105
                'Contacts',
                'Teams',
                'seed-Teams10'
            ],
            [
                90, // (1000 / 100) * 90
                'Accounts',
                'LongNameModule',
                'seed-LongNodule1000900'
            ],
        ];
    }
}
