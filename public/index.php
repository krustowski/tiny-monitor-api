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

// check for app's config file
if (!file_exists(INIT_CONFIG))
    die (json_encode([
        "message" => "fatal error: no init_config.json!", 
        "code" => "500"
    ]));

// load and decode app's config file
$config = json_decode(json: file_get_contents(filename: INIT_CONFIG), associative: true) or die(json_encode([
    "message" => "fatal error: corrupted init_config.json!", 
    "code" => "500"
]));

// parse and define all constants (unstable)
foreach ($config as $key => $value) {
    $const_name = strtoupper(string: $key);
    defined(constant_name: $const_name) || define(constant_name: $const_name, value: $value);
}

// to be deleted later?
defined("SUPERVISOR_APIKEY")    || define("SUPERVISOR_APIKEY", $config["super_apikey"]);
defined("PUBLIC_APIKEY")        || define("PUBLIC_APIKEY", $config["public_apikey"]);
defined("DATABASE_FILE")        || define("DATABASE_FILE", $config["database_file"]);

// load dependencies' and app's classes
require_once ROOT_DIR . "/vendor/autoload.php";

// start the API main class
new Api(
    sql: new SQLite3(
        filename: DATABASE_FILE
    )
);
