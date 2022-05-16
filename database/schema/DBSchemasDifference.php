<?php

namespace sketch\database\schema;

class DBSchemasDifference
{

    /**
     * @var string
     */
    public $name;
    /**
     * @var array
     */
    public $tablesToDelete;
    /**
     * @var DBSchemaTable[]
     */
    public $tablesToAdd;
    /**
     * @var array
     */
    public $tablesToChange;
    /**
     * @var bool
     */
    public $present;

    public function __construct($name="public")
    {
        $this->name = $name;
        $this->clearAll();
    }

    /**
     * @return void
     */
    public function clearAll():void
    {
        $this->present = false;
        $this->clearTablesToDelete();
        $this->clearTablesToAdd();
        $this->clearTablesToChange();
    }
    /**
     * @return void
     */
    public function clearTablesToDelete():void
    {
        $this->tablesToDelete = [];
    }
    /**
     * @return void
     */
    public function clearTablesToAdd():void
    {
        $this->tablesToAdd = [];
    }
    /**
     * @return void
     */
    public function clearTablesToChange():void
    {
        $this->tablesToChange = [];
    }

    /**
     * @param string $table_name
     * @param array $data
     * @return void
     */
    public function addTableToDelete(string $table_name, array $data=[]):void
    {
        $this->tablesToDelete[$table_name] = $data;
        $this->present = true;
    }
    /**
     * @param string $table_name
     * @param array $data
     * @return void
     */
    public function addTableToAdd(string $table_name, array $data=[]):void
    {
        $this->tablesToAdd[$table_name] = $data;
        $this->present = true;
    }
    /**
     * @param string $table_name
     * @param array $data
     * @return void
     */
    public function addTableToChange(string $table_name, array $data=[]):void
    {
        $this->tablesToChange[$table_name] = $data;
        $this->present = true;
    }

    /**
     * @return array
     */
    public function toArray():array
    {
        $result = [
            "name" => $this->name
        ];

        if (count($this->tablesToDelete))
            $result['tables']['toDelete'] = $this->tablesToDelete;

        if (count($this->tablesToAdd))
            $result['tables']['toAdd'] = $this->tablesToAdd;

        if (count($this->tablesToChange))
            $result['tables']['toChange'] = $this->tablesToChange;

        return $result;
    }

}