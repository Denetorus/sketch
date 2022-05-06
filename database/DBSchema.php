<?php

namespace sketch\database;

class DBSchema
{

    private $db;
    private $schema_name;
    private $next_schema = [];
    private $prev_schema = [];
    private $dif_schema = [];

    /**
     * @param DBSQL $db
     * @param string $schema_name
     */
    public function __construct(DBSQL $db, string $schema_name='public')
    {
        $this->db = $db;
        $this->schema_name = $schema_name;
    }

    /**
     * @param array $next_schema
     */
    public function setNextSchema(array $next_schema): void
    {
        $this->next_schema = $next_schema;
    }

    /**
     * @param string $schema_file
     */
    public function setNextSchemaByFile(string $schema_file): void
    {

        $this->next_schema = $this->loadSchemaByFile($schema_file);
    }

    public function setNextSchemaByDB(): void
    {
        $this->next_schema = $this->loadSchemaByDB();
    }


    /**
     * @param array $next_schema
     */
    public function setPrevSchema(array $next_schema): void
    {

        $this->prev_schema = $next_schema;
    }

    /**
     * @param string $schema_file
     */
    public function setPrevSchemaByFile(string $schema_file): void
    {

        $this->prev_schema = $this->loadSchemaByFile($schema_file);
    }

    public function setPrevSchemaByDB(): void
    {
        $this->prev_schema = $this->loadSchemaByDB();
    }

    /**
     * @param array $dif_schema
     */
    public function setDifSchema(array $dif_schema): void
    {
        $this->dif_schema = $dif_schema;
    }



    public function clearDifference(): void
    {
        $this->dif_schema = [];
    }

    public function generateDifference(): void
    {
        $this->clearDifference();

        $toDelete = $this->getExcessTablesNames();
        $toAdd = $this->getMissingTables();
        $toChange = $this->getDifferentTables($toDelete);

        $tables = [];
        if (count($toDelete)===0)
            $tables["toDelete"] = $toDelete;
        if (count($toAdd)===0)
            $tables["toAdd"] = $toAdd;
        if (count($toChange)===0)
            $tables["toChange"] = $toChange;

        if (count($tables)===0)
            $this->dif_schema["tables"] = $tables;

    }

    public function migrateByDifference(): void
    {
        $tables = &$this->dif_schema["tables"];
        if ( count($tables["toDelete"])>0 )
            $this->deleteTables($tables["toDelete"]);
        if ( count($tables["toAdd"])>0 )
            $this->addTables($tables["toAdd"]);
        if ( count($tables["toChange"])>0 )
            $this->alignmentTables($tables["toChange"]);
    }


    public function createMigrateFileByDifference($directory, $file_header): void
    {

        if (count($this->dif_schema)===0){
            echo "Migrate file did not created => no difference";
            return;
        }


        $class_name = "migrate_".date('YmdHis');

        $content = "<?php
".$file_header."
class ".$class_name." extends sketch\database\ObjectMigration
{
    public function up()
    {
        \$this->migrateBySchema(json_decode('
            ".json_encode($this->dif_schema)."
        ',true));
    }
}
";

        file_put_contents($directory."/".$class_name.".php", $content);

    }



    private function loadSchemaByFile($schema_file): array
    {
        return json_decode(file_get_contents($schema_file), true);
    }

    private function loadSchemaByDB(): array
    {

        $result = [];
        $result["tables"] = [];


        $table_names = $this->db->getTablesBySchema($this->schema_name);
        foreach ($table_names as $table_name) {
            $result["tables"][$table_name]=[
                "columns" => []
            ];
        }

        $db_columns = $this->db->getColumnsBySchema($this->schema_name);
        foreach ($db_columns as $db_column) {

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

            $result["tables"][$db_column["table_name"]]["columns"][$db_column["column_name"]] = $column;

        }

        $db_columns = $this->db->getPrimaryKeysBySchema($this->schema_name);
        foreach ($db_columns as $db_column) {
            $result["tables"][$db_column["table_name"]]["columns"]
            [$db_column["column_name"]]["primary_key"] = true;
        }

        return $result;

    }

    private function getExcessTablesNames(): array
    {
        $result = [];
        foreach($this->prev_schema["tables"] as $key=> $table){
            if (!isset($this->next_schema["tables"][$key])) {
                $result[] = $key;
            }
        }
        return $result;

    }

    private function getMissingTablesNames(): array
    {
        $result = [];
        foreach($this->next_schema["tables"] as $key=> $table){
            if (!isset($this->prev_schema["tables"][$key])) {
                $result[] = $key;
            }
        }
        return $result;

    }

    private function getMissingTables(): array
    {
        $result = [];
        $table_names = $this->getMissingTablesNames();
        foreach ($table_names as $table_name) {
            $result[$table_name] = $this->next_schema["tables"][$table_name];
        }
        return $result;
    }

    private function getDifferentTables($not_checked_tables_names):array
    {
        $result = [];

        foreach ($this->next_schema["tables"] as $key=> $value) {

            if( in_array($key,$not_checked_tables_names) )
                continue;

            $difference = $this->compareTable($key);
            if ($difference===[])
                continue;

            $result[$key] = $difference;

        }

        return $result;
    }

    private function compareTable($table_name):array
    {
        $result = [];

        $table_columns = &$this->next_schema["tables"][$table_name]["columns"];
        $db_table_columns = &$this->prev_schema["tables"][$table_name]["columns"];

        foreach ($db_table_columns as $column_name=>$column) {
            if(!array_key_exists($column_name, $table_columns)){
                $result["columns"]["delete"][$column_name] = $column;
            }
        }

        foreach ($table_columns as $column_name=>$column) {
            if(!array_key_exists($column_name, $db_table_columns)){
                $result["columns"]["add"][$column_name] = $column;
                continue;
            }
            $content = $this->prepareColumnContent($column);
            $db_content = $this->prepareColumnContent($db_table_columns[$column_name]);
            if ($content!==$db_content) {
                $result["columns"]["change"][$column_name] =
                    [
                        "new" => $column,
                        "old" => $db_table_columns[$column_name]
                    ];
            }
        }


        return $result;
    }

    private function deleteTables($tables_names): void
    {
        foreach($tables_names as $table_name){
            $this->db->dropTable($table_name, $this->schema_name);
            unset($this->prev_schema["tables"][$table_name]);
            echo $table_name." was deleted\n";
        }
    }

    private function deleteColumns($table_name, $columns): void
    {
        foreach ($columns as $column_name=>$column) {
            $this->db->dropColumn($table_name, $column_name, $this->schema_name);
            echo "$this->schema_name => $table_name 
                    => column $column_name was deleted\n";
        }
    }

    private function addColumns($table_name, $columns)
    {
        foreach ($columns as $column_name=>$column) {
            $column_content = $this->prepareColumnContent($column);
            $this->db->addColumn(
                $table_name,
                $column_name,
                $column_content,
                $this->schema_name
            );
            echo "$this->schema_name => $table_name 
                        => column $column_name was added\n";
        }
    }

    private function changeColumns($table_name, $columns)
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
                    echo "$this->schema_name => $table_name 
                            => column $column_name SET NOT NULL\n";
                }else{
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "DROP NOT NULL",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name 
                            => column $column_name DROP NOT NULL\n";
                }
            }

            if(!isset($column["old"]["default"])) $column["old"]["default"]=null;
            if(!isset($column["new"]["default"])) $column["new"]["default"]=null;
            if ($column["old"]["default"]!==$column["new"]["default"]){
                if ($column["new"]["default"]===null){
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "DROP DEFAULT",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name 
                                => column $column_name DROP DEFAULT\n";
                }else{
                    $this->db->changeColumn(
                        $table_name,
                        $column_name,
                        "SET DEFAULT {$column["new"]["default"]}",
                        $this->schema_name
                    );
                    echo "$this->schema_name => $table_name}
                            => column $column_name
                            SET DEFAULT {$column["new"]["default"]}\n";
                }
            }

            echo "$this->schema_name => $table_name 
                    => column $column_name was changed\n";
        }

    }

    private function addTables($tables)
    {
        foreach($tables as $table_name=>$table){
            $this->addTable($table_name, $table);
        }
    }

    private function alignmentTables($different_tables)
    {

        foreach ($different_tables as $table_name=>$table) {

            foreach ($table["columns"] as $action=>$columns) {
                switch ($action){
                    case "delete":
                        $this->deleteColumns($table_name, $columns);
                        break;
                    case "add":
                        $this->addColumns($table_name, $columns);
                        break;
                    case "rename":
                        break;
                    case "change":
                        $this->changeColumns($table_name, $columns);
                        break;
                }
            }
        }
    }

    private function prepareColumnContent($column): string
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

    private function addTable($table_name, $table_schema){

        $params = [];
        foreach ($table_schema["columns"] as $column_name=>$column_params) {
            $params[$column_name] = $this->prepareColumnContent($column_params);
        }

        $this->db->createTable(
            $table_name,
            $params,
            null,
            $this->schema_name
        );

        echo "table ".$table_name." was added\n";

    }

    private function prepareTypeContent($column)
    {
        $content = $column["db_type"];
        if (isset($column["length"])){
            $content .= "(".$column["length"].")";
        }
        return $content;
    }


}