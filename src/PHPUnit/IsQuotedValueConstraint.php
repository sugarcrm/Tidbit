<?php

namespace Sugarcrm\Tidbit\PHPUnit;

/**
 * Class IsQuotedValueConstraint
 * @package Sugarcrm\Tidbit\PHPUnit
 */
class IsQuotedValueConstraint extends \PHPUnit\Framework\Constraint\Constraint
{
    /**
     * Matches that string value is quoted
     */
    public function matches($other): bool
    {
        $result = false;

        if (is_string($other) && strlen($other) >= 2) {
            $result = ($other[0] == '\'' && $other[strlen($other) - 1] == '\'');
        }

        return $result;
    }

    public function toString(): string
    {
        return 'value is quoted. Expecting first and last chars should be "\'"';
    }
}
