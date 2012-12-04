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

    /**
     * CRUD constants
     */

    const METHOD_READ = 'read';
    const METHOD_CREATE = 'create';
    const METHOD_UPDATE = 'update';
    const METHOD_DELETE = 'delete';

    public function method($name = null) {
        if (is_null($name)) {
           return $this->_method;
        }
        $this->_method = $name;
        return $this;
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
           return $this->_path ? $this->_path : $this->table();
        }
        $this->_path = $name;
        return $this;
    }

    public function result(HttpSourceResult $Result) {
        $this->_result = $Result;
        return $this;
    }

    public function addField(HttpSourceField $Field) {
        $this->_fields[] = $Field;
        return $this;
    }

    public function addCondition(HttpSourceCondition $Field) {
        $this->_fields[] = $Field;
        return $this;
    }

}