<?php

namespace sketch\database;

use Exception;
use ExceptionDatabaseConnectParamMissing;

abstract class DBSQL
{

    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @param array $attr
     * @throws ExceptionDatabaseConnectParamMissing
     */
    public function __construct(array $attr)
    {
        $this->connect($attr);
    }

    /* COMMON */

    /**
     * @return array
     */
    public function getOptions():array
    {
        return [
            // return associative arrays
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            // return Exception on Error
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            //Use server placeholders
            \PDO::ATTR_EMULATE_PREPARES => false
        ];
    }

    /**
     * @param array $attr
     * @return void
     * @throws ExceptionDatabaseConnectParamMissing
     */
    public function connect(array $attr):void
    {

        if (!isset($attr['dsn']))
            throw new ExceptionDatabaseConnectParamMissing('dsn');

        if (!isset($attr['user']))
            throw new ExceptionDatabaseConnectParamMissing('user');

        if (!isset($attr['password']))
            throw new ExceptionDatabaseConnectParamMissing('password');

        $this->db = new \PDO(
            $attr['dsn'],
            $attr['user'],
            $attr['password'],
            $this->getOptions()
        );
    }

    /**
     * @param string $query
     * @param array $params
     * @return void
     */
    public function onQueryError(string $query, array $params):void
    {
    }

    /**
     * @param string $query
     * @param array $params
     * @return bool
     */
    public function query(string $query, array $params=[]): bool
    {
        $sth = $this->db->prepare($query);
        return $sth->execute($params);
    }

    /**
     * @param string $query
     * @param array $params
     * @return false|\PDOStatement
     */
    public function queryResult(string $query, array $params=[])
    {
        $sth = $this->db->prepare($query);
        $sth->execute($params);
        return $sth;
    }

    /**
     * @param string $query
     * @param array $params
     * @return array
     */
    public function select(string $query, array $params=[]): array
    {
        $result = $this->queryResult($query, $params);
        if ($result)
            return $result->fetchAll();

        $this->onQueryError($query, $params);
        return [];
    }

    /**
     * @param string $query
     * @param array $params
     * @return array
     */
    public function selectOne(string $query, array $params=[]): array
    {
        $result = $this->queryResult($query, $params);
        if ($result){

            $answer = $result->fetch();
            if ($answer===false)
                return [];

            return $answer;
        }


        $this->onQueryError($query, $params);
        return [];
    }


    /* DATABASES */

    /**
     * @param string $database_name
     * @return void
     */
    public function createDatabase(string $database_name): void
    {
        $this->query("CREATE DATABASE $database_name;");
    }

    /**
     * @param $database_name
     * @return void
     */
    public function dropDatabase($database_name): void
    {
        $this->query("DROP DATABASE $database_name;");
    }

    /**
     * @param string $database
     * @param string $file_name
     * @param array $attr
     * @return void
     * @throws Exception
     */
    abstract public function exportDatabase(string $database, array $attr, string $file_name): void;


    /* SCHEMAS */

    /**
     * @param string $schema_name
     * @return void
     */
    abstract public function createSchema(string $schema_name):void;
    /**
     * @return array
     */
    abstract public function getSchemasNames(): array;


    /* TABLES */

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return bool
     */
    abstract public function tableIsExist(string $table_name, string $schema_name='public'): bool;

    /**
     * @param string $table_name
     * @param array|null $params
     * @param array|null $options
     * @param string $schema_name
     * @return void
     */
    public function createTable(string $table_name, array $params=[], array $options=[], string $schema_name='public'):void
    {

        $paramsText = '';
        foreach ($params as $key=>$val){
            $paramsText .= $key.' '.$val.',';
        }

        foreach ($options as $val){
            $paramsText .= $val.',';
        }

        if (strlen($paramsText)>0){
            $paramsText = substr($paramsText, 0, -1);
        }

        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $queryText = "CREATE TABLE IF NOT EXISTS $table_name ($paramsText)";

        $this->query($queryText);

    }

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return void
     */
    public function dropTable(string $table_name, string $schema_name='public')
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $this->query("DROP  TABLE $table_name");
    }

    /**
     * @param string $schema_name
     * @return array
     */
    abstract public function getTablesBySchema(string $schema_name='public'): array;

    /**
     * @param string $schema_name
     * @return array
     */
    abstract public function getPrimaryKeysBySchema(string $schema_name='public'): array;


    /* COLUMNS */

    /**
     * @param string $table_name
     * @param string $column_name
     * @param string $column_content
     * @param string $schema_name
     * @return void
     */
    public function addColumn(string $table_name, string $column_name, string $column_content, string $schema_name='public')
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $this->query(
            "ALTER TABLE $table_name ADD COLUMN $column_name $column_content;"
        );
    }

    /**
     * @param string $table_name
     * @param string $column_name
     * @param string $schema_name
     * @return void
     */
    public function dropColumn(string $table_name, string $column_name, string $schema_name='public')
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $this->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS $column_name");
    }

    /**
     * @param string $table_name
     * @param string $column_name
     * @param string $column_content
     * @param string $schema_name
     * @return void
     */
    public function changeColumn(string $table_name, string $column_name, string $column_content, string $schema_name='public')
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $this->query(
            "ALTER TABLE $table_name ALTER COLUMN $column_name $column_content;"
        );
    }

    /**
     * @param string $schema_name
     * @return array
     */
    abstract public function getColumnsBySchema(string $schema_name='public'): array;

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return array
     */
    abstract public function getTableColumnsBySchema(string $table_name, string $schema_name='public'): array;


    /* RECORDS */

    /**
     * @param string $table_name
     * @param array $conditions
     * @param string $schema_name
     * @return bool
     */
    public function recordIsExist(string $table_name, array $conditions, string $schema_name='public'): bool
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $result = $this->select(
            "SELECT * FROM $table_name WHERE {$this->prepareQueryConditionsText($conditions)}",
            $conditions
        );
        return count($result) !== 0;
    }

    /**
     * @param string $table_name
     * @param array $conditions
     * @param string $schema_name
     * @return array
     */
    public function getRecords(string $table_name, array $conditions, string $schema_name='public'): array
    {

        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        return $this->select(
            "SELECT * FROM $table_name WHERE {$this->prepareQueryConditionsText($conditions)};",
            $conditions
        );

    }

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return array
     */
    public function getList(string $table_name, string $schema_name='public'): array
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        return $this->select("SELECT * FROM $table_name");
    }

    /**
     * @param string $table_name
     * @param array $conditions
     * @param string $schema_name
     * @return array
     */
    public function getRecord(string $table_name, array $conditions, string $schema_name='public'): array
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        return $this->selectOne(
            "SELECT * FROM $table_name WHERE {$this->prepareQueryConditionsText($conditions)}",
            $conditions
        );

    }


    /**
     * @param string $table_name
     * @param array $params
     * @param string $schema_name
     * @return void
     */
    public function setRecord(string $table_name, array $params, string $schema_name='public'): void
    {

        $query_text = $this->getRecordInsertQuery($table_name, $params, $schema_name);

        $this->query(
            $query_text,
            $params
        );

    }

    /**
     * @param string $table_name
     * @param array $params
     * @param string $PK_name
     * @param string $schema_name
     * @return array
     */
    public function setRecordAndReturnPrimaryKey(
        string $table_name,
        array $params,
        string $PK_name,
        string $schema_name='public'):array
    {

        $query_text = $this->getRecordInsertQuery($table_name, $params, $schema_name);

        return $this->selectOne(
            $query_text." RETURNING  $PK_name",
            $params
        );

    }

    private function getRecordInsertQuery(string $table_name, array $params, string $schema_name='public'):string
    {
        $symbol = "";
        $paramsName = "";
        $valueName = "";
        foreach ( $params as $key => $val) {
            $paramsName .= $symbol.$key;
            $valueName .= $symbol." :".$key;
            $symbol = ", ";
        }

        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        return "INSERT INTO $table_name ($paramsName) VALUES ($valueName)";

    }

    /**
     * @param string $table_name
     * @param array $conditions
     * @param array $params
     * @param string $schema_name
     * @return void
     */
    public function updateRecord(string $table_name, array $conditions, array $params, string $schema_name='public'):void
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $query_text = "UPDATE $table_name SET 
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

    /**
     * @param string $table_name
     * @return array
     */
    abstract public function createRecord(string $table_name): array;

    /**
     * @param string $table_name
     * @param array $conditions
     * @param string $schema_name
     * @return void
     */
    public function deleteRecord(string $table_name, array $conditions, string $schema_name='public'):void
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $this->query(
            "DELETE FROM $table_name WHERE {$this->prepareQueryConditionsText($conditions)};",
            $conditions
        );
    }

    /**
     * @param string $table_name
     * @param string $schema_name
     * @return void
     */
    public function deleteAllRecords(string $table_name, string $schema_name='public'):void
    {
        if ($schema_name!=='public')
            $table_name = $schema_name.".".$table_name;

        $this->query("DELETE FROM $table_name;");
    }


    /**
     * @param array $conditions
     * @param string $separator
     * @param string $param_prefix
     * @return string
     */
    protected function prepareQueryConditionsText(array $conditions, string $separator=" && ", string $param_prefix=""): string
    {

        $query_text = "" ;

        $countConditions = count($conditions);
        foreach ($conditions as $key=>$value){
            $query_text .= $key."=:".$param_prefix.$key;
            $countConditions--;
            if ($countConditions !== 0){
                $query_text .= $separator;
            }
        }

        return $query_text;

    }

}
