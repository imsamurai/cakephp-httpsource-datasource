<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:08:58
 *
 */
App::uses('HttpSourceField', 'HttpSource.Lib/Config');

/**
 * @method HttpSourceCondition name($name = null)
 * @method HttpSourceCondition map(callable $callback = null, $map_to_name = null)
 */
class HttpSourceCondition extends HttpSourceField {

    protected $_type = HttpSourceCondition::TYPE_STRING;
    protected $_null = true;
    protected $_length = null;
    protected $_key = null;
    protected $_default = null;

    const TYPE_INT = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOL = 'boolean';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const KEY_PRIMARY = 'primary';

    public function type() {
        if (is_null($this->_type)) {
                throw new HttpSourceConfigException('Condition type is null!');
            }
        return $this->_type;
    }

    public function typeInt() {
        $this->_type = static::TYPE_INT;
        return $this;
    }

    public function typeFloat() {
        $this->_type = static::TYPE_FLOAT;
        return $this;
    }

    public function typeBool() {
        $this->_type = static::TYPE_BOOL;
        return $this;
    }

    public function typeString() {
        $this->_type = static::TYPE_STRING;
        return $this;
    }

    public function typeText() {
        $this->_type = static::TYPE_TEXT;
        return $this;
    }

    public function null($null = null) {
        if (is_null($null)) {
            return $this->_null;
        }
        $this->_null = (bool) $null;
        return $this;
    }

    public function length($length = null) {
        if (is_null($length)) {
            return $this->_length;
        }
        $this->_length = (int) $length;
        return $this;
    }

    public function keyPrimary() {
        $this->_key = static::KEY_PRIMARY;
        return $this;
    }

    public function key() {
        return $this->_key;
    }

    public function defaults($value = null) {
        if (is_null($value)) {
            return $this->_default;
        }
        $this->_default = $value;
        return $this;
    }

}