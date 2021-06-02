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
 * Class Group
 * 
 * @OA\Schema(
 *      title="Group model",
 *      description="Group model",
 * )
 * @OA\Tag(
 *      name="group",
 *      description="tiny-monitor group operations"
 * )
 */
final class Group extends Property
{
    /**
     * @OA\Property(
     *     format="int64",
     *     description="group's unique ID",
     *     title="group_id",
     * )
     *
     * @var integer
     */
    private int $id;
    //= private int $group_id;

    /**
     * @OA\Property(
     *     format="string",
     *     description="group's name or alias",
     *     title="group_name",
     * )
     *
     * @var string
     */
    private string $name;

    /**
     * @OA\Property(
     *     format="string",
     *     description="groups's descriptions",
     *     title="group_desc",
     * )
     *
     * @var string
     */
    private string $desc;

    /**
     * @OA\Property(
     *     format="string",
     *     description="groups's type",
     *     title="group_type",
     * )
     *
     * @var string
     */
    private string $type;

    /**
     * @OA\Property(
     *     format="string",
     *     description="group's API key -- group options? TBI?",
     *     title="user_apikey",
     * )
     *
     * @var string
     */
    private string $apikey;
 
    /**
     * @OA\Property(
     *     format="int64",
     *     description="group memebers' last access in UNIX timestamp",
     *     title="group_last_access",
     * )
     *
     * @var integer
     */
    private int $last_access;
    
    /**
     * @OA\Property(
     *     format="bool",
     *     description="activated status -- always required!",
     *     title="user_activated",
     * )
     *
     * @var boolean 
     */
    private bool $activated;

    public function __construct(int $id, string $name, string $desc, string $type, string $apikey, string $last_time, bool $activated = false) 
    {
        $this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->type = $type;
        $this->apikey = $apikey;

        $this->last_time = $last_time;
        $this->activated = $activated;
        $this->group_id = $id;

        return $this;
    }
}