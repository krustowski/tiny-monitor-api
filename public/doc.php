<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <tmv2@n0p.cz>
 * @license MIT
 */

 // OpenAPI API + swagger documentation experiment

defined("ROOT_DIR") || define("ROOT_DIR", __DIR__ . "/..");

// composer load
require_once ROOT_DIR . "/vendor/autoload.php";

$openapi = \OpenApi\Generator::scan([ROOT_DIR . '/src/']);

header('Content-Type: application/x-yaml');
echo $openapi->toYaml();
