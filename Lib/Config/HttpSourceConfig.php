<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 22:55:25
 *
 */
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');

class HttpSourceConfig {
    protected $_endpoints = array();

    public function add(HttpSourceEndpoint $Endpoint) {
        $this->_endpoints[$Endpoint->method()][$Endpoint->table()][$Endpoint->path()] = $Endpoint;
        return $this;
    }

    public function endpoints($method, $table) {
        return $this->_endpoints[$method][$table];
    }
}