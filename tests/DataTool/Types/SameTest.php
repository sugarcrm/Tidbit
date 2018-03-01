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

    /**
     * Trick: set local ref instead of remove ref by specifying the same module name
     * Sum all remote field values and return result
     *
     * @covers ::handleType
     */
    public function testSumRefType()
    {
        $this->dataTool->module = 'Contacts';

        $type = array('sum_ref' => array(
            array(
                'module' => 'Contacts',
                'field'  => 'field1',
            ),
            array(
                'module' => 'Contacts',
                'field'  => 'field2',
            ),
            array(
                'module' => 'Contacts',
                'field'  => 'field3',
            )
        ));

        $this->dataTool->setFields([
            'field1' => 'field1',
            'field2' => 'field2',
            'field3' => 'field3'
        ]);

        $this->dataTool->installData = array(
            'field1' => 10,
            'field2' => 20,
            'field3' => 33,
        );

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals(63, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameType()
    {
        $type = array('same' => 'field1');
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expected = 'some_test_value';
        $this->dataTool->installData['field1'] = $expected;

        $actual = $this->dataTool->handleType($type, '', '', true);
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
        $type = array('same' => 'field1');
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expected = '   some_test_value   ';
        $this->dataTool->installData['field1'] = $expected;

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals('some_test_value', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameToUpperType()
    {
        $type = array('same' => 'field1', 'toUpper' => true);
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expected = 'some_test_value';
        $this->dataTool->installData['field1'] = $expected;

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals(strtoupper($expected), $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSameToLowerType()
    {
        $type = array('same' => 'field1', 'toLower' => true);
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expected = 'some_TesT_value';
        $this->dataTool->installData['field1'] = $expected;

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals(strtolower($expected), $actual);
    }

    /**
     * Value should be quoted
     *
     * @covers ::handleType
     */
    public function testSameHashType()
    {
        $type = array('same_hash' => 'field1');
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expected = 'field1 value';
        $this->dataTool->installData['field1'] = "'" . $expected . "'";
        $actual = $this->dataTool->handleType($type, '', '', true);

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
        $type = array('same_hash' => 'field1');
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expected = 20;
        $this->dataTool->installData['field1'] = $expected;
        $actual = $this->dataTool->handleType($type, '', '', true);

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
        $type = array('same_sugar_hash' => 'field1');
        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $GLOBALS['sugar_config'] = array('sugar_version' => '7.6.2');

        $expected = 'not_hashed_value';

        // Expected that value in installData will be quoted
        $this->dataTool->installData['field1'] = "'" . $expected . "'";

        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals(md5($expected), $this->removeQuotes($actual));
    }
}
