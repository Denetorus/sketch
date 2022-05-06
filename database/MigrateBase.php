<?php

namespace sketch\database;

use sketch\CommandInterface;

class MigrateBase implements CommandInterface
{
    public $db;


    public function up(){}
    public function down(){}

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function checkMigrationTable()
    {
        return $this->db->tableIsExist('migration');
    }

    private function createMigrationTable()
    {
        $this->db->createTable('migration', [
            'version' => 'character varying(180) NOT NULL',
            'apply_time' => 'integer',
        ],
            [
                'CONSTRAINT migration_pkey PRIMARY KEY (version)',

            ]
        );
    }

    public function getMigrationListAll()
    {
        $MigrationsNameSpase =  get_class($this)."_files";
        $path = ROOT.'\\'.str_replace('\\','/',$MigrationsNameSpase)."\\*.php";
        $List = [];
        foreach (glob($path) as $File) {
            $List[] = $MigrationsNameSpase.'\\'.basename($File, ".php");
        }
        return $List;
    }

    public function getMigrationListNew()
    {
        $MigrationsNameSpase =  get_class($this)."_files";
        $path = ROOT.'/'.str_replace('\\','/',$MigrationsNameSpase)."/*.php";
        $List = [];
        foreach (glob($path) as $File) {
            $className = basename($File, ".php");
            if (! $this->db->recordIsExist('migration', ["version" => $className])){
                $List[] = $MigrationsNameSpase.'\\'.$className;
            };
        }
        return $List;
    }

    public function upOne($className)
    {
        $CurrentMigrate = new $className;
        $CurrentMigrate->up();
        $time = time();
        $MigrateName = join('', array_slice(explode('\\', $className), -1));
        $this->db->query(
            "INSERT INTO migration (version, apply_time) 
             VALUES ('{$MigrateName}', {$time})"
        );
        echo "Migrate {$MigrateName} is execute \n";
    }

    public function run($params=[])
    {
        if ($this->checkMigrationTable()){
            $list = $this->getMigrationListNew();
        } else {
            $this->createMigrationTable();
            $list = $this->getMigrationListAll();
        };


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
            echo "Migrate {$className} is starting \n";
            $this->upOne($className);
        }
        echo "Migrate all is execute \n";
        return "";
    }
}
