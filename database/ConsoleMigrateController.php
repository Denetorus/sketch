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
    public string $directory_js_objects = "";

    public object $schema;
    public string $schema_file = "";
    public string $new_schema_file = "";

    public function loadSchema($schema_file): void
    {
        $this->schema = new DBSchema();
        $this->schema->loadByFile($schema_file);
    }


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

        $this->loadSchema($this->schema_file);

        $directory_origin = $this->directory_db.'\object';
        $directory_default = $this->directory_db.'\object_default';
        $namespace_object_origin = $this->namespace_db."\object";
        $namespace_object_default = $this->namespace_db."\object_default";
        foreach ( $this->schema->tables as $table_name=>$table) {
            $class_name = $table_name;
            $table_object = '['.PHP_EOL;
            foreach ($table->columns as $column_name=>$column){
                $table_object .='          [ ';
                $table_object .='"name" => "'.$column_name.'",';
                if ($column->type !== "") {
                    $table_object .='"type" => "'.$column->type.'",';
                }
                if ($column->refTable !== "") {
                    $table_object .='"refTable" => "'.$column->refTable.'",';
                    $refTable = $this->schema->tables[$column->refTable];
                    $table_object .='"refColumn" => "'.$refTable->refColumn.'",';
                    $table_object .='"refPresentation" => "'.$refTable->refPresentation.'",';
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

            $object_origin = $directory_origin."\\".$class_name.".php";
            if (!is_file($object_origin)){
                $content = $this->getContent_object_origin(
                    $class_name,
                    $namespace_object_origin,
                    $namespace_object_default
                );
                file_put_contents($object_origin, $content);
            }
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

    public function actionGenerate_js_schema(): void
    {

        echo "\e[1;32mStart create js scheme\e[0m\n";

        $this->loadSchema($this->schema_file);

        $FieldDescriptions = [];
        $Schemas = "";
        foreach ($this->schema->tables as $table_name=>$table) {

            $class_name = $table_name;
            $title = $table->title ?? $class_name;
            $titleList = $table->titleList ?? $class_name;

            $Schema = "\t".$class_name.":{".PHP_EOL;
            $Schema .= "\t"."\t".'name: "'.$class_name.'",'.PHP_EOL;
            $Schema .= "\t"."\t".'db: window.db,'.PHP_EOL;
            $Schema .= "\t"."\t".'title: "'.$title.'",'.PHP_EOL;
            $Schema .= "\t"."\t".'titleList: "'.$titleList.'",'.PHP_EOL;
            $Schema .= "\t"."\t".'fieldDescriptions: {'.PHP_EOL;

            foreach ($table->columns as $columnName=>$column) {
                $Line = $columnName.": new ";
                switch ($column->type) {
                    case "number":
                        $Line = $Line."DBFieldDescriptionNumber";
                        if (!isset($FieldDescriptions["number"])){
                            $FieldDescriptions["number"] = "DBFieldDescriptionNumber";
                        }
                        break;
                    case "id":
                        $Line = $Line."DBFieldDescriptionId";
                        if (!isset($FieldDescriptions["id"])){
                            $FieldDescriptions["id"] = "DBFieldDescriptionId";
                        }
                        break;
                    case "uid":
                        $Line = $Line."DBFieldDescriptionUID";
                        if (!isset($FieldDescriptions["uid"])){
                            $FieldDescriptions["uid"] = "DBFieldDescriptionUID";
                        }
                        break;
                    case "string":
                    case "json":
                    default:
                        $Line = $Line."DBFieldDescriptionString";
                        if (!isset($FieldDescriptions["string"])){
                            $FieldDescriptions["string"] = "DBFieldDescriptionString";
                        }
                        break;
                }
                $Line .= "({";
                if($column->title !== ""){
                    $Line .= 'title: "'.$column->title.'", ';
                }
                if($column->primary_key){
                    $Line .= "isKey: true, ";
                }
                $Line .= "}),";
                $Schema .= "\t"."\t"."\t".$Line.PHP_EOL;

            }

            $Schema .= "\t"."\t".'}'.PHP_EOL;
            $Schema .= "\t"."},".PHP_EOL;

            $Schemas .= $Schema;

        }
        $content = "import {";
        $separator = "";
        foreach ($FieldDescriptions as $FieldDescription) {
            $content .= $separator.$FieldDescription;
            $separator = ", ";
        }
        $content .= "} from '/js/external/sk-cmp/sk-cmp-db-objects.js'".PHP_EOL.PHP_EOL;
        $content .= "export const ObjectsSchemas = {".PHP_EOL;
        $content .= $Schemas.PHP_EOL;
        $content .= "}";


        file_put_contents($this->directory_js_objects."/ObjectsSchemas.js", $content);

        echo "\e[1;32mFinish js schema\e[0m\n";

    }

    public function actionGenerate_js_objects(): void
    {

        echo "\e[1;32mStart create js objects\e[0m\n";

        $this->loadSchema($this->schema_file);

        foreach ($this->schema->tables as $table_name=>$table) {

            $class_name = $table_name;

            $DirName = $this->directory_js_objects . "\\" . $class_name;
            if (!is_dir($DirName)) {
                mkdir($DirName);
                echo "Generated directory ".$DirName." \n";
            }

            $FileName = $DirName . "\\" . $table_name . "_Object.js";
            if (!is_file($FileName)) {
                $content = $this->getContent_js_Object($class_name);
                file_put_contents($FileName, $content);
                echo "Generated js object ".$FileName." \n";
            }

            $FileName = $DirName . "\\" . $table_name . "_ListForm.js";
            if (!is_file($FileName)) {
                $content = $this->getContent_js_ListForm($class_name);
                file_put_contents($FileName, $content);
                echo "Generated js object ".$FileName." \n";
            }

            $FileName = $DirName . "\\" . $table_name . "_ItemForm.js";
            if (!is_file($FileName)) {
                $content = $this->getContent_js_ItemForm($class_name);
                file_put_contents($FileName, $content);
                echo "Generated js object's Item Form ".$FileName." \n";
            }

            $FileName = $DirName . "\\" . $table_name . "_TableObject.js";
            if (!is_file($FileName)) {
                $content = $this->getContent_js_TableObject($class_name);
                file_put_contents($FileName, $content);
                echo "Generated js Table Object ".$FileName." \n";
            }

        }
        echo "\e[1;32mFinish js objects\e[0m\n";

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

class $class_name extends \\$this->namespace_controller_base
{

    public function getNewObject(\$key=-1, \$notCreated=false): $table_name
    {
        return new $table_name(\$key);
    }
}
EOT;

    }
    public function getContent_js_ItemForm($class_name): string
    {
        return  <<<EOT
import {Schema_ItemForm} from "../Schema_ItemForm.js";
import {{$class_name}_Object} from "./{$class_name}_Object.js";

export class {$class_name}_ItemForm extends Schema_ItemForm{

    constructor(windowOwner, key) {
        const object = new {$class_name}_Object(key);
        super("app_ItemForm", windowOwner, object);
    }
}
customElements.define("$class_name-itemform",{$class_name}_ItemForm);
EOT;
    }
    public function getContent_js_ListForm($class_name): string
    {
        return  <<<EOT
import {Schema_ListForm} from "../Schema_ListForm.js";
import {Schema_Table} from "../Schema_Table.js";
import {{$class_name}_ItemForm} from "./{$class_name}_ItemForm.js";
import {{$class_name}_TableObject} from "./{$class_name}_TableObject.js";

export class {$class_name}_ListForm extends Schema_ListForm{

    constructor(windowOwner) {
        const table = new Schema_Table(new {$class_name}_TableObject());
        super("{$class_name}_ListForm", windowOwner, table);
    }

    getItemForm(key){
        return new {$class_name}_ItemForm(this, key)
    }

}
customElements.define('$class_name-listform', {$class_name}_ListForm);
EOT;

    }
    public function getContent_js_Object($class_name): string
    {
        return  <<<EOT
import {Schema_Object} from "../Schema_Object.js";
import {ObjectsSchemas} from "../ObjectsSchemas.js";

export class {$class_name}_Object extends Schema_Object{
    constructor(key) {
        super(ObjectsSchemas.$class_name, key);
    }
}
EOT;

    }
    public function getContent_js_TableObject($class_name): string
    {
        return  <<<EOT
import {Schema_TableObject} from "../Schema_TableObject.js";
import {ObjectsSchemas} from "../ObjectsSchemas.js";
import {{$class_name}_Object} from "./{$class_name}_Object.js";

export class {$class_name}_TableObject extends Schema_TableObject{
    constructor() {
        super(ObjectsSchemas.$class_name);
        this.objectsSchema = ObjectsSchemas.$class_name;
    }

    getItemObject(key){
        return new {$class_name}_Object(key);
    }

}
EOT;

    }

}