<?php

namespace sketch\database\DBRecord;

use sketch\database\DBRecord;
use sketch\database\DBSQL;

abstract class DBRecordBase implements DBRecord
{

    /** @var DBSQL */
    protected $db = null;
    /**
     * @var string
     */
    public $schema_name = 'public';
    /**
     * @var string
     */
    public $table_name = '';
    /**
     * @var string
     */
    public $key_name = 'id';
    /**
     * @var int|string|null
     */
    public $ref = null;
    /**
     * @var array
     */
    public $props = [];

    /**
     * @param int|string|null $ref
     * @param bool $notCreated
     */
    public function __construct($ref=null, bool $notCreated=false)
    {
        $this->setDB();
        $this->ref = $ref;


        if ($notCreated)
            return;

        if ($ref === null) {
            $this->createNew();
            return;
        }

        $this->load();
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if (isset($this->props[$name])) {
            return $this->props[$name];
        }
        return null;
    }

    public function upsert():void
    {
        $this->db->upsertRecord(
            $this->table_name,
            [$this->key_name => $this->ref],
            $this->props,
            $this->schema_name
        );
    }

    /**
     * @param bool $with_new_ref
     * @return void
     */
    public function save(bool $with_new_ref=true):array
    {
        $params = $this->props;
        if ($with_new_ref){
            unset($params[$this->key_name]);
        }
        return $this->db->setRecordAndReturnPrimaryKey($this->table_name, $params, $this->key_name, $this->schema_name);
    }

    /**
     * @return void
     */
    public function update():void
    {
        $this->db->updateRecord(
            $this->table_name,
            [$this->key_name => $this->ref],
            $this->props,
            $this->schema_name
        );
    }

    /**
     * @return void
     */
    public function load():void
    {
        $this->props = $this->db->getRecord(
            $this->table_name,
            [$this->key_name => $this->ref],
            $this->schema_name
        );
    }

    /**
     * @param array $conditions
     * @return void
     */
    public function loadByConditions(array $conditions):void
    {

        if (empty($conditions)){
            $this->createNew();
            return;
        }

        $this->props = $this->db->getRecord($this->table_name, $conditions);
        $this->ref = $this->props[$this->key_name] ?? null;

    }

    /**
     * @return void
     */
    public function createNew():void
    {
        $this->props = $this->db->createRecord($this->table_name);
        $this->ref = null;
    }

    /**
     * @return void
     */
    public function delete():void
    {
        $this->db->deleteRecord($this->table_name, [$this->key_name=>$this->ref], $this->schema_name);
    }

    /**
     * @return void
     */
    public function deleteAll():void
    {
        $this->db->deleteAllRecords($this->table_name);
    }

    /**
     * @return void
     */
    public function clear():void
    {
        $this->createNew();
    }

    /**
     * @return array
     */
    public function getList():array
    {
        return $this->db->getList($this->table_name);
    }

}