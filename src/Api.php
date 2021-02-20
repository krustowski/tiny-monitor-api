<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

use RedisClient\RedisClient;

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

    // API config vars
    private $logFile = "/dev/stdout";
    private $redisConfig = [
        'server' => 'redis-server:6379',
        'timeout' => 1
    ];

    // API data vars
    private $engineOutput = [];
    private $routePath;
    private $apiKey;
    private $safeGET = [];
    private $safePOST = [];
    private $dataPayload = [];

    // constants
    const JSON_ASSOCIATIVE = true;
    const MICROTIME_AS_FLOAT = true;
    const MAX_API_USAGE_HOURLY = 1000;
    const ACCESS_TIME_LIMIT = 3599; // 1 hour

    public function __construct() 
    {
        // init
        $this->apiTimestampStart = (double) microtime(self::MICROTIME_AS_FLOAT) ?? null;
        $this->remoteAddress = $_SERVER["REMOTE_ADDR"] ?? null;
        $this->$apiUsage = $this->getAPIUsage();

        // clear HTTP requests
        $this->safeGET = (array) array_map("htmlspecialchars", $_GET);
        $this->safePOST = (array) array_map("htmlspecialchars", $_POST);

        // get API key
        $this->apiKey = $this->safeGET["apikey"] ?? null;

        // POST data, payload
        $this->dataPayload = json_decode(file_get_contents('php://input'), self::JSON_ASSOCIATIVE) ?? null;

        // explode over full path query variable
        $this->routePath = explode('/', $this->safeGET["fullPath"]) ?? null;

        $this->handleRequest();
    }

    /**
     * checks Redis cache for API usage
     * 
     * @return int $usage usage for custom IP and user
     */
    private function getAPIUsage() 
    {
        $hour = \date("H");
        $uid = 0 ?? $this->getUID();
        $remoteAddress = $this->remoteAddress;
        $key = "access_limiter_tiny-monitor-api_${remoteAddress}_${hour}_${uid}";
        $redis = new RedisClient($this->redisConfig);
        
        $val = (int) $redis->get($key);
        
        if ($val > self::MAX_API_USAGE_HOURLY) { 
            $this->statusMessage = "Too many requests!";
            $this->writeJSON(429);
        }

        $redis->multi();
        $redis->incr($key);
        $redis->expire($key, self::ACCESS_TIME_LIMIT);
        $redis->exec();
        
        $val++;
        return $val;        
    }

    /**
     * entrypoint for all API calls
     */
    private function handleRequest() 
    {
        switch ($this->routePath[0]) {
            case 'GetStatus':
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
                    $this->statusMessage = "Hash list is required for this query!";
                    $this->writeJSON(400);
                }

                $list = explode(",", $this->routePath[1]);

                // test input
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

                //$this->engineOutput = Engine\getDetail($list);
                $this->writeJSON();
                break;

            case 'TestRedis':
                $redis = new RedisClient($this->redisConfig);

                $this->engineOutput = [
                    "redis_client_version" => $redis->getSupportedVersion(),
                    "redis_version" => $redis->info('Server')['redis_version']
                ];
                $this->writeJSON();
                break;

            case 'WriteRedis':
                $redis = new RedisClient($this->redisConfig);
                $redis->executeRaw(['SET', 'kokot', 'mrdka']);
                $this->statusMessage = 'DATA WRITTEN';
                $this->writeJSON();
                break;

            case 'ReadRedis':
                $redis = new RedisClient($this->redisConfig);
                $engineOutput = [
                   "'" . $redis->executeRaw(['GET', 'kokot']) . "'"
                ];
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
                $this->statusMessage = "Unknown query. Please, see API documentation.";
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
        $query = empty($this->routePath[0]) ? null : $this->routePath[0];

        $apiHeader = [
            "name" => $this->apiName,
            "version" => $this->apiVersion,
            "processing_time_in_ms" => round((microtime(true) - $this->apiTimestampStart) * 1000, 2),
            "api_quota_hourly" => self::MAX_API_USAGE_HOURLY,
            "api_usage_hourly" => $this->$apiUsage,
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
