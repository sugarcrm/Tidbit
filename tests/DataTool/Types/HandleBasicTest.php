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
        unset($GLOBALS['dataTool']);
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
        $GLOBALS['dataTool']['Contacts']['total'] = ['sum' => ['subtotal', 'shipping', 'tax']];
        $GLOBALS['dataTool']['Contacts']['subtotal'] = [];
        $GLOBALS['dataTool']['Contacts']['shipping'] = [];
        $GLOBALS['dataTool']['Contacts']['tax'] = [];
        $this->dataTool->module = 'Contacts';

        $this->dataTool->setFields([
            'total' => ['name' => 'total', 'type' => 'currency'],
            'subtotal' => ['name' => 'subtotal', 'type' => 'currency'],
            'shipping' => ['name' => 'shipping', 'type' => 'currency'],
            'tax' => ['name' => 'tax', 'type' => 'currency'],
        ]);

        $this->dataTool->installData = [
            'subtotal' => 10,
            'shipping' => 20,
            'tax'      => 33,
        ];

        $this->dataTool->generateData();
        $this->assertEquals(63, $this->dataTool->installData['total']);
    }

    /**
     * @covers ::handleType
     */
    public function testSumReturnIsNotNumericType()
    {
        $GLOBALS['dataTool']['Contacts']['total'] = ['sum' => ['subtotal', 'shipping', 'tax']];
        $GLOBALS['dataTool']['Contacts']['subtotal'] = [];
        $GLOBALS['dataTool']['Contacts']['shipping'] = [];
        $GLOBALS['dataTool']['Contacts']['tax'] = [];
        $this->dataTool->module = 'Contacts';
        $this->dataTool->setFields([
            'total' => ['name' => 'total', 'type' => 'currency'],
            'subtotal' => ['name' => 'subtotal', 'type' => 'currency'],
            'shipping' => ['name' => 'shipping', 'type' => 'currency'],
            'tax' => ['name' => 'tax', 'type' => 'currency'],
        ]);

        $this->dataTool->installData = array(
            'subtotal' => 10,
            'shipping' => 'some_string',
            'tax'      => 33,
        );

        $this->dataTool->generateData();
        $this->assertEquals(43, $this->dataTool->installData['total']);
    }

    /**
     * @covers ::handleType
     */
    public function testSumFieldsPlusNumericValuesType()
    {
        $GLOBALS['dataTool']['Contacts']['total'] = ['sum' => ['subtotal', 20, 'tax']];
        $GLOBALS['dataTool']['Contacts']['subtotal'] = [];
        $GLOBALS['dataTool']['Contacts']['tax'] = [];
        $this->dataTool->module = 'Contacts';

        $this->dataTool->setFields([
            'total' => ['name' => 'total', 'type' => 'currency'],
            'subtotal' => ['name' => 'subtotal', 'type' => 'currency'],
            'tax' => ['name' => 'tax', 'type' => 'currency'],
        ]);

        $this->dataTool->installData = array(
            'subtotal' => 10,
            'tax'      => 33,
        );

        $this->dataTool->generateData();
        $this->assertEquals(63, $this->dataTool->installData['total']);
    }

    /**
     * @covers ::handleType
     */
    public function testGetModuleType()
    {
        $this->dataTool->module = 'Contacts';
        $type = array('getmodule' => true);

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals("Contacts", $actual);
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
    }

    /**
     * @covers ::handleType
     */
    public function testGibberishLengthLimitType()
    {
        $type = array('gibberish' => 100);
        $GLOBALS['fieldData'] = array('len' => 20);

        $actual = $this->dataTool->handleType($type, '', '', true);

        // 20 is a limit
        $this->assertTrue(strlen($actual) == 20);
    }

    /**
     * @covers ::handleType
     */
    public function testGibberishDecimalLengthType()
    {
        $type = array('gibberish' => 100);
        $GLOBALS['fieldData'] = array('len' => '10,2');

        $actual = $this->dataTool->handleType($type, '', '', true);

        // 10 is a limit
        $this->assertTrue(strlen($actual) == 10);
    }
}
