<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @license MIT
 */

// TODO
// public status page from public variables == __very__ simple TMv2 client
// public hosts very basic statuspage

// get unique public APIKEY
$public_apikey = null;

try {
  $raw_jon = file_get_contents(filename: __DIR__ . "/../init_config.json");
  $public_apikey = json_decode($raw_json, associative: true)["public_apikey"];
} catch (Exception $e) {
  die ($e);
}

if (!$public_apikey) {
  die ("No public APIKEY found!");
}

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
    "service_name" => "frank_ssh",
    "service_desc" => "SSH access to frank",
    "service_endpoint" => "telnet://frank:22",
    "service_status" => true
  ],
  [
    "service_name" => "monitor_frontend",
    "service_desc" => "TM frontend site status",
    "service_endpoint" => "https://monitor:443",
    "service_status" => true
  ],
  [
    "service_name" => "frank_ssh",
    "service_desc" => "SSH access to hel1",
    "service_endpoint" => "telnet://hel1:22",
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
  