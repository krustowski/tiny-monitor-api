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

use SQLite3 as SQLite;

/**
 * Class Host
 * 
 * @OA\Schema(
 *      title="Host model",
 *      description="Host model",
 * )
 * @OA\Tag(
 *      name="host",
 *      description="tiny-monitor host operations"
 * )
 */
final class Host extends Property
{
    /**
     * @OA\Property(
     *     format="int64",
     *     description="host's unique ID",
     *     title="host_id",
     * )
     *
     * @var integer
     */
    private int $id;

    /**
     * @OA\Property(
     *     format="string",
     *     description="hosts's name or alias",
     *     title="host_name",
     * )
     *
     * @var string
     */
    private string $name;

    /**
     * @OA\Property(
     *     format="string",
     *     description="hosts's description",
     *     title="host_desc",
     * )
     *
     * @var string
     */
    private string $desc;

    /**
     * @OA\Property(
     *     format="string",
     *     description="host's API key -- e.g. to update its status",
     *     title="host_apikey",
     * )
     *
     * @var string
     */
    private string $apikey;
 
    /**
     * @OA\Property(
     *     format="int64",
     *     description="host's last access in UNIX timestamp",
     *     title="host_last_access",
     * )
     *
     * @var integer
     */
    private int $last_time;
    
    /**
     * @OA\Property(
     *     format="bool",
     *     description="activated status -- always required!",
     *     title="host_activated",
     * )
     *
     * @var boolean 
     */
    private bool $activated;

    /**
     * @OA\Property(
     *     format="int64",
     *     description="group access list -- explicit enumeration",
     *     title="group_id",
     * )
     *
     * @var [integer]
     */
    private int $group_id = [];

    public function __construct(int $id, string $name, string $desc, string $type, string $apikey, string $last_time, string $ip_address, bool $activated = false, int $group_id = []) 
    {
        parent::__construct(id: $id, name: $name, desc: $desc, type: $type, apikey: $apikey, last_time: $last_time, activated: $activated, group_id: $group_id);

        /*$this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->type = $type;
        $this->apikey = $apikey;
        $this->last_time = $last_time;
        $this->activated = $activated;
        $this->group_id = $group_id;*/

        $sql = new SQLite(DATABASE_FILE);

        $this->ip_address = $ip_address;

        return $this;
    }
}