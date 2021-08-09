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
    public function select($query, $params = array())
    {
        $result = $this->query($query, $params);
        if ($result) {
            return $result->fetchAll();
        }
        return null;
    }
    public function selectOne($query, $params = array())
    {
        $result = $this->query($query, $params);
        if ($result) {
            return $result->fetch();
        }
        return null;
    }

    public function createTable($table, $params=null, $options=null)
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

        $queryText = 'CREATE TABLE "'.$table.'" ('.$paramsText.')';

        $this->query($queryText);

    }


    public function dropTable($table)
    {
        $this->query("DROP  TABLE {$table}");
    }
    public function tableIsExist($table)
    {
        $result = $this->select(
            "SELECT table_name 
                  FROM information_schema.tables  
                  where table_schema='public' and table_name='{$table}'"
        );

        return Count($result) === 1;
    }

    public function recordIsExist($table, $conditions)
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }
        $query_text = "SELECT * FROM {$table} WHERE ".$this->prepareQueryConditionsText($conditions);
        $result = $this->select($query_text, $conditions);
        return Count($result) !== 0;
    }
    public function getRecords($table, $conditions)
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }

        $query_text = "SELECT * FROM {$table} WHERE ".$this->prepareQueryConditionsText($conditions);

        return $this->select($query_text, $conditions);

    }
    public function getList($table)
    {
        return $this->select("SELECT * FROM {$table}");
    }
    public function getRecord($table, $conditions)
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }
        $query_text = "SELECT * FROM {$table} WHERE ".$this->prepareQueryConditionsText($conditions);

        $result = $this->selectOne($query_text, $conditions);
        if ($result) {
            return $result;
        }
        return null;
    }
    public function setRecord($table, $params, $withNewID=true)
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
            "INSERT INTO {$table} ({$paramsName}) VALUES ({$valueName})",
            $params
        );


    }
    public function updateRecord($table, $conditions, $params)
    {
        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }

        $query_text = "UPDATE {$table} SET ".
                        $this->prepareQueryConditionsText($params, ",").
                      " WHERE ".
                        $this->prepareQueryConditionsText($conditions);

        foreach ($conditions as $key=>$value){
            $params[$key] = $value;
        }

        $this->query($query_text, $params);

    }
    public function createRecord($table)
    {
        $columns = $this->select(
            "SELECT column_name, column_default 
                  FROM information_schema.columns 
                  WHERE table_schema='public' and table_name='{$table}'"
        );

        $items = [];
        foreach ($columns as $column) {
            $items[$column["column_name"]]=$column["column_default"];
        }
        return $items;
    }
    public function deleteRecord($table, $conditions)
    {

        if (!is_array($conditions)){
            $conditions = ["id" => $conditions];
        }

        $query_text = "DELETE FROM {$table} WHERE ".$this->prepareQueryConditionsText($conditions);

        $this->query($query_text, $conditions);

    }

    protected function prepareQueryConditionsText($conditions, $separator=" && "){

        $query_text = "" ;

        $countConditions = Count($conditions);
        if ( $countConditions !== 0 ) {
            foreach ($conditions as $key=>$value){
                $countConditions -= 1;
                $query_text .= $key."=:".$key;
                if ($countConditions !== 0){
                    $query_text .= $separator;
                }
            }
        }

        return $query_text;

    }

    public function createUUID($trim = true){
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
            substr($charid,  0,  8).$hyphen.
            substr($charid,  8,  4).$hyphen.
            substr($charid, 12,  4).$hyphen.
            substr($charid, 16,  4).$hyphen.
            substr($charid, 20, 12).
            $rbrace;
        return $guidv4;
    }

}