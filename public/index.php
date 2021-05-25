<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <tmv2@n0p.cz>
 * @license MIT
 */

namespace tinyMonitor;

// core constants
defined("ROOT_DIR")          || define("ROOT_DIR", __DIR__ . "/..");
defined("DATABASE_FILE")     || define("DATABASE_FILE", ROOT_DIR . "/tiny_monitor_core.db");

if (!defined("SUPERVISOR_APIKEY") && file_exists(ROOT_DIR . "/.supervisor_apikey")) {
    define("SUPERVISOR_APIKEY", file_get_contents(ROOT_DIR . "/.supervisor_apikey"));
}

// composer load
require_once ROOT_DIR . "/vendor/autoload.php";

new Api();
