<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <tmv2@n0p.cz>
 * @license MIT
 */

namespace tinyMonitor;

use SQLite3;

// core constants
defined("ROOT_DIR")          || define("ROOT_DIR", __DIR__ . "/..");
defined("INIT_CONFIG")       || define("INIT_CONFIG", ROOT_DIR . "/init_config.json");

if (!file_exists(INIT_CONFIG))
    die ('{"message": "fatal error: no init_config.json!", "code": "500"}');

$config = json_decode(json: file_get_contents(filename: INIT_CONFIG), associative: true);

defined("SUPERVISOR_APIKEY")    || define("SUPERVISOR_APIKEY", $config["super_apikey"]);
defined("PUBLIC_APIKEY")        || define("PUBLIC_APIKEY", $config["public_apikey"]);
defined("DATABASE_FILE")        || define("DATABASE_FILE", $config["database_file"]);

// load dependencies and app
require_once ROOT_DIR . "/vendor/autoload.php";

new Api(
    sql: new SQLite3(
        filename: DATABASE_FILE
    )
);
