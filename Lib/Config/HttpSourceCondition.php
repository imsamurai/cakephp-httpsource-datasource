<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:08:58
 *
 */

App::uses('HttpSourceField', 'HttpSource.Lib/Config');

/**
 * @method HttpSourceCondition name($name)
 * @method HttpSourceCondition map(callable $callback = null, $map_to_name = null)
 */
class HttpSourceCondition extends HttpSourceField {
    protected $_type = null;
    protected $_null = null;
    protected $_length = null;
    protected $_key = null;
    protected $_default = null;

    public function type($name) {
        $this->_type = (string)$name;
        return $this;
    }

    public function null($null) {
        $this->_null = (bool)$null;
        return $this;
    }

    public function length($length) {
        $this->_length = (int)$length;
        return $this;
    }

    public function key($name) {
        $this->_key = (string)$name;
        return $this;
    }

    public function defaults($value) {
        $this->_default = $value;
        return $this;
    }
}