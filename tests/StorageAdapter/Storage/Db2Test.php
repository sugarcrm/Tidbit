<?php

namespace Sugarcrm\Tidbit\Tests\StorageAdapter\Storage;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\StorageAdapter\Storage\Db2;

/**
 * Class Db2Test
 * @package Sugarcrm\Tidbit\Tests\StorageAdapter\Storage
 *
 * @coversDefaultClass \Sugarcrm\Tidbit\StorageAdapter\Storage\Db2
 */
class Db2Test extends TidbitTestCase
{
    /** @var mixed */
    protected $storageResource;

    /**
     * @covers ::getSequenceFromValues
     */
    public function testGetSequenceFromValuesWithEmptyData()
    {
        $storage = new Db2($this->storageResource);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'getSequenceFromValues');
        $installData = array();
        $actual = $method->invokeArgs($storage, array($installData));

        $this->assertEmpty($actual);
    }

    /**
     * @covers ::getSequenceFromValues
     */
    public function testGetSequenceFromValues()
    {
        $storage = new Db2($this->storageResource);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'getSequenceFromValues');

        $installData = array(
            'some_field' => 'some_value',
            'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
        );

        $actual = $method->invokeArgs($storage, array($installData));

        $this->assertEquals(array('field' => 'field_name', 'name' => 'CASES_CASE_NUMBER_SEQ'), $actual);
    }

    /**
     * Due to issue in Storage classes, we can process only one sequence per table
     * TODO: Fix DB2 and Oracle classes to support multiple sequences per table
     *
     * @covers ::getSequenceFromValues
     */
    public function testGetSequenceFromValuesIgnoreSecondSequence()
    {
        $storage = new Db2($this->storageResource);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'getSequenceFromValues');

        $installData = array(
            'some_field' => 'some_value',
            'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            'field_name2' => 'CASES_CASE_NUMBER2_SEQ.NEXTVAL',
        );

        $actual = $method->invokeArgs($storage, array($installData));

        $this->assertEquals(array('field' => 'field_name', 'name' => 'CASES_CASE_NUMBER_SEQ'), $actual);
    }

    /**
     * @covers ::getCurrentSequenceValue
     */
    public function testGetCurrentSequenceValue()
    {
        $mock = $this->getMock('\Sugarcrm\Tidbit\Tests\SugarObject\DBManager', array('query', 'fetchByAssoc'));
        $expectedValue = 10;

        $mock->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $mock->expects($this->once())
            ->method('fetchByAssoc')
            ->willReturn(array('current_val' => $expectedValue));

        $storage = new Db2($mock);
        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2',
            'getCurrentSequenceValue'
        );

        $actual = $method->invokeArgs($storage, array('some_sequence_name'));
        $this->assertEquals($expectedValue, $actual);
    }

    /**
     * @covers ::getCurrentSequenceValue
     */
    public function testGetCurrentSequenceValueReloadSequence()
    {
        $mock = $this->getMock('\Sugarcrm\Tidbit\Tests\SugarObject\DBManager', array('query', 'fetchByAssoc'));
        $expectedValue = 1;

        $mock->expects($this->exactly(3))
            ->method('query')
            ->willReturn(true);

        $mock->expects($this->exactly(2))
            ->method('fetchByAssoc')
            ->will($this->onConsecutiveCalls(array('current_val' => -1), array('current_val' => $expectedValue)));

        $storage = new Db2($mock);
        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2',
            'getCurrentSequenceValue'
        );

        $actual = $method->invokeArgs($storage, array('some_sequence_name'));
        $this->assertEquals($expectedValue, $actual);
    }

    /**
     * @covers ::patchSequenceValues
     */
    public function testPatchSequenceValuesShouldReturnEmptyStringIfSequenceIsNotFound()
    {
        $installData = array(
            array(
                'some_field' => 'some_value',
            ),
        );

        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Db2')
            ->disableOriginalConstructor()
            ->setMethods(array('getSequenceFromValues', 'getCurrentSequenceValue', 'setNewSequenceValue'))
            ->getMock();

        $mock->expects($this->once())
            ->method('getSequenceFromValues')
            ->with($installData[0])
            ->willReturn(array());

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'patchSequenceValues');
        $actual = $method->invokeArgs($mock, array(&$installData));

        $this->assertEmpty($actual);
    }

    /**
     * @covers ::patchSequenceValues
     */
    public function testPatchSequenceValues()
    {
        $currentSequenceValue = 5;
        $installData = array(
            array(
                'some_field' => 'some_value',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ),
            array(
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ),
            array(
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ),
        );

        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Db2')
            ->disableOriginalConstructor()
            ->setMethods(array('getSequenceFromValues', 'getCurrentSequenceValue', 'setNewSequenceValue'))
            ->getMock();

        $mock->expects($this->once())
            ->method('getSequenceFromValues')
            ->with($installData[0])
            ->willReturn(array('field' => 'field_name', 'name' => 'CASES_CASE_NUMBER_SEQ'));

        $mock->expects($this->once())
            ->method('getCurrentSequenceValue')
            ->with('CASES_CASE_NUMBER_SEQ')
            ->willReturn($currentSequenceValue);

        $mock->expects($this->once())
            ->method('setNewSequenceValue')
            ->with('CASES_CASE_NUMBER_SEQ', count($installData))
            ->willReturn(true);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'patchSequenceValues');
        $method->invokeArgs($mock, array(&$installData));

        // Assert that values were changed to current sequence value + iteration number
        for ($i = 0; $i < count($installData); $i++) {
            $this->assertEquals($currentSequenceValue + $i + 1, $installData[$i]['field_name']);
        }
    }

    /**
     * @covers ::prepareQuery
     * @dataProvider dataTestPrepareQueryExceptionProvider
     * @expectedException \Sugarcrm\Tidbit\Exception
     *
     * @param string $tableName
     * @param mixed $installData
     */
    public function testPrepareQueryException($tableName, $installData)
    {
        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Db2')
            ->disableOriginalConstructor()
            ->setMethods(array('patchSequenceValues'))
            ->getMock();

        $mock->expects($this->never())
            ->method('patchSequenceValues');

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'prepareQuery');
        $method->invokeArgs($mock, array($tableName, $installData));
    }

    /**
     * @see testPrepareQueryException
     * @return array
     */
    public function dataTestPrepareQueryExceptionProvider()
    {
        return array(
            array(
                '',
                array(),
            ),
            array(
                'some_table',
                array(),
            ),
            array(
                '',
                array('1', '2', '3'),
            )
        );
    }

    /**
     * @covers ::prepareQuery
     */
    public function testPrepareQuery()
    {
        $installData = array(
            array(
                'some_field' => 'some_value',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ),
            array(
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ),
            array(
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ),
        );

        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Db2')
            ->disableOriginalConstructor()
            ->setMethods(array('patchSequenceValues'))
            ->getMock();

        $mock->expects($this->once())
            ->method('patchSequenceValues')
            ->with($installData);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Db2', 'prepareQuery');
        $actual = $method->invokeArgs($mock, array('some_table', $installData));

        // For 3 records prepareQuery should put 2 "UNION ALL" into final SQL
        $this->assertEquals(2, substr_count($actual, 'UNION ALL'));
        $this->assertEquals(3, substr_count($actual, 'VALUES ('));
    }
}
