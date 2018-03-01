<?php

namespace Sugarcrm\Tidbit\Tests\DataTool\Types;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\DataTool;
use Sugarcrm\Tidbit\Tests\SugarObject\TimeDate;

/**
 * Class RangeTest
 * @package Sugarcrm\Tidbit\Tests\DataTool\Types
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class RangeTest extends TidbitTestCase
{
    /** @var DataTool */
    protected $dataTool;

    public function setUp()
    {
        parent::setUp();
        $this->dataTool = new DataTool('mysql');
    }

    /**
     * @covers ::handleType
     */
    public function testRangeDefault()
    {
        $type = array('range' => array('min' => 5, 'max' => 10));

        $actual = $this->dataTool->handleType($type, 'decimal', '', true);

        $this->assertTrue(is_numeric($actual));
        $this->assertTrue($actual >= 5 && $actual <= 10);
    }

    /**
     * @covers ::handleType
     */
    public function testRangeMultiplier()
    {
        $type = array('range' => array('min' => 5, 'max' => 5), 'multiply' => '3.7');

        $actual = $this->dataTool->handleType($type, 'decimal', '', true);

        $this->assertTrue(is_numeric($actual));
        $this->assertEquals(18.5, $actual);
    }

    /**
     * DataTool should add 5 days to current date and return in Date format
     *
     * @covers ::handleType
     */
    public function testRangeDateType()
    {
        $GLOBALS['timedate'] = TimeDate::getInstance();
        $type = array('range' => array('min' => 5, 'max' => 5), 'type' => 'date');

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $expected = new \DateTime();
        $expected->modify('5 days');

        $this->assertIsQuoted($actual);
        $this->assertEquals($expected->format('Y-m-d'), $this->removeQuotes($actual));
    }

    /**
     * DataTool should add 5 days to current date and return in Datetime format
     *
     * @covers ::handleType
     */
    public function testRangeSameDatetimeType()
    {
        $GLOBALS['timedate'] = TimeDate::getInstance();
        $time = time();
        $type = array('range' => array('min' => 5, 'max' => 5), 'type' => 'datetime', 'basetime' => $time);

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setTimestamp($time);
        $expected->modify('5 days');

        $this->assertIsQuoted($actual);
        $this->assertEquals($expected->format('Y-m-d H:i:s'), $this->removeQuotes($actual));
    }

    /**
     * DataTool should sub-struck 10 days from current date and return in Datetime format
     *
     * @covers ::handleType
     */
    public function testRangeSameDatetimeNegativeDaysType()
    {
        $GLOBALS['timedate'] = TimeDate::getInstance();
        $time = time();
        $type = array('range' => array('min' => -10, 'max' => -10), 'type' => 'datetime', 'basetime' => $time);

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setTimestamp($time);
        $expected->modify('-10 days');

        $this->assertIsQuoted($actual);
        $this->assertEquals($expected->format('Y-m-d H:i:s'), $this->removeQuotes($actual));
    }

    /**
     * Test that same_datetime will return datetime same as in local fields
     *
     * @covers ::handleType
     */
    public function testSameDatetimeType()
    {
        $type = array('same_datetime' => 'field1');

        $this->dataTool->setFields([
            'field1' => 'field1'
        ]);

        $expectedDatetime = "'2016-05-20 10:12:13'";

        $this->dataTool->installData = array(
            'field1' => $expectedDatetime,
        );

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals($expectedDatetime, $actual);
    }

    /**
     * Test that same_datetime will return empty quoted results if field does not exists
     *
     * @covers ::handleType
     */
    public function testSameDatetimeFieldDoNotExistsType()
    {
        $type = array('same_datetime' => 'field1');

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);
        $this->assertEquals("''", $actual);
    }

    /**
     * Test that same_datetime will return datetime modified by 'duration_hours' hours and 30 minutes
     *
     * @covers ::handleType
     */
    public function testSameDatetimeModifyByFieldType()
    {
        $type = array(
            'same_datetime' => 'field1',
            'modify' => array(
                'hours' => array(
                    'field' => 'duration_hours'
                ),
                'minutes' => '30'
            )
        );

        $this->dataTool->setFields([
            'field1'         => 'field1',
            'duration_hours' => 'duration_hours'
        ]);

        $expectedDatetime = "'2016-05-20 10:12:13'";

        $this->dataTool->installData = array(
            'field1'         => $expectedDatetime,
            'duration_hours' => 2,
        );

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);

        // Expecting value will be modified by 2 hours and 30 minutes
        $this->assertEquals("'2016-05-20 12:42:13'", $actual);
    }

    /**
     * Test that same_datetime will return datetime modified by '5' hours and 10 minutes
     *
     * @covers ::handleType
     */
    public function testSameDatetimeModifyByConstantType()
    {
        $type = array(
            'same_datetime' => 'field1',
            'modify' => array(
                'hours' => 5,
                'minutes' => '10'
            )
        );

        $this->dataTool->setFields([
            'field1'         => 'field1'
        ]);

        $expectedDatetime = "'2016-05-20 10:12:13'";

        $this->dataTool->installData = array(
            'field1'         => $expectedDatetime,
        );

        // Set varchar type, so DB->convert won't be called
        $actual = $this->dataTool->handleType($type, 'varchar', '', true);

        $this->assertIsQuoted($actual);

        // Expecting value will be modified by 5 hours and 10 minutes
        $this->assertEquals("'2016-05-20 15:22:13'", $actual);
    }
}
