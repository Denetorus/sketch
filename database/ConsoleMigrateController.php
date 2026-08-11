<?php

namespace sketch\database;

use sketch\database\DBSQL\DBSQL;
use sketch\database\schema\DBSchema;
use sketch\database\schema\DBSchemaMigrateConstructor;

class ConsoleMigrateController
{

    public DBSQL $db;
    public string $namespace_db = "";
    public string $namespace_rest = "";
    public string $namespace_object_base = "";
    public string $namespace_controller_base = "";

    public string $directory_db = "";
    public string $directory_rest = "";

    public string $schema_file = "";
    public string $new_schema_file = "";

    public function actionDo_migrate(): void
    {
        echo "\e[1;33mStart migration\e[0m\n";

        $migrate = new DBMigrate(
            $this->db,
            $this->directory_db."\migration",
            "\\".$this->namespace_db."\migration"
        );
        $migrate->run();

        echo "\e[1;33mFinish migration\e[0m\n";
    }

    public function actionGenerate_migrates(): void
    {

        echo "\e[1;32mStart migrate generation\e[0m\n";

        $prev_schema = new DBSchema('public');
        $prev_schema->loadByFile($this->schema_file);

        $next_schema = new DBSchema('public');
        $next_schema->loadByFile($this->new_schema_file);

        $constructor = new DBSchemaMigrateConstructor($prev_schema, $next_schema);
        $constructor->findDifference();
        $constructor->generateMigrateFile(
            $this->directory_db."\migration",
            "namespace ".$this->namespace_db."\migration;
use sketch\database\schema\ObjectMigration;"
        );

        unlink($this->schema_file);
        copy($this->new_schema_file, $this->schema_file);

        echo "\e[1;32mFinish migrate generation\e[0m\n";

    }

    public function actionGenerate_objects(): void
    {

        echo "\e[1;32mStart create objects\e[0m\n";

        if (!is_file($this->schema_file))
            exit("Schema file is unavailable: $this->schema_file");

        $schema = json_decode(file_get_contents($this->schema_file), true);
        if (!is_array($schema) || !isset($schema['name']))
            exit("Schema file don't contains the correct schema: $this->schema_file");

        $directory_origin = $this->directory_db.'\object';
        $directory_default = $this->directory_db.'\object_default';
        $namespace_object_origin = $this->namespace_db."\object";
        $namespace_object_default = $this->namespace_db."\object_default";
        foreach ($schema['tables'] as $table_name=>$table) {
            $class_name = $table_name;
            $table_object = '['.PHP_EOL;
            foreach ($table['columns'] as $column_name=>$column){
                if (!isset($column['uid'])){
                    exit('Undefined "uid" in table "'.$table_name.'"');
                }
                $table_object .='          [ ';
                $table_object .='"name" => "'.$column['uid'].'",';
                if (isset($column['type'])) {
                    $table_object .='"type" => "'.$column['type'].'",';
                }
                if (isset($column['refTable'])) {
                    $table_object .='"refTable" => "'.$column['refTable'].'",';
                }
                $table_object .='],'.PHP_EOL;
            }
            $table_object .='         ]';
            $content = $this->getContent_object_default(
                $class_name,
                $table_name,
                $table_object,
                $namespace_object_default
            );

            file_put_contents($directory_default."/".$class_name.".php", $content);

            $content = $this->getContent_object_origin(
                $class_name,
                $namespace_object_origin,
                $namespace_object_default
            );


            file_put_contents($directory_origin."/".$class_name.".php", $content);

        }



        echo "\e[1;32mFinish create objects\e[0m\n";

    }

    public function actionGenerate_controllers(): void
    {

        echo "\e[1;32mStart create rest controllers\e[0m\n";

        if (!is_file($this->schema_file))
            exit("Schema file is unavailable: $this->schema_file");

        $schema = json_decode(file_get_contents($this->schema_file), true);
        if (!is_array($schema) || !isset($schema['name']))
            exit("Schema file don't contains the correct schema: $this->schema_file");

        $namespace_object_origin = $this->namespace_db."\object";

        foreach ($schema['tables'] as $table_name=>$table) {
            if ($table_name==='users') continue;
            $class_name = ucfirst($table_name.'Controller');
            $content = $this->getContent_rest_controller(
                $class_name,
                $table_name,
                $namespace_object_origin
            );

            file_put_contents($this->directory_rest."/".$class_name.".php", $content);

        }

        echo "\e[1;32mFinish rest controllers\e[0m\n";

    }


    public function getContent_object_default($class_name, $table_name, $table_object, $namespace_object_default): string
    {
        return  <<<EOT
<?php

namespace $namespace_object_default;

class $class_name extends \\$this->namespace_object_base
{

    public string \$table_name = "$table_name";

    public function getFields(): array
    {
        return $table_object;
    }
}
EOT;
    }
    public function getContent_object_origin($class_name, $namespace_object, $namespace_object_default): string
    {
        $object_default = $namespace_object_default."\\".$class_name;
        return <<<EOT
<?php

namespace $namespace_object;

class $class_name extends \\$object_default
{

}
EOT;

    }
    public function getContent_rest_controller($class_name, $table_name, $namespace_object_origin): string
    {
        $namespace_object = $namespace_object_origin."\\".$table_name;
        return <<<EOT
<?php

namespace $this->namespace_rest;

use $namespace_object;

class $class_name extends \\$this->namespace_controller_base;
{

    public function getNewObject(\$key=-1, \$notCreated=false): $table_name
    {
        return new $table_name(\$key);
    }
}
EOT;

    }
}