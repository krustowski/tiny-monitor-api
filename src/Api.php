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
 * 
 * contains request handling methods
 */
class Api {

    // API header vars
    private $apiName = "tiny-monitor REST API";
    private $apiVersion = "v1";
    private $apiTimestampStart;
    private $remoteIP;
    private $statusMessage;

    // API config vars
    private $logfile = "/dev/stdout";

    // API data vars
    private $engineOutput = [];
    private $routePath;
    private $apiKey;
    private $safeGET = [];
    private $safePOST = [];
    private $dataPayload = [];

    public function __construct() {
        // clear HTTP requests
        $this->safeGET = (array) array_map("htmlspecialchars", $_GET);
        $this->safePOST = (array) array_map("htmlspecialchars", $_POST);

        // get API key
        $this->apiKey = $this->safeGET["apikey"] ?? null;

        // POST data, payload
        $this->dataPayload = json_decode(file_get_contents('php://input'), true) ?? null;

        $this->apiTimestampStart = (double) microtime(true) ?? null;
        $this->remoteAddress = $_SERVER["REMOTE_ADDR"] ?? null;

        // explode over full path query variable
        $this->routePath = explode('/', $this->safeGET["fullPath"]) ?? null;

        $this->handleRequest();
    }

    /**
     * handleRequest function
     * 
     * entrypoint for all API calls
     */
    private function handleRequest() {
        switch ($this->routePath[0]) {
            case 'GetStatus':
                #$this->engineOutput = Engine\getStatus();

                $sites = [
                    "https://digikatalog.cz",
                    "https://julia.mxd.cz",
                    "https://khanovaskola.cz",
                    "https://moodle-uploader-988765764d26.gscloud.cz",
                    "https://php.gscloud.cz",
                    "https://pma.gscloud.cz",
                    "https://pma35.mxd.cz",
                    "https://ssl.gscloud.cz:2083",
                    "https://sys.gscloud.cz",
                    "https://andini.cz",
                    "https://bbqpoint.cz",
                    "https://book.gscloud.cz",
                    "https://csking.gscloud.cz",
                    "https://foodinc.cz",
                    "https://foodincubator.cz",
                    "https://funclubbrno.cz",
                    "https://game.gscloud.cz",
                    "https://gscloud.cz",
                    "https://hirobistro.cz",
                    "https://hmc.gscloud.cz",
                    "https://indexmatrik.cz",
                    "https://jo.gscloud.cz",
                    "https://lasagna.gscloud.cz",
                    "https://mnamky.gscloud.cz",
                    "https://mini.gscloud.cz",
                    "https://podcast.gscloud.cz",
                    "https://podcastsk.gscloud.cz",
                    "https://moodle.andini.cz",
                    "https://moodle310.mxd.cz",
                    "https://moodle35.mxd.cz",
                    "https://moodle37.mxd.cz",
                    "https://moodle39.mxd.cz",
                    "https://academiacafe.cz",
                    "https://amadis.cz",
                    "https://andini-research.com",
                    "https://comunityenergy.com",
                    "https://handshop.cz",
                    "https://kepert.cz",
                    "https://kestrasnicke.cz",
                    "https://kouzelnehratky.cz",
                    "https://mxd.cz",
                    "https://parappuduwa.info",
                    "https://sirhal.cz",
                    "https://televariete2019.cz",
                    "https://zitaterapie.cz",
                    "https://monitor.gscloud.cz",
                    "https://newz.mxd.cz/",
                    "https://red.mxd.cz/",
                    "https://wordpress-in-docker.mxd.cz/"
                ];

                foreach ($sites as $site) {
                    array_push($this->engineOutput, [
                        "hash" => hash("sha256", $site),
                        "url" => $site,
                        "alive" => rand(0, 1),
                        "time" => time() + (-1)^(rand(0, 1)) * rand(100, 500)
                    ]);
                }

                /*$this->engineOutput = [
                    "hash" => hash("sha256", "https://mon.n0p.cz/"),
                    "url" => "https://mon.n0p.cz/",
                    "alive" => 1,
                    "timestamp" => time()
                ];*/

                $this->writeJSON();
                break;

            case 'GetDetail':
                $this->engineOutput = [];
                $this->writeJSON();
                break;
            
            default:
                $this->statusMessage = "Unknown query.";
                $this->writeJSON(404);
                break;
        }
    }

    /**
     * writeJSON function
     * 
     * API return function
     */
    private function writeJSON($code = 200) {
        $query = empty($this->routePath[0]) ? null : $this->routePath[0];

        $apiHeader = [
            "name" => $this->apiName,
            "version" => $this->apiVersion,
            "processing_time_in_ms" => round((microtime(true) - $this->apiTimestampStart) * 1000, 2),
            "query" => $query,
            "message" => $this->statusMessage ?? "DATA OK",
            "status_code" => $code
        ];

        $dataOutput = [
            "api" => $apiHeader,
            "data" => $this->engineOutput ?? null
        ];

        header('Content-type: application/json');
        echo json_encode($dataOutput, JSON_PRETTY_PRINT);
        exit();
    }
}
