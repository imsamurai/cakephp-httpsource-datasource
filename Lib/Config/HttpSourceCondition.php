<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:08:58
 */
App::uses('HttpSourceField', 'HttpSource.Lib/Config');

/**
 * Http source endpoint condition
 *
 * @method HttpSourceCondition name($name = null)
 * @method HttpSourceCondition map(callable $callback = null, $map_to_name = null)
 * 
 * @package HttpSource
 * @subpackage Config
 */
class HttpSourceCondition extends HttpSourceField {

	/**
	 * Send in constants
	 */
	const SEND_IN_ANY = 'any';
	const SEND_IN_QUERY = 'query';
	const SEND_IN_BODY = 'body';
	const SEND_IN_VIRTUAL = 'virtual';

	/**
	 * Condition type
	 *
	 * @var string
	 */
	protected $_type = HttpSourceCondition::TYPE_STRING;

	/**
	 * True if condition required, false otherwise
	 * Used to describe model and build request
	 *
	 * @var bool
	 */
	protected $_null = true;

	/**
	 * Max length of conditions
	 * Used to describe model
	 *
	 * @var int
	 */
	protected $_length = null;

	/**
	 * Key type, actually supports only 'primary'
	 * Used to describe model
	 *
	 * @var string
	 */
	protected $_key = null;

	/**
	 * Default condition value
	 * Used to describe model and build request
	 *
	 * @var mixed
	 */
	protected $_default = null;
	
	/**
	 * Extract values
	 *
	 * @var bool
	 */
	protected $_extract = false;

	/**
	 * Sepecified where to place condition - in body, in query
	 * or any - depends on current endpoint
	 *
	 * @var string
	 */
	protected $_sendIn = HttpSourceCondition::SEND_IN_ANY;

	/**
	 * Available types
	 */
	const TYPE_INT = 'integer';
	const TYPE_FLOAT = 'float';
	const TYPE_BOOL = 'boolean';
	const TYPE_STRING = 'string';
	const TYPE_TEXT = 'text';

	/**
	 * Available keys
	 */
	const KEY_PRIMARY = 'primary';

	/**
	 * Returns all send in types
	 * 
	 * @return array
	 */
	public static function getSendInTypes() {
		$types = array(
			static::SEND_IN_ANY,
			static::SEND_IN_QUERY,
			static::SEND_IN_BODY,
			static::SEND_IN_VIRTUAL
		);
		return array_combine($types, $types);
	}

	/**
	 * Returns all data types
	 * 
	 * @return array
	 */
	public static function getDataTypes() {
		$types = array(
			static::TYPE_INT,
			static::TYPE_FLOAT,
			static::TYPE_BOOL,
			static::TYPE_STRING,
			static::TYPE_TEXT,
		);
		return array_combine($types, $types);
	}

	/**
	 * Set force send in query
	 *
	 * @return HttpSourceCondition
	 */
	public function sendInQuery() {
		$this->_sendIn = static::SEND_IN_QUERY;
		return $this;
	}

	/**
	 * Set force send in body
	 *
	 * @return HttpSourceCondition
	 */
	public function sendInBody() {
		$this->_sendIn = static::SEND_IN_BODY;
		return $this;
	}

	/**
	 * Set condition force to virtual
	 *
	 * @return HttpSourceCondition
	 */
	public function sendInVirtual() {
		$this->_sendIn = static::SEND_IN_VIRTUAL;
		return $this;
	}

	/**
	 * Set force send in body or query, depends on current endpoint
	 *
	 * @return HttpSourceCondition
	 */
	public function sendInAny() {
		$this->_sendIn = static::SEND_IN_ANY;
		return $this;
	}

	/**
	 * True if condition must be in query
	 *
	 * @return bool
	 */
	public function mustSendInQuery() {
		return $this->_sendIn === static::SEND_IN_QUERY;
	}

	/**
	 * True if condition must be in body
	 *
	 * @return bool
	 */
	public function mustSendInBody() {
		return $this->_sendIn === static::SEND_IN_BODY;
	}

	/**
	 * True if condition must be in body or query depends on current endpoint
	 *
	 * @return bool
	 */
	public function mustSendInAny() {
		return $this->_sendIn === static::SEND_IN_ANY;
	}

	/**
	 * True if condition must be virtial
	 *
	 * @return bool
	 */
	public function mustSendInVirtual() {
		return $this->_sendIn === static::SEND_IN_VIRTUAL;
	}

	/**
	 * Returns condition type
	 *
	 * @return string
	 */
	public function type() {
		return $this->_type;
	}

	/**
	 * Set type to int
	 *
	 * @return HttpSourceCondition
	 */
	public function typeInt() {
		$this->_type = static::TYPE_INT;
		return $this;
	}

	/**
	 * Set type to int
	 *
	 * @return HttpSourceCondition
	 */
	public function typeInteger() {
		return $this->typeInt();
	}

	/**
	 * Set type to float
	 *
	 * @return HttpSourceCondition
	 */
	public function typeFloat() {
		$this->_type = static::TYPE_FLOAT;
		return $this;
	}

	/**
	 * Set type to bool
	 *
	 * @return HttpSourceCondition
	 */
	public function typeBool() {
		$this->_type = static::TYPE_BOOL;
		return $this;
	}

	/**
	 * Set type to bool
	 *
	 * @return HttpSourceCondition
	 */
	public function typeBoolean() {
		return $this->typeBool();
	}

	/**
	 * Set type to string
	 *
	 * @return HttpSourceCondition
	 */
	public function typeString() {
		$this->_type = static::TYPE_STRING;
		return $this;
	}

	/**
	 * Set type to text
	 *
	 * @return HttpSourceCondition
	 */
	public function typeText() {
		$this->_type = static::TYPE_TEXT;
		return $this;
	}

	//@codingStandardsIgnoreStart
	/**
	 * Mark condition as required or not
	 * If $null = null returns current value
	 *
	 * @param bool $null
	 * @return HttpSourceCondition
	 * @return bool
	 */
	public function null($null = null) {
		if (is_null($null)) {
			return $this->_null;
		}
		$this->_null = (bool)$null;
		return $this;
	}
	//@codingStandardsIgnoreEnd

	/**
	 * Mark condition as required
	 *
	 * @return HttpSourceCondition
	 */
	public function required() {
		$this->_null = false;
		return $this;
	}

	/**
	 * Sets or gets condition length
	 *
	 * @param int $length
	 * @return HttpSourceCondition
	 * @return int
	 */
	public function length($length = null) {
		if (is_null($length)) {
			return $this->_length;
		}
		$this->_length = (int)$length;
		return $this;
	}

	/**
	 * Set condition as primary key
	 *
	 * @return HttpSourceCondition
	 */
	public function keyPrimary() {
		$this->_key = static::KEY_PRIMARY;
		return $this;
	}

	/**
	 * Return current key value
	 *
	 * @return string
	 */
	public function key() {
		return $this->_key;
	}

	/**
	 * Sets or gets condition default value
	 *
	 * @param mixed $value
	 * @return HttpSourceCondition
	 * @return mixed
	 */
	public function defaults($value = null) {
		if (is_null($value)) {
			return $this->_default;
		}
		$this->_default = $value;
		return $this;
	}
	
	/**
	 * Sets or gets should we extract value or not
	 *
	 * @param bool $value
	 * @return HttpSourceCondition
	 * @return mixed
	 */
	public function extract($value = null) {
		if (is_null($value)) {
			return $this->_extract;
		}
		$this->_extract = $value;
		return $this;
	}

}
