<?php

namespace sketch\database;

use sketch\database\DBSQL\DBSQL;
use sketch\database\schema\DBSchema;
use sketch\database\schema\DBSchemaMigrateConstructor;

class ConsoleMigrateController
{

    public string $namespace = "";

    public string $namespace_rest = "controller\object";

    public string $schema_file = "db_public_schema.json";
    public string $new_schema_file = "db_public_schema_new.json";

    public function getDB(): DBSQL|null
    {
        return null;
    }
    public function getDirectory_DB(): string
    {
        return dirname(__FILE__)."/".$this->namespace;
    }
    public function getParentObjectNamespace(): string
    {
        return $this->namespace."\DBObjectBase";
    }

    public function getParentControllerNamespace(): string
    {
        return "ControllerRestDBObject";
    }

    public function actionDo_migrate(): void
    {
        echo "\e[1;33mStart migration\e[0m\n";

        $migrate = new DBMigrate(
            $this->getDB(),
            $this->getDirectory_DB()."/migration",
            "\\".$this->namespace."\migration"
        );
        $migrate->run();

        echo "\e[1;33mFinish migration\e[0m\n";
    }

    public function actionGenerate_migrates(): void
    {

        echo "\e[1;32mStart migrate generation\e[0m\n";

        $schema_file = $this->getDirectory_DB()."/".$this->schema_file;
        $new_schema_file = $this->getDirectory_DB()."/".$this->new_schema_file;
        $prev_schema = new DBSchema('public');
        $prev_schema->loadByFile($schema_file);

        $next_schema = new DBSchema('public');
        $next_schema->loadByFile($new_schema_file);

        $constructor = new DBSchemaMigrateConstructor($prev_schema, $next_schema);
        $constructor->findDifference();
        $constructor->generateMigrateFile(
            $this->getDirectory_DB()."/migration",
            "namespace ".$this->namespace."\migration;
use sketch\database\schema\ObjectMigration;"
        );

        unlink($schema_file);
        copy($new_schema_file, $schema_file);

        echo "\e[1;32mFinish migrate generation\e[0m\n";

    }

    public function actionGenerate_objects(): void
    {

        echo "\e[1;32mStart create objects\e[0m\n";

        $scheme_file = $this->getDirectory_DB()."\\".$this->schema_file;
        if (!is_file($scheme_file))
            exit("Schema file is unavailable: $scheme_file");

        $schema = json_decode(file_get_contents($scheme_file), true);
        if (!is_array($schema) || !isset($schema['name']))
            exit("Schema file don't contains the correct schema: $scheme_file");

        $directory_origin = $this->getDirectory_DB().'\object';
        $directory_default = $this->getDirectory_DB().'\object_default';
        $namespace_object_origin = $this->namespace."\object";
        $namespace_object_default = $this->namespace."\object_default";
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

    public function actionGenerate_controllersRest(): void
    {

        echo "\e[1;32mStart create rest controllers\e[0m\n";

        $scheme_file = $this->getDirectory_DB().$this->schema_file;

        if (!is_file($scheme_file))
            exit("Schema file is unavailable: $scheme_file");

        $schema = json_decode(file_get_contents($scheme_file), true);
        if (!is_array($schema) || !isset($schema['name']))
            exit("Schema file don't contains the correct schema: $scheme_file");

        $directory_rest = dirname(__FILE__).'/'.$this->namespace_rest;
        $namespace_object_origin = $this->namespace."\object";

        foreach ($schema['tables'] as $table_name=>$table) {
            if ($table_name==='users') continue;
            $class_name = ucfirst($table_name.'Controller');
            $content = $this->getContent_rest_controller(
                $class_name,
                $table_name,
                $namespace_object_origin
            );

            file_put_contents($directory_rest."/".$class_name.".php", $content);

        }

        echo "\e[1;32mFinish rest controllers\e[0m\n";

    }


    public function getContent_object_default($class_name, $table_name, $table_object, $namespace_object_default): string
    {
        $namespace_parent_object = $this->getParentObjectNamespace();
        return  <<<EOT
<?php

namespace $namespace_object_default;

class $class_name extends \\$namespace_parent_object
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
        $namespace_parent_controller = $this->getParentControllerNamespace();
        return <<<EOT
<?php

namespace $this->namespace_rest;

use $namespace_object;

class $class_name extends \\$namespace_parent_controller
{

    public function getNewObject(\$key=-1, \$notCreated=false): $table_name
    {
        return new $table_name(\$key);
    }
}
EOT;

    }
}