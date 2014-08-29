<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.08.2014
 * Time: 11:45:52
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceConfigFactoryTest
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConfigFactoryTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test singleton
	 */
	public function testSingleton() {
		$Method = new ReflectionMethod('HttpSourceConfigFactory', '__construct');
		$this->assertTrue($Method->isPrivate());
	}

	/**
	 * Test making new instance
	 * 
	 * @param string $factoryName
	 * @param bool $exception
	 * @dataProvider instanceProvider
	 */
	public function testInstance($factoryName, $exception) {
		if ($exception) {
			$this->expectException('HttpSourceConfigException');
		}
		$Instance1 = $factoryName ? HttpSourceConfigFactory::instance($factoryName) : HttpSourceConfigFactory::instance();
		$Instance2 = $factoryName ? HttpSourceConfigFactory::instance($factoryName) : HttpSourceConfigFactory::instance();
		$this->assertSame($Instance1, $Instance2);
	}

	/**
	 * Data provider for testInstance
	 * 
	 * @return array
	 */
	public function instanceProvider() {
		return array(
			//set #0
			array(
				//factoryName
				null,
				//exception
				false
			),
			//set #1
			array(
				//factoryName
				'HttpSourceConfigFactory__iAmNotExists__',
				//exception
				true
			),
			//set #2
			array(
				//factoryName
				'HttpSourceConfigFactoryiAmExists',
				//exception
				true
			),
			//set #3
			array(
				//factoryName
				'HttpSourceConfigFactoryiAmExistsANDValid',
				//exception
				false
			),
		);
	}

	/**
	 * Test factory
	 * 
	 * @param string $factoryName
	 * @param array $classMethodsMap
	 * @dataProvider factoryProvider
	 */
	public function testFactory($factoryName, array $classMethodsMap) {
		$Instance = $factoryName ? HttpSourceConfigFactory::instance($factoryName) : HttpSourceConfigFactory::instance();
		foreach ($classMethodsMap as $method => $class) {
			$this->assertSame($class, get_class($Instance->$method()));
		}
	}

	/**
	 * Data provider for testFactory
	 * 
	 * @return array
	 */
	public function factoryProvider() {
		return array(
			//set #0
			array(
				//factoryName
				null,
				//classMethodsMap
				array(
					'config' => 'HttpSourceConfig',
					'endpoint' => 'HttpSourceEndpoint',
					'condition' => 'HttpSourceCondition',
					'field' => 'HttpSourceField',
					'result' => 'HttpSourceResult'
				)
			),
			//set #1
			array(
				//factoryName
				'HttpSourceConfigFactoryiAmExistsANDValid',
				//classMethodsMap
				array(
					'config' => 'HttpSourceConfigiAmExistsANDValid',
					'endpoint' => 'HttpSourceEndpointiAmExistsANDValid',
					'condition' => 'HttpSourceConditioniAmExistsANDValid',
					'field' => 'HttpSourceFieldiAmExistsANDValid',
					'result' => 'HttpSourceResultiAmExistsANDValid'
				)
			),
		);
	}

	/**
	 * Test load config
	 */
	public function testLoad() {
		$Instance = HttpSourceConfigFactory::instance();
		$Config = new HttpSourceConfig($Instance);
		$sourceName = 'some_name';
		Configure::write("$sourceName.config", $Config);
		$this->assertSame($Config, $Instance->load($sourceName));
	}

}

/**
 * HttpSourceConfigFactoryiAmExists
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConfigFactoryiAmExists {
	
}

/**
 * HttpSourceConfigFactoryiAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConfigFactoryiAmExistsANDValid extends HttpSourceConfigFactory {

	/**
	 * {@inheritdoc}
	 * 
	 * @return HttpSourceConfigiAmExistsANDValid
	 */
	public function config() {
		return new HttpSourceConfigiAmExistsANDValid($this);
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return HttpSourceEndpointiAmExistsANDValid
	 */
	public function endpoint() {
		return new HttpSourceEndpointiAmExistsANDValid($this);
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return HttpSourceConditioniAmExistsANDValid
	 */
	public function condition() {
		return new HttpSourceConditioniAmExistsANDValid($this);
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return HttpSourceFieldiAmExistsANDValid
	 */
	public function field() {
		return new HttpSourceFieldiAmExistsANDValid($this);
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return HttpSourceResultiAmExistsANDValid
	 */
	public function result() {
		return new HttpSourceResultiAmExistsANDValid($this);
	}

}

/**
 * HttpSourceConfigiAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConfigiAmExistsANDValid extends HttpSourceConfig {
	
}

/**
 * HttpSourceEndpointiAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceEndpointiAmExistsANDValid extends HttpSourceEndpoint {
	
}

/**
 * HttpSourceConditioniAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConditioniAmExistsANDValid extends HttpSourceCondition {
	
}

/**
 * HttpSourceFieldiAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceFieldiAmExistsANDValid extends HttpSourceField {
	
}

/**
 * HttpSourceResultiAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceResultiAmExistsANDValid extends HttpSourceResult {
	
}
