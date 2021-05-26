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
defined("PUBLIC_APIKEY")     || define("PUBLIC_APIKEY", '52ec36471a0c747eea554181a5e2620c2eec1fb685f34b157bfe30529d58740a61d030f0b5e15101de2722baa27f04407e2415c948c7359faa95d7e8bca72a3a');

if (!defined("SUPERVISOR_APIKEY") && file_exists(ROOT_DIR . "/.supervisor_apikey")) {
    define("SUPERVISOR_APIKEY", file_get_contents(ROOT_DIR . "/.supervisor_apikey"));
}

// composer load
require_once ROOT_DIR . "/vendor/autoload.php";

new Api();
