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

use Exception;
use \SQLite3 as SQLite;

/**
 * abstract Property class
 * 
 * @class Property
 */
abstract class Property
{
    protected int $id;
    protected string $name;
    protected string $desc;
    protected string $type;
    protected string $apikey;

    protected int $last_time;
    protected bool $activated;

    protected string $ip_address;
    protected string $group_id;

    protected function __construct()
    {
        return $this;
    }
    protected function add() {}
    protected function getDetail() {}
    protected function setDetail() {}
    protected function list() {}
    protected function delete() {}
}