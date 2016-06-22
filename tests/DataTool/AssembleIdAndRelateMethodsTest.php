<?php

namespace Sugarcrm\Tidbit\Tests\DataTool;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\DataTool;

/**
 * Class AssembleIdAndRelateMethodsTest
 * @package Sugarcrm\Tidbit\Tests\DataTool
 * @coversDefaultClass Sugarcrm\Tidbit\DataTool
 */
class AssembleIdAndRelateMethodsTest extends TidbitTestCase
{
    /** @var DataTool */
    protected $dataTool;

    public function setUp()
    {
        parent::setUp();
        $this->dataTool = new DataTool('mysql');
    }
    
    /**
     * @covers ::assembleId
     * @dataProvider dataTestAssembleId
     *
     * @param string $module
     * @param string $id
     * @param bool $quotes
     * @param bool $reset
     * @param string $expected
     */
    public function testAssembleId($module, $id, $quotes, $reset, $expected)
    {
        $time = time();
        $GLOBALS['baseTime'] = $time;

        $actual = $this->dataTool->assembleId($module, $id, $quotes, $reset);
        $expected = str_replace('{time}', $time, $expected);

        if ($quotes) {
            $this->assertIsQuoted($actual);
            $actual = $this->removeQuotes($actual);
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @see testAssembleId
     * @return array
     */
    public function dataTestAssembleId()
    {
        return array(
            array(
                'Contacts',
                10,
                true,
                true,
                'seed-Contacts{time}10'
            ),
            array(
                'Contacts',
                10,
                false,
                true,
                'seed-Contacts{time}10'
            ),
            array( // Users and Teams have special rules
                'Users',
                255,
                false,
                true,
                'seed-Users255'
            ),
            array( // Users and Teams have special rules
                'Teams',
                100,
                false,
                true,
                'seed-Teams100'
            ),
        );
    }
}
