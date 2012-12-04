<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:04:48
 *
 */
abstract class HttpSourceEndpointItem {

    protected $_map = null;

    public function map(callable $callback = null) {
        $this->_map = $callback;
        return $this;
    }

}