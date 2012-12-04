<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:04:01
 *
 */

App::uses('HttpSourceEndpointItem', 'HttpSource.Lib/Config');

class HttpSourceField extends HttpSourceEndpointItem {
    protected $_name = null;
    protected $_mapToName = null;

    public function name($name) {
        $this->_name = (string)$name;
        return $this;
    }

    /**
     *
     * @param callable $callback
     * @param type $map_to_name
     * @return HttpSourceField
     */
    public function map(callable $callback = null, $map_to_name = null) {
        $this->_mapToName = $map_to_name;
        return parent::map($callback);
    }
}