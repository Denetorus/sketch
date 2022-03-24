<?php

namespace sketch\database;

use sketch\SK;

abstract class DBBase
{

    protected static $DB = null;

    public static function getInstance():DBSQL
    {
        if (static::$DB === null) {
            static::$DB = new DBSQL();
            static::$DB->connect(static::getAttributes());
        }
        return static::$DB;
    }

    public static function getAttributes()
    {
        return SK::getProps()['db_params'];
    }
}
