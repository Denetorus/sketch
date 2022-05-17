<?php

namespace sketch\database;

class DBMigrate
{
    private $db;
    private $schema_name;
    private $directory;
    private $namespace;

    public function __construct(DBSQL $db, string $directory, string $namespace, string $schema_name='migrate')
    {
        $this->db = $db;
        $this->directory = $directory;
        $this->namespace = $namespace;
        $this->schema_name = $schema_name;
    }

    public function UpOne($class_name): void
    {
        $full_class_name = $this->namespace."\\".$class_name;

        $obj = new $full_class_name($this->db);
        $obj->up();

        $this->db->setRecord(
            $this->schema_name.".version",
            ["version"=>$class_name]
        );

        echo "Migrate $class_name is execute \n";

    }

    public function getMigrationListAll(): array
    {
        $result = [];
        $files = glob($this->directory.'\\*.php');
        foreach ($files as $file) {
            $result[] = basename($file, ".php");
        }
        return $result;
    }

    public function getMigrationListNew(): array
    {

        $result = $this->getMigrationListAll();

        $migrated_versions = $this->db->select(
            "SELECT $this->schema_name.version FROM $this->schema_name.version;"
        );

        foreach ($migrated_versions as $migrated_version) {
            $key = array_search($migrated_version["version"], $result);
            if ($key!==false){
                unset($result[$key]);
            }
        }

        return $result;

    }


    public function run($params=[]): string
    {
        if ($this->checkVersionTable()){
            $list = $this->getMigrationListNew();
        } else {
            $this->createVersionTable();
            $list = $this->getMigrationListAll();
        }

        if (Count($list)===0){
            echo "Migrate no required \n";
            return "";
        }

        if (!empty($params['up'])){
            $this->upOne($list[0]);
            echo "Migrate up 1 is execute \n";
            return "";
        }

        foreach ($list as $className) {
            echo "Migrate $className \n";
            $this->upOne($className);
        }
        echo "Migrate all is execute \n";
        return "";
    }


    private function checkVersionTable(): bool
    {
        return $this->db->tableIsExist('version',$this->schema_name);
    }

    private function createVersionTable(): void
    {
        $this->db->createSchema($this->schema_name);
        $this->db->createTable(
            "version",
            [
                'version' => 'character varying(180) NOT NULL PRIMARY KEY',
                'apply_time' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            ],
            null,
            $this->schema_name
        );
    }


}