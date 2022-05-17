<?php
namespace sketch\database;

abstract class ObjectUIDBase implements DBRecord
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
    public $ref = "";
    /**
     * @var array
     */
    public $props = [];

    /**
     * @param string $uid
     * @param bool $notCreated
     */
    public function __construct(string $uid="", bool $notCreated=false)
    {
        $this->setDB();

        if ($notCreated)
            return;

        if ($uid === '') {
            $this->createNew();
            return;
        }

        $this->ref = $uid;
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
            unset($params["uid"]);
        }
        return $this->db->setRecordAndReturnPrimaryKey($this->table_name, $params, 'uid', $this->schema_name);
    }

    /**
     * @return void
     */
    public function update():void
    {
        $this->db->updateRecord(
            $this->table_name,
            ['uid'=>$this->ref],
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
            ['uid' => $this->ref],
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
        $this->ref = $this->props['uid'];

    }

    /**
     * @return void
     */
    public function createNew():void
    {
        $this->props = $this->db->createRecord($this->table_name);
        $this->ref = "";
    }

    /**
     * @return void
     */
    public function delete():void
    {
        $this->db->deleteRecord($this->table_name, ['uid'=>$this->ref], $this->schema_name);
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