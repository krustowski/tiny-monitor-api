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
 *      title="User model",
 *      description="User model",
 * )
 * @OA\Tag(
 *      name="user",
 *      description="tiny-monitor user operations"
 * )
 */
final class User extends Property
{
    /**
     * @OA\Property(
     *     format="int64",
     *     description="user's unique ID",
     *     title="user_id",
     * )
     *
     * @var integer
     */
    private int $id;

    /**
     * @OA\Property(
     *     format="string",
     *     description="user's name or alias",
     *     title="user_name",
     * )
     *
     * @var string
     */
    protected string $name;

    /**
     * @OA\Property(
     *     format="string",
     *     description="user's description",
     *     title="user_desc",
     * )
     *
     * @var string
     */
    protected string $desc;
    
    /**
     * @OA\Property(
     *     format="string",
     *     description="user type -- admin/user/bot",
     *     title="user_type",
     * )
     *
     * @var string
     */
    protected string $type;

    /**
     * @OA\Property(
     *     format="string",
     *     description="user's API key -- always required!",
     *     title="user_apikey",
     * )
     *
     * @var string
     */
    protected string $apikey;

    /**
     * @OA\Property(
     *     format="string",
     *     description="user's last IP address (try and implement IPv6 TODO)",
     *     title="user_ip_address",
     * )
     *
     * @var string
     */
    protected string $ip_address;
 
    /**
     * @OA\Property(
     *     format="int64",
     *     description="user's last access in UNIX timestamp",
     *     title="user_last_access",
     * )
     *
     * @var integer
     */
    protected int $last_time;
    
    /**
     * @OA\Property(
     *     format="bool",
     *     description="activated status -- always required!",
     *     title="user_activated",
     * )
     *
     * @var boolean 
     */
    protected bool $activated = false;

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

    public function __construct(int $id, string $name, string $desc, string $type, string $apikey, string $ip_address, string $last_time, bool $activated = false, int $group_id) 
    {
        $this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->type = $type;
        $this->apikey = $apikey;

        $this->ip_address = $ip_address;
        $this->last_access = $last_time;
        $this->activated = $activated;
        $this->group_id = $group_id;

        return $this;
    }
}