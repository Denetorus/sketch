<?php

namespace sketch\database;

class ObjectMigration
{

    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function migrateBySchema($schema): void
    {
        $migrate = new DBSchema($this->db);
        $migrate->setDifSchema($schema);
        $migrate->migrateByDifference();
    }

}