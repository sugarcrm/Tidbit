<?php

namespace Sugarcrm\Tidbit\Tests\FieldData;

use Sugarcrm\Tidbit\Tests\TidbitTestCase;
use Sugarcrm\Tidbit\FieldData\Phone;

class PhoneTest extends TidbitTestCase
{
    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::getNumber
     */
    public function testGetNumberReturnsPhoneNumber()
    {
        $number = Phone::getNumber();
        $this->assertRegExp('/^\d{3}\-\d{3}\-\d{4}$/', $number);
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::get
     */
    public function testGetReturnsPhoneNumberFromList()
    {
        $phone = new Phone();
        $phonesList = static::getNonPublicProperty('\Sugarcrm\Tidbit\FieldData\Phone', 'phonesList', $phone);
        $this->assertTrue(in_array($phone->get(), $phonesList));
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::generatePhones
     */
    public function testGeneratePhonesCreateCountNumbersAccordingPattern()
    {
        $neededCount = 25;
        $neededPattern = '###-###-###';
        $phonesMock = $this->getMockBuilder('\Sugarcrm\Tidbit\FieldData\Phone')
            ->setMethods(array('generatePhone'))
            ->getMock();
        $phonesMock->expects($this->exactly($neededCount))->method('generatePhone')->with($neededPattern);

        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\FieldData\Phone', 'generatePhones');
        $method->invokeArgs($phonesMock, array($neededCount, $neededPattern));
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::generatePhone
     */
    public function testGeneratePhone()
    {
        $phone = new Phone();
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\FieldData\Phone', 'generatePhone');

        $this->assertRegExp('/^\d{4}$/', $method->invokeArgs($phone, array('####')));
        $this->assertRegExp('/^\(\d{3}\)\-\d{4}$/', $method->invokeArgs($phone, array('({{areaCode}})-####')));
        $this->assertRegExp(
            '/^\d{3}\-33\-\-\d{3}$/',
            $method->invokeArgs($phone, array('{{areaCode}}-33--{{exchangeCode}}'))
        );
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::areaCode
     */
    public function testAreaCode()
    {
        $phone = new Phone();
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\FieldData\Phone', 'areaCode');
        $code = $method->invokeArgs($phone, array());

        $this->assertTrue(strlen($code) == 3);

        $digit1 = intval(substr($code, 0, 1));
        $digit2 = intval(substr($code, 1, 1));
        $digit3 = intval(substr($code, 2, 1));

        $this->assertTrue($digit1 >= 2 && $digit1 <= 9);
        $this->assertTrue($digit2 >= 0 && $digit2 <= 9);
        $this->assertTrue($digit3 >= 0 && $digit3 <= 9);
        $this->assertNotEquals($digit2, $digit3);
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::exchangeCode
     */
    public function testExchangeCode()
    {
        $phone = new Phone();
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\FieldData\Phone', 'exchangeCode');
        $code = $method->invokeArgs($phone, array());

        $this->assertTrue(strlen($code) == 3);

        $digit1 = intval(substr($code, 0, 1));
        $digit2 = intval(substr($code, 1, 1));
        $digit3 = intval(substr($code, 2, 1));

        $this->assertTrue($digit1 >= 2 && $digit1 <= 9);
        $this->assertTrue($digit2 >= 0 && $digit2 <= 9);
        $this->assertTrue($digit3 >= 0 && $digit3 <= 9);
        if ($digit2 == 1) {
            $this->assertNotEquals($digit2, $digit3);
        }
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::getRandomDigitNot
     */
    public function testGetRandomDigitNot()
    {
        $phone = new Phone();
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\FieldData\Phone', 'getRandomDigitNot');
        $testDigit = 5;
        $digit = $method->invokeArgs($phone, array($testDigit));

        $this->assertTrue(is_int($digit));
        $this->assertTrue($digit >= 0 && $digit <= 9);
        $this->assertNotEquals($testDigit, $digit);
    }

    /**
     * @covers Sugarcrm\Tidbit\FieldData\Phone::getRandomDigit
     */
    public function testGetRandomDigit()
    {
        $phone = new Phone();
        $method = static::accessNonPublicMethod('\Sugarcrm\Tidbit\FieldData\Phone', 'getRandomDigit');
        $digit = $method->invokeArgs($phone, array());

        $this->assertTrue(is_int($digit));
        $this->assertTrue($digit >= 0 && $digit <= 9);
    }
}
