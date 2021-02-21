<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <k@n0p.cz>
 * @author mxdpeep <f@mxd.cz>
 * @license MIT
 */

namespace tinyMonitor;

/**
 * Engine class
 * contains all core methods and vars
 */
class Engine 
{    
    private function getSites() : array
    {
        return [
            "hash1" => "https://api.n0p.cz",
            "hash2" => "https://mon.n0p.cz"
        ];
    }

    /** site checker engine using cURL and cURL-multi */
    public static function checkSite(array $sites) : array
    {
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
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 30,            // seconds
            CURLOPT_DNS_CACHE_TIMEOUT => 120,        // seconds
            CURLOPT_MAXREDIRS => 20,
            //CURLOPT_PORT => 80,
            CURLOPT_DNS_LOCAL_IP4 => "1.1.1.1",
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_HTTPHEADER => ["Content-type: text/plain"]
        ];

        // set the curl-multi handle
        $multiHandle = curl_multi_init();

        //foreach ($this->getSites() ?? [] as $hash => $url) {
        foreach ($sites ?? [] as $url) {
            $hash = hash("sha256", $url);
            $handles[$hash] = curl_init($url);

            curl_setopt_array($handles[$hash], $curlOpts);
            curl_multi_add_handle($multiHandle, $handles[$hash]);
        }

        //execute the multi handle
        /*do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active) {
                curl_multi_select($multiHandle);
            }
        } while ($active && $status == CURLM_OK);

        // atempt #2
        do {
            curl_multi_exec($multiHandle, $running);
        } while ($running > 0);*/

        $active = null;
        do {
            $mrc = curl_multi_exec($multiHandle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multiHandle) != -1) {
                do {
                    $mrc = curl_multi_exec($multiHandle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        //read info and close the handles
        foreach ($handles as $hash => $handle) {
            if (curl_errno($handle))
                $gotInfo = "failed"; // TODO
            else
                $gotInfo = curl_getinfo($handle);

            array_push($engineOutput, [
                $hash => $gotInfo
                ]);

            curl_multi_remove_handle($multiHandle, $handle);
        }
        curl_multi_close($multiHandle);

        return $engineOutput;
    }
}