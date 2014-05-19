<?php

namespace Rezzza;

/**
 * TimeTraveler
 *
 * Needs AOP extension to works.
 *
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class TimeTraveler
{
    /**
     * @param integer|null
     */
    private static $currentTimeOffset;

    /**
     * @param integer
     */
    private static $currentTime;

    /**
     * @param boolean
     */
    private static $enabled = false;

    /**
     * Enable the time traveler.
     */
    public static function enable()
    {
        if (self::$enabled === true) {
            return;
        }

        if (!function_exists('\aop_add_after')) {
            throw new \LogicException('Aop extension seems to not be installed.');
        }

        $createDateTimeWithStr = function(\DateTime $date, $str = null) {
            if (TimeTraveler::getCurrentTime()) {
                $date->setTimestamp(TimeTraveler::getCurrentTime());

                if ($str) {
                    $date->modify($str);
                }
            }

            return $date;
        };

        aop_add_after('time()', function(\AopJoinPoint $joinPoint) {
            if (TimeTraveler::getCurrentTimeOffset()) {
                $joinPoint->setReturnedValue($joinPoint->getReturnedValue() + TimeTraveler::getCurrentTimeOffset());
            }
        });

        aop_add_after('microtime()', function(\AopJoinPoint $joinPoint) {
            if (TimeTraveler::getCurrentTimeOffset()) {
                $returnedValue = $joinPoint->getReturnedValue();

                if (is_float($returnedValue)) {
                    $joinPoint->setReturnedValue($joinPoint->getReturnedValue() + TimeTraveler::getCurrentTimeOffset());
                } else {
                    list($micro, $seconds) = explode(' ', $joinPoint->getReturnedValue());
                    $seconds += TimeTraveler::getCurrentTimeOffset();

                    $joinPoint->setReturnedValue($micro.' '.$seconds);
                }
            }
        });

        aop_add_after('DateTime->__construct()', function(\AopJoinPoint $joinPoint) use ($createDateTimeWithStr) {
            $createDateTimeWithStr($joinPoint->getObject(), isset($args[0]) ? $args[0] : null);
        });

        $functionDates = function(\AopJoinPoint $joinPoint) {
            if (TimeTraveler::getCurrentTimeOffset()) {
                $args = $joinPoint->getArguments();
                if (isset($args[1]) && !empty($args[1])) {
                    return;
                }

                $function = $joinPoint->getFunctionName();
                $joinPoint->setReturnedValue($function($args[0], time() + TimeTraveler::getCurrentTimeOffset()));
            }
        };

        aop_add_after('date()', $functionDates);
        aop_add_after('gmdate()', $functionDates);

        aop_add_after('date_create()', function(\AopJoinPoint $joinPoint) use ($createDateTimeWithStr) {
            $createDateTimeWithStr($joinPoint->getReturnedValue(), isset($args[0]) ? $args[0] : null);
        });

        aop_add_after('strtotime()', function(\AopJoinPoint $joinPoint) use ($createDateTimeWithStr) {
            $arguments = $joinPoint->getArguments();
            if (isset($arguments[1]) && !empty($arguments[1])) {
                // time is given, we haven't anything to do.
                return;
            }

            if (TimeTraveler::getCurrentTime()) {
                $date = $createDateTimeWithStr(new \DateTime(), $arguments[0]);

                $joinPoint->setReturnedValue($date->getTimestamp());
            }
        });

        aop_add_after('gettimeofday()', function(\AopJoinPoint $joinPoint) {
            if (TimeTraveler::getCurrentTime()) {
                $args = $joinPoint->getArguments();
                if (array_key_exists(0, $args) && false !== $args[0]) {
                    $joinPoint->setReturnedValue($joinPoint->getReturnedValue() + TimeTraveler::getCurrentTimeOffset());
                } else {
                    $returnedValue = $joinPoint->getReturnedValue();
                    $returnedValue['sec'] += TimeTraveler::getCurrentTimeOffset();

                    $joinPoint->setReturnedValue($returnedValue);
                }
            }
        });

        self::$enabled = true;
    }

    /**
     * Edit current date of your system.
     *
     * @param string $date date
     */
    public static function moveTo($date)
    {
        if (!is_scalar($date)) {
            throw new \InvalidArgumentException('TimeTraveler::moveTo expects a scalar.');
        }

        $now = self::$currentTimeOffset ? time() - self::$currentTimeOffset : time();

        self::$currentTime = strtotime($date);

        if (self::$currentTime === false) {
            throw new \InvalidArgumentException(sprintf('Cannot parse "%s" as a date.', $date));
        }

        self::$currentTimeOffset = self::$currentTime - $now;
    }

    /**
     * Remove current time and offset. Come back to true current date time.
     */
    public static function comeBack()
    {
        self::$currentTime       = null;
        self::$currentTimeOffset = null;
    }

    /**
     * @return integer|null
     */
    public static function getCurrentTimeOffset()
    {
        return self::$currentTimeOffset;
    }

    /**
     * @return integer
     */
    public static function getCurrentTime()
    {
        return self::$currentTime;
    }
}
