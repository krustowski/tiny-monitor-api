<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @license MIT
 */

 // OpenAPI API + swagger documentation experiment

require(__DIR__ . "/../vendor/autoload.php");

$openapi = \OpenApi\scan(__DIR__ . '/../src');

header('Content-Type: application/x-yaml');
echo $openapi->toYaml();
