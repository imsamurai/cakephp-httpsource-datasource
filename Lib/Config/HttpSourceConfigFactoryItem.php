<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 12.07.2013
 * Time: 19:04:01
 */

/**
 * HttpSource configuration factory item
 * 
 * @package HttpSource
 * @subpackage Lib.Config
 */
abstract class HttpSourceConfigFactoryItem {

	/**
	 * Config factory instance
	 *
	 * @var HttpSourceConfigFactory
	 */
	protected $_ConfigFactory = null;

	/**
	 * Constructor
	 *
	 * @param HttpSourceConfigFactory $ConfigFactory Config factory instance
	 */
	public function __construct(HttpSourceConfigFactory $ConfigFactory) {
		$this->_ConfigFactory = $ConfigFactory;
	}
	
	/**
	 * Retunrn config factory
	 * 
	 * @return HttpSourceConfigFactory
	 */
	public function getConfigFactory() {
		return $this->_ConfigFactory;
	}

}
