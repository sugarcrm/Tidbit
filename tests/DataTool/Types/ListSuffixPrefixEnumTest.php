<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\DataTool;

/**
 * Class ListSuffixPrefixEnum
 * @package Sugarcrm\Tidbit\Tests\Types
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class ListSuffixPrefixEnum extends TidbitTestCase
{
    /** @var DataTool */
    protected $dataTool;

    public function setUp()
    {
        parent::setUp();
        $this->dataTool = new DataTool('mysql');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers ::handleType
     */
    public function testListType()
    {
        $GLOBALS['first_name'] = array('test_name');
        $type = array('list' => 'first_name');

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals("test_name", $actual);
    }

    /**
     * Will generate empty quoted string, cause "first_name" is not defined in globals
     *
     * @covers ::handleType
     */
    public function testListNotDefinedType()
    {
        $type = array('list' => 'first_name');

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals("", $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testEnumOptionsType()
    {
        $type = [
            'enum' => true,
            'enum_key_probabilities' => [
                [0, 'OPTION_1'],
            ],
        ];

        $GLOBALS['fieldData'] = array('options' => 'LBL_SOME_OPTION');
        $GLOBALS['app_list_strings'] = array(
            'LBL_SOME_OPTION' => array(
                'OPTION_1' => 'Translation 1',
            ));

        $actual = $this->dataTool->handleType($type, 'enum', '', true);

        $this->assertEquals("OPTION_1", $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testEnumOptionsShouldBeTrimmedType()
    {
        $type = [
            'enum' => true,
            'enum_key_probabilities' => [
                [0, 'OPTION_1'],
            ],
        ];

        $GLOBALS['fieldData'] = array('options' => 'LBL_SOME_OPTION');
        $GLOBALS['app_list_strings'] = array(
            'LBL_SOME_OPTION' => array(
                'OPTION_1   ' => 'Translation 1',
            ));

        $actual = $this->dataTool->handleType($type, 'enum', '', true);

        $this->assertEquals("OPTION_1", $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testEnumMultiOptionsType()
    {
        $type = [
            'enum' => true,
            'enum_key_probabilities' => [
                [0, 'OPTION_1'],
                [30, 'OPTION_2'],
                [60, 'OPTION_3'],
            ],
        ];

        $options = array(
            'OPTION_1' => 'Translation 1',
            'OPTION_2' => 'Translation 1',
            'OPTION_3' => 'Translation 3',
        );

        $GLOBALS['fieldData'] = array('options' => 'LBL_SOME_OPTION');
        $GLOBALS['app_list_strings'] = array('LBL_SOME_OPTION' => $options);

        $actual = $this->dataTool->handleType($type, 'enum', '', true);
        $this->assertContains($actual, array_keys($options));
    }

    /**
     * @covers ::handleType
     */
    public function testSuffixListType()
    {
        $type = array('suffixlist' => array('suf1', 'suf2'));

        $GLOBALS['suf1'] = array('suf1value');
        $GLOBALS['suf2'] = array('suf2value');

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals(' suf1value suf2value', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testPrefixListType()
    {
        $type = array('prefixlist' => array('pref1', 'pref2'));

        $GLOBALS['pref1'] = array('pref1value');
        $GLOBALS['pref2'] = array('pref2value');

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals('pref2value pref1value ', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSuffixType()
    {
        $type = array('suffix' => '@test', 'list' => 'last_name_array');

        $GLOBALS['last_name_array'] = array('last_name_value');

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals('last_name_value@test', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testPrefixType()
    {
        $type = array('prefix' => 'test@', 'list' => 'last_name_array');

        $GLOBALS['last_name_array'] = array('last_name_value');

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals('test@last_name_value', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testPrefixPlusMaxLengthType()
    {
        $type = array('prefix' => 'test@', 'list' => 'last_name_array');

        $GLOBALS['last_name_array'] = array('last_name_value');
        $GLOBALS['fieldData'] = array('len' => 9);

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertEquals('test@last', $actual);
    }
}
