<?php
namespace sketch\database;

abstract class ObjectUIDBase
{
    public $db = null;
    public $table = "";
    public $ref = "";
    public $props = [];

    public function __construct($uid=null)
    {
        if ( $uid!==null) {
            $this->ref = $uid;
            $this->load();
        } else {
            $this->createNew();
            $this->ref = "";
        }
    }

    public function __get($name)
    {
        if (isset($this->props[$name])) {
            return $this->props[$name];
        }
        return null;
    }

    public function save($withNewUID=true)
    {
        if ($withNewUID){
            $this->ref = UUID::createUUID();
            $this->props['uid'] = $this->ref;
        }
        $this->db->setRecord($this->table, $this->props, false);
    }

    public function update()
    {
        $this->db->updateRecord($this->table, ['uid'=> $this->ref], $this->props);
    }

    public function load()
    {
        $this->props = $this->db->getRecord($this->table, ['uid'=> $this->ref]);
    }


    public function createNew()
    {
        $this->props = $this->db->createRecord($this->table);
    }

    public function delete()
    {
        $this->props = $this->db->deleteRecord($this->table, ['uid'=> $this->ref]);
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