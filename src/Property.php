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

use SQLite3;

/**
 * abstract Property class
 * mainly pseudo-controller schema for child classes
 * 
 * @class Property
 */
abstract class Property
{
    protected int $destruct_flag = 0;

    private SQLite3 $sql;
    private string $child_class;
    private string $table_name;

    /** destruct flags */
    const FLAG_INTACT   = 0;
    const FLAG_ADD      = 1;
    const FLAG_UPDATE   = 2;
    const FLAG_DELETE   = 3;

    const DATABASE_PREFIX = 'monitor_';

    public function __construct(SQLite3 $sql = null)
    {
        // prepare database connection if not available from Api class
        $this->sql = $sql ?? new SQLite3(filename: DATABASE_FILE);

        // format table_name from child class name
        $child_class = explode(separator: '\\', string: get_class(object: $this));
        $this->child_class = end(array: $child_class);
        $this->table_name = self::DATABASE_PREFIX . strtolower(string: $this->child_class);

        $this->checkSchema();

        // check if there is any record for given id/name
        $num_rows = $this->sql->query(
            query: "SELECT COUNT(*) AS count FROM $this->table_name WHERE id  = '$this->id' OR name = '$this->name'"
            )->fetchArray(mode: SQLITE3_ASSOC)["count"];

        if ($num_rows == 0)
            $this->add();

        $this->load();
    }

    /** check database schema -- child classes' tables */
    protected function checkSchema() 
    {
        $datatypes = [
            "integer" => "INTEGER",
            "string" => "TEXT",
            "double" => "REAL",
            "null" => "NULL",
            "object" => "BLOB",
            null => "NULL"
        ];

        // redner query string and exec
        $query = "CREATE TABLE IF NOT EXISTS $this->table_name(";

        foreach($this as $key => $value) 
        {    
            $value = $this->sql->escapeString(string: $value);

            if ($key == "id") {
                $query .= "$key " . $datatypes[gettype(value: $value)] . " PRIMARY KEY AUTOINCREMENT"; 
            } else {
                $query .= ", $key " . $datatypes[gettype(value: $value)];
            }
        }

        $query .= ')';
        $this->sql->query(query: $query);
    }

    /** add new record */
    protected function add() 
    {
        // redner query string and exec
        $query = "INSERT OR IGNORE INTO $this->table_name VALUES (";

        foreach($this as $key => $value) 
        {
            $value = $this->sql->escapeString(string: $value);
            $qq[] = "$key = '$value'";
        }

        $query .= implode(separator: ',', array: $qq) . ")";
        $this->sql->query(query: $query);

        return $this;
    }

    /** load object's data from database */
    protected function load()
    {
        $q = "SELECT * FROM $this->table_name WHERE id = '" . $this->sql->escapeString(string: $this->id) . "'";
        $res = $this->sql->query($q);

        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_BOTH)) { $rows = $row; }

        // reload parameters' values
        foreach ($rows as $key => $value) 
        {
            $this->$key = $value;
        }

        return $this;
    }

    /** update/reload object's data in database (for destructor only) */
    private function save() 
    {
        $query = "UPDATE $this->table_name SET ";

        foreach($this as $key => $value) 
        {
            $value = $this->sql->escapeString(string: $value);

            // never change the ID!
            if ($key == "id")
                continue; 

            $qq[] = "$key = '$value'";
        }

        $query .= implode(separator: ',', array: $qq) .  " WHERE id = '" . $this->id . "'";
        $this->sql->query(query: $query);
    }

    /** delete record and destroy the instance immediately */
    protected function delete() 
    {
        $query = "DELETE FROM $this->table_name WHERE id = '$this->id'";
        $this->sql->query(query: $query);

        $this->destruct_flag = self::FLAG_DELETE;
        $this->__destruct();
    }

    /** save object's data */
    protected function __destruct()
    {
        // no further actions needed
        if ($this->destruct_flag == self::FLAG_DELETE) {
            $this->sql->close();
            return null;
        }

        /** UPDATE */
        $this->save();
        $this->sql->close();
    }
}