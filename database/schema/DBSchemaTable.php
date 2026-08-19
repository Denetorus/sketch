<?php

namespace sketch\database\schema;

class DBSchemaTable
{

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $refColumn = "";

    /**
     * @var string
     */
    public string $refPresentation = "";

    /**
     * @var string
     */
    public string $title = "";

    /**
     * @var string
     */
    public string $titleList = "";


    /**
     * @var DBSchemaTableColumn[]
     */
    public array $columns = [];

    /**
     * @param string $name
     * @param array $data
     */
    public function __construct(string $name, array $data=[])
    {
        $this->name = $name;
        $this->fillData($data);

    }

    public function fillData(array $data): void
    {
        if (isset($data["refColumn"])){
            $this->refColumn = $data["refColumn"];
        }
        if (isset($data["refPresentation"])){
            $this->refPresentation = $data["refPresentation"];
        }
        if (isset($data["title"])){
            $this->title = $data["title"];
        }
        if (isset($data["titleList"])){
            $this->titleList = $data["titleList"];
        }

        if (isset($data['columns'])){
            $this->addColumns($data['columns']);
        }
    }


    /**
     * @param array $columns
     */
    public function addColumns(array $columns):void
    {
        foreach ($columns as $column_name=>$column) {
            $this->addColumn($column_name, $column);
        }
    }

    /**
     * @param array $columns
     */
    public function deleteColumns(array $columns): void
    {
        foreach ($columns as $column_name=>$column) {
            $this->deleteColumn($column_name);
        }
    }

    /**
     * @param string $column_name
     * @param array $column_data
     */
    public function addColumn(string $column_name, array $column_data):void
    {
        $this->columns[$column_name] = new DBSchemaTableColumn($column_name, $column_data);
    }

    /**
     * @param string $column_name
     */
    public function deleteColumn(string $column_name):void
    {
        unset($this->columns[$column_name]);
    }

    /**
     * @param array $columns
     */
    public function changeColumns(array $columns):void
    {
        foreach ($columns as $column_name=>$column) {
            $this->addColumn($column_name, $column);
        }
    }

    /**
     * @param string $column_name
     * @param bool $primary_key
     * @return void
     */
    public function setPrimaryKey(string $column_name, bool $primary_key=true):void
    {
        $this->columns[$column_name]->setPrimaryKey($primary_key);
    }

    /**
     * @return array
     */
    public function toArray():array
    {
        $result = [];
        $result['columns'] = [];

        foreach ($this->columns as $column_name=>$column) {
            $result['columns'][$column_name] = $column->toArray();
        }

        return $result;
    }
}