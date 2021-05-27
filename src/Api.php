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
 * // the port HAS TO be dynamic!
 * @OA\Server(
 *      url="http://localhost:8051/api/v2/",
 *      description="Docker-compose-linked private REST API server" 
 * ),
 * @OA\Server(
 *      url="https://mon.n0p.cz/api/v2/",
 *      description="Public self-hosted REST API Server" 
 * ),
 * @OA\SecurityScheme(
 *      type="apiKey",
 *      in="header",
 *      securityScheme="api_key_auth",
 *      name="X-Api-Key"
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
 *      name="status",
 *      description="tiny-monitor operations about service/host/group statuses"
 * ),
 * @OA\Tag(
 *      name="system",
 *      description="tiny-monitor system operations"
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

    // API config vars
    private $log_file = "/dev/stdout";

    // API data vars
    private $engine_output = [];
    private $route_path;
    private $apikey;
    private $api_identity;
    private $api_groups;
    private $api_acl;
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
        405 => "Method Not Allowed",
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
        $this->apikey = \getallheaders()["X-Api-Key"] ?? $this->safe_GET["apikey"] ?? null;

        if (!$this->apikey) {
            $this->status_message = "API key reuqired!";
            $this->writeJSON(code: 403);
        }

        $sql = new SQLite(DATABASE_FILE);

        if ($sql->querySingle("SELECT COUNT(*) as count FROM monitor_users WHERE user_apikey='" . $this->apikey . "' AND user_activated='1'") == 0) {
            $this->status_message = "This API key is not authorized.";
            $this->writeJSON(code: 401);
        }

        $rows = $sql->query("SELECT * FROM monitor_users  where user_apikey='" . $this->apikey . "'")->fetchArray(SQLITE3_ASSOC) ?? null;

        if (!$rows) {
            $this->status_message = "SQLite3 identity fetch error!";
            $this->writeJSON(code: 500);
        }

        $this->api_identity = $rows["user_id"];
        $this->api_groups = $rows["group_id"];
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
                    group_name VARCHAR,
                    group_type VARCHAR
                )",
                "CREATE TABLE IF NOT EXISTS monitor_users(
                    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_name VARCHAR,
                    user_acl VARCHAR,
                    user_apikey TEXT,
                    user_ip_address VARCHAR,
                    user_last_access TIMESTAMP,
                    user_activated BOOLEAN,
                    group_id INTEGER
                )",
                "INSERT OR IGNORE INTO monitor_users(user_id, user_name, user_apikey, user_activated, group_id) VALUES (
                    0, 
                    'supervisor', 
                    '" . SUPERVISOR_APIKEY . "',
                    1,
                    0
                )",
                "INSERT OR IGNORE INTO monitor_users(user_id, user_name, user_apikey, user_activated, group_id) VALUES (
                    1, 
                    'public_agent', 
                    '" . PUBLIC_APIKEY . "',
                    1,
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
                    service_desc TEXT,
                    service_endpoint VARCHAR,
                    service_port INTEGER,
                    service_downtime TIME,
                    service_activated BOOLEAN,
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
                $this->engine_output = $data;
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
    private function getPropertyList(string $property, bool $return = false)
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

        if (!$return)
            $this->writeJSON();
    }

    /** property function -- fetching
     * 
     * @return void
     */
    private function getPublicList(string $property = "service", bool $return = false)
    {       
        $property_id = $property . "_id";
        $property_index = $property . "_name";
        $property_table = "monitor_" . $property . "s";

        try {
            $sql = new SQLite(DATABASE_FILE);
            $res = $sql->query("SELECT * FROM $property_table WHERE service_activated = '1' AND service_public = '1'");
        }
        catch (Exception $e) {
            $this->status_message = $e->getMessage();
            $this->writeJSON(503);
        }   

        // fetch rows
        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_BOTH)) {
            // https://stackoverflow.com/questions/15290811/php-json-encode-issue-with-array-0-key
            $rows[] = (object)[
                //$row[$property_id] => $row[$property_index]
                "service_id" => $row[$property_id],
                "service_name" => $row[$property_index],
                "service_desc" => $row["service_desc"],
                "service_endpoint" => $row["service_endpoint"],
                "service_status" => $row["service_status"]
            ];
        }

        $this->engine_output = $rows;

        if (!$return)
            $this->writeJSON();
    }

    /**
     * property function -- detail fetching
     * 
     * @return void 
     */
    private function getPropertyDetail(string $property, bool $return = false) 
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

        if (!$return)
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
             *     tags={"status"},
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
             *      path="/GetPublicStatus",
             *      tags={"status"},
             *      @OA\Response(
             *          response="200", 
             *          description="All public services listed"
             *      )
             * )
             */
            case 'GetPublicStatus':
                $this->getPublicList(property: "service", return: false);
                //$this->engineOutput = Engine\getStatus();
                break;

            /**
             * @OA\Post(
             *   path="/GetStatusDetail",
             *      tags={"status"},
             *      @OA\RequestBody(
             *          description="test service specified by service_id",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="service_id",
             *                      type="int64"
             *                  ),
             *                  example={"service_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200",
             *          description="Service service_id queued for test"
             *      )
             * )
             */
            case 'GetStatusDetail':
                if (empty($this->route_path[1])) {
                    $this->status_message = "Hash list is required for this function!";
                    $this->writeJSON(code: 400);
                }

                $list = explode(",", $this->route_path[1]);

                //$this->engineOutput = Engine\getDetail($list);
                $this->writeJSON();
                break;

            /**
             * @OA\Post(
             *     path="/AddGroup",
             *     tags={"group"},
             *     @OA\RequestBody(
             *          description="place parameters for group addition",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="group_name",
             *                      type="int64"
             *                  ),
             *                  example={"group_name": "new_snake_case_group_name"}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Group added"
             *     )
             * )
             */
            case 'AddGroup':
                $this->addProperty(property: "group");
                break;

            /**
             * @OA\Get(
             *     path="/GetGroupList",
             *     tags={"group"},
             *      @OA\Response(
             *         response=200,
             *         description="Got group list"
             *     )
             * )
             */
            case "GetGroupList":
                $this->getPropertyList(property: "group");
                break;

            /**
             * @OA\Post(
             *     path="/GetGroupDetail",
             *     tags={"group"},
             *     @OA\RequestBody(
             *          description="place parameters for group detail",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="group_id",
             *                      type="int64"
             *                  ),
             *                  example={"group_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Got group detail"
             *     )
             * )
             */
            case 'GetGroupDetail':
                $this->getPropertyDetail(property: "group");
                break;

            /**
             * @OA\Put(
             *      path="/SetGroupDetail",
             *      tags={"group"},
             *      @OA\RequestBody(
             *          description="place parameters for group modification",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="group_id",
             *                      type="int64"
             *                  ),
             *                  example={
             *                      "group_id": 1,
             *                      "group_name": "new_group_name_if_exists"
             *                  }
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Group modified"
             *     )
             * )
             */
            case 'SetGroupDetail':
                $this->setPropertyDetail(property: "group");
                break;

            /**
             * @OA\Delete(
             *      path="/DeleteGroup",
             *      tags={"group"},
             *      @OA\RequestBody(
             *          description="place parameters for group deletion",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="group_id",
             *                      type="int64"
             *                  ),
             *                  example={"group_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Group deleted"
             *     )
             * )
             */
            case 'DeleteGroup':
                $this->deleteProperty(property: "group");
                break;

            /**
             * @OA\Post(
             *      path="/AddHost",
             *      tags={"host"},
             *      @OA\RequestBody(
             *          description="place parameters for host addition",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="host_name",
             *                      type="int64"
             *                  ),
             *                  example={"host_name": "testing-vps-name"}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Host added"
             *     )
             * )
             */
            case 'AddHost':
                $this->addProperty(property: "host");
                break;
            
            /**
             * @OA\Get(
             *      path="/GetHostList",
             *      tags={"host"},
             *      @OA\Response(
             *         response=200,
             *         description="Got host list"
             *     )
             * )
             */
            case 'GetHostList':
                $this->getPropertyList(property: "host");
                break;

            /**
             * @OA\Post(
             *      path="/GetHostDetail",
             *      tags={"host"},
             *      @OA\RequestBody(
             *          description="place parameters for host detail",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="host_id",
             *                      type="int64"
             *                  ),
             *                  example={"host_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Got host detail"
             *     )
             * )
             */
            case 'GetHostDetail':
                $this->getPropertyDetail(property: "host");
                break;

            /**
             * @OA\Put(
             *     path="/SetHostDetail",
             *     tags={"host"},
             *     @OA\RequestBody(
             *          description="place parameters for host modification",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="host_id",
             *                      type="int64"
             *                  ),
             *                  @OA\Property(
             *                      property="host_name",
             *                      type="string"
             *                  ),
             *                  @OA\Property(
             *                      property="host_type",
             *                      type="string"
             *                  ),
             *                  @OA\Property(
             *                      property="group_id",
             *                      type="[integer array]"
             *                  ),
             *                  example={
             *                      "host_id": 1,
             *                      "host_name": "new_snake_cased_hostname",
             *                      "host_type": "VPS",
             *                      "group_id": {1, 2, 3, 4}
             *                  }
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Host modified"
             *     )
             * )
             */
            case 'SetHostDetail':
                $this->setPropertyDetail(property: "host");
                break;

            /**
             * @OA\Delete(
             *     path="/DeleteHost",
             *     tags={"host"},
             *     @OA\RequestBody(
             *          description="place parameters for host deletion",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="host_id",
             *                      type="int64"
             *                  ),
             *                  example={"host_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="Host deleted"
             *     )
             * )
             */
            case 'DeleteHost':
                $this->deleteProperty(property: "host");
                break;

            /**
             * @OA\Post(
             *      path="/AddUser",
             *      tags={"user"},
             *      @OA\RequestBody(
             *          description="place parameters for user creation",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="user_name",
             *                      type="string"
             *                  ),
             *                  example={"user_name": "snake_cased_nickname"}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *         response=200,
             *         description="New user added"
             *     )
             * )
             */
                case 'AddUser':
                $this->addProperty(property: "user");
                break;

            /**
             * @OA\Get(
             *      path="/GetUserList",
             *      tags={"user"},
             *      @OA\Response(
             *         response=200,
             *         description="Got list of all users"
             *     )
             * )
             */
            case 'GetUserList':
                $this->getPropertyList(property: "user");
                break;

            /**
             * @OA\Post(
             *      path="/GetUserDetail",
             *      tags={"user"},
             *      @OA\RequestBody(
             *          description="place user_id to get its info",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="user_id",
             *                      type="int64"
             *                  ),
             *                  example={"user_id": 2}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200", 
             *          description="Got explicit user detail"
             *      )
             * )
             */
            case 'GetUserDetail':
                $this->getPropertyDetail(property: "user");
                break;

            /**
             * @OA\Put(
             *      path="/SetUserDetail",
             *      tags={"user"},
             *      @OA\RequestBody(
             *          description="place user parameters to (re)set",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="user_id",
             *                      type="int64"
             *                  ),
             *                  @OA\Property(
             *                      property="user_name",
             *                      type="string"
             *                  ),
             *                  @OA\Property(
             *                      property="user_activated",
             *                      type="boolean"
             *                  ),
             *                  @OA\Property(
             *                      property="group_id",
             *                      type="[integer array]"
             *                  ),
             *                  example={
             *                      "user_id": 2, 
             *                      "user_name": "new_snake_case_nickname",
             *                      "user_activated": 0,
             *                      "group_id": {1, 999}
             *                  }
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200", 
             *          description=""
             *      )
             * )
             */
            case 'SetUserDetail':
                $this->setPropertyDetail(property: "user");
                break;

            /**
             * @OA\Delete(
             *      path="/DeleteUser",
             *      tags={"user"},
             *      @OA\RequestBody(
             *          description="place user_id to delete it",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="user_id",
             *                      type="int64"
             *                  ),
             *                  example={"user_id": 2}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200", 
             *          description="User deleted"
             *      )
             * )
             */
            case 'DeleteUser':
                $this->deleteProperty(property: "user");
                break;

            /**
             * @OA\Post(
             *      path="/AddService",
             *      summary="add new service",
             *      tags={"service"},
             *      @OA\RequestBody(
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  example={
             *                      "service_name": "SSH",
             *                      "service_type": "port",
             *                      "service_desc": "SSH port",
             *                      "service_endpoint": "telnet://localhost",
             *                      "service_port": 22,
             *                      "service_activated": 1,
             *                      "service_public": 0,
             *                      "group_id": {1, 4},
             *                      "host_id": 1
             *                  }
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200", 
             *          description="New service addded"
             *      )
             * )
             */
            case 'AddService':
                $this->addProperty(property: "service");
                break;

            /**
             * @OA\Get(
             *      path="/GetServiceList",
             *      tags={"service"},
             *      @OA\Response(
             *          response="200", 
             *          description="Got services list"
             *      )
             * )
             */
            case 'GetServiceList':
                $this->getPropertyList(property: "service");
                break;

            /**
             * @OA\Post(
             *      path="/GetServiceDetail",
             *      tags={"service"},
             *      @OA\RequestBody(
             *          description="test service specified by service_id",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="service_id",
             *                      type="int64"
             *                  ),
             *                  example={"service_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200", 
             *          description="Got service detail"
             *      )
             * )
             */
            case 'GetServiceDetail':
                $this->getPropertyDetail(property: "service");
                break;

            /**
             * @OA\Put(
             *     path="/SetServiceDetail",
             *     tags={"service"},
             *     @OA\RequestBody(
             *          description="test service specified by service_id",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="service_id",
             *                      type="int64"
             *                  ),
             *                  example={
             *                      "service_id": 1,
             *                      "service_name": "SSH",
             *                      "service_type": "port",
             *                      "service_desc": "SSH service test on localhost machine",
             *                      "service_endpoint": "telnet://localhost",
             *                      "service_port": 22,
             *                      "service_downtime": "UTC+2/1.00AM",
             *                      "service_activated": 1,
             *                      "service_public": 1,
             *                      "group_id": {1, 4, 5, 999},
             *                      "host_id": 1
             *                  }
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200",
             *          description="Service service_id queued for test"
             *      )
             * )
             */
            case 'SetServiceDetail':
                $this->setPropertyDetail(property: "service");
                break;

            /**
             * @OA\Post(
             *   path="/TestService",
             *      tags={"service"},
             *      @OA\RequestBody(
             *          description="test service specified by service_id",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="service_id",
             *                      type="int64"
             *                  ),
             *                  example={"service_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200",
             *          description="Service service_id queued for test"
             *      )
             * )
             */
            case 'TestService':
                //$this->testService();
                $this->writeJSON(code: 406);
                break;

            /**
             * @OA\Delete(
             *      path="/DeleteService",
             *      tags={"service"},
             *      @OA\RequestBody(
             *          description="specify service by service_id to be deleted",
             *          required=true,
             *          @OA\MediaType(
             *              mediaType="application/json",
             *              @OA\Schema(
             *                  @OA\Property(
             *                      property="service_id",
             *                      type="int64"
             *                  ),
             *                  example={"service_id": 1}
             *              )
             *          )
             *      ),
             *      @OA\Response(
             *          response="200", 
             *          description="Service deleted"
             *      )
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
            "api_identity" => $this->api_identity,
            "api_groups" => $this->api_groups,
            "function" => $function,
            "message" => $this->status_message ?? self::HTTP_CODE[$code],
            "status_code" => $code
        ];

        $data_output = [
            "api" => $api_header,
            "data" => $this->engine_output
        ];

        // CORS for swagger_ui container
        // https://swagger.io/docs/open-source-tools/swagger-ui/usage/cors/
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); 
        header("Access-Control-Allow-Headers: Content-Type, X-Api-Key, Authorization");
        
        // system-specified headers
        header("User-Agent: " . $this->user_agent);
        header("Content-type: application/json");
        //header("$this->http_version $code " . self::HTTP_CODE[$code]);
        
        // this one result into NetworkError in Swagger UI -- FIXME!
        //header($_SERVER["SERVER_PROTOCOL"] . " $code " . self::HTTP_CODE[$code] . ": " . $this->status_message);

        // JSON_FORCE_OBJECT: supervisor user_id is 0, therefore we need to explicitly print "0" as array key
        // https://stackoverflow.com/questions/15290811/php-json-encode-issue-with-array-0-key
        // https://www.php.net/manual/en/function.json-encode.php
        echo json_encode($data_output, JSON_PRETTY_PRINT);
        exit;
    }
}
