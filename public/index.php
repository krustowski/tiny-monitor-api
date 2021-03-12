<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

// core constants
defined("ROOT_DIR")          || define("ROOT_DIR", __DIR__ . "/..");
defined("DATABASE_FILE")     || define("DATABASE_FILE", ROOT_DIR . "/tiny_monitor_core.db");
defined("SUPERVISOR_APIKEY") || define("SUPERVISOR_APIKEY", /* getenv('SUPERVISOR_APIKEY') ?? */"a57e09822f12a615b5b0bce7008516a081294fe3855abeffe7c2e2c4a340a870a1fd485635cf12889d3daf381d51701fe263c497dd4052901f14997127e5ad4c");

// composer load
require_once ROOT_DIR . "/vendor/autoload.php";

new Api();
