<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 29.10.2013
 * Time: 18:00:00
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 *
 */
App::uses('ConnectionManager', 'Model');
App::uses('TestHttpSourceModel', 'TestHttpSource.Model');

require_once dirname(__FILE__) . DS . 'Data' . DS . 'models.php';

/**
 * Tests
 */
abstract class HttpSourceAppTest extends CakeTestCase {

	/**
	 * TestHttpSourceModel Model
	 *
	 * @var TestHttpSourceModel
	 */
	public $HttpModel = null;

	/**
	 * {@inheritdoc}
	 *
	 * @param string $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct($name = null, array $data = array(), $dataName = '') {
		CakePlugin::load('TestHttpSource');
		$this->_loadModel();
		parent::__construct($name, $data, $dataName);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->_loadModel();
	}

	/**
	 * Load model
	 *
	 * @param array $configName
	 * @param array $config
	 */
	protected function _loadModel($configName = 'testHttpSource', $config = array()) {
		$dbConfigs = ConnectionManager::enumConnectionObjects();

		if (!empty($dbConfigs['httpsourceTest'])) {
			$TestDS = ConnectionManager::getDataSource('httpsourceTest');
			$config += $TestDS->config;
		} else {
			$config += array(
				'datasource' => 'TestHttpSource.Http/TestHttpSource',
				'host' => 'raw.githubusercontent.com',
				'port' => 443,
				'timeout' => 5,
				'ssl_verify_host' => false
			);
		}

		$config += array('prefix' => '');

		ConnectionManager::create($configName, $config);
		$this->HttpModel = new TestHttpSourceModel(false, null, $configName);
	}

}
