<?php

namespace sketch\database\DBSQL;

use sketch\database\DBSQL;

class DBPostSQL extends DBSQL
{

    /* DATABASES */

    /**
     * @param string $database
     * @param string $file_name
     * @param array $attr
     * @return void
     * @throws ExceptionDatabaseConnectParamMissing
     */
    public function exportDatabase(string $database, array $attr, string $file_name): void
    {
        if (!isset($attr['user']))
            throw new ExceptionDatabaseConnectParamMissing('user');

        if (!isset($attr['password']))
            throw new ExceptionDatabaseConnectParamMissing('password');

        exec("pg_dump -U {$attr['user']} -h localhost $database >> $file_name");
    }


    /* SCHEMAS */

    /**
     * @param string $schema_name
     * @return void
     */
    public function createSchema(string $schema_name):void
    {
        $this->query("CREATE SCHEMA IF NOT EXISTS $schema_name;");
    }

    /**
     * @return array
     */
    public function getSchemasNames(): array
    {
        $result = [];

        $schemas =  $this->select("
            SELECT table_schema 
            FROM information_schema.tables
            WHERE table_schema NOT IN ('information_schema', 'pg_catalog') 
                AND table_type = 'BASE TABLE'
            GROUP BY
                table_schema");

        foreach ($schemas as $schema) {
            $result[] = $schema["table_schema"];
        }

        return $result;
    }


    /* TABLES */

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return bool
     */
    public function tableIsExist(string $table_name, string $schema_name='public'): bool
    {
        $result = $this->select(
            "SELECT table_name 
                  FROM information_schema.tables  
                  where table_schema=:schema_name and table_name=:table_name",
            [
                "schema_name"=>$schema_name,
                "table_name"=>$table_name
            ]
        );

        return count($result) === 1;
    }

    /**
     * @param string $schema_name
     * @return array
     */
    public function getTablesBySchema(string $schema_name='public'): array
    {
        $result = [];
        $queryResult = $this->select(
            "SELECT tablename as table_name 
                    FROM pg_catalog.pg_tables 
                    where schemaname=:schema_name;",
            ["schema_name"=>$schema_name]
        );

        foreach ($queryResult as $item) {
            $result[] = $item["table_name"];
        }

        return $result;
    }

    /**
     * @param string $schema_name
     * @return array
     */
    public function getPrimaryKeysBySchema(string $schema_name='public'): array
    {
        return $this->select(
            "SELECT c.table_name, c.column_name
                    FROM information_schema.table_constraints tc 
                        JOIN information_schema.constraint_column_usage AS ccu USING (constraint_schema, constraint_name) 
                        JOIN information_schema.columns AS c ON 
                            c.table_schema = tc.constraint_schema
                                AND tc.table_name = c.table_name
                                AND ccu.column_name = c.column_name
                    WHERE constraint_type = 'PRIMARY KEY' and constraint_schema =:schema_name;",
            ["schema_name"=>$schema_name]
        );
    }


    /* COLUMNS */

    /**
     * @param string $schema_name
     * @return array
     */
    public function getColumnsBySchema(string $schema_name='public'): array
    {
        return $this->select("
            SELECT 
                   table_name as table_name, 
                   column_name as column_name, 
                   column_default as column_default, 
                   is_nullable='NO' as column_not_null,
                   data_type as column_data_type,
                   character_maximum_length as column_max_length
            FROM information_schema.columns
            WHERE table_schema=:schema_name;
            ",
            ["schema_name"=>$schema_name]
        );
    }

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return array
     */
    public function getTableColumnsBySchema(string $table_name, string $schema_name='public'): array
    {
        return $this->select(
            "SELECT * 
                    FROM information_schema.columns 
                    where table_schema=:schema_name and table_name=:table_name;",
            [
                "table_name" => $table_name,
                "schema_name" => $schema_name
            ]
        );
    }


    /* RECORDS */

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return array
     */
    public function createRecord(string $table_name, string $schema_name='public'): array
    {
        $columns = $this->getTableColumnsBySchema($table_name, $schema_name);

        $result = [];
        foreach ($columns as $column) {
            $result[$column["column_name"]]=$column["column_default"];
        }

        return $result;
    }

}