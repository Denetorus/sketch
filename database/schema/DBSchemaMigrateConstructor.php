<?php

namespace sketch\database\schema;

class DBSchemaMigrateConstructor
{

    /**
     * @var DBSchema
     */
    public $next_schema;
    /**
     * @var DBSchema
     */
    public $prev_schema;
    /**
     * @var DBSchemasDifference
     */
    public $difference;

    /**
     * @param DBSchema $prev_schema
     * @param DBSchema $next_schema
     */
    public function __construct(DBSchema $prev_schema, DBSchema $next_schema)
    {
        $this->prev_schema = $prev_schema;
        $this->next_schema = $next_schema;
        $this->difference = new DBSchemasDifference();
    }

    /**
     * @param DBSchema $schema
     */
    public function setNextSchema(DBSchema $schema): void
    {
        $this->next_schema = $schema;
    }

    /**
     * @param DBSchema $schema
     */
    public function setPrevSchema(DBSchema $schema): void
    {
        $this->prev_schema = $schema;
    }

    /**
     * @return void
     */
    public function findDifference(): void
    {
        $this->difference->clearAll();
        $this->findTablesDifference();
    }

    /**
     * @return void
     */
    public function findTablesDifference()
    {
        $this->findTablesToDelete();
        $this->findTablesToAddAndChange();
    }

    /**
     * @return void
     */
    private function findTablesToDelete():void
    {
        foreach ($this->prev_schema->tables as $table_name => $table){
            if ( !isset($this->next_schema->tables[$table_name]) ) {
                $this->difference->addTableToDelete($table_name);
            }
        }
    }

    /**
     * @return void
     */
    private function findTablesToAddAndChange():void
    {
        foreach ($this->next_schema->tables as $table_name=>$table_data) {

            if (!isset($this->prev_schema->tables[$table_name])) {
                $this->difference->addTableToAdd($table_name, $table_data->toArray());
                continue;
            }

            $table_diff = $this->compareTables($table_name);
            if ($table_diff===[])
                continue;

            $this->difference->addTableToChange($table_name, $table_diff);

        }
    }

    /**
     * @param string $table_name
     * @return array
     */
    public function compareTables(string $table_name):array
    {
        $result = [];
        $result["columns"] = $this->compareTableColumns(
            $this->next_schema->tables[$table_name]["columns"],
            $this->prev_schema->tables[$table_name]["columns"]
        );
        return $result;
    }

    /**
     * @param array $next_columns
     * @param array $prev_columns
     * @return array
     */
    private function compareTableColumns(array $next_columns, array $prev_columns):array
    {

        $result = [];
        foreach ($prev_columns as $column_name=>$column_data) {
            if(!array_key_exists($column_name, $next_columns)){
                $result["toDelete"][$column_name] = $column_data;
            }
        }

        foreach ($next_columns as $column_name=>$column) {

            if (!array_key_exists($column_name, $prev_columns)){
                $result["toAdd"][$column_name] = $column;
                continue;
            }

            $compare = $prev_columns[$column_name]->compareByOtherColumn($column);
            if ($compare!==[]) {
                $result["toChange"][$column_name] = $compare;
            }

        }

        return $result;

    }


    /**
     * @param $directory
     * @param $file_header
     * @return void
     */
    public function generateMigrateFile($directory, $file_header): void
    {

        if (!$this->difference->present){
            echo "Migrate file did not created => no difference\n";
            return;
        }

        $class_name = "migrate_".date('YmdHis');

        $content = <<<EOT
<?php

$file_header

class ".$class_name." extends ObjectMigration
{
    public function up()
    {
        \$this->migrateBySchema(json_decode('
            ".json_encode({$this->difference->toArray()}, JSON_PRETTY_PRINT)."
        ',true));
    }
}
EOT;

        file_put_contents($directory."/".$class_name.".php", $content);

    }


}