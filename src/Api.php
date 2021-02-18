<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 */

namespace tinyMonitor;

/**
 * Api class
 * contains request handling methods
 */
class Api {

    private $routePath;
    private $safeGET = [];
    private $safePOST = [];

    public function __construct() {
        // clear 
        $this->safeGET  = array_map("htmlspecialchars", $_GET);
        $this->safePOST = array_map("htmlspecialchars", $_POST);

        $this->routePath = $safeGET["query"] ?? "";
    }
}