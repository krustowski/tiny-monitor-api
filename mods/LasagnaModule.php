<?php

/**
 * tiny-monitor-api
 * 
 * @author krustowski <tmv2@n0p.cz>
 * @license MIT
 *
 * @OA\Info(
 *      title="tiny-monitor REST API", 
 *      version="1.9",
 *      @OA\Contact(
 *          name="krustowski",
 *          email="tmv2@n0p.cz"
 *      )
 * )
 */

namespace tinyMonitor;

class LasagnaModule extends Module 
{
    /* LASAGNA only!
    if (isset($lasagna)) {
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
    }*/
}