<?php

namespace Sugarcrm\Tidbit\Tests\SugarObject;

/**
 * Class TimeDate
 * Copy some SugarCRM TimeDate class methods, to make tests works correctly
 *
 * @package Sugarcrm\Tidbit\Tests\SugarObject
 */
class TimeDate extends \DateTime
{
    const DB_DATE_FORMAT = 'Y-m-d';
    const DB_TIME_FORMAT = 'H:i:s';
    const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Global instance of TimeDate
     * @var TimeDate
     */
    protected static $timedate;

    /**
     * GMT timezone object
     *
     * @var \DateTimeZone
     */
    protected static $gmtTimezone;

    /**
     * Create TimeDate handler
     */
    public function __construct()
    {
        static::$gmtTimezone = new \DateTimeZone("UTC");
    }

    /**
     * Singleton class instance
     *
     * @return mixed
     */
    public static function getInstance()
    {
        if (empty(static::$timedate)) {
            static::$timedate = new static();
        }

        return static::$timedate;
    }
    
    /**
     * Format date as DB-formatted field type
     * @param \DateTime $date
     * @param string $type Field type - date, time, datetime[combo]
     * @param boolean $setGMT Set timezone to GMT (defaults to null, will not be passed to any method)
     * @return string Formatted date
     */
    public function asDbType(\DateTime $date, $type, $setGMT = null)
    {
        $args = array($date);
        // because asDbDate and asDb have different default value for $setGMT
        // (true and false) we have to use NULL as default value for this method
        if (!is_null($setGMT)) {
            $args[] = $setGMT;
        }

        switch ($type) {
            case "date":
                return call_user_func_array(array($this, 'asDbDate'), $args);
                break;
            case 'time':
                return $this->asDbTime($date);
                break;
            case 'datetime':
            case 'datetimecombo':
            default:
                return call_user_func_array(array($this, 'asDb'), $args);
        }
    }

    /**
     * Format DateTime object as DB datetime
     *
     * @param \DateTime $date
     * @param boolean $setGMT Set timezone to GMT (defaults to true)
     * @return string
     */
    public function asDb(\DateTime $date, $setGMT = true)
    {
        if ($setGMT) {
            $date->setTimezone(static::$gmtTimezone);
        }
        return $date->format(static::DB_DATETIME_FORMAT);
    }

    /**
     * Format DateTime object as DB date
     * Note: by default does not convert TZ!
     * @param \DateTime $date
     * @param boolean $tz Perform TZ conversion?
     * @return string
     */
    public function asDbDate(\DateTime $date, $tz = false)
    {
        if ($tz) {
            $date->setTimezone(static::$gmtTimezone);
        }
        return $date->format(static::DB_DATE_FORMAT);
    }

    /**
     * Format DateTime object as DB time
     *
     * @param \DateTime $date
     * @return string
     */
    public function asDbTime(\DateTime $date)
    {
        $date->setTimezone(static::$gmtTimezone);
        return $date->format(static::DB_TIME_FORMAT);
    }
}
