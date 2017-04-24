# Lightweight PHP profiler class

[Developer website](http://ivanovdmitry.com)

This class uses for profiling PHP applications. 
It's very useful tool for finding slow places in your apps.

The class provides follow functions:

* Set time point
* Print function trace
* Print overall report as an HTML comment on website page
* Works with error_log for easily control AJAX and other queries
* Divides traces and time points by group
* Prints overall work times report in a table. It can be divided into groups.
* Detects memory usage

With this solution, you don't need huge solutions. You can setup this profiler only for development mode. 
It's only 2 files:

* Profiler class
* ASCII table class

Once you've included file, you can use it.
You can find slow places in your application, you can find break places in your code etc.
I recommend to include profiler as top as possible.
If you wish to use final report I recommend place code as end as possible.

Error log output is enabled by default.

# Installation

```php
require_once('phpprofiler/profiler.class.php');
```

# Usage

There aren't difficult methods in this script.

Parameters in "<...>" can be omitted.
For setup time point you can use:

```php
Profiler::point('Name of point',<group>);
```

For print overall report as an HTML comment and print overall work time:

```php
Profiler::report(<(bool)Print an HTML Comment>);
```

For trace point:

```php
Profiler::trace('Name of trace',,<group>);
```

For enable\disable Profiler you can use:

```php
Profiler::enable(bool);
```

For enable\disable error_log output:

```php
Profiler::logOutput(bool);
```

Reset all data:

```php
Profiler::resetData
```

# Example

```php
require_once('phpprofiler/profiler.class.php');
Profiler::point("First point");
Profiler::trace("Trace point");
Profiler::point("End point");
Profiler::report(true);
```

# Support

If you have any questions about this class you can e-mail author:

me@ivanovdmitry.com