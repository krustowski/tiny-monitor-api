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
foreach (glob("src/*.php") as $filename) {
    include_once $filename;
}

// composer load
require_once __DIR__ . "/vendor/autoload.php";

new Api();