<?php

namespace sketch\database\schema;

use sketch\database\DBSQL;

class DBSchema
{

    /**
     * @var string
     */
    public $name;
    /**
     * @var DBSchemaTable[]
     */
    public $tables = [];

    /**
     * @param string $name
     */
    public function __construct(string $name='public')
    {
        $this->name = $name;
    }


    /**
     * @param string $schema_file_name
     */
    public function loadByFile(string $schema_file_name): void
    {
        if (!is_file($schema_file_name))
            exit("Schema file is unavailable: $schema_file_name");

        $schema = json_decode(file_get_contents($schema_file_name), true);
        if (!is_array($schema)
                || !isset($schema['name'])
        )
            exit("Schema file don't contains the correct schema: $schema_file_name");

        $this->name = $schema['name'];
        $this->clear();

        foreach ($schema['tables'] as $table_name=>$table) {
            $this->tables[$table_name] = new DBSchemaTable($table_name, $table);
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
     * @return void
     */
    public function clear():void
    {
        $this->tables = [];
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