<?php
/*
 * This is a simple PHP profiler.
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
     * Power const's
     *
     * @const bool
     */
    const POWER_ON = true;
    const POWER_OFF = false;

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
     * Warehouse for tracing calls
     *
     * @var array
     */
    private static $trace = array();

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
     * Power button constant
     *
     * @const bool
     */
    private static $enable = self::POWER_ON;

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
        } else {
            self::$log = self::LOG_FALSE;
        }
    }

    /**
     * Turn ON or OFF depend from parameter received
     *
     * @return nothing
     */
    public static function enable($mode)
    {
        if ($mode == self::POWER_ON)
        {
            self::$enable = self::POWER_ON;
        } else {
            self::$enable = self::POWER_OFF;
        }
    }

    /**
     * Is service enable?
     *
     * @return bool True if service is enabled and false if service isn't enabled.
     */
    public static function isEnable()
    {
        return (self::$enable == self::POWER_ON);
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
        if (!self::isEnable())
        {
            return ;
        }
        self::printLogsStartMessage();
        $newPoint = array();
        $currentTime = microtime(true);
        $currentRuntime = 0;
        if (self::$previousTime != 0)
        {
            $currentRuntime = ($currentTime - self::$previousTime);
        }
        $newPoint["name"] = $name;
        $newPoint["time"] = $currentRuntime;
        self::$totalTime += $currentRuntime;
        $newPoint["overall"] = self::$totalTime;
        self::detectMemoryUsage($newPoint);
        self::$stack[$group][] = $newPoint;
        if (self::isLogEnabled())
        {
            $log = array();
            $log[] = "Profiler event: \"".$newPoint["name"]."\"";
            if (!empty($group)) {
                $log[] = "Group: ".$group;
            }
            if (self::$previousTime != 0 && self::$previousTime < $currentTime)
            {
                $log[] = "Time: ".$currentRuntime;
                $log[] = "Overall: ".$newPoint["overall"];
            }
            self::errorLog(implode(',', $log));
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
        if (!self::isEnable())
        {
            return ;
        }
        $ascii_table = new ascii_table();
        $report = array();
        $report[] = <<<REPORT
<!--
This is profiler usage report:

REPORT;

        foreach (self::$stack as $groupName => $group)
        {
            $tableName = "DEFAULT";
            if (!empty($groupName))
            {
                $tableName = $groupName;
            }
            $report[] = $ascii_table->make_table($group,$tableName,true);

        }
        $report[] = "TOTAL RUNTIME: ".self::$totalTime;
        if (self::isLogEnabled())
        {
            self::errorLog("TOTAL RUNTIME: ".self::$totalTime);
        }

        if (count(self::$trace)>0) {
            $report[] = "TRACES ";
            foreach (self::$trace as $group => $traceValue) {
                $report[] = "TRACE: ".$group;
                foreach ($traceValue as $trace) {
                    $report[] = self::formatLogString($trace);
                }
                $report[] = "END TRACE: ".$group;
            }
        }
        $report[] = <<<REPORT
-->
REPORT;
        $reportResult = implode("\n", $report);
        if ($print == self::REPORT_PRINT_TRUE)
        {
            echo $reportResult;
        }
        return $reportResult;
    }

    /**
     * Returns stack trace for point
     *
     * @return string
     * @author http://php.net/manual/en/function.debug-backtrace.php#112238
     */
    public static function trace($name = "")
    {
        if (!self::isEnable())
        {
            return ;
        }
        self::printLogsStartMessage();
        if (empty($name))
        {
            $groupName = "default";
        } else {
            $groupName = $name;
        }
        $trace = debug_backtrace();
        if (count($trace) == 0 || !isset($trace[0]))
        {
            return ;
        }
        if (empty($groupName) && isset($trace[0]))
        {
            $group = array();
            if (isset($trace[0]["file"])) {
                $group[] = $trace[0]["file"];
            }
            if (isset($trace[0]["line"])) {
                $group[] = ":".$trace[0]["line"];
            }
            $groupName = implode(',', $group);
        }
        if (self::isLogEnabled())
        {
            self::errorLog("Trace: \"".$groupName."\":");
        }
        foreach ($trace as $key=>$currentTrace)
        {
            self::$trace[$groupName][] = $currentTrace;
            if (self::isLogEnabled())
            {
                $message = self::formatLogString($currentTrace);
                self::errorLog($message);
            }
        }
        if (self::isLogEnabled())
        {
            self::errorLog("End of trace: \"".$groupName."\"");
        }
    }

    /**
     * Just reset saved data
     *
     * @return nothing
     */
    public static function resetData()
    {
        self::$stack = array();
        self::$trace = array();
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

    /**
     * Prints message into the error_log
     *
     * @param $message
     *
     * @return nothing
     */
    private static function errorLog($message)
    {
        error_log(self::LOG_SEPARATOR.$message.self::LOG_SEPARATOR);
    }

    /**
     * Formats readable trace string
     *
     * @param array $currentTrace Array with trace data
     *
     * @return string Formatted string
     */
    private static function formatLogString($currentTrace)
    {
        $log = array();
        if (!empty($currentTrace["file"])) {
            $log[] = $currentTrace["file"];
        }
        if (!empty($currentTrace["line"])) {
            $log[] = ':'.$currentTrace["line"];
        }
        if (!empty($currentTrace["class"]) || !empty($currentTrace["function"])) {
            $log[] = ' <';
            $hasClass = false;
            if (!empty($currentTrace["class"])) {
                $log[] = $currentTrace["class"];
                $hasClass = true;
            }
            if (!empty($currentTrace["function"])) {
                if ($hasClass && isset($currentTrace["type"])) {
                    $log[] = $currentTrace["type"];
                }
                $log[] = $currentTrace["function"];
            }
            $log[] = '>';
        }
        return implode('', $log);
    }

    /**
     * Print introduce message
     *
     * @return nothing
     */
    private static function printLogsStartMessage() {
        if ((count(self::$stack) != 0) || (count(self::$trace) != 0) || !self::isLogEnabled())
        {
            return ;
        }
        self::errorLog('Profiler instance: '.date('d.m.Y H:i:s'));
        if (isset($_SERVER["REQUEST_URI"])) {
            $log = array();
            $log[] = "URL: ";
            if (isset($_SERVER["HTTPS"])) {
                $log[] = 'https://';
            } else {
                $log[] = 'http://';
            }
            if (isset($_SERVER["HTTP_HOST"])) {
                $log[] = $_SERVER["HTTP_HOST"];
            }
            if (isset($_SERVER["REQUEST_URI"])) {
                $log[] = $_SERVER["REQUEST_URI"];
            }
            self::errorLog(implode('', $log));
        }
    }
}
?>