<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

//namespace tinyMonitor;

//use \SQLite3 as SQLite;

// TODO
// public status page from public variables == __very__ simple TMv2 client
// public hosts very basic statuspage

// curl + curlopts + public apikey

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
