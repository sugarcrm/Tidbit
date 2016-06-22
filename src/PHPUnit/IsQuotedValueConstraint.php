<?php

namespace Sugarcrm\Tidbit\PHPUnit;

/**
 * Class IsQuotedValueConstraint
 * @package Sugarcrm\Tidbit\PHPUnit
 */
class IsQuotedValueConstraint extends \PHPUnit_Framework_Constraint
{
    /**
     * Matches that string value is quoted
     *
     * @param mixed $other
     * @return bool
     */
    public function matches($other)
    {
        $result = false;

        if (is_string($other) && strlen($other) >= 2) {
            $result = ($other[0] == '\'' && $other[strlen($other) - 1] == '\'');
        }

        return $result;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'value is quoted. Expecting first and last chars should be "\'"';
    }
}
