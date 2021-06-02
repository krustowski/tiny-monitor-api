<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @license MIT
 */

// core constants
defined("ROOT_DIR")          || define("ROOT_DIR", __DIR__ . "/..");
defined("INIT_CONFIG")       || define("INIT_CONFIG", ROOT_DIR . "/init_config.json");

if (!file_exists(filename: INIT_CONFIG))
    die ("Fatal error: no init_config.json!");

// get unique public APIKEY
$public_apikey = null;

$raw_json = file_get_contents(filename: INIT_CONFIG);
$public_apikey = json_decode(json: $raw_json, associative: true)["public_apikey"];

if (!$public_apikey)
  die ("No public APIKEY found!");

$endpoint = "http://localhost/api/v2/GetPublicStatus";
$user_agent = "tiny-monitor status page / cURL " . \curl_version()["version"] ?? null;

$curl_opts = [
    CURLOPT_FRESH_CONNECT => true,           // true = no cached cons
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'X-Api-Key: ' . $public_apikey
    ]
];

// get services' raw list
$curl_handle = \curl_init(url: $endpoint);
\curl_setopt_array(handle: $curl_handle, options: $curl_opts);
$services = json_decode(\curl_exec(handle: $curl_handle), true)["data"] ?? null;
\curl_close(handle: $curl_handle);

// demo data
$demo_services = [
  [
    "service_name" => "google_web",
    "service_desc" => "Google.co.uk website",
    "service_endpoint" => "https://google.co.uk/",
    "service_status" => true
  ],
  [
    "service_name" => "monitor_web",
    "service_desc" => "TMv2 frontend site status",
    "service_endpoint" => "https://mon.n0p.cz/status",
    "service_status" => true
  ],
  [
    "service_name" => "swis_web",
    "service_desc" => "sWI5 frontend",
    "service_endpoint" => "https://swis.n0p.cz/",
    "service_status" => false
  ]
];

// load demo data, if API returns no data at all
if (!$services || empty($services)) {
  $services = $demo_services;
}

// check how many services are down (not-operational status)
(int) $not_operational_count = 0;
foreach ($services as $s) { if (!$s["service_status"]) $not_operational_count++; }

// formated timestamp of last test
$refreshed_formated = "just seconds"; //date("H:i:s d-m-Y", time()); // "29 minutes";
?>

<!doctype html>
<head>
  <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
  <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
  <!------ Include the above in your HEAD tag ---------->

  <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
  <link href="//bootswatch.com/yeti/bootstrap.min.css" rel="stylesheet" type="text/css" />

  <script src="//code.jquery.com/jquery.min.js"></script>
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
  <title>Public Status Page / tiny-monitor</title>
</head>
<body>
  
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <h1>Public Status Page</h1>
      </div>
    </div>
    
    <div class="row clearfix">
      <div class="col-md-12 column">

          <?php if ($not_operational_count > 0): ?>
          <div class="panel panel-warning">
            <div class="panel-heading">
              <h3 class="panel-title">
                Not All Systems Operational
                <small class="pull-right">Refreshed <?= $refreshed_formated; ?> ago</small>
              </h3>
            </div>                
          </div>
          <?php endif; ?>
            
          <div class="row clearfix">
            <div class="col-md-12 column">
              <div class="list-group">

                <?php foreach($services as $s): ?>
                <div class="list-group-item">
                  <h4 class="list-group-item-heading">
                    <?php echo $s["service_name"]; ?>
                    <a href="<?php echo $s["service_endpoint"]; ?>"  data-toggle="tooltip" data-placement="bottom" title="<?php echo $s["service_desc"]; ?>">
                      <i class="fa fa-question-circle"></i>
                    </a>
                  </h4>
                  <p class="list-group-item-text">
                    <?php if (!$s["service_status"]): ?>
                      <span class="label label-danger">Not Operational</span>
                    <?php else: ?>
                      <span class="label label-success">Operational</span>
                    <?php endif; ?>
                  </p>
                </div>
                <?php endforeach; ?>
              
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
  