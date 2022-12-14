<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2010 SugarCRM Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

namespace Sugarcrm\Tidbit\FieldData;

class Phone
{
    /** @var string  */
    const DEFAULT_PATTERN = '{{areaCode}}-{{exchangeCode}}-####';

    private $phonesList = array();
    
    /** @var  Phone */
    private static $instance;

    /**
     * Phone constructor.
     *
     * @param int $countOfPhones
     * @param string $pattern
     */
    public function __construct($countOfPhones = 10, $pattern = self::DEFAULT_PATTERN)
    {
        $this->generatePhones($countOfPhones, $pattern);
    }

    /**
     * Get phone from single instance of class.
     *
     * @return string
     */
    public static function getNumber()
    {
        if (!self::$instance) {
            self::$instance = new Phone();
        }
        
        return self::$instance->get();
    }

    /**
     * Returns one from generated phones.
     *
     * @return string
     */
    public function get()
    {
        return $this->phonesList[mt_rand(0, count($this->phonesList) - 1)];
    }

    /**
     * Fill phones list
     *
     * @param int $count
     * @param string $pattern
     */
    protected function generatePhones($count, $pattern)
    {
        for ($i=0; $i<$count; $i++) {
            $this->phonesList[] = $this->generatePhone($pattern);
        }
    }

    /**
     * @param $pattern string
     * @return string
     */
    protected function generatePhone($pattern)
    {
        $result = str_replace('{{areaCode}}', $this->areaCode(), $pattern);
        $result = str_replace('{{exchangeCode}}', $this->exchangeCode(), $result);
        $pos = strpos($result, '#');
        while ($pos !== false) {
            $result = substr_replace($result, $this->getRandomDigit(), $pos, 1);
            $pos = strpos($result, '#');
        }

        return $result;
    }

    /**
     * NPA-format area code.
     *
     * @see https://en.wikipedia.org/wiki/North_American_Numbering_Plan#Numbering_system
     *
     * @return string
     */
    protected function areaCode()
    {
        $digits[] = mt_rand(2, 9);
        $digits[] = $this->getRandomDigit();
        $digits[] = $this->getRandomDigitNot($digits[1]);

        return join('', $digits);
    }

    /**
     * NXX-format central office exchange code
     *
     * @see https://en.wikipedia.org/wiki/North_American_Numbering_Plan#Numbering_system
     *
     * @return string
     */
    protected function exchangeCode()
    {
        $digits[] = mt_rand(2, 9);
        $digits[] = $this->getRandomDigit();

        if ($digits[1] === 1) {
            $digits[] = $this->getRandomDigitNot(1);
        } else {
            $digits[] = $this->getRandomDigit();
        }

        return join('', $digits);
    }

    /**
     * Generates a random digit, which cannot be $except.
     *
     * @param int $except
     * @return int
     */
    protected function getRandomDigitNot($except)
    {
        $result = mt_rand(0, 8);
        if ($result >= $except) {
            $result++;
        }
        return $result;
    }

    /**
     * Generates a random digit
     *
     * @return int
     */
    protected function getRandomDigit()
    {
        return mt_rand(0, 9);
    }
}
