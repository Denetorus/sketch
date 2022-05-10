<?php

namespace sketch\database;

class DBSQL
{

    protected $db;
    protected $dsn;
    protected $user;
    protected $password;

    public function setAttributes($attr)
    {
        foreach ($attr as $key => $val){
            $this->$key = $val;
        }
    }


    /* COMMON */

    public function connect($attr = null)
    {
        if ($attr !== null){
            $this->setAttributes($attr);
        }

        $this->db = new \PDO(
            $this->dsn,
            $this->user,
            $this->password,
            [
                // возвращать ассоциативные массивы
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // возвращать Exception в случае ошибки
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                //Использовать серверные плейсхолдеры
                \PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
    
    public function query($query, $params = array())
    {
        $res = $this->db->prepare($query);
        $res->execute($params);
        return $res;
    }
    
    public function select($query, $params = array()): ?array
    {
        $result = $this->query($query, $params);
        if ($result) {
            return $result->fetchAll();
        }
        return null;
    }
    
    public function selectOne($query, $params = array()): ?array
    {
        $result = $this->query($query, $params);
        if ($result) {
            return $result->fetch();
        }
        return null;
    }


    /* DATABASES */

    public function createDatabase($database_name): void
    {
        $this->query("CREATE DATABASE $database_name;");
    }

    public function dropDatabase($database_name): void
    {
        $this->query("DROP DATABASE $database_name;");
    }

    public function exportDatabase($database, $file_name): void
    {
        exec("pg_dump -U $this->user -h localhost $database >> $file_name");
    }
    
    /* SCHEMAS */

    public function createSchema($schema_name):void
    {
        $this->query("CREATE SCHEMA IF NOT EXISTS $schema_name;");
    }

    public function getSchemasNames(): array
    {
        $result = [];

        $schemas =  $this->query("
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

    public function tableIsExist($table_name,$schema_name='public'): bool
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

        return Count($result) === 1;
    }

    public function createTable($table_name, $params=null, $options=null, $schema_name='public'):void
    {

        $paramsText = '';
        if ($params !== null){
            foreach ($params as $key=>$val){
                $paramsText .= $key.' '.$val.',';
            }
        }

        if ($options !== null){
            foreach ($options as $val){
                $paramsText .= $val.',';
            }
        }

        if (strlen($paramsText)>0){
            $paramsText = substr($paramsText, 0, -1);
        }

        $queryText = "CREATE TABLE $schema_name.$table_name ($paramsText)";

        $this->query($queryText);

    }

    public function dropTable($table_name, $schema_name='public')
    {
        $this->query("DROP  TABLE $schema_name.$table_name");
    }

    public function getTablesBySchema($schema_name='public'): array
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

    public function getPrimaryKeysBySchema($schema_name='public'): array
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

    public function addColumn($table_name, $column_name, $column_content, $schema_name='public')
    {
        $this->query(
            "ALTER TABLE $schema_name.$table_name ADD COLUMN $column_name $column_content;"
        );
    }

    public function dropColumn($table_name, $column_name, $schema_name='public')
    {
        $this->query("ALTER TABLE $schema_name.$table_name DROP COLUMN IF EXISTS $column_name");
    }

    public function changeColumn($table_name, $column_name, $column_content, $schema_name='public')
    {
        $this->query(
            "ALTER TABLE $schema_name.$table_name ALTER COLUMN $column_name $column_content;"
        );
    }

    public function getColumnsBySchema($schema_name='public'): array
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

    public function getTableColumnsBySchema($table_name, $schema_name='public'): array
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

    public function recordIsExist($table, $conditions): bool
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }
        $query_text = "SELECT * FROM $table WHERE {$this->prepareQueryConditionsText($conditions)}";
        $result = $this->select($query_text, $conditions);
        return Count($result) !== 0;
    }
    
    public function getRecords($table, $conditions): ?array
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }

        $query_text = "SELECT * FROM $table WHERE {$this->prepareQueryConditionsText($conditions)};";

        return $this->select($query_text, $conditions);

    }
    
    public function getList($table,$schema_name='public'): ?array
    {
        return $this->select("SELECT * FROM $schema_name.$table");
    }
    
    public function getRecord($table, $conditions): ?array
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }
        $query_text = "SELECT * FROM $table WHERE {$this->prepareQueryConditionsText($conditions)}";

        $result = $this->selectOne($query_text, $conditions);
        if ($result) {
            return $result;
        }
        return null;
    }
    
    public function setRecord($table, $params, $withNewID=true): void
    {

        $symbol = "";
        $paramsName = "";
        $valueName = "";
        foreach ( $params as $key => $val) {
            if ($withNewID and $key === "id") {
                unset($params["id"]);
                continue;
            }
            $paramsName .= $symbol.$key;
            $valueName .= $symbol." :".$key;
            $symbol = ", ";
        }


        $this->query(
            "INSERT INTO $table ($paramsName) VALUES ($valueName)",
            $params
        );

    }
    
    public function updateRecord($table, $conditions, $params)
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }

        $query_text = "UPDATE $table SET 
                        {$this->prepareQueryConditionsText($params, ",", "_S_")}
                       WHERE 
                        {$this->prepareQueryConditionsText($conditions," && ","_W_")}";

        $sendParams = [];
        foreach ($params as $key=>$value){
            $sendParams["_S_".$key] = $value;
        }
        foreach ($conditions as $key=>$value){
            $sendParams["_W_".$key] = $value;
        }

        $this->query($query_text, $sendParams);

    }
    
    public function createRecord($table_name): array
    {
        $columns = $this->select(
            "SELECT column_name, column_default 
                  FROM information_schema.columns 
                  WHERE table_schema='public' and table_name='$table_name'"
        );

        $items = [];
        foreach ($columns as $column) {
            $items[$column["column_name"]]=$column["column_default"];
        }
        return $items;
    }
    
    public function deleteRecord($table, $conditions, $schema_name='public')
    {

        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }

        $query_text = "DELETE FROM $schema_name.$table WHERE {$this->prepareQueryConditionsText($conditions)};";

        $this->query($query_text, $conditions);

    }
    
    public function deleteAllRecords($table, $schema_name='public'):void
    {
        $this->query("DELETE FROM $schema_name.$table;");
    }



    protected function prepareQueryConditionsText($conditions, $separator=" && ", $param_prefix=""): string
    {

        $query_text = "" ;

        $countConditions = Count($conditions);
        if ( $countConditions !== 0 ) {
            foreach ($conditions as $key=>$value){
                $countConditions -= 1;
                $query_text .= $key."=:".$param_prefix.$key;
                if ($countConditions !== 0){
                    $query_text .= $separator;
                }
            }
        }

        return $query_text;

    }

}
