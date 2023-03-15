<?php

namespace sketch\database\schema;

use sketch\database\DBSQL;

class ObjectMigration
{

    /**
     * @var DBSQL
     */
    protected $db;
    /**
     * @var string
     */
    private $schema_name="public";

    /**
     * @param DBSQL $db
     */
    public function __construct(DBSQL $db)
    {
        $this->db = $db;
    }

    /**
     * @param array $schema
     * @return void
     */
    public function migrateBySchema(array $schema): void
    {

        $this->schema_name = $schema["name"];

        if ( isset($schema["tables"]["toDelete"]) )
            $this->deleteTables($schema["tables"]["toDelete"]);

        if ( isset($schema["tables"]["toAdd"]) )
            $this->addTables($schema["tables"]["toAdd"]);

        if ( isset($schema["tables"]["toChange"]) )
            $this->alignmentTables($schema["tables"]["toChange"]);

    }


    /**
     * @param string[] $tables_names
     * @return void
     */
    private function deleteTables(array $tables_names): void
    {
        foreach($tables_names as $table_name=>$table){
            $this->db->dropTable($table_name, $this->schema_name);
            echo "$table_name was deleted\n";
        }
    }

    /**
     * @param array $tables
     * @return void
     */
    private function addTables(array $tables):void
    {
        foreach($tables as $table_name=>$table){
            $this->addTable($table_name, $table);
        }
    }

    /**
     * @param string $table_name
     * @param array $table_data
     * @return void
     */
    private function addTable(string $table_name, array $table_data):void
    {

        $params = [];
        foreach ($table_data["columns"] as $column_name=>$column_data) {
            $params[$column_name] = $this->prepareColumnContent($column_data);
        }

        $this->db->createTable(
            $table_name,
            $params,
            [],
            $this->schema_name
        );

        echo "table ".$table_name." was added\n";

    }

    /**
     * @param array $different_tables
     * @return void
     */
    private function alignmentTables(array $different_tables)
    {

        foreach ($different_tables as $table_name=>$table) {

            foreach ($table["columns"] as $action=>$columns) {
                switch ($action){
                    case "toDelete":
                        $this->deleteColumns($table_name, $columns);
                        break;
                    case "toAdd":
                        $this->addColumns($table_name, $columns);
                        break;
                    case "toChange":
                        $this->changeColumns($table_name, $columns);
                        break;
                }
            }
        }
    }

    /**
     * @param string $table_name
     * @param array $columns
     * @return void
     */
    private function deleteColumns(string $table_name, array $columns): void
    {
        foreach ($columns as $column_name=>$column) {
            $this->db->dropColumn($table_name, $column_name, $this->schema_name);
            echo "$this->schema_name => $table_name => column $column_name was deleted\n";
        }
    }

    /**
     * @param string $table_name
     * @param array $columns
     * @return void
     */
    private function addColumns(string $table_name, array $columns):void
    {
        foreach ($columns as $column_name=>$column) {
            $column_content = $this->prepareColumnContent($column);
            $this->db->addColumn(
                $table_name,
                $column_name,
                $column_content,
                $this->schema_name
            );
            echo "$this->schema_name => $table_name => column $column_name was added\n";
        }
    }

    /**
     * @param string $table_name
     * @param array $columns
     * @return void
     */
    private function changeColumns(string $table_name, array $columns):void
    {

        foreach ($columns as $column_name=>$column) {

            $OldType = $this->prepareTypeContent($column["old"]);
            $NewType = $this->prepareTypeContent($column["new"]);

            if ($OldType!==$NewType){
                $this->db->changeColumn(
                    $table_name,
                    $column_name,
                    "TYPE $NewType",
                    $this->schema_name
                );
                echo "$this->schema_name => $table_name 
                            => column $column_name TYPE $NewType\n";
            }

            if(!isset($column["old"]["not_null"]))
                $column["old"]["not_null"]=false;
            if(!isset($column["new"]["not_null"]))
                $column["new"]["not_null"]=false;
            if ($column["old"]["not_null"]!==$column["new"]["not_null"]){
                if ($column["new"]["not_null"]){
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "SET NOT NULL",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name => column $column_name SET NOT NULL\n";
                }else{
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "DROP NOT NULL",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name => column $column_name DROP NOT NULL\n";
                }
            }

            if(!isset($column["old"]["default"]) || $column["old"]["default"]=="") $column["old"]["default"]=null;
            if(!isset($column["new"]["default"]) || $column["old"]["default"]=="") $column["new"]["default"]=null;
            if ($column["old"]["default"]!==$column["new"]["default"]){
                if ($column["new"]["default"]===null){
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "DROP DEFAULT",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name => column $column_name DROP DEFAULT\n";
                }else{
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "SET DEFAULT {$column["new"]["default"]}",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name => column $column_name SET DEFAULT {$column["new"]["default"]}\n";
                }
            }

            echo "$this->schema_name => $table_name => column $column_name was changed\n";
        }

    }

    /**
     * @param array $column
     * @return string
     */
    private function prepareColumnContent(array $column): string
    {
        $content = $this->prepareTypeContent($column);

        if (isset($column["not_null"])&&($column["not_null"])){
            $content .= " NOT NULL";
        }
        if (isset($column["default"])&&($column["default"])){
            $content .= " DEFAULT ".$column["default"];
        }
        if (isset($column["primary_key"])&&($column["primary_key"])){
            $content .= " PRIMARY KEY";
        }

        return $content;
    }

    /**
     * @param array $column
     * @return string
     */
    private function prepareTypeContent(array $column):string
    {
        $content = $column["db_type"];
        if (isset($column["length"])){
            $content .= "(".$column["length"].")";
        }
        return $content;
    }



}