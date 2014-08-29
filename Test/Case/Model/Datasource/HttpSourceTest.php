<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 20.08.2014
 * Time: 14:54:41
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('Model', 'Model');
App::uses('CakeSchema', 'Model');
App::uses('HttpSource', 'HttpSource.Model/Datasource');
App::uses('HttpSourceConnection', 'HttpSource.Model/Datasource');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceTest
 */
class HttpSourceTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function tearDown() {
		parent::tearDown();
		Cache::clear(false, '__test_cache__');
	}

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		Cache::config('__test_cache__', array(
			'engine' => 'File',
			'prefix' => '__test_cache__',
			'path' => CACHE,
			'serialize' => true,
			'duration' => '1 hour'
		));

		$CF = HttpSourceConfigFactory::instance();
		Configure::write('HttpSource', array(
			'config_version' => 2,
			'config' => $CF->config()->cacheName('__test_cache__')
					->add(
							$CF->endpoint()
							->id(1)
							->table('endpoint1')
							->addCondition(
									$CF->condition()->name('condition1')
							)
							->cacheName(false)
					)
					->add(
							$CF->endpoint()
							->id(2)
							->table('app_models')
							->addCondition(
									$CF->condition()->name('condition2')
							)
					)
					->add(
							$CF->endpoint()
							->id(3)
							->methodCreate()
							->table('app_models')
							->addCondition(
									$CF->condition()->name('condition2')
							)
					)
					->add(
							$CF->endpoint()
							->id(4)
							->methodUpdate()
							->table('app_models')
							->addCondition(
									$CF->condition()->name('condition2')
							)
							->addCondition(
									$CF->condition()->name('condition1')
							)
					)
					->add(
							$CF->endpoint()
							->id(5)
							->methodDelete()
							->table('app_models')
							->addCondition(
									$CF->condition()->name('condition2')
							)
					)
					->add(
							$CF->endpoint()
							->id(6)
							->methodCheck()
							->table('app_models')
							->addCondition(
									$CF->condition()->name('condition2')
							)
					)
					->add(
							$CF->endpoint()
							->id(7)
							->methodCreate()
							->table('app_models2')
							->addCondition(
									$CF->condition()->name('condition3')
							)
							->addCondition(
									$CF->condition()->name('condition4')
							)
					)
					->add(
							$CF->endpoint()
							->id(8)
							->methodDelete()
							->table('app_models2')
							->addCondition(
									$CF->condition()->name('condition3')
							)
							->addCondition(
									$CF->condition()->name('condition4')
							)
					)
		));
	}

	/**
	 * Test construct
	 * 
	 * @param array $sourceConfig
	 * @param array $config
	 * @param string|HttpSourceConnection|null $Connection
	 * @param string $exception
	 * @param int $debugLevel
	 * @dataProvider constructProvider
	 */
	public function testConstruct($sourceConfig, $config, $Connection, $exception, $debugLevel) {
		if ($exception) {
			$this->expectException($exception);
		}

		if (!empty($sourceConfig['datasource'])) {
			list($plugin) = pluginSplit($sourceConfig['datasource']);
			Configure::write($plugin, $config);
		}

		$Source = $this->getMockBuilder('HttpTestSource')
				->disableOriginalConstructor()
				->setMethods(array('_loadConfig'))
				->getMock();
		$Source->expects($this->exactly((empty($config) && !empty($sourceConfig['datasource'])) ? 1 : 0))
				->method('_loadConfig')
				->with(isset($plugin) ? $plugin : null);

		$reflectedClass = new ReflectionClass('HttpTestSource');
		$constructor = $reflectedClass->getConstructor();
		$oldDebugLevel = Configure::read('debug');
		Configure::write('debug', $debugLevel);
		$constructor->invoke($Source, $sourceConfig, $Connection);
		Configure::write('debug', $oldDebugLevel);
		$this->assertSame($debugLevel > 1, $Source->fullDebug);

		if (is_object($Connection)) {
			$this->assertSame($Connection, $Source->getConnection());
		} else {
			$this->assertSame('HttpSourceConnection', get_class($Source->getConnection()));
		}

		$this->assertSame($config['config'], $Source->getConfig());
	}

	/**
	 * Data provider for 
	 * 
	 * @return array
	 */
	public function constructProvider() {
		return array(
			//set #0
			array(
				//sourceConfig
				array(),
				//config,
				array(),
				//Connection
				null,
				//exception
				'HttpSourceException',
				1
			),
			//set #1
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(),
				//Connection
				null,
				//exception
				'HttpSourceException',
				0
			),
			//set #2
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(),
				//Connection
				null,
				//exception
				'HttpSourceException',
				2
			),
			//set #3
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(
					'config_version' => 1
				),
				//Connection
				null,
				//exception
				'NotImplementedException',
				3
			),
			//set #4
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(
					'config_version' => 2,
					'config' => new stdClass
				),
				//Connection
				null,
				//exception
				'HttpSourceException',
				1
			),
			//set #5
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(
					'config_version' => 2,
					'config' => HttpSourceConfigFactory::instance()->config()
				),
				//Connection
				null,
				//exception
				'',
				0
			),
			//set #6
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(
					'config_version' => 2,
					'config' => HttpSourceConfigFactory::instance()->config()
				),
				//Connection
				new HttpSourceConnection,
				//exception
				'',
				0
			),
			//set #7
			array(
				//sourceConfig
				array(
					'datasource' => 'HttpSource.Http/HttpSource'
				),
				//config,
				array(
					'config_version' => 2,
					'config' => HttpSourceConfigFactory::instance()->config()
				),
				//Connection
				new HttpSourceConnection,
				//exception
				'',
				2
			),
		);
	}

	/**
	 * Test credentials
	 */
	public function testCredentials() {
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$credentials = array(
			'user' => 'root',
			'pass' => '123***',
			'a' => 'b'
		);
		$Source->setCredentials($credentials);
		$this->assertSame($credentials, $Source->getCredentials());
	}

	/**
	 * Test decoders
	 */
	public function testDecoders() {
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$contentType = 'test/test';
		$callback = function ($v) {
			return 123;
		};
		$Source->setDecoder($contentType, $callback, true);
		$decoder = $Source->getDecoder($contentType);
		$this->assertSame(123, $decoder(444));
	}

	/**
	 * Test query
	 * 
	 * @param array $request
	 * @param mixed $otherArg
	 * @dataProvider queryProvider
	 */
	public function testQuery($request, $otherArg) {
		if ($otherArg) {
			$this->expectException('NotImplementedException');
		}

		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array('request'))
				->getMock();

		if ($otherArg) {
			$this->assertTrue($Source->query($request, $otherArg));
		} else {
			$Source->expects($this->once())
					->method('request')
					->with(null, $request)
					->will($this->returnValue(true));
			$this->assertTrue($Source->query($request));
		}
	}

	/**
	 * Data provider for testQuery
	 * 
	 * @return array
	 */
	public function queryProvider() {
		return array(
			//set #0
			array(
				//request
				array('a' => 'b'),
				//otherArg
				null
			),
			//set #1
			array(
				//request
				array('a' => 'b'),
				//otherArg
				'some arg'
			),
		);
	}

	/**
	 * Test execute
	 * 
	 * @param array $request
	 * @param array $result
	 * @dataProvider executeProvider
	 */
	public function testExecute($request, $result) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array('query'))
				->getMock();

		$Source->expects($this->any())
				->method('query')
				->will($this->returnArgument(0));

		$this->assertSame($result, $Source->execute($request));
	}

	/**
	 * Data provider for testExecute
	 * 
	 * @return array
	 */
	public function executeProvider() {
		return array(
			//set #0
			array(
				//request
				array(),
				//result
				array()
			),
			//set #1
			array(
				//request
				array('a' => 'b'),
				//result
				array('a' => 'b')
			),
			//set #2
			array(
				//request
				array(
					1 => array('a' => 'b'),
					5 => array('c' => 'd'),
					10 => array('e' => 'f')
				),
				//result
				array(
					array('a' => 'b'),
					array('c' => 'd'),
					array('e' => 'f')
				)
			),
		);
	}

	/**
	 * Test request method flow
	 * 
	 * @param Model $Model
	 * @param string|array $requestData
	 * @param string $requestMethod
	 * @param array $request
	 * @dataProvider requestFlowProvider
	 */
	public function testRequestFlow(Model $Model = null, $requestData = null, $requestMethod = null, $request = null) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'_splitRequest',
					'_singleRequest',
					'_joinResponses',
					'afterRequest'
				))
				->getMock();

		$response1 = array('test response 1');
		$response2 = array('test response 2');
		$response = array_merge($response1, $response2);

		if ($request === null) {
			$this->expectException('HttpSourceException');
			$this->assertSame($response, $Source->request($Model, $requestData, $requestMethod));
		}

		$Source->expects($this->once())->method('_splitRequest')->with($request)->will($this->returnValue(array($request, $request)));
		$Source->expects($this->at(1))->method('_singleRequest')->with($request, $requestMethod, $Model)->will($this->returnValue($response1));
		$Source->expects($this->at(2))->method('_singleRequest')->with($request, $requestMethod, $Model)->will($this->returnValue($response2));
		$Source->expects($this->once())->method('_joinResponses')->with(array($response1, $response2))->will($this->returnValue($response));
		if ($Model) {
			$Source->expects($this->once())->method('afterRequest')->with($Model, $response, $requestMethod)->will($this->returnValue($response));
		}

		$this->assertSame($response, $Source->request($Model, $requestData, $requestMethod));
	}

	/**
	 * Data provider for testRequestFlow
	 * 
	 * @return array
	 */
	public function requestFlowProvider() {
		$Model = new Model;
		$Model->request = array('key2' => 'value2');
		return array(
			//set #0
			array(
				//Model
				null,
				//requestData
				null,
				//requestMethod
				HttpSource::METHOD_READ,
				//request
				null
			),
			//set #1
			array(
				//Model
				null,
				//requestData
				array('key' => 'value'),
				//requestMethod
				HttpSource::METHOD_READ,
				//request
				array('key' => 'value')
			),
			//set #2
			array(
				//Model
				$Model,
				//requestData
				null,
				//requestMethod
				HttpSource::METHOD_READ,
				//request
				array('key2' => 'value2')
			),
			//set #3
			array(
				//Model
				$Model,
				//requestData
				array('key' => 'value'),
				//requestMethod
				HttpSource::METHOD_READ,
				//request
				array('key2' => 'value2')
			),
			//set #4
			array(
				//Model
				null,
				//requestData
				'blahblah',
				//requestMethod
				HttpSource::METHOD_READ,
				//request
				array('uri' => 'blahblah')
			),
		);
	}

	/**
	 * Test single request
	 * 
	 * @param array $request
	 * @param array $requestFull
	 * @param array $response
	 * @param array $responseResult
	 * @param bool $asModel
	 * @param string $requestMethod
	 * @param array $config
	 * @param array $connectionData
	 * @param string $log
	 * @param HttpSourceEndpoint $Endpoint
	 * @dataProvider singleRequestProvider
	 */
	public function testSingleRequest($request, $requestFull, $response, $responseResult, $asModel, $requestMethod, array $config, array $connectionData, $log, $Endpoint = null) {
		$Connection = $this->getMockBuilder('HttpSourceConnection')
				->setMethods(array(
					'request',
					'getError',
					'getTook',
					'getQuery',
					'getAffected',
					'getNumRows'
				))
				->getMock();
		$Connection->expects($this->once())->method('request')->with($requestFull)->will($this->returnValue($response));
		$Connection->expects($this->once())->method('getError')->will($this->returnValue($connectionData['error']));
		$Connection->expects($this->once())->method('getTook')->will($this->returnValue($connectionData['took']));
		$Connection->expects($this->once())->method('getQuery')->will($this->returnValue($connectionData['query']));
		$Connection->expects($this->once())->method('getAffected')->will($this->returnValue($connectionData['affected']));

		if (isset($connectionData['numRows'])) {
			$Connection->expects($this->once())->method('getNumRows')->with($responseResult)->will($this->returnValue($connectionData['numRows']));
		} else {
			$Connection->expects($this->never())->method('getNumRows');
			$connectionData['numRows'] = 1;
		}

		$sourceMethods = array(
			'afterRequest',
			'beforeRequest',
			'swapTokens',
			'logRequest',
			'log'
		);
		if ($Endpoint) {
			$sourceMethods[] = '_getCurrentEndpoint';
		} else {
			$sourceMethods[] = '_extractResult';
		}
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource') + $config, $Connection))
				->setMethods($sourceMethods)
				->getMock();
		$Source->expects($this->once())->method('logRequest');
		$Source->expects($this->once())->method('swapTokens')->with($requestFull);
		$Source->expects($this->once())->method('beforeRequest')->with($requestFull, $requestMethod)->will($this->returnArgument(0));

		if ($connectionData['error']) {
			$Source->expects($this->once())->method('log')->with($log, LOG_ERR);
		}

		if (!$asModel) {
			$this->assertSame($responseResult, $Source->request(null, $request, $requestMethod));
		} else {
			$Source->expects($this->once())->method('afterRequest')->will($this->returnArgument(1));
			$Model = $this->getMockBuilder('Model')
					->setMethods(array(
						'onError'
					))
					->getMock();
			$Model->request = $request;
			if (!$responseResult || $connectionData['error']) {
				$Model->expects($this->once())->method('onError');
			}

			if ($responseResult && $Endpoint) {
				$Source->expects($this->any())->method('_getCurrentEndpoint')->will($this->returnValue($Endpoint));
			} elseif ($responseResult) {
				$Source->expects($this->once())->method('_extractResult')->with($Model, $responseResult, $requestMethod)->will($this->returnArgument(1));
			} else {
				$Source->expects($this->never())->method('_extractResult');
			}

			$this->assertSame($responseResult, $Source->request($Model, null, $requestMethod));
			$this->assertSame($responseResult, $Model->response);
			$this->assertSame($requestFull, $Model->request);
		}
		$this->assertSame($connectionData['error'], $Source->error);
		$this->assertSame($connectionData['took'], $Source->took);
		$this->assertSame($connectionData['query'], $Source->query);
		$this->assertSame($connectionData['affected'], $Source->affected);
		$this->assertSame($connectionData['numRows'], $Source->numRows);
	}

	/**
	 * Data provider for testSingleRequest
	 * 
	 * @return array
	 */
	public function singleRequestProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol'
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				false,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'',
				//Endpoint
				null
			),
			//set #1
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example.com',
						'port' => 7880,
						'path' => '/api',
						'scheme' => 'http'
					)
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				false,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'',
				//Endpoint
				null
			),
			//set #2
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol'
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'',
				//Endpoint
				null
			),
			//set #3
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example.com',
						'port' => 7880,
						'path' => '/api',
						'scheme' => 'http'
					)
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'',
				//Endpoint
				null
			),
			//set #4
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example.com',
						'port' => 7880,
						'path' => '/api',
						'scheme' => 'http'
					)
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => 'oh crap!',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'HttpSource: oh crap! Request: GET somethong',
				//Endpoint
				null
			),
			//set #5
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example.com',
						'port' => 7880,
						'path' => '/api',
						'scheme' => 'http'
					)
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				false,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => 'oh crap!',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'HttpSource: oh crap! Request: GET somethong',
				//Endpoint
				null
			),
			//set #6
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				false,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1
				),
				//log,
				'',
				//Endpoint
				null
			),
			//set #7
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				false,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => 'oh crap!',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1
				),
				//log,
				'HttpSource: oh crap! Request: GET somethong',
				//Endpoint
				null
			),
			//set #8
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//response
				array(),
				//responseResult
				array(),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 0
				),
				//log,
				'',
				//Endpoint
				null
			),
			//set #9
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//response
				array(),
				//responseResult
				array(),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => 'oh crap!',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 0
				),
				//log,
				'HttpSource: oh crap! Request: GET somethong',
				//Endpoint
				null
			),
			//set #10
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//requestFull
				array(
					'lol',
					'uri' => array(
						'host' => 'example2.com',
						'port' => 2880,
						'path' => '/api2',
						'scheme' => 'https'
					)
				),
				//response
				array(),
				//responseResult
				array(),
				//asModel
				false,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(
					'path' => '/api',
					'scheme' => 'http',
					'port' => 7880,
					'host' => 'example.com'
				),
				//connectionData
				array(
					'error' => 'oh crap!',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 0
				),
				//log,
				'HttpSource: oh crap! Request: GET somethong',
				//Endpoint
				null
			),
			//set #11
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol'
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'',
				//Endpoint
				$CF->endpoint()
			),
			//set #12
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol'
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
				),
				//log,
				'',
				//Endpoint
				$CF->endpoint()
			),
			//set #13
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol'
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly', 'Hi Jack!'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_READ,
				//config
				array(),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
					'numRows' => 1
				),
				//log,
				'',
				//Endpoint
				$CF->endpoint()
						->result($CF->result()
								->map(function($result) {
									return array_merge($result, array('Hi Jack!'));
								}))
			),
			//set #14
			array(
				//request
				array(
					'lol'
				),
				//requestFull
				array(
					'lol'
				),
				//response
				array('hello dolly'),
				//responseResult
				array('hello dolly'),
				//asModel
				true,
				//requestMethod
				HttpSource::METHOD_CHECK,
				//config
				array(),
				//connectionData
				array(
					'error' => '',
					'took' => 10,
					'query' => 'GET somethong',
					'affected' => 1,
				),
				//log,
				'',
				//Endpoint
				$CF->endpoint()
						->result($CF->result()
								->map(function($result) {
									return array_merge($result, array('Hi Jack!'));
								})
						)
			),
		);
	}

	/**
	 * Test beforeRequest
	 * 
	 * @param string $requestMethod
	 * @dataProvider beforeRequestProvider
	 */
	public function testBeforeRequest($requestMethod) {
		$request = array(
			'a' => 'b',
			'c' => 'd',
		);
		$requestMethod = HttpSource::METHOD_CREATE;
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$this->assertSame($request, $Source->beforeRequest($request, $requestMethod));
	}

	/**
	 * Data provider for testBeforeRequest
	 * 
	 * @return array
	 */
	public function beforeRequestProvider() {
		return array_map(function($type) {
			return array($type);
		}, array_values(HttpSource::getMethods()));
	}

	/**
	 * Test swapTokens
	 * 
	 * @param array $request
	 * @param array $requestResult
	 * @dataProvider swapTokensProvider
	 */
	public function testSwapTokens(array $request, array $requestResult) {
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$Source->swapTokens($request);
		$this->assertSame($requestResult, $request);
	}

	/**
	 * Data provider for testSwapTokens
	 * 
	 * @return array
	 */
	public function swapTokensProvider() {
		return array(
			//set #0
			array(
				//request
				array(),
				//requestResult
				array()
			),
			//set #1
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'path' => '123/'
					)
				),
				//requestResult
				array(
					'lol',
					'uri' => array(
						'path' => '123/'
					)
				)
			),
			//set #2
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'query' => 'qqq'
					)
				),
				//requestResult
				array(
					'lol',
					'uri' => array(
						'query' => 'qqq'
					)
				)
			),
			//set #3
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'a' => 1,
							'b' => 2,
							'c' => 3,
						),
						'path' => ':a/:b/:c'
					)
				),
				//requestResult
				array(
					'lol',
					'uri' => array(
						'query' => array(),
						'path' => '1/2/3'
					)
				)
			),
			//set #4
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'a' => 1,
							'b' => 2,
							'c' => 3,
						),
						'path' => ':a/:b/'
					)
				),
				//requestResult
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'c' => 3
						),
						'path' => '1/2/'
					)
				)
			),
			//set #5
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'a' => 1,
							'b' => 2,
							'c' => 3,
						),
						'path' => ':e'
					)
				),
				//requestResult
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'a' => 1,
							'b' => 2,
							'c' => 3,
						),
						'path' => ':e'
					)
				)
			),
			//set #6
			array(
				//request
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'a' => 1,
							'b' => 2,
							'c' => array(3),
						),
						'path' => ':a:b/:c'
					)
				),
				//requestResult
				array(
					'lol',
					'uri' => array(
						'query' => array(
							'c' => array(3)
						),
						'path' => '12/:c'
					)
				)
			),
		);
	}

	/**
	 * Test afterRequest
	 * 
	 * @param array $data
	 * @param array $result
	 * @param string $requestMethod
	 * @param array $queryData
	 * @param HttpSourceEndpoint $Endpoint
	 * @dataProvider afterRequestProvider
	 */
	public function testAfterRequest(array $data, array $result, $requestMethod, array $queryData, $Endpoint = null) {
		$methods = array(
			'_getQueryData'
		);
		if ($Endpoint) {
			$methods[] = '_getCurrentEndpoint';
		}
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods($methods)
				->getMock();
		$Source->expects($this->any())->method('_getQueryData')->will($this->returnCallback(function($path = null) use($queryData) {
			return is_null($path) ? $queryData : Hash::get($queryData, $path);
		}));
		if ($Endpoint) {
			$Source->expects($this->once())->method('_getCurrentEndpoint')->will($this->returnValue($Endpoint));
		}
		$this->assertSame($result, $Source->afterRequest(new Model, $data, $requestMethod));
		if ($requestMethod === HttpSource::METHOD_READ) {
			$this->assertSame(count($data), $Source->numRows);
		}
	}

	/**
	 * Data provider for testAfterRequest
	 * 
	 * @return array
	 */
	public function afterRequestProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//data
				array(),
				//result
				array(),
				//requestMethod
				HttpSource::METHOD_CHECK,
				//queryData
				array(),
				//Endpoint
				null
			),
			//set #1
			array(
				//data
				array('x' => 5),
				//result
				array('x' => 5),
				//requestMethod
				HttpSource::METHOD_CHECK,
				//queryData
				array(),
				//Endpoint
				null
			),
			//set #2
			array(
				//data
				array(),
				//result
				array(),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(),
				//Endpoint
				null
			),
			//set #3 functions
			array(
				//data
				array(
					array(1),
					array(2),
					array(3),
					array(4),
					array(5),
				),
				//result
				array(
					array(
						'Model' => array(
							'count' => 5
						)
					)
				),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(
					'fields' => HttpSource::FUNCTION_COUNT
				),
				//Endpoint
				null
			),
			//set #4 fields
			array(
				//data
				array(
					array(
						'a' => 1,
						'b' => 2
					),
					array(
						'a' => 11,
						'b' => 22
					),
					array(
						'a' => 111,
						'b' => 222
					),
				),
				//result
				array(
					array(
						'Model' => array(
							'a' => 1,
						)
					),
					array(
						'Model' => array(
							'a' => 11,
						)
					),
					array(
						'Model' => array(
							'a' => 111,
						)
					),
				),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(
					'fields' => array(
						'a'
					)
				),
				//Endpoint
				null
			),
			//set #5 order
			array(
				//data
				array(
					array('a' => 1, 'b' => 2),
					array('a' => 1, 'b' => 1),
					array('a' => 2, 'b' => 1),
					array('a' => 2, 'b' => 2),
				),
				//result
				array(
					array(
						'Model' => array(
							'a' => 2,
							'b' => 1
						)
					),
					array(
						'Model' => array(
							'a' => 2,
							'b' => 2
						)
					),
					array(
						'Model' => array(
							'a' => 1,
							'b' => 1
						)
					),
					array(
						'Model' => array(
							'a' => 1,
							'b' => 2
						)
					),
				),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(
					'order' => array(
						array(
							'a' => 'DESC',
							'b' => 'ASC',
						)
					)
				),
				//Endpoint
				null
			),
			//set #6 limit/offset
			array(
				//data
				array(
					array('a' => 1, 'b' => 2),
					array('a' => 1, 'b' => 1),
					array('a' => 2, 'b' => 1),
					array('a' => 2, 'b' => 2),
				),
				//result
				array(
					array(
						'Model' => array(
							'a' => 1,
							'b' => 1
						)
					),
					array(
						'Model' => array(
							'a' => 2,
							'b' => 1
						)
					),
				),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(
					'limit' => 2,
					'offset' => 1,
				),
				//Endpoint
				null
			),
			//set #7 format result
			array(
				//data
				array(
					array('x' => 5)
				),
				//result
				array(
					array(
						'Model' => array(
							'x' => 5
						)
					)
				),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(),
				//Endpoint
				null
			),
			//set #8 process fields
			array(
				//data
				array(
					array('x' => 5)
				),
				//result
				array(
					array(
						'Model' => array(
							'_x_' => 505
						)
					)
				),
				//requestMethod
				HttpSource::METHOD_READ,
				//queryData
				array(),
				//Endpoint
				$CF->endpoint()
						->addField($CF->field()
								->name('x')
								->map(function($x) {
									return $x * 101;
								}, '_x_')
						)
			),
		);
	}

	/**
	 * Test request splitter
	 * 
	 * @param array $request
	 * @param array $response
	 * @param HttpSourceEndpoint $Endpoint
	 * 
	 * @dataProvider splitRequestProvider
	 */
	public function testSplitRequest($request, $response, $Endpoint = null) {
		$mockMethods = array(
			'_singleRequest',
			'_joinResponses'
		);
		if ($Endpoint) {
			$mockMethods[] = '_getCurrentEndpoint';
		}
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods($mockMethods)
				->getMock();

		$Source->expects($this->atLeastOnce())->method('_singleRequest')->will($this->returnArgument(0));
		$Source->expects($this->atLeastOnce())->method('_joinResponses')->will($this->returnArgument(0));
		if ($Endpoint) {
			$Source->expects($this->once())->method('_getCurrentEndpoint')->will($this->returnValue($Endpoint));
		}
		$this->assertSame($response, $Source->request(null, $request));
	}

	/**
	 * Data provider for testSplitRequest
	 * 
	 * @return array
	 */
	public function splitRequestProvider() {
		return array(
			//set #0
			array(
				//request
				array('a' => 'b'),
				//response
				array(array('a' => 'b')),
				//Endpoint
				null
			),
			//set #1
			array(
				//request
				array(
					'a' => 'b',
					'c' => 'd',
					'e' => 'f',
				),
				//response
				array(array('a' => 'b'), array('c' => 'd'), array('e' => 'f')),
				//Endpoint
				HttpSourceConfigFactory::instance()->endpoint()->requestSplitter(function($request) {
					return array_chunk($request, 1, true);
				})
			),
		);
	}

	/**
	 * Test join responses
	 * 
	 * @param array $request
	 * @param array $response
	 * @param HttpSourceEndpoint $Endpoint
	 * 
	 * @dataProvider joinResponsesProvider
	 */
	public function testJoinResponses($request, $response, $Endpoint = null) {
		$mockMethods = array(
			'_singleRequest',
			'_splitRequest'
		);
		if ($Endpoint) {
			$mockMethods[] = '_getCurrentEndpoint';
		}
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods($mockMethods)
				->getMock();

		$Source->expects($this->atLeastOnce())->method('_singleRequest')->will($this->returnArgument(0));
		$Source->expects($this->atLeastOnce())->method('_splitRequest')->will($this->returnCallback(function($v) {
			return array($v);
		}));
		if ($Endpoint) {
			$Source->expects($this->once())->method('_getCurrentEndpoint')->will($this->returnValue($Endpoint));
		}
		$this->assertSame($response, $Source->request(null, $request));
	}

	/**
	 * Data provider for testJoinResponses
	 * 
	 * @return array
	 */
	public function joinResponsesProvider() {
		return array(
			//set #0
			array(
				//request
				array('a' => 'b'),
				//response
				array('a' => 'b'),
				//Endpoint
				null
			),
			//set #1
			array(
				//request
				array(
					'a' => 'b',
					'c' => 'd',
					'e' => 'f',
				),
				//response
				array('joined'),
				//Endpoint
				HttpSourceConfigFactory::instance()->endpoint()->responseJoiner(function($responses) {
					return array('joined');
				})
			),
		);
	}

	/**
	 * Test list sources
	 */
	public function testListSources() {
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$this->assertSame($Source->getConfig()->listSources(), $Source->listSources());
	}

	/**
	 * Test describe
	 */
	public function testDescribe() {
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$Model = new Model;
		$Model->useTable = 'endpoint1';
		$this->assertSame($Source->getConfig()->describe($Model), $Source->describe($Model));
		$Model->useTable = 'unknown_endpoint1';
		$this->assertSame(array(), $Source->describe($Model));
	}

	/**
	 * Test getRequestLog
	 * 
	 * @param string $query
	 * @param string $error
	 * @param int $affected
	 * @param int $numRows
	 * @param int $took
	 * @param array $result
	 * @dataProvider getRequestLogProvider
	 */
	public function testGetRequestLog($query, $error, $affected, $numRows, $took, array $result) {
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$Source->query = $query;
		$Source->error = $error;
		$Source->affected = $affected;
		$Source->numRows = $numRows;
		$Source->took = $took;
		$this->assertSame($result, $Source->getRequestLog());
	}

	/**
	 * Data provider for testGetRequestLog
	 * 
	 * @return array
	 */
	public function getRequestLogProvider() {
		return array(
			//set #0
			array(
				//query
				'GET to the Mars',
				//error
				'No enough money!',
				//affected
				0,
				//numRows
				1,
				//took
				99999,
				//result
				array(
					'query' => 'GET to the Mars',
					'error' => 'No enough money!',
					'affected' => 0,
					'numRows' => 1,
					'took' => 99999
				)
			),
			//set #1
			array(
				//query
				str_repeat('x', HttpTestSource::LOG_MAX_LENGTH),
				//error
				'What the ****!',
				//affected
				10,
				//numRows
				12,
				//took
				999991,
				//result
				array(
					'query' => str_repeat('x', HttpTestSource::LOG_MAX_LENGTH),
					'error' => 'What the ****!',
					'affected' => 10,
					'numRows' => 12,
					'took' => 999991
				)
			),
			//set #2
			array(
				//query
				str_repeat('x', HttpTestSource::LOG_MAX_LENGTH + 1),
				//error
				'',
				//affected
				0,
				//numRows
				0,
				//took
				0,
				//result
				array(
					'query' => str_repeat('x', HttpTestSource::LOG_MAX_LENGTH) . ' ' . HttpTestSource::LOG_TRUNCATED,
					'error' => '',
					'affected' => 0,
					'numRows' => 0,
					'took' => 0
				)
			),
			//set #3
			array(
				//query
				str_repeat('x', HttpTestSource::LOG_MAX_LENGTH * 2),
				//error
				'wow!',
				//affected
				1,
				//numRows
				2,
				//took
				3,
				//result
				array(
					'query' => str_repeat('x', HttpTestSource::LOG_MAX_LENGTH) . ' ' . HttpTestSource::LOG_TRUNCATED,
					'error' => 'wow!',
					'affected' => 1,
					'numRows' => 2,
					'took' => 3
				)
			),
		);
	}

	/**
	 * Test logRequest ans getLog
	 * 
	 * @param array $logs
	 * @param array $result
	 * @dataProvider logRequestProvider
	 */
	public function testLogRequest(array $logs, array $result) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array('log', 'getRequestLog'))
				->getMock();

		$at = 0;
		foreach ($logs as $log) {
			$Source->expects($this->at($at++))->method('getRequestLog')->will($this->returnValue($log));
			if (!empty($log['error'])) {
				$Source->expects($this->at($at++))->method('log')->with(get_class($Source) . ': ' . $log['error'] . "\n" . $log['query'], LOG_ERR);
			}
		}

		foreach ($logs as $log) {
			$Source->logRequest();
		}

		$this->assertSame($result, $Source->getLog(false, false));
		$result['log'] = sortByKey($result['log'], 'took', 'desc', SORT_NUMERIC);
		$this->assertSame($result, $Source->getLog(true, true));
		$result['log'] = array();
		$this->assertSame($result, $Source->getLog(false, false));
	}

	/**
	 * Data provider for testLogRequest
	 * 
	 * @return array
	 */
	public function logRequestProvider() {
		return array(
			//set #0
			array(
				//logs
				array(
					array(
						'query' => 'sing river song',
						'error' => 'doctor who?',
						'affected' => 1,
						'numRows' => 2,
						'took' => 3
					),
					array(
						'query' => 'what is 42?',
						'error' => '',
						'affected' => 3,
						'numRows' => 4,
						'took' => 6
					),
					array(
						'query' => 'anybody there?',
						'error' => 'no!',
						'affected' => 10,
						'numRows' => 5,
						'took' => 8
					),
					array(
						'query' => 'find some cookies',
						'error' => 'monkey on keyboard',
						'affected' => 0,
						'numRows' => 6,
						'took' => 11
					),
				),
				//result
				array(
					'log' => array(
						array(
							'query' => 'what is 42?',
							'error' => '',
							'affected' => 3,
							'numRows' => 4,
							'took' => 6
						),
						array(
							'query' => 'anybody there?',
							'error' => 'no!',
							'affected' => 10,
							'numRows' => 5,
							'took' => 8
						),
						array(
							'query' => 'find some cookies',
							'error' => 'monkey on keyboard',
							'affected' => 0,
							'numRows' => 6,
							'took' => 11
						)
					),
					'count' => 4,
					'time' => 3 + 6 + 8 + 11
				)
			),
			//set #1
			array(
				//logs
				array(
					array(
						'query' => 'sing river song',
						'error' => 'doctor who?',
						'affected' => 1,
						'numRows' => 2,
						'took' => 3
					)
				),
				//result
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => 'doctor who?',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						)
					),
					'count' => 1,
					'time' => 3
				)
			),
			//set #2
			array(
				//logs
				array(
					array(
						'query' => 'sing river song',
						'error' => '',
						'affected' => 1,
						'numRows' => 2,
						'took' => 3
					)
				),
				//result
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => '',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						)
					),
					'count' => 1,
					'time' => 3
				)
			),
		);
	}

	/**
	 * Test showLog
	 * 
	 * @param array $logs
	 * @param bool $sorted
	 * @param bool $html
	 * @param array $result
	 * @dataProvider showLogProvider
	 */
	public function testShowLog(array $logs, $sorted, $html, array $result) {
		Configure::write('debug', 2);
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array('getLog'))
				->getMock();
		$Source->expects($this->once())->method('getLog')->with($sorted, false)->will($this->returnValue($logs));
		ob_start();
		$Source->showLog($sorted, $html);
		$output = ob_get_clean();
		if (!$result) {
			$this->assertSame('', $output);
		} else {
			foreach ($result as $line) {
				$this->assertContains($line, $output);
			}
		}
	}

	/**
	 * Data provider for testShowLog
	 * 
	 * @return array
	 */
	public function showLogProvider() {
		return array(
			//set #0
			array(
				//logs
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => 'doctor who?',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						)
					),
					'count' => 1,
					'time' => 3
				),
				//sorted
				false,
				//html
				false,
				//result
				array(
					"1. sing river song\n"
				)
			),
			//set #1
			array(
				//logs
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => '',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						),
						array(
							'query' => 'Hi jack',
							'error' => '',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						),
					),
					'count' => 1,
					'time' => 3
				),
				//sorted
				false,
				//html
				false,
				//result
				array(
					"1. sing river song\n",
					"2. Hi jack\n",
				)
			),
			//set #2
			array(
				//logs
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => 'doctor who?',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						)
					),
					'count' => 1,
					'time' => 3
				),
				//sorted
				false,
				//html
				true,
				//result
				array(
					"sing river song",
					"doctor who?",
					">1<",
					">2<",
					">3<",
					"1 query took 3 ms"
				)
			),
			//set #3
			array(
				//logs
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => 'doctor who?',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						),
						array(
							'query' => 'Hi jack',
							'error' => '',
							'affected' => 111,
							'numRows' => 222,
							'took' => 333
						),
					),
					'count' => 2,
					'time' => 3 + 333
				),
				//sorted
				false,
				//html
				true,
				//result
				array(
					"sing river song",
					"doctor who?",
					">1<",
					">2<",
					">3<",
					"Hi jack",
					">111<",
					">222<",
					">333<",
					"2 queries took 336 ms"
				)
			),
			//set #4
			array(
				//logs
				array(
					'log' => array(
						array(
							'query' => 'sing river song',
							'error' => 'doctor who?',
							'affected' => 1,
							'numRows' => 2,
							'took' => 3
						)
					),
					'count' => 1,
					'time' => 3
				),
				//sorted
				true,
				//html
				false,
				//result
				array(
					"1. sing river song\n"
				)
			),
			//set #5
			array(
				//logs
				array(
					'log' => array(),
					'count' => 0,
					'time' => 0
				),
				//sorted
				true,
				//html
				false,
				//result
				array()
			),
		);
	}

	/**
	 * Test calculate
	 * 
	 * @param string $func
	 * @param string $result
	 * @param string $exception
	 * @dataProvider calculateProvider
	 */
	public function testCalculate($func, $result, $exception) {
		if ($exception) {
			$this->expectException($exception);
		}
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$this->assertSame($result, $Source->calculate(new Model, $func));
	}

	/**
	 * Data provider for testCalculate
	 * 
	 * @return array
	 */
	public function calculateProvider() {
		return array(
			//set #0
			array(
				//func
				'count',
				//result,
				HttpTestSource::FUNCTION_COUNT,
				//exception
				null
			),
			//set #1
			array(
				//func
				'some_unknown_func',
				//result,
				null,
				//exception
				'NotImplementedException'
			),
		);
	}

	/**
	 * Test read
	 * 
	 * @param Model $Model
	 * @param array $queryData
	 * @param int $recursive
	 * @param array $result
	 * @param bool $cached
	 * @param array $request
	 * @dataProvider readProvider
	 */
	public function testRead(Model $Model, array $queryData, $recursive, $result, $cached, $request) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'getQueryCache',
					'_writeQueryCache',
					'request',
				))
				->getMock();
		if ($Model->cacheQueries) {
			$Source->expects($this->once())->method('getQueryCache')->with($request)->will($this->returnValue($cached ? $result : false));
		} else {
			$Source->expects($this->never())->method('getQueryCache');
		}
		if (!$Model->cacheQueries || !$cached) {
			$Source->expects($this->once())->method('request')->with($Model, null, HttpSource::METHOD_READ)->will($this->returnValue($result));
		} else {
			$Source->expects($this->never())->method('request');
		}
		if ($Model->cacheQueries && $result && !$cached) {
			$Source->expects($this->once())->method('_writeQueryCache')->with($request, $result);
		} else {
			$Source->expects($this->never())->method('_writeQueryCache');
		}

		$this->assertSame($result, $Source->read($Model, $queryData, $recursive));
		$this->assertSame($request, $Model->request);
	}

	/**
	 * Data provider for testRead
	 * 
	 * @return array
	 */
	public function readProvider() {
		$CachedAppModel = new AppModel;
		$CachedAppModel->cacheQueries = true;
		return array(
			//set #0
			array(
				//Model
				new AppModel,
				//queryData
				array(
					'conditions' => array(
						'x' => 1,
						'condition2' => 3
					)
				),
				//recursive
				false,
				//result
				array(
					array('hello' => 'there')
				),
				//cached
				false,
				//request,
				array(
					'method' => HttpSource::HTTP_METHOD_READ,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 3
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
			//set #1
			array(
				//Model
				new AppModel,
				//queryData
				array(
					'conditions' => array(
						'x' => 1,
						'condition2' => 3
					)
				),
				//recursive
				false,
				//result
				array(
					array('hello' => 'there')
				),
				//cached
				true,
				//request,
				array(
					'method' => HttpSource::HTTP_METHOD_READ,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 3
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
			//set #2
			array(
				//Model
				$CachedAppModel,
				//queryData
				array(
					'conditions' => array(
						'x' => 1,
						'condition2' => 3
					)
				),
				//recursive
				false,
				//result
				array(
					array('hello' => 'there')
				),
				//cached
				true,
				//request,
				array(
					'method' => HttpSource::HTTP_METHOD_READ,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 3
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
			//set #3
			array(
				//Model
				$CachedAppModel,
				//queryData
				array(
					'conditions' => array(
						'x' => 1,
						'condition2' => 3
					)
				),
				//recursive
				false,
				//result
				array(
					array('hello' => 'there')
				),
				//cached
				false,
				//request,
				array(
					'method' => HttpSource::HTTP_METHOD_READ,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 3
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
			//set #4
			array(
				//Model
				$CachedAppModel,
				//queryData
				array(
					'conditions' => array(
						$CachedAppModel->alias . '.condition2' => 3
					)
				),
				//recursive
				false,
				//result
				array(
					array('hello' => 'there')
				),
				//cached
				false,
				//request,
				array(
					'method' => HttpSource::HTTP_METHOD_READ,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 3
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
		);
	}

	/**
	 * Test create
	 * 
	 * @param Model $Model
	 * @param array $fields
	 * @param array $values
	 * @param array $result
	 * @param array $request
	 * @param string $exception
	 * @dataProvider createProvider
	 */
	public function testCreate(Model $Model, array $fields, array $values, $result, $request, $exception) {
		if ($exception) {
			$this->expectException($exception);
		}
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'request',
				))
				->getMock();

		$Source->expects($exception ? $this->never() : $this->once())->method('request')->with($Model, null, HttpSource::METHOD_CREATE)->will($this->returnValue($result));

		$this->assertSame((bool)$result, $Source->create($Model, $fields, $values));
		$this->assertSame($request, $Model->request);
	}

	/**
	 * Data provider for testCreate
	 * 
	 * @return array
	 */
	public function createProvider() {
		$ModelNoTable = new AppModel;
		$ModelNoTable->useTable = null;
		return array(
			//set #0
			array(
				//Model
				new AppModel,
				//fields
				array('x', 'condition2'),
				//values
				array(1, 2),
				//result
				array(
					'ok' => true
				),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CREATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition2' => 2
					),
					'virtual' => array()
				),
				//exception
				''
			),
			//set #1
			array(
				//Model
				new AppModel,
				//fields
				array('x', 'AppModel.condition2'),
				//values
				array(1, 2),
				//result
				array(
					'ok' => true
				),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CREATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition2' => 2
					),
					'virtual' => array()
				),
				//exception
				''
			),
			//set #2
			array(
				//Model
				$ModelNoTable,
				//fields
				array('x', 'AppModel.condition2'),
				//values
				array(1, 2),
				//result
				array(
					'ok' => true
				),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CREATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition2' => 2
					),
					'virtual' => array()
				),
				//exception
				'HttpSourceException'
			),
		);
	}

	/**
	 * Test update
	 * 
	 * @param Model $Model
	 * @param array $fields
	 * @param array $values
	 * @param array $conditions
	 * @param array $result
	 * @param array $request
	 * @dataProvider updateProvider
	 */
	public function testUpdate(Model $Model, array $fields, array $values, array $conditions, $result, $request) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'request',
				))
				->getMock();

		$Source->expects($this->once())->method('request')->with($Model, null, HttpSource::METHOD_UPDATE)->will($this->returnValue($result));

		$this->assertSame((bool)$result, $Source->update($Model, $fields, $values, $conditions));
		$this->assertSame($request, $Model->request);
	}

	/**
	 * Data provider for testUpdate
	 * 
	 * @return array
	 */
	public function updateProvider() {
		return array(
			//set #0
			array(
				//Model
				new AppModel,
				//fields
				array('condition1', 'y'),
				//values
				array(1, 2),
				//conditions
				array(
					'd' => 546,
					'condition2' => 6
				),
				//result
				array(
					'ok' => true
				),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_UPDATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition2' => 6
					),
					'virtual' => array()
				)
			),
			//set #1
			array(
				//Model
				new AppModel,
				//fields
				array('condition1', 'y'),
				//values
				array(1, 2),
				//conditions
				array(),
				//result
				array(
					'ok' => true
				),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_UPDATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition1' => 1
					),
					'virtual' => array()
				)
			),
			//set #2
			array(
				//Model
				new AppModel,
				//fields
				array('AppModel.condition1', 'y'),
				//values
				array(1, 2),
				//conditions
				array(),
				//result
				array(),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_UPDATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition1' => 1
					),
					'virtual' => array()
				)
			),
			//set #3
			array(
				//Model
				new AppModel,
				//fields
				array('condition1', 'y'),
				//values
				array(1, 2),
				//conditions
				array(
					'd' => 546,
					'AppModel.condition2' => 6
				),
				//result
				array(),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_UPDATE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array()
					),
					'body' => array(
						'condition2' => 6
					),
					'virtual' => array()
				)
			),
		);
	}

	/**
	 * Test delete
	 * 
	 * @param Model $Model
	 * @param array $conditions
	 * @param array $result
	 * @param array $request
	 * @dataProvider deleteProvider
	 */
	public function testDelete(Model $Model, array $conditions, $result, $request) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'request',
				))
				->getMock();

		$Source->expects($this->once())->method('request')->with($Model, null, HttpSource::METHOD_DELETE)->will($this->returnValue($result));

		$this->assertSame((bool)$result, $Source->delete($Model, $conditions));
		$this->assertSame($request, $Model->request);
	}

	/**
	 * Data provider for testDelete
	 * 
	 * @return array
	 */
	public function deleteProvider() {
		return array(
			//set #0
			array(
				//Model
				new AppModel,
				//conditions
				array(
					'd' => 546,
					'condition2' => 6
				),
				//result
				array(
					'ok' => true
				),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_DELETE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 6
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
			//set #1
			array(
				//Model
				new AppModel,
				//conditions
				array(
					'd' => 546,
					'AppModel.condition2' => 6
				),
				//result
				array(),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_DELETE,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 6
						)
					),
					'body' => array(),
					'virtual' => array()
				)
			),
		);
	}

	/**
	 * Test exists
	 * 
	 * @param Model $Model
	 * @param array $conditions
	 * @param array $result
	 * @param array $request
	 * @param bool $endpointConfigured
	 * @dataProvider existsProvider
	 */
	public function testExists(Model $Model, array $conditions, $result, $request, $endpointConfigured) {
		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'request',
				))
				->getMock();

		if ($endpointConfigured) {
			$Source->expects($this->once())->method('request')->with($Model, null, HttpSource::METHOD_CHECK)->will($this->returnValue($result));
		} else {
			$Source->expects($this->never())->method('request');
		}

		$this->assertSame((bool)$result, $Source->exists($Model, $conditions));
		$this->assertSame($request, $Model->request);
	}

	/**
	 * Data provider for testExists
	 * 
	 * @return array
	 */
	public function existsProvider() {
		$UnconfiguredModel = new AppModel;
		$UnconfiguredModel->useTable = 'endpoint1';
		return array(
			//set #0
			array(
				//Model
				$UnconfiguredModel,
				//conditions
				array(
					'd' => 546,
					'condition2' => 6
				),
				//result
				true,
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CHECK
				),
				//endpointConfigured
				false
			),
			//set #1
			array(
				//Model
				new AppModel,
				//conditions
				array(
					'd' => 546,
					'condition2' => 6
				),
				//result
				true,
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CHECK,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 6
						)
					),
					'body' => array(),
					'virtual' => array()
				),
				//endpointConfigured
				true
			),
			//set #2
			array(
				//Model
				new AppModel,
				//conditions
				array(
					'd' => 546,
					'AppModel.condition2' => 6
				),
				//result
				array(),
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CHECK,
					'uri' => array(
						'path' => 'app_models',
						'query' => array(
							'condition2' => 6
						)
					),
					'body' => array(),
					'virtual' => array()
				),
				//endpointConfigured
				true
			),
		);
	}

	/**
	 * Test query cache
	 */
	public function testQueryCache() {
		$result = array(array('result' => 1));

		$Model = new AppModel;
		$Model->cacheQueries = true;

		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'request',
				))
				->getMock();

		$Source->expects($this->once())->method('request')->with($Model, null, HttpSource::METHOD_READ)->will($this->returnValue($result));

		$this->assertSame($result, $Source->read($Model));
		$this->assertSame($result, $Source->read($Model));
		$this->assertSame($result, $Source->read($Model));
	}

	/**
	 * Test no query cache
	 */
	public function testNoQueryCache() {
		$result = array(array('result' => 1));

		$Model = new AppModel;
		$Model->useTable = 'endpoint1';
		$Model->cacheQueries = true;

		$Source = $this->getMockBuilder('HttpTestSource')
				->setConstructorArgs(array(array('datasource' => 'HttpSource.Http/HttpSource')))
				->setMethods(array(
					'request',
				))
				->getMock();

		$Source->expects($this->exactly(3))->method('request')->with($Model, null, HttpSource::METHOD_READ)->will($this->returnValue($result));

		$this->assertSame($result, $Source->read($Model));
		$this->assertSame($result, $Source->read($Model));
		$this->assertSame($result, $Source->read($Model));
	}

	/**
	 * Test create schema
	 * 
	 * @param array $schema
	 * @param array $result
	 * @param string $tableName
	 * @dataProvider createSchemaProvider
	 */
	public function testCreateSchema(array $schema, array $result, $tableName) {
		$Schema = new CakeSchema($schema);
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$this->assertSame($result, $Source->createSchema($Schema, $tableName));
	}

	/**
	 * Data provider for testCreateSchema
	 * 
	 * @return array
	 */
	public function createSchemaProvider() {
		return array(
			//set #0
			array(
				//schema
				array(
					'name' => 'TestSuite',
					'app_models' => array(
						'tableParameters' => array(
							'condition2' => 'value2',
							'y' => 99
						)
					),
					'app_models2' => array(
						'tableParameters' => array(
							'condition3' => 'value3',
							'condition4' => 'value4',
							'x' => 3
						)
					),
				),
				//result
				array(
					array(
						'method' => HttpSource::HTTP_METHOD_CREATE,
						'uri' => array(
							'path' => 'app_models',
							'query' => array()
						),
						'body' => array(
							'condition2' => 'value2'
						),
						'virtual' => array()
					),
					array(
						'method' => HttpSource::HTTP_METHOD_CREATE,
						'uri' => array(
							'path' => 'app_models2',
							'query' => array()
						),
						'body' => array(
							'condition3' => 'value3',
							'condition4' => 'value4'
						),
						'virtual' => array()
					)
				),
				//tableName
				null
			),
			//set #1
			array(
				//schema
				array(
					'name' => 'TestSuite',
					'app_models2' => array(
						'tableParameters' => array(
							'condition3' => 'value3',
							'condition4' => 'value4',
							'x' => 3
						)
					),
				),
				//result
				array(
					array(
						'method' => HttpSource::HTTP_METHOD_CREATE,
						'uri' => array(
							'path' => 'app_models2',
							'query' => array()
						),
						'body' => array(
							'condition3' => 'value3',
							'condition4' => 'value4'
						),
						'virtual' => array()
					)
				),
				//tableName
				null
			),
			//set #2
			array(
				//schema
				array(
					'name' => 'TestSuite',
					'app_models' => array(
						'tableParameters' => array(
							'condition2' => 'value2',
							'y' => 99
						)
					),
					'app_models2' => array(
						'tableParameters' => array(
							'condition3' => 'value3',
							'condition4' => 'value4',
							'x' => 3
						)
					),
				),
				//result
				array(
					array(
						'method' => HttpSource::HTTP_METHOD_CREATE,
						'uri' => array(
							'path' => 'app_models',
							'query' => array()
						),
						'body' => array(
							'condition2' => 'value2'
						),
						'virtual' => array()
					)
				),
				//tableName
				'app_models'
			),
		);
	}

	/**
	 * Test drop schema
	 * 
	 * @param array $schema
	 * @param array $result
	 * @param string $tableName
	 * @dataProvider dropSchemaProvider
	 */
	public function testDropSchema(array $schema, array $result, $tableName) {
		$Schema = new CakeSchema($schema);
		$Source = new HttpTestSource(array('datasource' => 'HttpSource.Http/HttpSource'));
		$this->assertSame($result, $Source->dropSchema($Schema, $tableName));
	}

	/**
	 * Data provider for testDropSchema
	 * 
	 * @return array
	 */
	public function dropSchemaProvider() {
		return array(
			//set #0
			array(
				//schema
				array(
					'name' => 'TestSuite',
					'app_models' => array(
						'tableParameters' => array(
							'condition2' => 'value2',
							'y' => 99
						)
					),
					'app_models2' => array(
						'tableParameters' => array(
							'condition3' => 'value3',
							'condition4' => 'value4',
							'x' => 3
						)
					),
				),
				//result
				array(
					array(
						'method' => HttpSource::HTTP_METHOD_DELETE,
						'uri' => array(
							'path' => 'app_models',
							'query' => array(
								'condition2' => 'value2'
							)
						),
						'body' => array(),
						'virtual' => array()
					),
					array(
						'method' => HttpSource::HTTP_METHOD_DELETE,
						'uri' => array(
							'path' => 'app_models2',
							'query' => array(
								'condition3' => 'value3',
								'condition4' => 'value4'
							)
						),
						'body' => array(),
						'virtual' => array()
					)
				),
				//tableName
				null
			),
			//set #1
			array(
				//schema
				array(
					'name' => 'TestSuite',
					'app_models2' => array(
						'tableParameters' => array(
							'condition3' => 'value3',
							'condition4' => 'value4',
							'x' => 3
						)
					),
				),
				//result
				array(
					array(
						'method' => HttpSource::HTTP_METHOD_DELETE,
						'uri' => array(
							'path' => 'app_models2',
							'query' => array(
								'condition3' => 'value3',
								'condition4' => 'value4'
							)
						),
						'body' => array(),
						'virtual' => array()
					)
				),
				//tableName
				null
			),
			//set #2
			array(
				//schema
				array(
					'name' => 'TestSuite',
					'app_models' => array(
						'tableParameters' => array(
							'condition2' => 'value2',
							'y' => 99
						)
					),
					'app_models2' => array(
						'tableParameters' => array(
							'condition3' => 'value3',
							'condition4' => 'value4',
							'x' => 3
						)
					),
				),
				//result
				array(
					array(
						'method' => HttpSource::HTTP_METHOD_DELETE,
						'uri' => array(
							'path' => 'app_models',
							'query' => array(
								'condition2' => 'value2'
							)
						),
						'body' => array(),
						'virtual' => array()
					)
				),
				//tableName
				'app_models'
			),
		);
	}

}

class HttpTestSource extends HttpSource {

	protected $_requestsLogMax = 3;

}
