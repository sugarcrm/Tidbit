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
     * @param bool $quoted
     * @param string $expected
     * @param array $cache
     */
    public function testAssembleId($module, $id, $quoted, $expected, $cache = array())
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
     * @see testAssembleId
     * @return array
     */
    public function dataTestAssembleIdProvider()
    {
        return array(
            array( // generic module case
                'Contacts',
                10,
                false,
                'seed-Contacts100010'
            ),
            array( // Users cases
                'Users',
                10,
                false,
                'seed-Users10'
            ),
            array( // Teams cases
                'Teams',
                100,
                false,
                'seed-Teams100'
            ),
            array( // Cached case
                'Leads',
                100,
                false,
                'seed-LeadsBLABLA100',
                array('Leads' => 'seed-LeadsBLABLA')
            ),
            array( // Cached case
                'Leads',
                100,
                true,
                'seed-LeadsBLABLA100',
                array('Leads' => 'seed-LeadsBLABLA')
            ),
        );
    }

    /**
     * @see testGetRelatedIdRelAndBaseAreTheSame
     * @return array
     */
    public function dataTestGetRelatedIdProvider()
    {
        return array(
            array( // Initial values
                0,
                array('ContactsAccounts' => 0),
                'Contacts',
                'Accounts',
                0
            ),
            array( // Less than Contacts/Accounts, 5th Contact should be linked on 1st Account
                4,
                array('ContactsAccounts' => 4),
                'Contacts',
                'Accounts',
                0
            ),
            array( // Already generated 10 records, for each 10 Contacts there should be one Account
                10,
                array('ContactsAccounts' => 10),
                'Contacts',
                'Accounts',
                1
            ),
            array( // 95th contact should be linked to 10th account
                95,
                array('ContactsAccounts' => 95),
                'Contacts',
                'Accounts',
                9
            ),
        );
    }

    /**
     * @see testGenerateRelatedTidbitID
     */
    public function dataTestGenerateRelatedTidbitIDProvider()
    {
        return array(
            array(
                0,
                'Contacts',
                'Accounts',
                'seed-Accounts10000'
            ),
            array(
                105, // (100 / 400) * 105
                'Contacts',
                'Accounts',
                'seed-Accounts100026'
            ),
            array(
                105, // (20 / 400) * 105
                'Contacts',
                'Users',
                'seed-Users5'
            ),
            array(
                105, // (40 / 400) * 105
                'Contacts',
                'Teams',
                'seed-Teams10'
            ),
            array(
                90, // (1000 / 100) * 90
                'Accounts',
                'LongNameModule',
                'seed-LongNodule1000900'
            ),
        );
    }

    /**
     * @covers Sugarcrm\Tidbit\Core\Intervals::generateTidbitID
     * @dataProvider dataTestGenerateTidbitID
     *
     * @param integer $counter
     * @param string $curModule
     * @param string $expected
     */
    public function testGenerateTidbitID($counter, $curModule, $expected)
    {
        $GLOBALS['baseTime'] = '1467883185';

        $GLOBALS['aliases'] = array(
            'EmailAddresses' => 'Emadd',
            'ProductBundles' => 'Prodb',
            'Opportunities'  => 'Oppty',
        );

        $instance = new Intervals($this->getConfig());
        $actual = $instance->generateTidbitID($counter, $curModule);

        $this->assertIsQuoted($actual);
        $this->assertEquals($expected, $this->removeQuotes($actual));
    }

    /**
     * @see testGenerateTidbitID
     * @return array
     */
    public function dataTestGenerateTidbitID()
    {
        return array(
            array(
                0,
                'Contacts',
                'seed-Contacts14678831850',
            ),
            array(
                400,
                'Contacts',
                'seed-Contacts1467883185400',
            ),
            array(
                20,
                'Users',
                'seed-Users20',
            ),
            array(
                5,
                'Teams',
                'seed-Teams5',
            ),
            array( // Long ID to test MD5 logic for long IDs
                12345678912345,
                'SugarFavorites',
                'seed-Sugarrites825e0544bcbf3bf4b501b',
            ),
        );
    }
}
