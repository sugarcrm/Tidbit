<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

/**
 * Class ListSuffixPrefixEnumTest
 * @package Sugarcrm\Tidbit\Tests\Types
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class ListSuffixPrefixEnumTest extends TidbitTestCase
{
    /** @var DataTool */
    protected $dataTool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataTool = new DataTool('mysql');
    }

    /**
     * @covers ::handleType
     */
    public function testListType()
    {
        $GLOBALS['first_name'] = ['test_name'];
        $type = ['list' => 'first_name'];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("'test_name'", $actual);
    }

    /**
     * Will generate empty quoted string, cause "first_name" is not defined in globals
     *
     * @covers ::handleType
     */
    public function testListNotDefinedType()
    {
        $type = ['list_undefined' => 'first_name'];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("''", $actual);
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

        $GLOBALS['fieldData'] = ['options' => 'LBL_SOME_OPTION'];
        $GLOBALS['app_list_strings'] = [
            'LBL_SOME_OPTION' => [
                'OPTION_1' => 'Translation 1',
            ]];

        $actual = $this->dataTool->handleType($type, 'enum', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("'OPTION_1'", $actual);
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

        $GLOBALS['fieldData'] = ['options' => 'LBL_SOME_OPTION'];
        $GLOBALS['app_list_strings'] = [
            'LBL_SOME_OPTION' => [
                'OPTION_1   ' => 'Translation 1',
            ]];

        $actual = $this->dataTool->handleType($type, 'enum', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("'OPTION_1'", $actual);
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

        $options = [
            'OPTION_1' => 'Translation 1',
            'OPTION_2' => 'Translation 1',
            'OPTION_3' => 'Translation 3',
        ];

        $GLOBALS['fieldData'] = ['options' => 'LBL_SOME_OPTION'];
        $GLOBALS['app_list_strings'] = ['LBL_SOME_OPTION' => $options];

        $actual = $this->dataTool->handleType($type, 'enum', '', true);

        $this->assertIsQuoted($actual);
        $this->assertContains($this->removeQuotes($actual), array_keys($options));
    }

    /**
     * @covers ::handleType
     */
    public function testSuffixListType()
    {
        $type = ['suffixlist' => ['suf1', 'suf2']];

        $GLOBALS['suf1'] = ['suf1value'];
        $GLOBALS['suf2'] = ['suf2value'];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals('suf1value suf2value', $this->removeQuotes($actual));
    }

    /**
     * @covers ::handleType
     */
    public function testPrefixListType()
    {
        $type = ['prefixlist' => ['pref1', 'pref2']];

        $GLOBALS['pref1'] = ['pref1value'];
        $GLOBALS['pref2'] = ['pref2value'];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals('pref2value pref1value', $this->removeQuotes($actual));
    }

    /**
     * @covers ::handleType
     */
    public function testSuffixType()
    {
        $type = ['suffix' => '@test', 'list' => 'last_name_array'];

        $GLOBALS['last_name_array'] = ['last_name_value'];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals('last_name_value@test', $this->removeQuotes($actual));
    }

    /**
     * @covers ::handleType
     */
    public function testPrefixType()
    {
        $type = ['prefix' => 'test@', 'list' => 'last_name_array'];

        $GLOBALS['last_name_array'] = ['last_name_value'];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals('test@last_name_value', $this->removeQuotes($actual));
    }

    /**
     * @covers ::handleType
     */
    public function testPrefixPlusMaxLengthType()
    {
        $type = ['prefix' => 'test@', 'list' => 'last_name_array'];

        $GLOBALS['last_name_array'] = ['last_name_value'];
        $GLOBALS['fieldData'] = ['len' => 9];

        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals('test@last', $this->removeQuotes($actual));
    }
}
