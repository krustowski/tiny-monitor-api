<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 */

namespace tinyMonitor;

// load all classes
foreach (glob("src/*.php") as $filename) {
    include_once $filename;
}

new Api();