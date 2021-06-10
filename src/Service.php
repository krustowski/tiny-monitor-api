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
 * Class Service
 * 
 * @OA\Schema(
 *      title="Service model",
 *      description="Service model",
 * )
 * @OA\Tag(
 *      name="service",
 *      description="tiny-monitor service operations"
 * )
 */
final class Service extends Property
{
    /**
     * @OA\Property(
     *     format="int64",
     *     description="service's unique ID",
     *     title="service_id",
     * )
     *
     * @var integer
     */
    private int $id;

    /**
     * @OA\Property(
     *     format="string",
     *     description="service's name or alias",
     *     title="service_name",
     * )
     *
     * @var string
     */
    protected string $name;

    /**
     * @OA\Property(
     *     format="string",
     *     description="services's description",
     *     title="service_desc",
     * )
     *
     * @var string
     */
    protected string $desc;

    /**
     * @OA\Property(
     *     format="string",
     *     description="service's type -- http/tcp/udp/ssh",
     *     title="service_type",
     * )
     *
     * @var string
     */
    protected string $type;

    /**
     * @OA\Property(
     *     format="string",
     *     description="services's API key -- TBI?",
     *     title="service_apikey",
     * )
     *
     * @var string
     */
    protected string $apikey;

    /**
     * @OA\Property(
     *     format="string",
     *     description="service's endpoint -- IP address or hostname (if any)",
     *     title="service_endpoint",
     * )
     *
     * @var string
     */
    protected string $endpoint;
    //protected string $ip_address;
 
    /**
     * @OA\Property(
     *     format="int64",
     *     description="service's port (if any) -- implement TCP/UDP/ICMP",
     *     title="service_port",
     * )
     *
     * @var integer
     */
    protected int $port;

    /**
     * @OA\Property(
     *     format="int64",
     *     description="service's last test in UNIX timestamp",
     *     title="service_last_test",
     * )
     *
     * @var integer
     */
    protected int $last_time;
    
    /**
     * @OA\Property(
     *     format="bool",
     *     description="activated status -- always required!",
     *     title="service_activated",
     * )
     *
     * @var boolean 
     */
    protected bool $activated = false;

    /**
     * @OA\Property(
     *     format="bool",
     *     description="public status -- always required!",
     *     title="service_public",
     * )
     *
     * @var boolean 
     */
    protected bool $public = false;

    /**
     * @OA\Property(
     *     format="bool",
     *     description="service's status -- unknown/okay/failed -- 0/1/2",
     *     title="service_status",
     * )
     *
     * @var int 
     */
    protected int $status = 0;

    /**
     * @OA\Property(
     *     format="int64",
     *     description="group access list -- explicit enumeration",
     *     title="group_id",
     * )
     *
     * @var [integer]
     */
    protected int $group_id = [];

    /**
     * @OA\Property(
     *     format="int64",
     *     description="host access list -- explicit enumeration",
     *     title="host_id",
     * )
     *
     * @var [integer]
     */
    protected int $host_id = [];

    public function __construct(
        int $id, 
        string $name, 
        string $desc, 
        string $type, 
        string $apikey, 
        int $downtime, 
        string $ip_address, 
        int $port, 
        string $last_time, 
        bool $activated = false, 
        bool $public = false, 
        int $status = 0, 
        int $host_id = 999, 
        int $group_id = [999]
        ) 
    {
        $this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->type = $type;
        $this->apikey = $apikey;
        $this->last_time = $last_time;
        $this->activated = $activated;
        $this->group_id = $group_id;

        $this->downtime = $downtime;
        $this->ip_address = $ip_address;
        $this->port = $port;
        $this->public = $public;
        $this->status = $status;
        $this->host_id = $host_id;

        parent::__construct();
        //return $this;
    }
}