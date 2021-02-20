<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

// load all classes
foreach (glob(__DIR__ . "src/*.php") as $filename) {
    include_once $filename;
}

// composer load
require_once __DIR__ . "/vendor/autoload.php";

// defaults
date_default_timezone_set('Europe/Prague');

new Api();