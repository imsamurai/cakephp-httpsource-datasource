<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:04:48
 *
 */
App::uses('HttpSourceConfigFactoryItem', 'HttpSource.Lib/Config');

/**
 * Base class for endpoint items (field, result)
 */
abstract class HttpSourceEndpointItem extends HttpSourceConfigFactoryItem {

	/**
	 * Holds callback
	 *
	 * @var callable
	 */
	protected $_map = null;

	/**
	 * Constructor
	 *
	 * @param HttpSourceConfigFactory $ConfigFactory Config factory instance
	 */
	public function __construct(HttpSourceConfigFactory $ConfigFactory) {
		parent::__construct($ConfigFactory);
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
