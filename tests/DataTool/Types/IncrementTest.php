<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

/**
 * Class IncrementTest
 * @package Sugarcrm\Tidbit\Tests\DataTool\Types
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class IncrementTest extends TidbitTestCase
{
    /** @var DataTool */
    protected $dataTool;

   protected function setUp(): void
    {
        parent::setUp();
        $this->dataTool = new DataTool('mysql');
    }

    /**
     * Test increment with DataTool $inc is in initial state
     *
     * @covers ::handleType
     */
    public function testIncrementMinOnlyValueType()
    {
        $type = ['increment' => ['min' => 0, 'max' => 0]];
        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertEquals(0, $actual);
    }

    /**
     * Test increment with DataTool min is not zero
     *
     * @covers ::handleType
     */
    public function testIncrementMinNotZeroValueType()
    {
        $type = ['increment' => ['min' => 5, 'max' => 0]];
        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertEquals(5, $actual);
    }

    /**
     * Test increment with DataTool $inc is increasing each time we call "handleType"
     *
     * @covers ::handleType
     */
    public function testIncrementMinOnlyMultipleTimesValueType()
    {
        $type = ['increment' => ['min' => 0, 'max' => 0]];

        for ($i = 0; $i < 5; $i++) {
            // Reset static variables for first time call only
            $actual = $this->dataTool->handleType($type, '', '', ($i == 0));
            $this->assertEquals($i, $actual);
        }
    }

    /**
     * Test increment with DataTool $inc is in initial state
     *
     * @covers ::handleType
     */
    public function testIncrementValueType()
    {
        $type = ['increment' => ['min' => 0, 'max' => 10]];
        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertEquals(0, $actual);
    }

    /**
     * Test increment with DataTool with "min" and "max" and check value do not overflow [min, max] range
     *
     * @covers ::handleType
     */
    public function testIncrementValueMultipleTimesType()
    {
        $type = ['increment' => ['min' => 1, 'max' => 4]];

        for ($i = 0; $i < 10; $i++) {
            // Reset static variables for first time call only
            $actual = $this->dataTool->handleType($type, '', '', $i == 0);
            $this->assertEquals(1 + $i % 3, $actual);
        }
    }

    /**
     * $ninc starts with 1
     *
     * @covers ::handleType
     */
    public function testIncrementNameType()
    {
        $type = ['incname' => 'user'];
        $this->dataTool->count = 1;
        $actual = $this->dataTool->handleType($type, '', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("'user1'", $actual);
    }

    /**
     * $ninc starts with 1
     *
     * @covers ::handleType
     */
    public function testIncrementNameMultipleTimesType()
    {
        $type = ['incname' => 'teams'];

        for ($i = 0; $i < 5; $i++) {
            // Reset static variables for first time call only
            $this->dataTool->count = $i;
            $actual = $this->dataTool->handleType($type, '', '');

            $this->assertIsQuoted($actual);
            $this->assertEquals("'teams" . $i . "'", $actual);
        }
    }

    /**
     * $ninc starts with 1
     *
     * @covers ::handleType
     */
    public function testIncrementNameTrimType()
    {
        $type = ['incname' => '  user'];
        $this->dataTool->count = 1;
        $actual = $this->dataTool->handleType($type, '', '');

        $this->assertIsQuoted($actual);
        $this->assertEquals("'user1'", $actual);
    }

    /**
     * Should be empty for MySQL storage
     *
     * @covers ::handleType
     */
    public function testAutoIncrementType()
    {
        $type = ['autoincrement' => true];
        $actual = $this->dataTool->handleType($type, '', '', true);
        $this->assertEquals('', $actual);
    }

    /**
     * Should generate sequence for OCI dbs
     *
     * @covers ::handleType
     */
    public function testAutoIncrementOCIType()
    {
        $this->dataTool = new DataTool('oracle');
        $this->dataTool->table_name = 'test';

        $type = ['autoincrement' => true];
        $actual = $this->dataTool->handleType($type, '', 'case_num', true);
        $this->assertEquals('TEST_CASE_NUM_SEQ.NEXTVAL', $actual);
    }
}
