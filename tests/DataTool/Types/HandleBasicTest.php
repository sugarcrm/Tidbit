<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\DataTool;

/**
 * Class HandleTypeTest
 * @package Sugarcrm\Tidbit\DataTool\Tests
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class HandleBasicTest extends TidbitTestCase
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
    public function testSkippedType()
    {
        $type = array('skip' => true);
        $actual = $this->dataTool->handleType($type, '', '');

        $this->assertEquals('', $actual, '"skip" flag should return empty value');
    }

    /**
     * @covers ::handleType
     * @dataProvider dataTestValueType
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testValueType($value, $expected)
    {
        $type = array('value' => $value);
        $actual = $this->dataTool->handleType($type, '', '');

        $this->assertEquals($expected, $actual, '"value" flag should return value if it is not empty or "0" value');
    }

    /**
     * @see testValueType
     * @return array
     */
    public function dataTestValueType()
    {
        return array(
            array(5, 5),
            array("5", "5"),
            array(0, 0),
            array("0", "0"),
        );
    }

    /**
     * @covers ::handleType
     */
    public function testBinaryEnumType()
    {
        $binaryValues = array('First', 'Second');
        $type = array('binary_enum' => $binaryValues);

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals('First', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testBinaryEnumMultipleTimesType()
    {
        $binaryValues = array('First', 'Second');
        $type = array('binary_enum' => $binaryValues);

        for ($i = 0; $i < 5; $i++) {
            // Reset static variables for first time call only
            $actual = $this->dataTool->handleType($type, '', '', $i == 0);
            $this->assertEquals($binaryValues[$i % 2], $actual);
        }
    }

    /**
     * @covers ::handleType
     */
    public function testSumType()
    {
        $this->dataTool->module = 'Contacts';

        $type = array('sum' => array('subtotal', 'shipping', 'tax'));

        $this->dataTool->fields = array(
            'subtotal' => 'subtotal',
            'shipping' => 'shipping',
            'tax'      => 'tax',
        );

        $this->dataTool->installData = array(
            'subtotal' => 10,
            'shipping' => 20,
            'tax'      => 33,
        );

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals(63, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSumReturnIsNotNumericType()
    {
        $this->dataTool->module = 'Contacts';

        $type = array('sum' => array('subtotal', 'shipping', 'tax'));

        $this->dataTool->fields = array(
            'subtotal' => 'subtotal',
            'shipping' => 'shipping',
            'tax'      => 'tax',
        );

        $this->dataTool->installData = array(
            'subtotal' => 10,
            'shipping' => 'some_string',
            'tax'      => 33,
        );

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals(43, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testSumFieldsPlusNumericValuesType()
    {
        $this->dataTool->module = 'Contacts';

        $type = array('sum' => array('subtotal', 20, 'tax'));

        $this->dataTool->fields = array(
            'subtotal' => 'subtotal',
            'tax'      => 'tax',
        );

        $this->dataTool->installData = array(
            'subtotal' => 10,
            'tax'      => 33,
        );

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals(63, $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testGetModuleType()
    {
        $this->dataTool->module = 'Contacts';
        $type = array('getmodule' => true);

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals("'Contacts'", $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testGibberishType()
    {
        $type = array('gibberish' => 10);

        $actual = $this->dataTool->handleType($type, '', '', true);

        // actual will be text with 10 words separated by space char
        $this->assertCount(10, explode(' ', $actual));
        $this->assertIsQuoted($actual);
    }

    /**
     * @covers ::handleType
     */
    public function testGibberishLengthLimitType()
    {
        $type = array('gibberish' => 100);
        $GLOBALS['fieldData'] = array('len' => 20);

        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertIsQuoted($actual);

        // 20 is a limit + 2 chars for quotes
        $this->assertTrue(strlen($actual) == 20 + 2);
    }

    /**
     * @covers ::handleType
     */
    public function testGibberishDecimalLengthType()
    {
        $type = array('gibberish' => 100);
        $GLOBALS['fieldData'] = array('len' => '10,2');

        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertIsQuoted($actual);

        // 10 is a limit + 2 chars for quotes
        $this->assertTrue(strlen($actual) == 10 + 2);
    }
}
