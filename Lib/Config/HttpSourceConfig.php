<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 22:55:25
 *
 */
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');

class HttpSourceConfig {

    protected $_result = null;
    protected $_endpoints = array();
    protected $_readParams = array();
    protected $_methods = array(
        HttpSourceEndpoint::METHOD_READ,
        HttpSourceEndpoint::METHOD_CREATE,
        HttpSourceEndpoint::METHOD_UPDATE,
        HttpSourceEndpoint::METHOD_DELETE
    );

    public function result(HttpSourceResult $Result = null) {
        if (is_null($Result)) {
            return $this->_result;
        }
        $this->_result = $Result;
        return $this;
    }

    public function readParams(array $params = null) {
        if (is_null($params)) {
            return $this->_readParams;
        }
        $this->_readParams = $params;
        return $this;
    }

    public function add(HttpSourceEndpoint $Endpoint) {
        $this->_endpoints[$Endpoint->method()][$Endpoint->table()][$Endpoint->path()] = $Endpoint;
        return $this;
    }

    public function describe(Model $model) {
        if (empty($model->useTable)) {
            return array();
        }
        foreach ($this->_methods as $method) {
            if (!empty($this->_endpoints[$method][$model->useTable])) {
                $this->_endpoints[$method][$model->useTable]->describe();
            }
        }
    }

    public function listSources() {
        $sources = array();
        foreach ($this->_methods as $method) {
            $sources = array_merge($sources, array_keys(Hash::get($sources, $method)));
        }

        return array_unique($sources);
    }

    public function endpoint($method, $table, $fields = array()) {
        if (!isset($this->_endpoints[$method][$table])) {
            throw new HttpSourceConfigException('Table ' . $table . ' not found in HttpSource Configuration');
        }

        foreach ($this->_endpoints[$method][$table] as $Endpoint) {
            if ($this->_endpointMatch($Endpoint, $fields)) {
                if (is_null($Endpoint->result())) {
                    $Endpoint->result($this->result());
                }
                if (is_null($Endpoint->readParams())) {
                    $Endpoint->readParams($this->readParams());
                }
                return $Endpoint;
            }
        }

        throw new HttpSourceConfigException('Could not find a match for passed conditions');
    }

    protected function _endpointMatch(HttpSourceEndpoint $Endpoint, $fields) {
        $required = $Endpoint->requiredConditions();
        $defaults = $Endpoint->defaultsConditions();
        debug($required);
        return (count(array_intersect(array_intersect(array_merge($fields, array_keys($defaults)), $required), $required)) === count($required));
    }

}