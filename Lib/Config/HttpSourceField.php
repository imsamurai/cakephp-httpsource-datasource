<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:04:01
 */
App::uses('HttpSourceEndpointItem', 'HttpSource.Lib/Config');

/**
 * Http source endpoint field
 * 
 * @package HttpSource
 * @subpackage Lib.Config
 */
class HttpSourceField extends HttpSourceEndpointItem {

	/**
	 * Field name
	 *
	 * @var string
	 */
	protected $_name = null;

	/**
	 * New field name
	 *
	 * @var string
	 */
	protected $_mapToName = null;

	/**
	 * Set or get field name
	 *
	 * @param string $name
	 * @return HttpSourceField
	 * @return string
	 * @throws HttpSourceConfigException
	 */
	public function name($name = null) {
		if (is_null($name)) {
			if (is_null($this->_name)) {
				throw new HttpSourceConfigException('Field or condition name is null!');
			}
			return $this->_name;
		}
		$this->_name = (string)$name;
		return $this;
	}

	/**
	 * Get mapToName or name
	 *
	 */
	public function mapToName() {
		return is_null($this->_mapToName) ? $this->_name : $this->_mapToName;
	}

	/**
	 * Get or set callback and new name
	 *
	 * @param callable $callback
	 * @param string $mapToName New field name
	 * @return HttpSourceField
	 * @return array
	 */
	public function map(callable $callback = null, $mapToName = null) {
		if (is_null($callback) && is_null($mapToName)) {
			return array($this->_map, $this->mapToName());
		}
		$this->_mapToName = $mapToName;
		parent::map($callback);
		return $this;
	}

	/**
	 * Typecast to string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->name();
	}

}
