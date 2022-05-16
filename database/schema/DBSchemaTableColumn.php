<?php

namespace sketch\database\schema;

class DBSchemaTableColumn
{

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $db_type;
    /**
     * @var string
     */
    public $length;
    /**
     * @var bool
     */
    public $not_null;
    /**
     * @var string
     */
    public $default;
    /**
     * @var bool
     */
    public $primary_key;

    public function __construct(string $name, array $data=[])
    {
        $this->name = $name;
        $this->fillByData($data);
    }

    /**
     * @param array $data
     * @return void
     */
    public function fillByData(array $data):void
    {
        $this->db_type = $data["db_type"] ?? "";
        $this->length = $data["length"] ?? "";
        $this->not_null = $data["not_null"] ?? false;
        $this->default = $data["default"] ?? "";
        $this->primary_key = $data["primary_key"] ?? false;
    }

    /**
     * @param bool $primary_key
     * @return void
     */
    public function setPrimaryKey(bool $primary_key=true):void
    {
        $this->primary_key = $primary_key;
    }

    /**
     * @param DBSchemaTableColumn $column
     * @return array
     */
    public function compareByOtherColumn(DBSchemaTableColumn $column):array
    {
        $result = [];
        if ($this->db_type !== $column->db_type)
            $result["db_type"] = [
                    "new" => $column->db_type,
                    "old" => $this->db_type
            ];

        if ($this->length !== $column->length)
            $result["length"] = [
                "new" => $column->length,
                "old" => $this->length
            ];

        if ($this->not_null !== $column->not_null)
            $result["not_null"] = [
                "new" => $column->not_null,
                "old" => $this->not_null
            ];

        if ($this->default !== $column->default)
            $result["default"] = [
                "new" => $column->default,
                "old" => $this->default
            ];

        if ($this->primary_key !== $column->primary_key)
            $result["primary_key"] = [
                "new" => $column->primary_key,
                "old" => $this->primary_key
            ];

        return $result;
    }

    /**
     * @return array
     */
    public function toArray():array
    {
        $result = [
            "name" => $this->name,
            "db_type" => $this->db_type
        ];

        if ($this->length!=="")
            $result['length'] = $this->length;

        if ($this->not_null)
            $result['not_null'] = true;

        if ($this->default!=="")
            $result['default'] = $this->default;

        if ($this->primary_key)
            $result['primary_key'] = $this->primary_key;

        return $result;

    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $result = $this->typeToString();

        if ($this->not_null)
            $result .= " NOT NULL";

        if ($this->default!=="")
            $result .= " DEFAULT $this->default";

        if ($this->primary_key)
            $result .= " PRIMARY KEY";

        return $result;
    }

    /**
     * @return string
     */
    private function typeToString():string
    {
        $content = $this->db_type;
        if ($this->length===""){
            $content .= "($this->length)";
        }
        return $content;
    }


}