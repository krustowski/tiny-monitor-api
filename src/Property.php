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

use ChildClass;
use Exception;
use \SQLite3 as SQLite;
use TypeError;

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

    protected string $group_id;

    protected bool $delete = false;

    private $sql;

    const CHILD_MAP_COLUMN = [
        "User" => "monitor_users",
        "Group" => "monitor_groups",
        "Host" => "monitor_hosts",
        "Service" => "monitor_services",
        "Usage" => "monitor_usage"
    ];

    const CHILD_MAP_PREFIX = [
        "User" => "user_",
        "Group" => "group_",
        "Host" => "host_",
        "Service" => "service_",
        "Usage" => "usage_"
     ];

    public function __construct(int $id, string $name, string $desc = "", string $type = "test", string $apikey = null, string $last_time = 0, bool $activated = false, int $group_id = [])
    {
        if (!$id && !$name) {
            throw new TypeError("`id` or `name` has to be set to spawn new instance!");
        }

        $this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->type = $type;
        $this->apikey = $apikey;
        $this->last_time = $last_time;
        $this->activated = $activated;
        $this->group_id = $group_id;
    
        try {
            $this->sql = new SQLite(filename: DATABASE_FILE);
        }
        catch (Exception $e) {
            die($e->getMessage());
        }

        $gen_table = self::CHILD_MAP_COLUMN[get_called_class()];
        $gen_name = self::CHILD_MAP_PREFIX[get_called_class()] . "name";
        $gen_id = self::CHILD_MAP_PREFIX[get_called_class()] . "id";

        // check if there is any record for given id/name
        $num_rows = $this->sql->query("SELECT COUNT(*) as count from $gen_table where $gen_name = '$name' OR $gen_id = '$id'")->fetchArray(SQLITE3_ASSOC)["count"];

        if ($num_rows == 0)
            $this->add();

        $this->load();

        $this->id = $data["id"] ?? null;
        $this->name = $data["name"] ?? null;

        return $this;
    }

    protected function add() 
    {
        $this->sql->query("INSERT INTO ... SET ... ");

        return $this;
    }

    protected function load()
    {
        $this->sql->query("SELECT * FROM ... WHERE ... ");
    
        return $this;
    }

    //protected function add() {}
    //protected function getDetail() {}
    //protected function setDetail() {}
    //protected function list() {}
    //protected function delete() {}
    
    /*protected function reload() {
        /*$this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->type = $type;
        $this->apikey = $apikey;
        $this->last_time = $last_time;
        $this->activated = $activated;
        $this->group_id = $group_id;
    }*/

    protected function __destruct()
    {
        if ($this->delete)
            $this->sql->query("DELETE ... ");
        else
            $this->sql->query("UPDATE ... ");
    }
}