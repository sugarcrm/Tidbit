<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\DataTool;

/**
 * Class SameTest
 * @package Sugarcrm\Tidbit\Tests\DataTool\Types
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class SameTest extends TidbitTestCase
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
        unset($GLOBALS['dataTool']);
    }

    /**
     * Trick: set local ref instead of remove ref by specifying the same module name
     * Sum all remote field values and return result
     *
     * @covers ::handleType
     */
    public function testSumRefType()
    {
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $GLOBALS['dataTool']['Contacts']['field2'] = [];
        $GLOBALS['dataTool']['Contacts']['field3'] = [];
        $GLOBALS['dataTool']['Contacts']['field4'] = [
            'sum_ref' => [
                [
                    'module' => 'Contacts',
                    'field'  => 'field1',
                ],
                [
                    'module' => 'Contacts',
                    'field'  => 'field2',
                ],
                [
                    'module' => 'Contacts',
                    'field'  => 'field3',
                ],
            ]
        ];
        $this->dataTool->module = 'Contacts';

        $this->dataTool->setFields([
            'field1' => ['type' => 'int'],
            'field2' => ['type' => 'int'],
            'field3' => ['type' => 'int'],
            'field4' => ['type' => 'int'],
        ]);

        $this->dataTool->installData = [
            'field1' => 10,
            'field2' => 20,
            'field3' => 33,
        ];

        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field4'];

        $this->assertEquals(63, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same' => 'field1'];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $expected = 'some_test_value';
        $this->dataTool->installData['field1'] = $expected;

        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameNotStringType()
    {
        $type = array('same' => 20);

        $expected = 20;

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameValueTrimType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same' => 'field1'];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $this->dataTool->installData['field1'] = '   some_test_value   ';

        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];
        $this->assertEquals('some_test_value', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameToUpperType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same' => 'field1', 'toUpper' => true];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $this->dataTool->installData['field1'] = 'some_test_value';

        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];
        $this->assertEquals('SOME_TEST_VALUE', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameToLowerType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same' => 'field1', 'toLower' => true];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $this->dataTool->installData['field1'] = 'some_TesT_value';

        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];
        $this->assertEquals('some_test_value', $actual);
    }

    /**
     * Value should be quoted
     *
     * @covers ::handleType
     */
    public function testSameHashType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same_hash' => 'field1'];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $expected = 'field1 value';
        $this->dataTool->installData['field1'] = "'" . $expected . "'";
        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];

        $this->assertIsQuoted($actual);
        $this->assertEquals("'" . md5($expected) . "'", $actual);
    }

    /**
     * Value should be quoted
     *
     * @covers ::handleType
     */
    public function testSameHashIntegerType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same_hash' => 'field1'];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $expected = 20;
        $this->dataTool->installData['field1'] = $expected;
        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];

        $this->assertIsQuoted($actual);
        $this->assertEquals("'" . md5($expected) . "'", $actual);
    }

    /**
     * Value should be quoted
     *
     * @covers ::handleType
     */
    public function testSameHashIntegerValueType()
    {
        $type = array('same_hash' => 20);

        $expected = 20;
        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("'" . md5($expected) . "'", $actual);
    }

    /**
     * Covers only md5 hash case.
     * We need to refactor Tidbit code to use Hashing interface instead
     * of calling sugar class directly
     *
     * @covers ::handleType
     */
    public function testSameSugarHashType()
    {
        $GLOBALS['dataTool']['Contacts']['field2'] = ['same_sugar_hash' => 'field1'];
        $GLOBALS['dataTool']['Contacts']['field1'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'field2' => ['type' => 'varchar'],
            'field1' => ['type' => 'varchar'],
        ]);

        $GLOBALS['sugar_config'] = array('sugar_version' => '7.6.2');

        $expected = 'not_hashed_value';

        // Expected that value in installData will be quoted
        $this->dataTool->installData['field1'] = "'" . $expected . "'";

        $this->dataTool->generateData();
        $actual = $this->dataTool->installData['field2'];

        $this->assertIsQuoted($actual);
        $this->assertEquals(md5($expected), $this->removeQuotes($actual));
    }
}
