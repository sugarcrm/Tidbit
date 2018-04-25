<?php

namespace Sugarcrm\Tidbit\Tests\Core;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\Core\Relationships;
use Sugarcrm\Tidbit\Core\Config;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class RelationshipsTest
 * @package Sugarcrm\Tidbit\Tests\Core
 * @coversDefaultClass Sugarcrm\Tidbit\Core\Relationships
 */
class RelationshipsTest extends TidbitTestCase
{
    public function getConfig()
    {
        return new Config();
    }

    /**
     * @covers ::generateRelID
     */
    public function testGenerateRelID()
    {
        $GLOBALS['baseTime'] = 100;

        $relationships = new Relationships($this->getConfig());
        $actual = $relationships->generateRelID('some_table');
        $expected = 'seed-rel-1001';

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::generateRelID
     */
    public function testGenerateRelIDCounterExists()
    {
        $GLOBALS['baseTime'] = 100;

        $relationships = new Relationships($this->getConfig());
        $relationships->relationshipCounters['some_table'] = 500;

        $actual = $relationships->generateRelID('some_table');
        $expected = 'seed-rel-100501';

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataTestCalculateRatioProvider
     * @covers ::calculateRatio
     *
     * @param string $module
     * @param array $relationship
     * @param string $relModule
     * @param string $expected
     */
    public function testCalculateRatio($module, $relationship, $relModule, $expected)
    {
        $GLOBALS['modules'] = array(
            'Calls'    => 1000,
            'Contacts' => 400,
            'Accounts' => 100,
        );

        $relationships = new Relationships($this->getConfig());
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\Core\Relationships', 'calculateRatio');

        $actual = $method->invokeArgs($relationships, array($module, $relationship, $relModule));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @see testCalculateRatio
     * @return array
     */
    public function dataTestCalculateRatioProvider()
    {
        return array(
            array( // Based on modules rel
                'Contacts',
                array(),
                'Accounts',
                0.25
            ),
            array( // Based on modules rel
                'Calls',
                array(),
                'Accounts',
                0.1
            ),
            array( // Rel definition contains "ratio"
                'Calls',
                array('ratio' => 5),
                'Accounts',
                5
            ),
            array( // Rel definition contains "random_ratio", for test put min and max the same
                'Calls',
                array('random_ratio' => array('min' => 2, 'max' => 2)),
                'Accounts',
                2
            ),
        );
    }

    /**
     * @covers ::getRelationshipInstallData
     */
    public function testGetRelationshipInstallData()
    {
        $GLOBALS['baseTime'] = 100;

        $module = 'Contacts';
        $count = 0;
        $baseId = 'seed-Contacts-100999';
        $relId = 'seed-Accounts-100499';
        $relationship = array(
            'table' => 'calls_contacts',
            'self'  => 'contact_id',
            'you'   => 'call_id',
        );

        $date = '2016-07-10 10:30:20';

        $relationships = $this->getMockBuilder('\Sugarcrm\Tidbit\Core\Relationships')
            ->disableOriginalConstructor()
            ->setMethods(array('getDataTool'))
            ->getMock();

        $dToolMock = $this->getMockBuilder('\Sugarcrm\Tidbit\DataTool')
            ->disableOriginalConstructor()
            ->setMethods(array('getConvertDatetime'))
            ->getMock();

        $dToolMock->expects($this->once())
            ->method('getConvertDatetime')
            ->willReturn($date);

        $relationships->expects($this->once())
            ->method('getDataTool')
            ->willReturn($dToolMock);

        $expected = array(
            'id'             => "'seed-rel-1001'",
            'contact_id'     => "'" . $baseId . "'",
            'call_id'        => "'" . $relId . "'",
            'deleted'        => 0,
            'date_modified'  => $date,
        );

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\Core\Relationships', 'getRelationshipInstallData');
        $actual = $method->invokeArgs($relationships, array($relationship, $module, $count, $baseId, $relId));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getRelationshipInstallData
     */
    public function testGetRelationshipInstallDataAdditionalRelFields()
    {
        $GLOBALS['baseTime'] = 100;
        $GLOBALS['dataTool'] = array(
            'calls_contacts' => array(
                'field_1' => array('value' => 1),
                'field_2' => array('value' => 2),
                'field_5' => array('value' => 5),
            ),
        );

        $module = 'Contacts';
        $count = 0;
        $baseId = 'seed-Contacts-100999';
        $relId = 'seed-Accounts-100499';
        $relationship = array(
            'table' => 'calls_contacts',
            'self'  => 'contact_id',
            'you'   => 'call_id',
        );

        $date = '2016-07-10 10:30:20';

        $relationships = $this->getMockBuilder('\Sugarcrm\Tidbit\Core\Relationships')
            ->disableOriginalConstructor()
            ->setMethods(array('getDataTool'))
            ->getMock();

        $dToolMock = $this->getMockBuilder('\Sugarcrm\Tidbit\DataTool')
            ->disableOriginalConstructor()
            ->setMethods(array('getConvertDatetime'))
            ->getMock();

        $dToolMock->expects($this->once())
            ->method('getConvertDatetime')
            ->willReturn($date);

        $relationships->expects($this->once())
            ->method('getDataTool')
            ->willReturn($dToolMock);

        $expected = array(
            'id'             => "'seed-rel-1001'",
            'contact_id'     => "'" . $baseId . "'",
            'call_id'        => "'" . $relId . "'",
            'deleted'        => 0,
            'date_modified'  => $date,
            'field_1'        => 1,
            'field_2'        => 2,
            'field_5'        => 5,
        );

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\Core\Relationships', 'getRelationshipInstallData');
        $actual = $method->invokeArgs($relationships, array($relationship, $module, $count, $baseId, $relId));

        $this->assertEquals($expected, $actual);
    }
}
