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

// curl + curlopts + public apikey

$public_key = "52ec36471a0c747eea554181a5e2620c2eec1fb685f34b157bfe30529d58740a61d030f0b5e15101de2722baa27f04407e2415c948c7359faa95d7e8bca72a3a";
$endpoint = "http://localhost/api/v2/GetPublicStatus";

$user_agent = "tiny-monitor status page / cURL " . curl_version()["version"] ?? null;
$engine_output = [];

$curl_opts = [
    CURLOPT_CERTINFO => false,               // true = check cert expiry later
    CURLOPT_DNS_SHUFFLE_ADDRESSES => true,   // true = use randomized addresses from DNS
    CURLOPT_FOLLOWLOCATION => true,          // true = follow Location: header!
    CURLOPT_FORBID_REUSE => true,            // true = do not use this con again
    CURLOPT_FRESH_CONNECT => true,           // true = no cached cons
    CURLOPT_HEADER => true,                  // true = send header in output
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_CONNECTTIMEOUT => 30,            // seconds
    CURLOPT_DNS_CACHE_TIMEOUT => 120,        // seconds
    CURLOPT_MAXREDIRS => 20,
    //CURLOPT_PORT => 80,
    CURLOPT_DNS_LOCAL_IP4 => "1.1.1.1",
    CURLOPT_TIMEOUT => 20,
    CURLOPT_USERAGENT => $user_agent,
    CURLOPT_HTTPHEADER => [
      "Content-type: application/json",
      "X-Api-Key: $public_key"
    ]
];

// get services' raw list
$curl_handle = curl_init($endpoint);
curl_setopt_array($curl_handle, $curl_opts);
$services = curl_exec($curl_handle)["data"] ?? null;
curl_close($curl_handle);

// demo data
$demo_services = [
  [
    "service_name" => "frank_ssh",
    "service_desc" => "SSH access to frank",
    "service_link" => "telnet://frank:22",
    "service_status" => true
  ],
  [
    "service_name" => "monitor_frontend",
    "service_desc" => "TM frontend site status",
    "service_link" => "https://monitor:443",
    "service_status" => true
  ],
  [
    "service_name" => "frank_ssh",
    "service_desc" => "SSH access to frank",
    "service_link" => "telnet://frank:22",
    "service_status" => true
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
$refreshed_formated = date("H:i:s d-m-Y", time()); // "29 minutes";

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
        <h1>Public Status Page / tiny-monitor</h1>
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
                    <a href="<?php echo $s["service_link"]; ?>"  data-toggle="tooltip" data-placement="bottom" title="<?php echo $s["service_desc"]; ?>">
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
  