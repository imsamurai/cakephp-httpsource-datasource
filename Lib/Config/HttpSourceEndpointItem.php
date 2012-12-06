<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:04:48
 *
 */
abstract class HttpSourceEndpointItem {

    protected $_map = null;

    public function __construct() {
        $this->_map = function ($value) {
            return $value;
        };
    }

    public function map(callable $callback = null) {
        if (is_null($callback)) {
            return $this->_map;
        }
        $this->_map = $callback;
        return $this;
    }

}