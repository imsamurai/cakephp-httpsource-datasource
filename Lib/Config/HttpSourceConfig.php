<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 22:55:25
 *
 */
App::uses('HttpSourceConfigFactoryItem', 'HttpSource.Lib/Config');
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');

/**
 * Http source endpoints config
 */
class HttpSourceConfig extends HttpSourceConfigFactoryItem {

    /**
     * Holds default result handler that applied
     * if no result handler found in endpoint
     *
     * @var HttpSourceResult
     */
    protected $_result = null;

    /**
     * Added endpoints
     *
     * @var array
     */
    protected $_endpoints = array();

    /**
     * Added endpoints by id
     *
     * @var array
     */
    protected $_endpointsById = array();

    /**
     * Default mapped read params (limit, offset, etc)
     *
     * @var array
     */
    protected $_readParams = array();

    /**
     * Available endpoint methods
     *
     * @var array
     */
    protected $_methods = array(
        HttpSourceEndpoint::METHOD_READ,
        HttpSourceEndpoint::METHOD_CREATE,
        HttpSourceEndpoint::METHOD_UPDATE,
        HttpSourceEndpoint::METHOD_DELETE
    );

    /**
     * Global cache name for store requests. If null cache not used.
     * Cache works only for read method
     *
     * @var string
     */
    protected $_cacheName = null;

    /**
     * Set or get endpoint cache name
     *
     * @param string $name
     * @return HttpSourceEndpoint
     * @return string Current cache name
     */
    public function cacheName($name = null) {
        if (is_null($name)) {
            return $this->_cacheName;
        }
        $this->_cacheName = $name;
        return $this;
    }

    /**
     * Get and set default endpoint result handler
     *
     * @param HttpSourceResult $Result
     * @return HttpSourceConfig
     * @return HttpSourceResult
     */
    public function result(HttpSourceResult $Result = null) {
        if (is_null($Result)) {
            return $this->_result;
        }
        $this->_result = $Result;
        return $this;
    }

    /**
     * Get and set default endpoint mapped read params
     *
     * @param array $params
     * @return HttpSourceConfig
     * @return array
     */
    public function readParams(array $params = null) {
        if (is_null($params)) {
            return $this->_readParams;
        }
        $this->_readParams = $params;
        return $this;
    }

    /**
     * Add and check endpoint
     *
     * @param HttpSourceEndpoint $Endpoint
     * @return HttpSourceConfig
     * @throws HttpSourceConfigException If endpoint configuration is incorrect
     */
    public function add(HttpSourceEndpoint $Endpoint) {
        if (is_null($Endpoint->id())) {
            throw new HttpSourceConfigException('You must set id for each endpoint by call id($id) method!');
        }
        if (isset($this->_endpointsById[$Endpoint->id()])) {
            throw new HttpSourceConfigException('Each endpoint must have unique id!');
        }
        $this->_endpoints[$Endpoint->method()][$Endpoint->table()][$Endpoint->path()] = $Endpoint;
        $this->_endpointsById[$Endpoint->id()] = $Endpoint;
        return $this;
    }

    /**
     * Get endpoint by id
     *
     * @param int $id
     * @return HttpSourceEndpoint
     * @throws HttpSourceConfigException If endpoint not found
     */
    public function endpoint($id) {
        if (empty($this->_endpointsById[$id])) {
            throw new HttpSourceConfigException("Endpoint with id=$id not found!");
        }

        return $this->_endpointsById[$id];
    }

    /**
     * Finds endpoint that match given arguments
     *
     * @param string $method CRUD method name
     * @param string $table
     * @param array $fields
     * @return HttpSourceEndpoint
     * @throws HttpSourceConfigException If endpoint not found
     */
    public function findEndpoint($method, $table, $fields = array()) {
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
                if (is_null($Endpoint->cacheName())) {
                    $Endpoint->cacheName($this->cacheName());
                }
                return $Endpoint;
            }
        }

        throw new HttpSourceConfigException('Could not find a match for passed conditions');
    }

    /**
     * Returns endpoint info for the model
     * in schema format
     *
     * @param Model $model
     * @return array
     *
     * TODO: improove
     */
    public function describe(Model $model) {
        if (empty($model->useTable)) {
            return array();
        }
        foreach ($this->_methods as $method) {
            if (!empty($this->_endpoints[$method][$model->useTable])) {
                $endpoints = $this->_endpoints[$method][$model->useTable];
                return $endpoints[key($endpoints)]->schema();
            }
        }
    }

    /**
     * Returns list of available sources (tables)
     *
     * @return array
     */
    public function listSources() {
        $sources = array();
        foreach ($this->_methods as $method) {
            $sources = array_merge($sources, array_keys((array) Hash::get($this->_endpoints, $method)));
        }

        return array_unique($sources);
    }

    /**
     * Check if endpoint matched by given fields
     *
     * @param HttpSourceEndpoint $Endpoint
     * @param array $fields
     * @return bool
     */
    protected function _endpointMatch(HttpSourceEndpoint $Endpoint, array $fields) {
        $required = $Endpoint->requiredConditions();
        $defaults = $Endpoint->conditionsDefaults();
        return (count(array_intersect(array_intersect(array_merge($fields, array_keys($defaults)), $required), $required)) === count($required));
    }

}