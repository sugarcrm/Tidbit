<?php

namespace Sugarcrm\Tidbit\Tests\SugarObject;

/**
 * Class DBManager
 * @package Sugarcrm\Tidbit\Tests\SugarObject
 */
class DBManager
{
    public function query($sql, $dieOnFailure = false, $message = '')
    {
        return true;
    }

    public function fetchByAssoc($statement)
    {
        return true;
    }
}
