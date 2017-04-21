<?php
/*
 * This is simple PHP profiler.
 * Usage is very simple:
 *
 * Profiler::point(); - set's point
 *
 * Script also uses third-party solutions:
 *
 *  PHP ASCII-Table <https://github.com/pgooch/PHP-Ascii-Tables> For generates beautiful ASCII tables
 *
 * @date 20170421
 * @author Ivanov Dmitry
 * @url http://ivanovdmitry.com/
 */
require_once(__DIR__.'/ascii_table.php');

final class Profiler {
    /**
     * Consts which showing need or needn't to put data in log
     *
     * @const boolean
     */
    const LOG_TRUE = true;
    const LOG_FALSE = false;

    /**
     * Need to print reports?
     *
     * @const bool
     */
    const REPORT_PRINT_TRUE = true;
    const REPORT_PRINT_FALSE = true;

    /**
     * Log separator
     *
     * @const String
     */
    const LOG_SEPARATOR = " *** ";

    /**
     * Main stack of points
     *
     * @var array
     */
    private static $stack = array();

    /**
     * Put messages into error.log or not
     *
     * @var bool
     */
    private static $log = self::LOG_FALSE;

    /**
     * Point of previous time
     *
     * @var int
     */
    private static $previousTime = 0;

    /**
     * Total running time
     *
     * @var int
     */
    private static $totalTime = 0;

    /**
     * Turn or off log input
     *
     * @param bool $log
     *
     * @return nothing
     */
    public static function logOutput($log = self::LOG_TRUE)
    {
        if ($log)
        {
            self::$log = self::LOG_TRUE;
        } else
        {
            self::$log = self::LOG_FALSE;
        }
    }

    /**
     * Sets point of event
     *
     * @param String name of point
     *
     * @return nothing
     */
    public static function point($name, $group = '')
    {
        $newPoint = array();
        $currentTime = microtime(true);
        $currentRuntime = 0;
        if (self::$previousTime != 0)
        {
            $currentRuntime = ($currentTime - self::$previousTime);
        }
        $newPoint["name"] = $name;
        $newPoint["time"] = $currentRuntime;
        self::detectMemoryUsage($newPoint);
        self::$stack[$group][] = $newPoint;
        if (self::isLogEnabled())
        {
            $log = array();
            $log[] = self::LOG_SEPARATOR."Profiler event: \"".$newPoint["name"]."\"";
            if (!empty($group)) {
                $log[] = "Group: ".$group;
            }
            if (self::$previousTime != 0 && self::$previousTime < $currentTime)
            {
                self::$totalTime += $currentRuntime;
                $log[] = "Time: ".$currentRuntime;
            }
            $log[] = self::LOG_SEPARATOR;
            error_log(implode(',', $log));
        }
        self::$previousTime = $currentTime;
    }

    /**
     * Generates HTML comment with profiling information
     *
     * @return nothing
     */
    public static function report($print = self::REPORT_PRINT_FALSE)
    {
        $ascii_table = new ascii_table();
        $totalTime = 0;
        $report = array();
        $report[] = <<<REPORT
<!--
This is profiler usage report:

REPORT;

        foreach (self::$stack as $groupName => $group)
        {
            $tableName = "DEFAULT";
            if (!empty($groupName)) {
                $tableName = $groupName;
            }
            $report[] = $ascii_table->make_table($group,$tableName,true);

        }
        $report[] = "TOTAL RUNTIME: ".self::$totalTime;
        if (self::isLogEnabled()) {
            error_log("TOTAL RUNTIME: ".self::$totalTime);
        }
        $report[] = <<<REPORT
-->
REPORT;
        $reportResult = implode("\n", $report);
        if ($print == self::REPORT_PRINT_TRUE) {
            echo $reportResult;
        }
        return $reportResult;
    }

    /**
     * Just reset saved data
     *
     * @return nothing
     */
    public static function resetData()
    {
        self::$stack = array();
        self::$previousTime = 0;
        self::$totalTime = 0;
    }

    /**
     * Checks whether log_error is enabled or not.
     *
     * @return bool
     */
    private static function isLogEnabled()
    {
        return (self::$log == self::LOG_TRUE);
    }

    /**
     * Detects script memory usage
     *
     * @param stdClass $newPoint reference to a point info object
     *
     * @return nothing
     */
    private static function detectMemoryUsage(&$newPoint)
    {
        $newPoint["memory_usage"] = function_exists('memory_get_usage') ? round(memory_get_usage() / 1024 / 1024, 2) : 0;
        $newPoint["memory_limit"] = (int) ini_get('memory_limit') ;
        if ( !empty($newPoint["memory_usage"]) && !empty($newPoint["memory_limit"]) ) {
            $newPoint["memory_percent"] = round ($newPoint["memory_usage"] / $newPoint["memory_limit"] * 100, 0);
        }
    }
}
?>