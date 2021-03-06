<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:27:31
 */
App::uses('HttpSourceConfig', 'HttpSource.Lib/Config');
App::uses('HttpSourceCondition', 'HttpSource.Lib/Config');
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');
App::uses('HttpSourceField', 'HttpSource.Lib/Config');
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');

/**
 * Factory to make HttpSource configuration
 * 
 * @package HttpSource
 * @subpackage Config
 */
class HttpSourceConfigFactory {

	/**
	 * List of instances
	 *
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Instance
	 *
	 * @param string $factoryName Description Replacement for default factory (this class must have same interface)
	 *
	 * @return HttpSourceConfigFactory
	 * @throws HttpSourceConfigException
	 */
	public static function instance($factoryName = 'HttpSource.HttpSourceConfigFactory') {
		if (empty(static::$_instances[$factoryName])) {

			list($factoryPluginName, $factoryClassName) = pluginSplit($factoryName, true);

			App::uses($factoryClassName, $factoryPluginName . 'Lib/Config');

			if (!class_exists($factoryClassName)) {
				throw new HttpSourceConfigException("Config factory '$factoryName' not exist!");
			}

			$Factory = new $factoryClassName();

			if (!($Factory instanceof HttpSourceConfigFactory)) {
				throw new HttpSourceConfigException("Config factory '$factoryName' nust be instance of HttpSourceConfigFactory!");
			}

			static::$_instances[$factoryName] = new $Factory();
		}

		return static::$_instances[$factoryName];
	}

	/**
	 * Create config
	 *
	 * @return HttpSourceConfig
	 */
	public function config() {
		return new HttpSourceConfig($this);
	}

	/**
	 * Create endpoint
	 *
	 * @return HttpSourceEndpoint
	 */
	public function endpoint() {
		return new HttpSourceEndpoint($this);
	}

	/**
	 * Create endpoint condition
	 *
	 * @return HttpSourceCondition
	 */
	public function condition() {
		return new HttpSourceCondition($this);
	}

	/**
	 * Create endpoint field
	 *
	 * @return HttpSourceField
	 */
	public function field() {
		return new HttpSourceField($this);
	}

	/**
	 * Create endpoint result
	 *
	 * @return HttpSourceResult
	 */
	public function result() {
		return new HttpSourceResult($this);
	}

	/**
	 * Load config by source name
	 *
	 * @param string $sourceName
	 * @return HttpSourceConfig
	 */
	public function load($sourceName) {
		return Configure::read($sourceName . '.config');
	}

	/**
	 * For single object
	 */
	private function __construct() {	
	}

}
