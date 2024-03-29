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

/**
 * Class User
 * 
 * @OA\Schema(
 *      title="module model",
 *      description="module model",
 * )
 * @OA\Tag(
 *      name="module",
 *      description="tiny-monitor module operations"
 * )
 */
class Module
{
    private function load() {}
    private function enable() {}
    private function disable() {}
}