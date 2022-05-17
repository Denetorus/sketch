<?php

namespace sketch\database;

use sketch\SK;

abstract class DBBase
{

    /**
     * @var DBSQL|null
     */
    protected static $DB = null;

    /**
     * @return DBSQL
     */
    public static function getInstance():DBSQL
    {
        if (static::$DB === null) {
            static::connect();
        }
        return static::$DB;
    }

    public static function connect():void
    {
        /** insert code for connection with database
         * for example: PostgresSQL
         *       static::$DB = new DBPostSQL(static::getAttributes());
         */
    }

    /**
     * @return array
     */
    public static function getAttributes():array
    {
        return SK::getProps()['db_params'];
    }

}
