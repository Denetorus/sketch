<?php

namespace sketch\database;

abstract class DBBase
{
    public static function getInstance()
    {
        if (static::$DB === null) {
            static::$DB = new DBSQL();
            static::$DB->connect(static::GetAttributes());
        }
        return static::$DB;
    }

    public static function getAttributes()
    {
        return [
            'dsn' => '',
            'user' => '',
            'password' => ''
        ];
    }
}
