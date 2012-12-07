<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:04:48
 *
 */

/**
 * Base class for endpoint items (field, result)
 */
abstract class HttpSourceEndpointItem {

    /**
     * Holds callback
     *
     * @var callable
     */
    protected $_map = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->_map = function ($value) {
                    return $value;
                };
    }

    /**
     * Set or get callback
     *
     * @param callable $callback
     * @return HttpSourceEndpointItem
     * @return callable
     */
    public function map(callable $callback = null) {
        if (is_null($callback)) {
            return $this->_map;
        }
        $this->_map = $callback;
        return $this;
    }

}