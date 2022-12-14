<?php

namespace Sugarcrm\Tidbit\Tests\StorageAdapter\Storage;

use Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

/**
 * Class OracleTest
 * @package Sugarcrm\Tidbit\Tests\StorageAdapter\Storage
 *
 * @coversDefaultClass \Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle
 */
class OracleTest extends TidbitTestCase
{
    /** @var mixed */
    protected $storageResource;

    /**
     * @covers ::getSequenceFromValues
     */
    public function testGetSequenceFromValuesWithEmptyData()
    {
        $storage = new Oracle($this->storageResource);

        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle',
            'getSequenceFromValues'
        );

        $installData = [];
        $actual = $method->invokeArgs($storage, [$installData]);

        $this->assertEmpty($actual);
    }

    /**
     * @covers ::getSequenceFromValues
     */
    public function testGetSequenceFromValues()
    {
        $storage = new Oracle($this->storageResource);

        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle',
            'getSequenceFromValues'
        );

        $installData = [
            'some_field' => 'some_value',
            'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
        ];

        $actual = $method->invokeArgs($storage, [$installData]);

        $this->assertEquals(['field' => 'field_name', 'name' => 'CASES_CASE_NUMBER_SEQ'], $actual);
    }

    /**
     * Due to issue in Storage classes, we can process only one sequence per table
     * TODO: Fix DB2 and Oracle classes to support multiple sequences per table
     *
     * @covers ::getSequenceFromValues
     */
    public function testGetSequenceFromValuesIgnoreSecondSequence()
    {
        $storage = new Oracle($this->storageResource);

        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle',
            'getSequenceFromValues'
        );

        $installData = [
            'some_field' => 'some_value',
            'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            'field_name2' => 'CASES_CASE_NUMBER2_SEQ.NEXTVAL',
        ];

        $actual = $method->invokeArgs($storage, [$installData]);

        $this->assertEquals(['field' => 'field_name', 'name' => 'CASES_CASE_NUMBER_SEQ'], $actual);
    }

    /**
     * @covers ::getCurrentSequenceValue
     */
    public function testGetCurrentSequenceValue()
    {
        $mock = $this->getMockBuilder('\Sugarcrm\Tidbit\Tests\SugarObject\DBManager')
            ->disableOriginalConstructor()
            ->onlyMethods(['query', 'fetchByAssoc'])
            ->getMock();
        $expectedValue = 10;

        $mock->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $mock->expects($this->once())
            ->method('fetchByAssoc')
            ->willReturn(['current_val' => $expectedValue]);

        $storage = new Oracle($mock);
        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle',
            'getCurrentSequenceValue'
        );

        $actual = $method->invokeArgs($storage, ['some_sequence_name']);
        $this->assertEquals($expectedValue, $actual);
    }

    /**
     * @covers ::patchSequenceValues
     */
    public function testPatchSequenceValuesShouldReturnEmptyStringIfSequenceIsNotFound()
    {
        $installData = [
            [
                'some_field' => 'some_value',
            ],
        ];

        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle')
            ->disableOriginalConstructor()
            ->onlyMethods(['getSequenceFromValues', 'getCurrentSequenceValue', 'setNewSequenceValue'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getSequenceFromValues')
            ->with($installData[0])
            ->willReturn([]);

        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle',
            'patchSequenceValues'
        );

        $actual = $method->invokeArgs($mock, [&$installData]);

        $this->assertEmpty($actual);
    }

    /**
     * @covers ::patchSequenceValues
     */
    public function testPatchSequenceValues()
    {
        $currentSequenceValue = 5;
        $installData = [
            [
                'some_field' => 'some_value',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ],
            [
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ],
            [
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ],
        ];

        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle')
            ->disableOriginalConstructor()
            ->onlyMethods(['getSequenceFromValues', 'getCurrentSequenceValue', 'setNewSequenceValue'])
            ->getMock();

        $mock->expects($this->once())
            ->method('getSequenceFromValues')
            ->with($installData[0])
            ->willReturn(['field' => 'field_name', 'name' => 'CASES_CASE_NUMBER_SEQ']);

        $mock->expects($this->once())
            ->method('getCurrentSequenceValue')
            ->with('CASES_CASE_NUMBER_SEQ')
            ->willReturn($currentSequenceValue);

        $mock->expects($this->once())
            ->method('setNewSequenceValue')
            ->with('CASES_CASE_NUMBER_SEQ', count($installData))
            ->willReturn(true);

        $method = static::accessNonPublicMethod(
            '\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle',
            'patchSequenceValues'
        );

        $method->invokeArgs($mock, [&$installData]);

        // Assert that values were changed to current sequence value + iteration number
        for ($i = 0; $i < count($installData); $i++) {
            $this->assertEquals($currentSequenceValue + $i + 1, $installData[$i]['field_name']);
        }
    }

    /**
     * @covers ::prepareQuery
     * @dataProvider dataTestPrepareQueryExceptionProvider
     *
     * @param string $tableName
     * @param mixed $installData
     */
    public function testPrepareQueryException($tableName, $installData)
    {
        $this->expectException(\Sugarcrm\Tidbit\Exception::class);
        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle')
            ->disableOriginalConstructor()
            ->onlyMethods(['patchSequenceValues'])
            ->getMock();

        $mock->expects($this->never())
            ->method('patchSequenceValues');

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle', 'prepareQuery');
        $method->invokeArgs($mock, [$tableName, $installData]);
    }

    /**
     * @return array
     * @see testPrepareQueryException
     */
    public function dataTestPrepareQueryExceptionProvider()
    {
        return [
            [
                '',
                [],
            ],
            [
                'some_table',
                [],
            ],
            [
                '',
                ['1', '2', '3'],
            ]
        ];
    }

    /**
     * @covers ::prepareQuery
     */
    public function testPrepareQuery()
    {
        $installData = [
            [
                'some_field' => 'some_value',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ],
            [
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ],
            [
                'some_field' => 'some_value2',
                'field_name' => 'CASES_CASE_NUMBER_SEQ.NEXTVAL',
            ],
        ];

        $mock = $this->getMockBuilder('Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle')
            ->disableOriginalConstructor()
            ->onlyMethods(['patchSequenceValues'])
            ->getMock();

        $mock->expects($this->once())
            ->method('patchSequenceValues')
            ->with($installData);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\StorageAdapter\Storage\Oracle', 'prepareQuery');
        $actual = $method->invokeArgs($mock, ['some_table', $installData]);

        $this->assertStringContainsString('INSERT /*+APPEND*/ ALL', $actual);
        $this->assertEquals(3, substr_count($actual, 'VALUES ('));
        $this->assertStringContainsString('SELECT * FROM dual', $actual);
    }
}
