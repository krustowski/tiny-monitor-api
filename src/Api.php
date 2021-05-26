<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <tmv2@n0p.cz>
 * @license MIT
 *
 * @OA\Info(
 *      description="Docker-linked REST API server",
 *      title="tiny-monitor REST API",
 *      version="1.9",
 *      @OA\Contact(
 *          name="krustowski",
 *          email="tmv2@n0p.cz"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * ),
 * @OA\OpenApi(
 *      security={{"api_key_auth": {}}}
 * ),
 * @OA\Tag(
 *      name="group",
 *      description="tiny-monitor group operations"
 * ),
 * @OA\Tag(
 *      name="user",
 *      description="tiny-monitor user operations"
 * ),
 * @OA\Tag(
 *      name="host",
 *      description="tiny-monitor host operations"
 * ),
 * @OA\Tag(
 *      name="service",
 *      description="tiny-monitor service operations"
 * ),
 * @OA\Tag(
 *      name="check",
 *      description="tiny-monitor operations about service checkings"
 * ),
 * @OA\Tag(
 *      name="system",
 *      description="tiny-monitor system operations"
 * ),
 * @OA\Server(
 *      url="https://mon.n0p.cz/api/v2/",
 *      description="Docker-linked REST API server" 
 * ),
 * @OA\SecurityScheme(
 *      type="apiKey",
 *      in="header",
 *      securityScheme="api_key_auth",
 *      name="X-Api-Key"
 * )
 * */

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
    private $api_name = "tiny-monitor REST API";
    private $api_version = "1.9";
    private $api_usage = 0;
    private $api_timestamp_start;
    private $remote_address;
    private $status_message;
    private $user_agent;
    private $http_version = "";

    // API config vars
    private $log_file = "/dev/stdout";

    // API data vars
    private $engine_output = [];
    private $route_path;
    private $apikey;
    private $safe_GET = [];
    //private $safePOST = [];
    private $payload = [];

    //private $supervisor_apikey;

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

    public function __construct() 
    {
        // init
        $this->api_timestamp_start = (double) microtime(self::MICROTIME_AS_FLOAT) ?? null;
        $this->remote_address = $_SERVER["REMOTE_ADDR"] ?? null;
        $this->user_agent = "tiny-monitor bot / cURL " . curl_version()["version"] ?? null;
	    
        $this->checkDatabase();
        $this->checkApiUsage();

        // cleanse HTTP requests
        $this->safe_GET  = array_map("htmlspecialchars", $_GET);
        //$this->safePOST = array_map("htmlspecialchars", $_POST);

        $this->checkApiKey();

        // POST data, payload
        try {
            $json = file_get_contents('php://input');
            $this->payload = json_decode($json, self::JSON_ASSOCIATIVE) ?? null;
        } catch (Exception $e) {
            $this->status_message = $e;
            $this->writeJSON(code: 406);
        }

        // explode over full path query variable
        $this->route_path = explode('/', $this->safe_GET["fullPath"]) ?? null;

        // parse and exec an Api call
        $this->handleRequest();
    }

    /**
     * check for API key to be present and usable
     */
    private function checkApiKey()
    {
        // get API key
        $this->apikey = $this->safe_GET["apikey"] ?? \getallheaders()["X-Api-Key"] ?? null;

        if (!$this->apikey) {
            $this->status_message = "API key reuqired!";
            $this->writeJSON(code: 403);
        }

        $sql = new SQLite(DATABASE_FILE);

        if ($sql->querySingle("SELECT COUNT(*) as count FROM monitor_users WHERE user_apikey='" . $this->apikey . "' AND user_activated='1'") == 0) {
            $this->status_message = "This API key is not authorized.";
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
                    '" . SUPERVISOR_APIKEY . "',
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
                    service_active BOOLEAN,
                    service_status BOOLEAN,
                    service_public BOOLEAN,
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
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }        
    }

    /**
     * reads monitor_usage table for API usage
     * 
     * @return void
     */
    private function checkApiUsage() 
    {
        $sql = new SQLite(DATABASE_FILE);

        // insert new usage
        $sql->querySingle("INSERT INTO monitor_usage(ip_address, time_stamp) VALUES (
            '" . $this->remote_address . "', 
            '" . time() . "'
            )");

        // flush old entries => CRON/Celery
        //$sql->querySingle("DELETE FROM api_usage WHERE timestamp < " . time() - 3600);

        // access from ip_address within 1 hour
        //$this->apiUsage = $sql->querySingle("SELECT COUNT(*) as count FROM api_usage WHERE ip_address='" . $this->remoteAddress . "'");
        $this->api_usage = $sql->querySingle("SELECT COUNT(*) as count FROM monitor_usage WHERE ip_address='" . $this->remote_address . "' AND time_stamp BETWEEN " . time() - self::API_USAGE_TIME_LIMIT . " AND " . time());       

        if ($this->api_usage > self::API_USAGE_LIMIT) {
            $this->api_usage = self::API_USAGE_LIMIT;
            $this->writeJSON(code: 429);
        }
    }

    /** property function -- addition
     * 
     * @return void
     */
    private function addProperty(string $property)
    {       
        $data = $this->payload;

        // property shoud be already defined in function call, therefore there is no need to mention it in message
        if (!$property || !$data) {
            $this->status_message = "Wrong JSON payload structure! Not Acceptable!";
            $this->writeJSON(code: 406);
        }

        // prefixes and suffixes for database transactions
        $property_index = $property . "_name";
        $property_table = "monitor_" . $property . "s";

        # XSS prevention/anti-sql-injection __experiment__
        if (!$data || empty($data) || !$data[$property_index]) {
            $this->status_message = "Wrong JSON payload structure! Not Acceptable!";
            $this->writeJSON(code: 406);
        }

        try {
            $sql = new SQLite(DATABASE_FILE);

            $control_query = "SELECT COUNT(*) as count from $property_table WHERE $property_index = '" . $data[$property_index] . "'";
            $num_rows = $sql->query($control_query)->fetchArray()["count"];
                    
            if ($num_rows > 0) {
                $this->status_message = "This $property already exists!";
                $this->writeJSON(code: 406);
            }

            $sql->query("INSERT into $property_table ($property_index) VALUES ('" . $data[$property_index] . "')");
        }
        catch (Exception $e) {
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }   

        $this->engine_output = $data;
        $this->writeJSON();
    }

    /** property function -- fetching
     * 
     * @return void
     */
    private function getPropertyList(string $property)
    {       
        /*if (!$property) {
            $this->statusMessage = "Wrong JSON payload structure! Not Acceptable!";
            $this->writeJSON(code: 406);
        }*/

        $property_id = $property . "_id";
        $property_index = $property . "_name";
        $property_table = "monitor_" . $property . "s";

        try {
            $sql = new SQLite(DATABASE_FILE);
            $res = $sql->query("SELECT * FROM $property_table");
        }
        catch (Exception $e) {
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }   

        // fetch rows
        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_BOTH)) {
            // https://stackoverflow.com/questions/15290811/php-json-encode-issue-with-array-0-key
            $rows[] = (object)[$row[$property_id] => $row[$property_index]];
            // or try this
            // $rows[] = [$row[$property_id], $row[$property_index]];
        }

        $this->engine_output = $rows;
        $this->writeJSON();
    }

    /**
     * property function -- detail fetching
     * 
     * @return void
     */
    private function getPropertyDetail(string $property) 
    {
        $data = $this->payload;

        if (!$property || !$data) {
            $this->status_message = "Wrong JSON payload structure! Not Acceptable!";
            $this->writeJSON(code: 406);
        }

        $property_id = $property . "_id";
        $property_index = $property . "_id";
        $property_table = "monitor_" . $property . "s";

        try {
            $sql = new SQLite(DATABASE_FILE);

            $control_query = "SELECT COUNT(*) as count from $property_table WHERE $property_index = '" . $data[$property_index] . "'";
            $num_rows = $sql->query($control_query)->fetchArray()["count"];

            if ($num_rows == 0) {
                $this->status_message = "This $property does not exist!";
                $this->writeJSON(code: 406);
            }

            // for over StructModel::propertyModel
            $rows = $sql->query("SELECT * FROM $property_table  where $property_index='" . $data[$property_index] . "'")->fetchArray(SQLITE3_ASSOC);

            $this->engine_output = $rows;

            //$res = $sql->query("DELETE from $property_table where $property_index='" . $data[$property_index] . "'");
        }
        catch (Exception $e) {
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }

        $this->writeJSON();
    }

    /** property function -- modification
     * 
     * @return void
     */
    private function setPropertyDetail(string $property)
    {       
        $data = $this->payload;

        if (!$property || !$data) {
            $this->status_message = "Wrong JSON payload structure! Not Acceptable!";
            $this->writeJSON(code: 406);
        }

        $property_id = $property . "_id";
        $property_index = $property . "_id";
        $property_table = "monitor_" . $property . "s";

        try {
            $sql = new SQLite(DATABASE_FILE);

            $control_query = "SELECT COUNT(*) as count from $property_table WHERE $property_index = '" . $data[$property_index] . "'";
            $num_rows = $sql->query($control_query)->fetchArray()["count"];

            if ($num_rows == 0) {
                $this->status_message = "This $property does not exist!";
                $this->writeJSON(code: 406);
            }

            foreach ($data as $column => $val) {
                if ($column == $property_id)
                    continue;

                @$sql->query("UPDATE $property_table set $column = '$val' where $property_index = '" . $data[$property_index] . "'");
            }

        }
        catch (Exception $e) {
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }

        // for over StructModel::propertyModel
        $rows = $sql->query("SELECT * FROM $property_table  where $property_index='" . $data[$property_index] . "'")->fetchArray(SQLITE3_ASSOC);

        $this->engine_output = $rows;

        $this->writeJSON();
    }

    /** property function -- erasing
     * 
     * @return void
     */
    private function deleteProperty(string $property)
    {       
        $data = $this->payload;

        if (!$property || !$data) {
            $this->status_message = "Wrong JSON payload structure! Not Acceptable!";
            $this->writeJSON(code: 406);
        }

        $property_index = $property . "_id";
        $property_table = "monitor_" . $property . "s";

        try {
            $sql = new SQLite(DATABASE_FILE);

            $control_query = "SELECT COUNT(*) as count from $property_table WHERE $property_index = '" . $data[$property_index] . "'";
            $num_rows = $sql->query($control_query)->fetchArray()["count"];

            if ($num_rows == 0) {
                $this->status_message = "This $property does not exist!";
                $this->writeJSON(code: 406);
            }

            $res = $sql->query("DELETE from $property_table where $property_index='" . $data[$property_index] . "'");
        }
        catch (Exception $e) {
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }

        $this->engine_output = $data;
        $this->writeJSON();
    }

    /**
     * entrypoint for all API calls
     * 
     * @return void
     */
    private function handleRequest() 
    {
        $function = $this->route_path[0];

        switch ($function) {
            /**
             * @OA\Get(
             *     path="/GetStatusAllTest",
             *     tags={"system"},
             *     summary="run the example curl execution",
             *     @OA\Response(
             *          response="200", 
             *          description="Run test over all code-defined sites (test function)")
             *     ),
             *     @OA\Response(
             *          response="default", 
             *          description="Server internal error, engine error, parse error")
             *     )
             */
            case 'GetStatusAllTest':
                //$engine = new Engine();
                $this->engine_output = Engine::checkSite(["https://mon.n0p.cz"]); //$engine->checkSite($this->testSites);
                $this->writeJSON();
                break;

            /**
             * @OA\Get(
             *     path="/GetSystemStatus",
             *     tags={"system"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetSystemStatus':
                $sql = new SQLite(DATABASE_FILE);

                // list tables
                $tables_query = $sql->query("SELECT name FROM sqlite_master WHERE type='table'");
                while ($table = $tables_query->fetchArray(SQLITE3_ASSOC)) {
                    if ($table["name"] != "sqlite_sequence") {
                        $tables[] = $table["name"];
                    }
                }

                $this->engine_output = [
                    "remote_address" => $this->remote_address,
                    "php_version" => phpversion() ?? null,
                    "curl_version" => \curl_version()["version"] ?? null,
                    "sqlite_version" => $sql->version()["versionString"] ?? null,
                    "system_load" => \sys_getloadavg() ?? null,
                    "database_tables" => $tables //$sql->querySingle('SELECT COUNT(*) as count FROM api_usage')
                ];

                $this->writeJSON();
                break;

            /**
             * @OA\Get(
             *     path="/GetStatus",
             *     tags={"system"},
             *     @OA\Response(response="200", description="Get system components version and statuses inc. load")
             * )
             */
            case 'GetStatus':
                $sites = ["https://mon.n0p.cz/"];

                //$this->engineOutput = Engine\getStatus();
                foreach ($sites as $site) {
                    $engine_output[] = [
                        "hash" => hash("sha256", $site),
                        "url" => $site,
                        "alive" => rand(0, 1),
                        "time" => time() + (-1)^(rand(0, 1)) * rand(100, 500)
                    ];
                }

                $this->writeJSON();
                break;

            /**
             * @OA\Get(
             *     path="/GetPublicStatus",
             *     tags={"system"},
             *     @OA\Response(response="200", description="Get system components version and statuses inc. load")
             * )
             */
            case 'GetPublicStatus':
                $sites = $this->test_sites;

                //$this->engineOutput = Engine\getStatus();
                foreach ($sites as $site) {
                    array_push($this->engine_output, [
                        "hash" => hash("sha256", $site),
                        "url" => $site,
                        "alive" => rand(0, 1),
                        "time" => time() + (-1)^(rand(0, 1)) * rand(100, 500)
                    ]);
                }

                $this->writeJSON();
                break;

            /**
             * @OA\Get(
             *     path="/GetDetail",
             *     tags={"system"},
             *     @OA\Response(response="200", description="Get system components version and statuses inc. load")
             * )
             */
            case 'GetStatusDetail':
                if (empty($this->route_path[1])) {
                    $this->status_message = "Hash list is required for this function!";
                    $this->writeJSON(code: 400);
                }

                $list = explode(",", $this->route_path[1]);

                // LASAGNA only!
                foreach($list as $item) {
                    $this->engine_output[] = [
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
                    ];
                }

                //$this->engineOutput = Engine\getDetail($list);
                $this->writeJSON();
                break;

            /**
             * @OA\Get(
             *     path="/AddGroup",
             *     tags={"group"},
             *     @OA\Response(response="200", description="Get system components version and statuses inc. load")
             * )
             */
            case 'AddGroup':
                $this->addProperty(property: "group");
                break;

            /**
             * @OA\Get(
             *     path="/GetGroupList",
             *     tags={"group"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case "GetGroupList":
                $this->getPropertyList(property: "group");
                break;

            /**
             * @OA\Get(
             *     path="/GetGroupDetail",
             *     tags={"group"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetGroupDetail':
                $this->getPropertyDetail(property: "group");
                break;

            /**
             * @OA\Get(
             *     path="/SetGroupDetail",
             *     tags={"group"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'SetGroupDetail':
                $this->setPropertyDetail(property: "group");
                break;

            /**
             * @OA\Get(
             *     path="/DeleteGroup",
             *     tags={"group"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'DeleteGroup':
                $this->deleteProperty(property: "group");
                break;

            /**
             * @OA\Get(
             *     path="/AddHost",
             *     tags={"host"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'AddHost':
                $this->addProperty(property: "host");
                break;
            
            /**
             * @OA\Get(
             *     path="/GetHostList",
             *     tags={"host"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetHostList':
                $this->getPropertyList(property: "host");
                break;

            /**
             * @OA\Get(
             *     path="/GetHostDetail",
             *     tags={"host"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetHostDetail':
                $this->getPropertyDetail(property: "host");
                break;

            /**
             * @OA\Get(
             *     path="/SetHostDetail",
             *     tags={"host"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'SetHostDetail':
                $this->setPropertyDetail(property: "host");
                break;

            /**
             * @OA\Get(
             *     path="/DeleteHost",
             *     tags={"host"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'DeleteHost':
                $this->deleteProperty(property: "host");
                break;
            /**
             * @OA\Get(
             *     path="/AddUser",
             *     tags={"user"},
             *     @OA\Response(
             *          response="200", 
             *          description="Add new user")
             * )
             */
                case 'AddUser':
                $this->addProperty(property: "user");
                break;
                
            /**
             * @OA\Get(
             *     path="/GetUserList",
             *     tags={"user"},
             *     @OA\Response(
             *          response="200", 
             *          description="Get list of all users")
             * )
             */
            case 'GetUserList':
                $this->getPropertyList(property: "user");
                break;

            /**
             * @OA\Get(
             *     path="/GetUserDetail",
             *     tags={"user"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetUserDetail':
                $this->getPropertyDetail(property: "user");
                break;

            /**
             * @OA\Get(
             *     path="/SetUserDetail",
             *     tags={"user"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'SetUserDetail':
                $this->setPropertyDetail(property: "user");
                break;

            /**
             * @OA\Get(
             *     path="/DeleteUser",
             *     tags={"user"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'DeleteUser':
                $this->deleteProperty(property: "user");
                break;

            /**
             * @OA\Get(
             *     path="/AddService",
             *     tags={"service"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'AddService':
                $this->addProperty(property: "service");
                break;

            /**
             * @OA\Get(
             *     path="/GetServiceList",
             *     tags={"service"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetServiceList':
                $this->getPropertyList(property: "service");
                break;

            /**
             * @OA\Get(
             *     path="/GetServiceDetail",
             *     tags={"service"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'GetServiceDetail':
                $this->getPropertyDetail(property: "service");
                break;

            /**
             * @OA\Get(
             *     path="/SetServiceDetail",
             *     tags={"service"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'SetServiceDetail':
                $this->setPropertyDetail(property: "service");
                break;

            /**
             * @OA\Get(
             *     path="/DeleteService",
             *     tags={"service"},
             *     @OA\Response(response="200", description="")
             * )
             */
            case 'DeleteService':
                $this->deleteProperty(property: "service");
                break;
                
            default:
                // TODO
                // try cases across loaded modules
                // $this->scanModules();
                
                $this->status_message = "Unknown function. Please, see API documentation at doc/.";
                $this->writeJSON(code: 404);
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
        $function = empty($this->route_path[0]) ? null : $this->route_path[0];

        $api_header = [
            "timestamp" => time() ?? null,
            "name" => $this->api_name,
            "version" => $this->api_version,
            "processing_time_in_ms" => round((microtime(true) - $this->api_timestamp_start) * 1000, 2),
            "api_quota_hourly" => self::API_USAGE_LIMIT,
            "api_usage_hourly" => $this->api_usage,
            "function" => $function,
            "message" => $this->status_message ?? self::HTTP_CODE[$code],
            "status_code" => $code
        ];

        $data_output = [
            "api" => $api_header,
            "data" => $this->engine_output
        ];

        header("User-Agent: " . $this->user_agent);
        header("$this->http_version $code " . self::HTTP_CODE[$code]);
        header("Content-type: application/json");
        
        // JSON_FORCE_OBJECT: supervisor user_id is 0, therefore we need to explicitly print "0" as array key
        // https://stackoverflow.com/questions/15290811/php-json-encode-issue-with-array-0-key
        // https://www.php.net/manual/en/function.json-encode.php
        echo json_encode($data_output, JSON_PRETTY_PRINT);
        exit;
    }
}
