<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

use Exception;
use \SQLite3 as SQLite;

/**
 * Api class
 * 
 * contains request handling methods
 */
class Api 
{
    // API header vars
    private $apiName = "tiny-monitor REST API";
    private $apiVersion = "v1";
    private $apiUsage = 0;
    private $apiTimestampStart;
    private $remoteAddress;
    private $statusMessage;
    public  $userAgent;

    // API config vars
    private $logFile = "/dev/stdout";

    // API data vars
    private $engineOutput = [];
    private $routePath;
    private $apiKey;
    private $safeGET = [];
    //private $safePOST = [];
    //private $dataPayload = [];

    private $supervisorApiKey;

    // constants
    const JSON_ASSOCIATIVE = true;
    const MICROTIME_AS_FLOAT = true;
    const API_USAGE_LIMIT = 600;
    const API_USAGE_TIME_LIMIT = 3600; // seconds

    const HTTP_CODE = [
        200 => "OK",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        406 => "Not Acceptable",
        410 => "Gone",
        420 => "Enhance Your Calm",
        429 => "Too Many Requests",
        500 => "Internal Server Error",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
    ];

    private $testSites = [
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

    public function __construct() 
    {
        // init
        $this->apiTimestampStart = (double) microtime(self::MICROTIME_AS_FLOAT) ?? null;
        $this->remoteAddress = $_SERVER["REMOTE_ADDR"] ?? null;
        $this->userAgent = "tiny-monitor bot / cURL " . curl_version()["version"] ?? null;
        $this->supervisorApiKey = getenv('SUPERVISOR_APIKEY');
	    
        $this->checkDatabase();
        $this->checkApiUsage();

        // cleanse HTTP requests
        $this->safeGET = (array) array_map("htmlspecialchars", $_GET);
        $this->safePOST = (array) array_map("htmlspecialchars", $_POST);

        $this->checkApiKey();

        // POST data, payload
        $this->dataPayload = json_decode(file_get_contents('php://input'), self::JSON_ASSOCIATIVE) ?? null;

        // explode over full path query variable
        $this->routePath = explode('/', $this->safeGET["fullPath"]) ?? null;

        // parse and exec an Api call
        $this->handleRequest();
    }

    /**
     * check for API key to be present and usable
     */
    private function checkApiKey()
    {
        // get API key
        $this->apiKey = $this->safeGET["apikey"] ?? null;

        if (!$this->apiKey) {
            $this->statusMessage = "API key reuqired!";
            $this->writeJSON(code: 403);
        }

        $sql = new SQLite(DATABASE_FILE);

        if ($sql->querySingle("SELECT COUNT(*) as count FROM monitor_users WHERE user_apikey='" . $this->apiKey . "' AND user_activated='1'") == 0) {
            $this->statusMessage = "This API key is not authorized.";
            $this->writeJSON(code: 401);
        }
    }

    /**
     * check for SQLite database file to be present, otherwise creates a new schema
     *
     * @return void
     */
    private function checkDatabase() 
    {
        try {
            $sql = new SQLite(DATABASE_FILE);

            // create database schema
            $queries = [
                "CREATE TABLE IF NOT EXISTS monitor_usage(
                    usage_id INTEGER PRIMARY KEY AUTOINCREMENT, 
                    ip_address VARCHAR, 
                    time_stamp INTEGER
                )",
                "CREATE TABLE IF NOT EXISTS monitor_groups(
                    group_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    group_name VARCHAR
                )",
                "CREATE TABLE IF NOT EXISTS monitor_users(
                    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_name VARCHAR,
                    user_apikey TEXT,
                    user_ip_address VARCHAR,
                    user_last_access TIMESTAMP,
                    user_activated BOOLEAN,
                    group_id INTEGER
                )",
                "INSERT OR IGNORE INTO monitor_users(user_id, user_name, user_apikey, user_activated) VALUES (
                    0, 
                    'supervisor', 
                    '" . $this->supervisorApiKey . "',
                    1
                    )",
                "CREATE TABLE IF NOT EXISTS monitor_hosts(
                    host_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    host_type VARCHAR,
                    host_name VARCHAR,
                    group_id INTEGER
                )",
                "CREATE TABLE IF NOT EXISTS monitor_services(
                    service_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    service_name VARCHAR,
                    service_type VARCHAR,
                    service_endpoint VARCHAR,
                    service_port INTEGER,
                    service_downtime TIME,
                    service_paused BOOLEAN,
                    service_failed BOOLEAN,
                    service_last_test TIMESTAMP
                    group_id INTEGER,
                    host_id INTEGER
                )"
            ];

            foreach ($queries as $q) {
                $sql->exec($q);
            }
        }
        catch (Exception $e) {
            $this->statusMessage = $e->getMessage();
            $this->writeJSON(503);
        }        
    }

    /**
     * checks Redis cache for API usage
     * 
     * @return void
     */
    private function checkApiUsage() 
    {
        $sql = new SQLite(DATABASE_FILE);

        // insert new usage
        $sql->querySingle("INSERT INTO monitor_usage(ip_address, time_stamp) VALUES (
            '" . $this->remoteAddress . "', 
            '" . time() . "'
            )");

        // flush old entries => CRON/Celery
        //$sql->querySingle("DELETE FROM api_usage WHERE timestamp < " . time() - 3600);

        // access from ip_address within 1 hour
        //$this->apiUsage = $sql->querySingle("SELECT COUNT(*) as count FROM api_usage WHERE ip_address='" . $this->remoteAddress . "'");
        $this->apiUsage = $sql->querySingle("SELECT COUNT(*) as count FROM monitor_usage WHERE ip_address='" . $this->remoteAddress . "' AND time_stamp BETWEEN " . time() - self::API_USAGE_TIME_LIMIT . " AND " . time());       

        if ($this->apiUsage > self::API_USAGE_LIMIT) {
            $this->apiUsage = self::API_USAGE_LIMIT;
            $this->writeJSON(429);
        }
    }

    /**
     * entrypoint for all API calls
     * 
     * @return void
     */
    private function handleRequest() 
    {
        switch ($this->routePath[0]) {
            case 'GetStatusAllTest':
                //$engine = new Engine();
                $this->engineOutput = Engine::checkSite($this->testSites); //$engine->checkSite($this->testSites);
                $this->writeJSON();
                break;

	    case 'GetSystemStatus':
	    	$sql = new SQLite(DATABASE_FILE);

            // list tables
            $tablesquery = $sql->query("SELECT name FROM sqlite_master WHERE type='table';");
            while ($table = $tablesquery->fetchArray(SQLITE3_ASSOC)) {
                if ($table['name'] != "sqlite_sequence") {
                    $tables[] = $table['name'];
                }
            }
	    	$this->engineOutput = [
                "remote_address" => $this->remoteAddress,
                "curl_version" => \curl_version()["version"] ?? null,
                "sqlite_version" => $sql->version()["version_string"] ?? null,
                "system_load" => \sys_getloadavg() ?? null,
                "database_tables" => $tables //$sql->querySingle('SELECT COUNT(*) as count FROM api_usage')
		    ];

	        $this->writeJSON();
	    	break;

            case 'GetStatus':
                $sites = $this->testSites;

                //$this->engineOutput = Engine\getStatus();

                foreach ($sites as $site) {
                    array_push($this->engineOutput, [
                        "hash" => hash("sha256", $site),
                        "url" => $site,
                        "alive" => rand(0, 1),
                        "time" => time() + (-1)^(rand(0, 1)) * rand(100, 500)
                    ]);
                }

                $this->writeJSON();
                break;

            case 'GetDetail':
                if (empty($this->routePath[1])) {
                    $this->statusMessage = "Hash list is required for this function!";
                    $this->writeJSON(400);
                }

                $list = explode(",", $this->routePath[1]);

                // LASAGNA only!
                foreach($list as $item) {
                    \array_push($this->engineOutput, [
                        "hash" => $item,
                        "time" => '$last_metering',
                        "downtime" => '$downtime',
                        "uptime" => '$uptime',
                        "availability60" => '$availability60',
                        "availability30" => '$availability30',
                        "availability14" => '$availability14',
                        "availability7" => '$availability7',
                        "availability1" => '$availability1',
                        "memavail" => '$memavail',
                        "diskavail" => '$diskavail',
                        "roundtrip" => '$roundtrip',
                        "roundtrip60" => '$roundtrip60',
                        "roundtrip30" => '$roundtrip30',
                        "roundtrip14" => '$roundtrip14',
                        "roundtrip7" => '$roundtrip7',
                        "roundtrip1" => '$roundtrip1'
                    ]);
                }

                case 'Post':
                    break;

                //$this->engineOutput = Engine\getDetail($list);
                $this->writeJSON();
                break;

            /**
             * POST /AddSite
             * 
             * @param string $url site URL
             * @param int $port site custom port (optional)
             */
            case 'AddSite':
                break;

            case 'AddService':
                break;
            
            default:
                $this->statusMessage = "Unknown function. Please, see API documentation.";
                $this->writeJSON(404);
                break;
        }
    }

    /**
     * API exit method -- outputs PRETTY JSON
     * 
     * @param int $code HTTP code (def. 200)
     * @return void
     */
    private function writeJSON(int $code = 200) 
    {
        $function = empty($this->routePath[0]) ? null : $this->routePath[0];

        $apiHeader = [
            "timestamp" => time() ?? null,
            "name" => $this->apiName,
            "version" => $this->apiVersion,
            "processing_time_in_ms" => round((microtime(true) - $this->apiTimestampStart) * 1000, 2),
            "api_quota_hourly" => self::API_USAGE_LIMIT,
            "api_usage_hourly" => $this->apiUsage,
            "function" => $function,
            "message" => $this->statusMessage ?? self::HTTP_CODE[$code] ?? "DATA OK",
            "status_code" => $code
        ];

        $dataOutput = [
            "api" => $apiHeader,
            "data" => $this->engineOutput
        ];

        header("User-Agent: " . $this->userAgent);
	    header("HTTP/1.1 ${code} " . self::HTTP_CODE[$code]);
        header("Content-type: application/json");
        
        echo json_encode($dataOutput, JSON_PRETTY_PRINT);
        exit;
    }
}
