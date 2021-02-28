<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

defined("ROOT_DIR") || define("ROOT_DIR", __DIR__);

// load all classes
foreach (glob(ROOT_DIR . "/src/*.php") as $filename) {
    include_once $filename;
}

// composer load
require_once ROOT_DIR . "/vendor/autoload.php";

new Api();
