<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

/**
 * Class HandleTypeTest
 * @package Sugarcrm\Tidbit\DataTool\Tests
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class HandleBasicTest extends TidbitTestCase
{
    /** @var DataTool */
    protected $dataTool;

   protected function setUp(): void
    {
        parent::setUp();
        $this->dataTool = new DataTool('mysql');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($GLOBALS['dataTool']);
    }

    /**
     * @covers ::handleType
     */
    public function testSkippedType()
    {
        $type = ['skip' => true];
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
        $type = ['value' => $value];
        $actual = $this->dataTool->handleType($type, '', '');

        $this->assertEquals($expected, $actual, '"value" flag should return value if it is not empty or "0" value');
    }

    /**
     * @return array
     * @see testValueType
     */
    public function dataTestValueType()
    {
        return [
            [5, 5],
            ["5", "5"],
            [0, 0],
            ["0", "0"],
        ];
    }

    /**
     * @covers ::handleType
     */
    public function testBinaryEnumType()
    {
        $binaryValues = ['First', 'Second'];
        $type = ['binary_enum' => $binaryValues];

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals('First', $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testBinaryEnumMultipleTimesType()
    {
        $binaryValues = ['First', 'Second'];
        $type = ['binary_enum' => $binaryValues];

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
            'tax' => 33,
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

        $this->dataTool->installData = [
            'subtotal' => 10,
            'shipping' => 'some_string',
            'tax' => 33,
        ];

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

        $this->dataTool->installData = [
            'subtotal' => 10,
            'tax' => 33,
        ];

        $this->dataTool->generateData();
        $this->assertEquals(63, $this->dataTool->installData['total']);
    }

    /**
     * @covers ::handleType
     */
    public function testGetModuleType()
    {
        $this->dataTool->module = 'Contacts';
        $type = ['getmodule' => true];

        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals("'Contacts'", $actual);
    }

    /**
     * @covers ::handleType
     */
    public function testGibberishType()
    {
        $type = ['gibberish' => 10];

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
        $type = ['gibberish' => 100];
        $GLOBALS['fieldData'] = ['len' => 20];

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
        $type = ['gibberish' => 100];
        $GLOBALS['fieldData'] = ['len' => '10,2'];

        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertIsQuoted($actual);

        // 10 is a limit + 2 chars for quotes
        $this->assertTrue(strlen($actual) == 10 + 2);
    }
}
