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

$publicKey = "";
$endpoint = "localhost/api/v2/GetPublicStatus?apikey=$publicKey";

$userAgent = "tiny-monitor bot / cURL " . curl_version()["version"] ?? null;
$handles = [];
$engineOutput = [];

$curlOpts = [
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
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_HTTPHEADER => ["Content-type: text/plain"]
];

$curl_handle = curl_init($endpoint);
curl_exec($curl_handle);
curl_close($curl_handle);

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

</head>
<body>
  
  <div class="container">
      <div class="row">
        <div class="col-md-12">
          <h1>Status Page</h1>
        </div>
      </div>
      <div class="row clearfix">
          <div class="col-md-12 column">
              <div class="panel panel-warning">
                <div class="panel-heading">
                  <h3 class="panel-title">s
                    Not All Systems Operational
                    <small class="pull-right">Refreshed 39 minutes ago</small>
                  </h3>
                </div>                
              </div>
            

              <div class="row clearfix">
                  <div class="col-md-12 column">
                      <div class="list-group">
                        
                          <div class="list-group-item">
                              <h4 class="list-group-item-heading">
                                  Website and API 
                                  <a href="#"  data-toggle="tooltip" data-placement="bottom" title="Access website and use site API">
                                    <i class="fa fa-question-circle"></i>
                                  </a>
                              </h4>
                              <p class="list-group-item-text">
                                  <span class="label label-danger">Not Operational</span>
                              </p>
                          </div>
                        
                          <div class="list-group-item">
                              <h4 class="list-group-item-heading">
                                  SSH 
                                  <a href="#"  data-toggle="tooltip" data-placement="bottom" title="Access site using SSH terminal">
                                    <i class="fa fa-question-circle"></i>
                                  </a>
                              </h4>
                              <p class="list-group-item-text">
                                  <span class="label label-success">Operational</span>
                              </p>
                          </div>
                        
                          <div class="list-group-item">
                              <h4 class="list-group-item-heading">
                                  Database Server 
                                  <a href="#"  data-toggle="tooltip" data-placement="bottom" title="Access database server and execute queries">
                                    <i class="fa fa-question-circle"></i>
                                  </a>
                              </h4>
                              <p class="list-group-item-text">
                                  <span class="label label-success">Operational</span>
                              </p>
                          </div>
                          
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  </body>
  </html>
  