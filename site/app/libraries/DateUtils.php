<?php

namespace app\libraries;

use \DateTime;
use \DateTimeZone;
use \DateInterval;

/**
 * Class DateUtils
 *
 * Utility functions for interacting with dates and times
 */
class DateUtils {
    
    /**
     * Given two dates, give the interval of time in days between these two times. Any partial "days" are rounded
     * up to the nearest day in the positive direction. Thus if there's a difference of 2 days and 3 hours, then
     * the function would return 3 days. Likewise, if the difference was -3 hours, then 0 days would be returned.
     *
     * @param string|DateTime $date1
     * @param string|DateTime $date2
     *
     * @return int
     */
    public static function calculateDayDiff($date1, $date2="Now") {
        if (!($date1 instanceof DateTime)) {
            $date1 = new DateTime($date1);
        }
        if (!($date2 instanceof DateTime)) {
            $date2 = new DateTime($date2);
        }
        // Set the period as "1 day" for the interval
        if ($date1 == $date2) {
            return 0;
        }
        $diff = $date1->diff($date2);
        $days_late = intval($diff->format('%r%a'));
        if ($date1 < $date2) {
            if ($diff->h > 0 || $diff->i > 0 || $diff->s > 0) {
                $days_late += 1;
            }
        }

        return $days_late;
    }

    public static function validateTimestamp($timestamp) {
    //IN:  $timestamp is actually a date string, not a Unix timestamp.
    //OUT: TRUE when date string conforms to an accetpable pattern
    //      FALSE otherwise.
    //PURPOSE: Validate string to (1) be a valid date and (2) conform to specific
    //         date patterns.
    //         'm-d-Y' -> mm-dd-yyyy
    //         'm-d-y' -> mm-dd-yy
    //         'm/d/Y' -> mm/dd/yyyy
    //         'm/d/y' -> mm/dd/yy

        //This bizzare/inverted switch-case block actually does work in PHP.
        //This operates as a form of "white list" of valid patterns.
        //This checks to ensure a date pattern is acceptable AND the date actually
        //exists.  e.g. "02-29-2016" is valid, while "06-31-2016" is not.
        //That is, 2016 is a leap year, but June has only 30 days.
        $tmp = array(date_create_from_format('m-d-Y', $timestamp),
                     date_create_from_format('m/d/Y', $timestamp),
                     date_create_from_format('m-d-y', $timestamp),
                     date_create_from_format('m/d/y', $timestamp));

        switch (true) {
        case ($tmp[0] && $tmp[0]->format('m-d-Y') === $timestamp):
        case ($tmp[1] && $tmp[1]->format('m/d/Y') === $timestamp):
        case ($tmp[2] && $tmp[2]->format('m-d-y') === $timestamp):
        case ($tmp[3] && $tmp[3]->format('m/d/y') === $timestamp):
            return true;
        default:
            return false;
        }
        return true;
    }

    /** @var string The php-limit for dates.  Note that the database limit is later */
    const MAX_TIME = '9999-02-01 00:00:00';

    /**
     * Parses a date string into a \DateTime object, or does nothing if $date is already a \DateTime object
     * Note: This will clamp the date to be earlier than MAX_TIME
     *
     * @param \DateTime|string $date The date to parse
     * @param \DateTimeZone $time_zone
     * @return \DateTime The parsed date
     * @throws \InvalidArgumentException If $date is not a string or a \DateTime, or not a valid \DateTime string
     */
    public static function parseDateTime($date, \DateTimeZone $time_zone) {
        if (gettype($date) === 'string') {
            try {
                $date = new \DateTime($date, $time_zone);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid DateTime Format');
                $date =  new \DateTime("9998-12-31 23:59:59", $time_zone);
            }
        } else if (!($date instanceof \DateTime)) {
            throw new \InvalidArgumentException('Passed object was not a DateTime object or a date string');
        }

        // Make sure we always set the timezone
        $date->setTimezone($time_zone);

        // Make sure we don't go above our range
        return min($date, new \DateTime(self::MAX_TIME, $time_zone));
    }

    /**
     * Parses a date string into a \DateTime object using regex.  This allows dates to be year >9999
     * Note: This is designed so that dates larger than year 9999 can be loaded from the db without exception.
     * Format YYYYY-MM-DD HH:mm:ssZ
     *
     * @param string $date_time
     * @param \DateTimeZone|null $default_time_zone The default timezone to use if none provided
     * @return DateTime
     */
    public static function parseDateTimeLong(string $date_time, $default_time_zone = null) {
        $matches = [];
        if (!preg_match('/^([0-9]{4,5})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?([-|+]?[0-9]{2})?/', $date_time, $matches)) {
            throw new \InvalidArgumentException('Invalid DateTime Format');
        }

        $timezone = $matches[8] ?? '';
        $date = new \DateTime('now', ($timezone !== '') ? new DateTimeZone($timezone) : $default_time_zone);
        $date->setDate($matches[1], $matches[2], $matches[3]);
        $date->setTime($matches[4], $matches[5], $matches[6]);
        return $date;
    }

    /**
     * Converts a \DateTime object to a string in one place so if we change the format
     *  here, it changes everywhere
     *
     * @param \DateTime  $date The date to format
     * @param bool $add_utc_offset If the UTC offset should be part of the output
     * @return string The formatted date
     */
    public static function dateTimeToString(DateTime $date, bool $add_utc_offset = true) {
        return $date->format('Y-m-d H:i:s' . ($add_utc_offset ? 'O' : ''));
    }

    /**
     * Gets a json which contains the current server time broken up into specific fields
     * Formatting the data in this manner makes it easier to work with when instantiating javascript Date() objects
     *
     * @param $core Core core
     * @return object
     */
    public static function getServerTimeJson($core) {
        $time = new \DateTime('now', $core->getConfig()->getTimezone());

        $json = (object)[];
        $json->year = $time->format('Y');
        $json->month = $time->format('m');
        $json->day = $time->format('j');
        $json->hour = $time->format('G');
        $json->minute = $time->format('i');
        $json->second = $time->format('s');

        return $json;
    }
}