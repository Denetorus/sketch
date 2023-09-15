<?php

namespace sketch\database;

interface DBRecord
{

    public function __construct();

    /**
     * @return void
     */
    public function setDB():void;


    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name);

    /**
     * @return void
     */
    public function upsert():void;

    /**
     * @param bool $with_new_ID
     * @return void
     */
    public function save(bool $with_new_ID=true):array;

    /**
     * @return void
     */
    public function update():void;

    /**
     * @return void
     */
    public function load():void;

    /**
     * @param array $conditions
     * @return void
     */
    public function loadByConditions(array $conditions):void;

    /**
     * @return void
     */
    public function createNew():void;

    /**
     * @return void
     */
    public function delete():void;

    /**
     * @return void
     */
    public function deleteAll():void;

    /**
     * @return void
     */
    public function clear():void;

    /**
     * @return array
     */
    public function getList():array;


}