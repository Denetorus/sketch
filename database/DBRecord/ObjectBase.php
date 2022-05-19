<?php

namespace sketch\database\DBRecord;

use sketch\database\DBRecord;
use sketch\database\DBSQL;

abstract class ObjectBase implements DBRecord
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
     * @var int
     */
    public $ref = -1;
    /**
     * @var array
     */
    public $props = [];

    /**
     * @param int $id
     * @param bool $notCreated
     */
    public function __construct(int $id=-1, bool $notCreated=false)
    {
        $this->setDB();

        if ($notCreated)
            return;

        if ($id < 0) {
            $this->createNew();
            return;
        }

        $this->ref = +$id;
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

    /**
     * @param bool $with_new_ID
     * @return void
     */
    public function save(bool $with_new_ID=true):array
    {
        $params = $this->props;
        if ($with_new_ID){
            unset($params["id"]);
        }
        return $this->db->setRecordAndReturnPrimaryKey($this->table_name, $params, 'id', $this->schema_name);
    }

    /**
     * @return void
     */
    public function update():void
    {
        $this->db->updateRecord(
            $this->table_name,
            ['id'=>$this->ref],
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
            ['id' => $this->ref],
            $this->schema_name
        );
    }

    /**
     * @param array $conditions
     * @return void
     */
    public function loadByConditions(array $conditions):void
    {

        if (empty($conditions===[])){
            $this->createNew();
            return;
        }

        $this->props = $this->db->getRecord($this->table_name, $conditions);
        $this->ref = $this->props['id'];

    }

    /**
     * @return void
     */
    public function createNew():void
    {
        $this->props = $this->db->createRecord($this->table_name);
        $this->ref = -1;
    }

    /**
     * @return void
     */
    public function delete():void
    {
        $this->db->deleteRecord($this->table_name, ['id'=>$this->ref], $this->schema_name);
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