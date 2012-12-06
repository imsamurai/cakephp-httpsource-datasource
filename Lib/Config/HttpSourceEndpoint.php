<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 22:52:50
 *
 */
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');
App::uses('HttpSourceField', 'HttpSource.Lib/Config');
App::uses('HttpSourceCondition', 'HttpSource.Lib/Config');

class HttpSourceEndpoint {

    protected $_method = null;
    protected $_table = null;
    protected $_path = null;
    protected $_fields = array();
    protected $_conditions = array();
    protected $_result = null;
    protected $_cache = array();

    /**
     * CRUD constants
     */

    const METHOD_READ = 'read';
    const METHOD_CREATE = 'create';
    const METHOD_UPDATE = 'update';
    const METHOD_DELETE = 'delete';

    public function methodRead() {
        $this->_method = static::METHOD_READ;
        return $this;
    }

    public function methodCreate() {
        $this->_method = static::METHOD_CREATE;
        return $this;
    }

    public function methodUpdate() {
        $this->_method = static::METHOD_UPDATE;
        return $this;
    }

    public function methodDelete() {
        $this->_method = static::METHOD_DELETE;
        return $this;
    }

    public function method() {
        return $this->_method;
    }

    public function table($name = null) {
        if (is_null($name)) {
            return $this->_table;
        }
        $this->_table = $name;
        return $this;
    }

    public function path($name = null) {
        if (is_null($name)) {
            return $this->_path === null ? $this->_path : $this->table();
        }
        $this->_path = $name;
        return $this;
    }

    public function result(HttpSourceResult $Result = null) {
        if (is_null($Result)) {
            return $this->_result;
        }
        $this->_result = $Result;
        return $this;
    }

    public function addField(HttpSourceField $Field) {
        $this->_fields[(string) $Field] = $Field;
        return $this;
    }

    public function addCondition(HttpSourceCondition $Condition) {
        $this->_conditions[(string) $Condition] = $Condition;
        return $this;
    }

    public function requiredConditions() {
        $conditions_list = array();
        foreach ($this->_conditions as $Condition) {
            if ($Condition->null() === false) {
                $conditions_list[] = $Condition->name();
            }
        }

        return $conditions_list;
    }

    public function defaultsConditions() {
        $conditions_list = array();
        foreach ($this->_conditions as $Condition) {
            if (!is_null($Condition->defaults())) {
                $conditions_list[] = $Condition->name();
            }
        }

        return $conditions_list;
    }

    public function processFields(Model $model, array &$fields) {
        $this->_process($fields, $this->_fields, $model);
    }

    public function processConditions(Model $model, array &$conditions) {
        $this->_process($conditions, $this->_conditions, $model);
    }

    public function processResult(Model $model, array &$result) {
        $Result = $this->result();
        if (!is_null($Result)) {
            $callback = $Result->map();
            $result = $callback($result, $model);
        }
    }

    public function schema() {
        $schema = array();
        foreach ($this->_conditions as $Condition) {

            $col = array(
                'type' => $Condition->type(),
                'null' => $Condition->null()
            );

            if (!is_null($Condition->length())) {
                $col['length'] = $Condition->length();
            }

            if (!is_null($Condition->key())) {
                $col['key'] = $Condition->key();
            }

            if (!is_null($Condition->defaults())) {
                $col['default'] = $Condition->defaults();
            }

            $schema[$Condition->name()] = $col;
        }

        return $schema;
    }

    protected function _process(&$items, $storage, Model $model) {
        foreach ($items as $item => $value) {
            if (!$storage[$item]) {
                continue;
            }

            list($callback, $to_field_name) = $storage[$item]->map();
            unset($items[$item]);
            $items[$to_field_name] = $callback($value, $model);
        }
    }
}