<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

// core contants
defined("ROOT_DIR")          || define("ROOT_DIR", __DIR__ . "/..");
defined("DATABASE_FILE")     || define("DATABASE_FILE", ROOT_DIR . "/tiny_monitor_core.db");
defined("SUPERVISOR_APIKEY") || define("SUPERVISOR_APIKEY", getenv('SUPERVISOR_APIKEY'));

// load all classes
foreach (glob(ROOT_DIR . "/src/*.php") as $filename) {
    include_once $filename;
}

// composer load
require_once ROOT_DIR . "/vendor/autoload.php";

new Api();
