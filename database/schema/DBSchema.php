<?php

namespace sketch\database\schema;

use sketch\database\DBSQL\DBSQL;
use stdClass;

class DBSchema
{

    /**
     * @var string
     */
    public string $name;

    /**
     * @var DBSchemaTable[]
     */
    public array $tables;

    /**
     * @param string $name
     */
    public function __construct(string $name='public')
    {
        $this->name = $name;
        $this->clear();
    }

    /**
     * @return void
     */
    public function clear():void
    {
        $this->tables = [];
    }

    public function getSchemaDataByFile($schema_file_name): object
    {
        $result = new stdClass();
        $result->errors = "";
        $result->data = [];

        if (!is_file($schema_file_name)){
            $result->errors = "Schema file not found: $schema_file_name";
            return $result;
        }

        try {
            $schema = json_decode(file_get_contents($schema_file_name), true);
        }catch (\Exception $e){
            $result->errors = "Schema file is not valid: $schema_file_name";
            return $result;
        }

        $result->data = $schema;

        if (!is_array($schema)){
            $result->errors = "Schema file is not valid (data type should be array): $schema_file_name";
        }elseif (!isset($schema['name'])){
            $result->errors = "Schema file is not valid (data should contain 'name'): $schema_file_name";
        }elseif (!isset($schema['tables'])){
            $result->errors = "Schema file is not valid (data should contain 'tables'): $schema_file_name";
        }

        return $result;

    }

    /**
     * @param string $schema_file_name
     */
    public function loadByFile(string $schema_file_name): void
    {
        $shemaData = $this->getSchemaDataByFile($schema_file_name);
        if ($shemaData->errors !== ""){
            exit($shemaData->errors);
        }

        $schema = $shemaData->data;

        $this->name = $schema['name'];
        $this->clear();

        foreach ($schema['tables'] as $table_name=>$table) {

            $this->tables[$table_name] = new DBSchemaTable($table_name);
            if (isset($table["objectType"])){
                $objectTypeTable = $schema['objectTypes'][$table["objectType"]];
                $this->tables[$table_name]->fillData($objectTypeTable);
            }
            $this->tables[$table_name]->fillData($table);

        }
    }

    /**
     * @param DBSQL $db
     * @param string $name
     */
    public function loadByDB(DBSQL $db, string $name='public'): void
    {

        $this->name = $name;
        $this->clear();

        $table_names = $db->getTablesBySchema($this->name);
        foreach ($table_names as $table_name) {
            $this->tables[$table_name]=new DBSchemaTable($table_name);
        }

        $db_columns = $db->getColumnsBySchema($this->name);
        foreach ($db_columns as $db_column) {

            $table_name = $db_column["table_name"];
            $column_name = $db_column["column_name"];

            $column = [];
            $column["db_type"] = $db_column["column_data_type"];
            if ($db_column["column_not_null"]){
                $column["not_null"] = true;
            }
            if ($db_column["column_default"]!==null){

                $column["default"] = $db_column["column_default"];
                $default_sequences = "nextval('"
                    .$db_column["table_name"]."_"
                    .$db_column["column_name"]."_seq'::regclass)";
                if ($column["default"]===$default_sequences){
                    if($column["db_type"]="integer"){
                        $column["db_type"]="serial";
                        unset($column["default"]);
                    }
                }

            }

            if ($db_column["column_max_length"]!==null){
                $column["length"] = $db_column["column_max_length"];
            }

            $this->tables[$table_name]->addColumn($column_name, $column);

        }

        $db_columns = $db->getPrimaryKeysBySchema($this->name);
        foreach ($db_columns as $db_column) {
            $this->tables[$db_column["table_name"]]
                ->setPrimaryKey($db_column["column_name"]);
        }

    }


    /**
     * @param string $table_name
     * @param array $table
     */
    public function addTable(string $table_name, array $table):void
    {
        $this->tables[$table_name] = new DBSchemaTable($table_name, $table);
    }

    /**
     * @param array $tables
     */
    public function addTables(array $tables):void
    {
        foreach($tables as $table_name=>$table){
            $this->addTable($table_name, $table);
        }
    }

    /**
     * @param string $table_name
     */
    public function deleteTable(string $table_name):void{
        unset($this->tables[$table_name]);
    }

    /**
     * @param array $tables_names
     */
    public function deleteTables(array $tables_names): void
    {
        foreach($tables_names as $table_name){
            $this->deleteTable($table_name);
        }
    }


}