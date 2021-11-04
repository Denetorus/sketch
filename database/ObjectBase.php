<?php
namespace sketch\database;

abstract class ObjectBase
{

    /** @var DBSQL */
    public $db = null;
    public $table = "";
    public $ref = 0;
    public $props = [];

    public function __construct($id=null)
    {
        if ($id === null) {
            $this->createNew();
        } else {
            $this->ref = +$id;
            $this->load();
        }

    }

    public function __get($name)
    {
        if (isset($this->props[$name])) {
            return $this->props[$name];
        }
        return null;
    }

    public function save($withNewID=true)
    {
        $this->db->setRecord($this->table, $this->props, $withNewID);
    }

    public function update()
    {
        $this->db->updateRecord($this->table, $this->ref, $this->props);
    }

    public function load()
    {
        $this->props = $this->db->getRecord($this->table, $this->ref);
    }

    public function loadByConditions($conditions=null)
    {
        if ($conditions===null){
            $this->load();
        }else{
            $this->props = $this->db->getRecord($this->table, $conditions);
        }
    }

    public function createNew()
    {
        $this->props = $this->db->createRecord($this->table);
    }

    public function delete()
    {
        $this->props = $this->db->deleteRecord($this->table, $this->ref);
    }

    public function deleteAll()
    {
        $this->props = $this->db->deleteAllRecords($this->table);
    }

    public function clear()
    {
        $this->createNew();
    }

    public function getList()
    {
        return $this->db->getList($this->table);
    }

}