<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 27.08.2014
 * Time: 15:07:18
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceConnection', 'HttpSource.Model/Datasource');
App::uses('HttpSource', 'HttpSource.Model/Datasource');
App::uses('HttpSocket', 'Network/Http');
App::uses('HttpSocketResponse', 'Network/Http');
App::uses('HttpSocketOauth', 'HttpSocketOauth.');

/**
 * HttpSourceConnectionTest
 * 
 * @package HttpSourceTest
 * @subpackage Model.Datasource
 */
class HttpSourceConnectionTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * TEst constructor
	 * 
	 * @param array $config
	 * @param string $transportName
	 * @param array $transportConfig
	 * @param HttpSocket $Transport
	 * @dataProvider constructProvider
	 */
	public function testConstruct(array $config, $transportName, array $transportConfig, HttpSocket $Transport = null) {
		$Connection = $this->getMockBuilder('HttpSourceConnection')
				->disableOriginalConstructor()
				->setMethods(array('setDecoder'))
				->getMock();
		$Connection->expects($this->at(0))
				->method('setDecoder')
				->with(array('application/xml', 'application/atom+xml', 'application/rss+xml'), $this->isType('callable'));
		$Connection->expects($this->at(1))
				->method('setDecoder')
				->with(array('application/json', 'application/javascript', 'text/javascript'), $this->isType('callable'));

		$reflectedClass = new ReflectionClass('HttpSourceConnection');
		$constructor = $reflectedClass->getConstructor();
		$constructor->invoke($Connection, $config, $Transport);
		$this->assertSame($transportName, get_class($Connection->getTransport()));
		$transportConfig += $Connection->getTransport()->config;
		$this->assertEquals($transportConfig, $Connection->getTransport()->config);
	}

	/**
	 * Data provider for construct
	 * 
	 * @return array
	 */
	public function constructProvider() {
		return array(
			//set #0
			array(
				//config
				array(),
				//transportName
				'HttpSocket',
				//transportConfig
				array(
					'persistent' => false,
					'host' => 'localhost',
					
					'port' => 80,
					'timeout' => (int)30,
					'ssl_verify_peer' => true,
					'ssl_allow_self_signed' => false,
					'ssl_verify_depth' => (int)5,
					'ssl_verify_host' => true,
					'request' => array(
						'uri' => array(
							'scheme' => array(
								'http',
								'https'
							),
							'host' => 'localhost',
							'port' => array(
								80,
								443
							)
						),
						'redirect' => false,
						'cookies' => array()
					)
				),
				//Transport
				null
			),
			//set #1
			array(
				//config
				array(
					'host' => 'example'
				),
				//transportName
				'HttpSocket',
				//transportConfig
				array(
					'persistent' => false,
					'host' => 'localhost',
					
					'port' => 80,
					'timeout' => (int)30,
					'ssl_verify_peer' => true,
					'ssl_allow_self_signed' => false,
					'ssl_verify_depth' => (int)5,
					'ssl_verify_host' => true,
					'request' => array(
						'uri' => array(
							'scheme' => array(
								'http',
								'https'
							),
							'host' => 'localhost',
							'port' => array(
								80,
								443
							)
						),
						'redirect' => false,
						'cookies' => array()
					)
				),
				//Transport
				new HttpSocket
			),
			//set #2
			array(
				//config
				array(
					'host' => 'example'
				),
				//transportName
				'HttpSocketOauth',
				//transportConfig
				array(
					'persistent' => false,
					'host' => 'localhost',
					
					'port' => 80,
					'timeout' => (int)30,
					'ssl_verify_peer' => true,
					'ssl_allow_self_signed' => false,
					'ssl_verify_depth' => (int)5,
					'ssl_verify_host' => true,
					'request' => array(
						'uri' => array(
							'scheme' => array(
								'http',
								'https'
							),
							'host' => 'localhost',
							'port' => array(
								80,
								443
							)
						),
						'redirect' => false,
						'cookies' => array()
					)
				),
				//Transport
				new HttpSocketOauth
			),
			//set #3
			array(
				//config
				array(
					'host' => 'example'
				),
				//transportName
				'HttpSocket',
				//transportConfig
				array(
					'persistent' => false,
					'host' => 'example',
					
					'port' => 80,
					'timeout' => (int)30,
					'ssl_verify_peer' => true,
					'ssl_allow_self_signed' => false,
					'ssl_verify_depth' => (int)5,
					'ssl_verify_host' => true,
					'request' => array(
						'uri' => array(
							'scheme' => array(
								'http',
								'https'
							),
							'host' => 'localhost',
							'port' => array(
								80,
								443
							)
						),
						'redirect' => false,
						'cookies' => array()
					)
				),
				//Transport
				null
			),
			//set #4
			array(
				//config
				array(
					'auth' => array(
						'name' => 'oauth',
						'version' => array(2)
					)
				),
				//transportName
				'HttpSocketOauth',
				//transportConfig
				array(
					'persistent' => false,
					'host' => 'localhost',
					
					'port' => 80,
					'timeout' => (int)30,
					'ssl_verify_peer' => true,
					'ssl_allow_self_signed' => false,
					'ssl_verify_depth' => (int)5,
					'ssl_verify_host' => true,
					'request' => array(
						'uri' => array(
							'scheme' => array(
								'http',
								'https'
							),
							'host' => 'localhost',
							'port' => array(
								80,
								443
							)
						),
						'redirect' => false,
						'cookies' => array()
					),
					'auth' => array(
						'name' => 'oauth',
						'version' => array(2),
						'method' => 'OAuthV2'
					)
				),
				//Transport
				null
			),
			//set #5
			array(
				//config
				array(
					'auth' => array(
						'name' => 'oauth',
						'version' => array(1)
					)
				),
				//transportName
				'HttpSocketOauth',
				//transportConfig
				array(
					'persistent' => false,
					'host' => 'localhost',
					
					'port' => 80,
					'timeout' => (int)30,
					'ssl_verify_peer' => true,
					'ssl_allow_self_signed' => false,
					'ssl_verify_depth' => (int)5,
					'ssl_verify_host' => true,
					'request' => array(
						'uri' => array(
							'scheme' => array(
								'http',
								'https'
							),
							'host' => 'localhost',
							'port' => array(
								80,
								443
							)
						),
						'redirect' => false,
						'cookies' => array()
					),
					'auth' => array(
						'name' => 'oauth',
						'version' => array(1),
						'method' => 'OAuth'
					)
				),
				//Transport
				null
			),
		);
	}

	/**
	 * Test get/set decoders
	 * 
	 * @param array $setDecoders
	 * @param array $getDecoders
	 * @param string $exception
	 * @dataProvider decodersProvider
	 */
	public function testDecoders(array $setDecoders, array $getDecoders, $exception) {
		if ($exception) {
			$this->expectException($exception);
		}
		
		$Connection = $this->getMockBuilder('HttpSourceConnection')
				->disableOriginalConstructor()
				->setMethods(array('_initDefaultDecoders'))
				->getMock();
		
		foreach ($setDecoders as $setDecoder) {
			call_user_func_array(array($Connection, 'setDecoder'), $setDecoder);
		}
		$contentTypes = array_unique(array_values(Hash::flatten(Hash::extract($setDecoders, '{n}.0'))));
		$this->assertSame($contentTypes, array_keys($Connection->getDecoders()));

		foreach ($getDecoders as $getDecoder) {
			$decoder = $Connection->getDecoder($getDecoder[0]);
			$this->assertSame($getDecoder[2], $decoder($getDecoder[1]));
		}
	}

	/**
	 * Data provider for testDecoders
	 * 
	 * @return array
	 */
	public function decodersProvider() {
		return array(
			//set #0
			array(
				//setDecoders
				array(
					array(
						'text/html',
						function($v) {
							return $v * 2;
						}
					)
				),
				//getDecoders
				array(
					array(
						'text/html',
						2,
						4
					)
				),
				//exception
				null
			),
			//set #1
			array(
				//setDecoders
				array(
					array(
						'text/html',
						function($v) {
							return $v * 2;
						}
					)
				),
				//getDecoders
				array(
					array(
						'text/plain',
					)
				),
				//exception
				'HttpSourceException'
			),
			//set #2
			array(
				//setDecoders
				array(
					array(
						array('text/html', 'text/plain'),
						function($v) {
							return $v * 2;
						}
					),
					array(
						array('text/plain'),
						function($v) {
							return $v * 3;
						}
					),
				),
				//getDecoders
				array(
					array(
						'text/html',
						2,
						4
					),
					array(
						'text/plain',
						2,
						4
					),
				),
				//exception
				null
			),
			//set #3
			array(
				//setDecoders
				array(
					array(
						array('text/html', 'text/plain'),
						function($v) {
							return $v * 2;
						}
					),
					array(
						array('text/plain'),
						function($v) {
							return $v * 3;
						},
						true
					),
				),
				//getDecoders
				array(
					array(
						'text/html',
						2,
						4
					),
					array(
						'text/plain',
						2,
						6
					),
				),
				//exception
				null
			),
		);
	}
	
	/**
	 * Test set/get credentials
	 */
	public function testCredentials() {
		$credentials = array(
			'user' => 'noob',
			'password' => 'monkey'
		);
		$Connection = new HttpSourceConnection;
		$Connection->setCredentials($credentials);
		$this->assertSame($credentials, $Connection->getCredentials());
		$Connection->setCredentials();
		$this->assertSame(array(), $Connection->getCredentials());
	}
	
	/**
	 * Test adding oauth to request
	 * 
	 * @param string $name
	 * @param array $request
	 * @param array $result
	 * @param array $config
	 * @param array $credentials
	 * @param string $exception
	 * @dataProvider addAuthProvider
	 */
	public function testAddOauth($name, array $request, array $result, array $config, array $credentials, $exception) {
		if ($exception) {
			$this->expectException($exception);
		}
		$Connection = new HttpSourceConnection($config);
		$Connection->setCredentials($credentials);
		$this->assertSame($result, $Connection->{$name}($request));
	}
	
	/**
	 * Data provider for testAddOauth
	 * 
	 * @return array
	 */
	public function addAuthProvider() {
		return array(
			//set #0
			array(
				//name
				'addOauth',
				//request
				array(),
				//result
				array(),
				//config
				array(),
				//credentials
				array(),
				//exception
				'HttpSourceConfigException'
			),
			//set #1
			array(
				//name
				'addOauth',
				//request
				array(),
				//result
				array(),
				//config
				array(
					'auth' => array(
						'oauth_consumer_key' => 123
					)
				),
				//credentials
				array(),
				//exception
				'HttpSourceConfigException'
			),
			//set #2
			array(
				//name
				'addOauth',
				//request
				array(),
				//result
				array(),
				//config
				array(
					'auth' => array(
						'oauth_consumer_secret' => 456
					)
				),
				//credentials
				array(),
				//exception
				'HttpSourceConfigException'
			),
			//set #3
			array(
				//name
				'addOauth',
				//request
				array(
					'key' => 'value'
				),
				//result
				array(
					'key' => 'value',
					'auth' => array(
						'method' => 'OAuth',
						'oauth_consumer_key' => 123,
						'oauth_consumer_secret' => 456,
						'user' => 'noob',
						'password' => 'monkey'
					)
				),
				//config
				array(
					'auth' => array(
						'oauth_consumer_key' => 123,
						'oauth_consumer_secret' => 456
					)
				),
				//credentials
				array(
					'user' => 'noob',
					'password' => 'monkey'
				),
				//exception
				null
			),
			//set #4
			array(
				//name
				'addOauthV2',
				//request
				array(
					'key' => 'value',
					'uri' => array(
						'query' => array(
							'one' => 'two'
						)
					)
				),
				//result
				array(
					'key' => 'value',
					'uri' => array(
						'query' => array(
							'one' => 'two',
							'user' => 'noob',
							'password' => 'monkey'
						)
					)
				),
				//config
				array(),
				//credentials
				array(
					'user' => 'noob',
					'password' => 'monkey'
				),
				//exception
				null
			),
			//set #5
			array(
				//name
				'addOauthV2',
				//request
				array(
					'key' => 'value'
				),
				//result
				array(
					'key' => 'value',
					'uri' => array(
						'query' => array(
							'user' => 'noob',
							'password' => 'monkey'
						)
					)
				),
				//config
				array(),
				//credentials
				array(
					'user' => 'noob',
					'password' => 'monkey'
				),
				//exception
				null
			),
		);
	}
	
	/**
	 * Test quote
	 * 
	 * @param mixed $data
	 * @param int $type
	 * @dataProvider quoteProvider
	 */
	public function testQuote($data, $type) {
		$Connection = new HttpSourceConnection();
		$this->assertSame($data, $Connection->quote($data, $type));
	}
	
	/**
	 * Data provider for testQuote
	 * 
	 * @return array
	 */
	public function quoteProvider() {
		return array(
			//set #0
			array(
				//data
				1,
				//type
				PDO::PARAM_STR
			),
			//set #1
			array(
				//data
				1,
				//type
				PDO::PARAM_BOOL
			),
			//set #2
			array(
				//data
				true,
				//type
				PDO::PARAM_STR
			),
			//set #3
			array(
				//data
				new stdClass,
				//type
				PDO::PARAM_LOB
			),
			//set #4
			array(
				//data
				array(123),
				//type
				PDO::PARAM_STR
			),
			//set #5
			array(
				//data
				null,
				//type
				PDO::PARAM_STMT
			),
		);
	}
	
	/**
	 * Test request
	 * 
	 * @param array $request
	 * @param array_bool $response
	 * @param array $data
	 * @param array $config
	 * @param string $authMethod
	 * @dataProvider requestProvider
	 */
	public function testRequest(array $request, $response, array $data, array $config, $authMethod) {
		if (!$data['noResponse']) {
			$Response = $this->getMockBuilder('HttpSocketResponse')
					->disableOriginalConstructor()
					->setMethods(array(
						'isOk',
						'getHeader',
						'__toString',
					))
					->getMock();

			$Response->expects($this->any())->method('isOk')->willReturn($data['isOk']);
			$Response->expects($this->any())->method('getHeader')->with('Content-Type')->willReturn($data['Content-Type']);
			$Response->expects($this->any())->method('__toString')->willReturn($data['data']);
			$Response->reasonPhrase = $data['reasonPhrase'];
		} else {
			$Response = false;
		}

		$Transport = new HttpSocket($config);
		$Transport->request['raw'] = $data['requestRaw'];

		$Connection = $this->getMockBuilder('HttpSourceConnection')
				->setConstructorArgs(array($config, $Transport))
				->setMethods(array(
					'_request',
					'addOauthV2',
					'addOauth',
				))
				->getMock();

		if ($authMethod) {
			$requestFull = array('auth' => array()) + $request;
			$Connection->expects($this->once())->method($authMethod)->with($request)->willReturn($requestFull);
		} else {
			$requestFull = $request;
		}
		$Connection->expects($this->once())->method('_request')->with($requestFull)->willReturn($Response);
		$this->assertSame($response, $Connection->request($request));
		$this->assertSame($data['requestQuery'], $Connection->getQuery());
		$this->assertSame($data['error'], $Connection->getError());
		$this->assertSame(0, $Connection->getAffected());
		$this->assertGreaterThanOrEqual(0, $Connection->getTook());
	}
	
	/**
	 * Data provider for testRequest
	 * 
	 * @return array
	 */
	public function requestProvider() {
		return array(
			//set #0
			array(
				//request
				array(),
				//response
				false,
				//data
				array(
					'isOk' => false,
					'data' => '',
					'reasonPhrase' => '',
					'Content-Type' => 'application/json',
					'error' => '',
					'noResponse' => true,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(),
				//authMethod
				null
			),
			//set #1
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_READ
				),
				//response
				false,
				//data
				array(
					'isOk' => false,
					'data' => '',
					'reasonPhrase' => 'Bad request',
					'Content-Type' => 'application/json',
					'error' => 'Bad request',
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(),
				//authMethod
				null
			),
			//set #2
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CHECK
				),
				//response
				array('ok' => true),
				//data
				array(
					'isOk' => true,
					'data' => '',
					'reasonPhrase' => '',
					'Content-Type' => 'application/json',
					'error' => '',
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(),
				//authMethod
				null
			),
			//set #3
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_READ
				),
				//response
				false,
				//data
				array(
					'isOk' => true,
					'data' => '{"x":1}',
					'reasonPhrase' => '',
					'Content-Type' => 'unknown/type',
					'error' => "Can't decode unknown format: 'unknown/type'",
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(),
				//authMethod
				null
			),
			//set #4
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_READ
				),
				//response
				array(
					'x' => 1
				),
				//data
				array(
					'isOk' => true,
					'data' => '{"x":1}',
					'reasonPhrase' => '',
					'Content-Type' => 'application/json',
					'error' => "",
					'noResponse' => false,
					'requestRaw' => "GET /_all//_mapping\nHTTP/1.1\nHost: elasticsearch.dev:9200\nConnection: close\nUser-Agent: CakePHP",
					'requestQuery' => "GET /_all//_mapping\nHTTP/1.1\nHost: elasticsearch.dev:9200\nConnection: close\nUser-Agent: CakePHP",
				),
				//config
				array(),
				//authMethod
				null
			),
			//set #5
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_READ
				),
				//response
				array(
					'x' => 1
				),
				//data
				array(
					'isOk' => true,
					'data' => '{"x":1}',
					'reasonPhrase' => '',
					'Content-Type' => 'application/json',
					'error' => "",
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(
					'auth' => array(
						'method' => 'OauthV2'
					)
				),
				//authMethod
				'addOauthV2'
			),
			//set #6
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_READ
				),
				//response
				array(
					'x' => 1
				),
				//data
				array(
					'isOk' => true,
					'data' => '{"x":1}',
					'reasonPhrase' => '',
					'Content-Type' => 'application/json',
					'error' => "",
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(
					'auth' => array(
						'method' => 'Oauth'
					)
				),
				//authMethod
				'addOauth'
			),
			//set #7
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_READ
				),
				//response
				false,
				//data
				array(
					'isOk' => true,
					'data' => '',
					'reasonPhrase' => '',
					'Content-Type' => '',
					'error' => "Can't decode unknown format: 'unknown/unknown'",
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(),
				//authMethod
				null
			),
			//set #8
			array(
				//request
				array(
					'method' => HttpSource::HTTP_METHOD_CHECK
				),
				//response
				false,
				//data
				array(
					'isOk' => false,
					'data' => '',
					'reasonPhrase' => '',
					'Content-Type' => 'application/json',
					'error' => '',
					'noResponse' => false,
					'requestRaw' => '',
					'requestQuery' => '',
				),
				//config
				array(),
				//authMethod
				null
			),
		);
	}
	
	/**
	 * Test request attempt
	 * 
	 * @param array $request
	 * @param array|bool $response
	 * @param array $attempts
	 * @param array $config
	 * @param string $lastError
	 * @param int $debugLevel
	 * @dataProvider requestAttemptProvider
	 */
	public function testRequestAttempt(array $request, $response, array $attempts, array $config, $lastError, $debugLevel = null) {
		$Transport = $this->getMockBuilder('HttpSocket')
				->setConstructorArgs(array($config))
				->setMethods(array(
					'request'
				))
				->getMock();
		
		foreach ($attempts as $number => $attempt) {
			if (isset($attempt['lastError'])) {
				$Transport->setLastError(0, $attempt['lastError']);
			}
			if (isset($attempt['exception'])) {
				$Transport->expects($this->at($number))->method('request')->with($request)->willThrowException(new Exception($attempt['exception']));
			} else {
				$Response = $this->getMockBuilder('HttpSocketResponse')
						->disableOriginalConstructor()
						->setMethods(array(
							'isOk'
						))
						->getMock();

				$Response->expects($this->any())->method('isOk')->willReturn($attempt['isOk']);
				$Response->code = $attempt['code'];
				$Response->raw = 'raw request';
				$Transport->expects($this->at($number))->method('request')->with($request)->willReturn($Response);
			}
		}

		$Connection = $this->getMockBuilder('HttpSourceConnection')
				->setConstructorArgs(array($config, $Transport))
				->setMethods(array(
					'_decode',
					'log'
				))
				->getMock();
		$Connection->expects($this->any())->method('_decode')->willReturn($response);
		
		$oldDebugLevel = Configure::read('debug');
		if (!is_null($debugLevel)) {
			Configure::write('debug', $debugLevel);
			if ($debugLevel >= 3 && $lastError) {
				$logsCount = $response ? count($attempts) - 1 : count($attempts);
				$Connection->expects($this->exactly($logsCount))
						->method('log')
						->with($this->matches("%x\nError: $lastError\nQuery:\n%s\nDump:\n%s"), 'HttpSourceError');
			}
		}
		$this->assertSame($response, $Connection->request($request));
		Configure::write('debug', $oldDebugLevel);
		$this->assertStringMatchesFormat($lastError . (!$response && $debugLevel >= 3 ? ' [log id: %x]' : ''), $Connection->getError());
	}

	/**
	 * Data provider for testRequestAttempt
	 * 
	 * @return array
	 */
	public function requestAttemptProvider() {
		return array(
			//set #0
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				array(
					'a' => 1
				),
				//attempts
				array(
					array(
						'isOk' => true,
						'code' => 200
					)
				),
				//config
				array(),
				//lastError
				'',
				//debugLevel
				2
			),
			//set #1
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				false,
				//attempts
				array(
					array(
						'exception' => 'Exception'
					)
				),
				//config
				array(),
				//lastError
				'Exception',
				//debugLevel
				2
			),
			//set #2
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				false,
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 429
					),
					array(
						'isOk' => false,
						'code' => 429
					),
					array(
						'isOk' => false,
						'code' => 429
					)
				),
				//config
				array(),
				//lastError
				'',
				//debugLevel
				2
			),
			//set #3
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				array(
					'a' => 1
				),
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 429
					),
					array(
						'isOk' => false,
						'code' => 429
					),
					array(
						'isOk' => true,
						'code' => 200
					)
				),
				//config
				array(),
				//lastError
				'',
				//debugLevel
				2
			),
			//set #4
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				array(
					'a' => 1
				),
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 429
					),
					array(
						'isOk' => true,
						'code' => 429
					)
				),
				//config
				array(),
				//lastError
				'',
				//debugLevel
				2
			),
			//set #5
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				false,
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 404
					)
				),
				//config
				array(),
				//lastError
				'',
				//debugLevel
				2
			),
			//set #6
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				array(
					'a' => 1
				),
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 404
					),
					array(
						'isOk' => false,
						'code' => 404
					),
					array(
						'isOk' => true,
						'code' => 200
					),
				),
				//config
				array(
					'connection' => array(
						'maxAttempts' => 3,
						'retryCodes' => array(404),
						'retryDelay' => 1
					)
				),
				//lastError
				'',
				//debugLevel
				2
			),
			//set #7
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				false,
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 0,
						'lastError' => 'Timeout'
					),
				),
				//config
				array(),
				//lastError
				'0: Timeout',
				//debugLevel
				2
			),
			//set #8
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				array(
					'a' => 1
				),
				//attempts
				array(
					array(
						'isOk' => true,
						'code' => 200
					)
				),
				//config
				array(),
				//lastError
				'',
				//debugLevel
				3
			),
			//set #9
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				false,
				//attempts
				array(
					array(
						'isOk' => false,
						'code' => 0,
						'lastError' => 'Timeout'
					),
				),
				//config
				array(),
				//lastError
				'0: Timeout',
				//debugLevel
				3
			),
			//set #10
			array(
				//request
				array(
					'method' => HttpSource::METHOD_READ
				),
				//response
				false,
				//attempts
				array(
					array(
						'exception' => 'Exception'
					)
				),
				//config
				array(),
				//lastError
				'Exception',
				//debugLevel
				3
			),
		);
	}
	
	/**
	 * Test get num rows
	 * 
	 * @param mixed $data
	 * @param int $numRows
	 * @dataProvider getNumRowsProvider
	 */
	public function testGetNumRows($data, $numRows) {
		$Connection = new HttpSourceConnection;
		$this->assertSame($numRows, $Connection->getNumRows($data));
	}

	/**
	 * Data provider fortestGetNumRows
	 * 
	 * @return array
	 */
	public function getNumRowsProvider() {
		return array(
			//set #0
			array(
				//data
				'xads',
				//numRows
				0
			),
			//set #1
			array(
				//data
				array(1, 2, 3, 4, 5),
				//numRows
				5
			),
			//set #2
			array(
				//data
				5,
				//numRows
				0
			),
		);
	}
	
	/**
	 * Test disconnect
	 */
	public function testDisconnect() {
		$Transport = $this->getMockBuilder('HttpSocket')
				->setMethods(array(
					'disconnect'
				))
				->getMock();
		$Transport->expects($this->once())->method('disconnect')->willReturn('test');
		$Connection = new HttpSourceConnection(array(), $Transport);
		$this->assertSame('test', $Connection->disconnect());
	}
	
	/**
	 * Test default decoders
	 * 
	 * @param string $contentType
	 * @param string $encoded
	 * @param array $decoded
	 * @dataProvider defaultDecodersProvider
	 */
	public function testDefaultDecoders($contentType, $encoded, array $decoded) {
		$Response = $this->getMockBuilder('HttpSocketResponse')
				->disableOriginalConstructor()
				->setMethods(array(
					'__toString'
				))
				->getMock();
		$Response->expects($this->once())->method('__toString')->willReturn($encoded);
		$Connection = new HttpSourceConnection;
		$decoder = $Connection->getDecoder($contentType);
		$this->assertSame($decoded, $decoder($Response));
	}

	/**
	 * Data provider for testDefaultDecoders
	 * 
	 * @return array
	 */
	public function defaultDecodersProvider() {
		return array(
			//set #0
			array(
				//contentType
				'application/xml',
				//encoded
				'<?xml version="1.0"?><greeting>Hello, world!</greeting>',
				//decoded
				array('greeting' => 'Hello, world!')
			),
			//set #1
			array(
				//contentType
				'application/atom+xml',
				//encoded
				'<?xml version="1.0"?><greeting>Hello, world!</greeting>',
				//decoded
				array('greeting' => 'Hello, world!')
			),
			//set #2
			array(
				//contentType
				'application/rss+xml',
				//encoded
				'<?xml version="1.0"?><greeting>Hello, world!</greeting>',
				//decoded
				array('greeting' => 'Hello, world!')
			),
			//set #3
			array(
				//contentType
				'application/json',
				//encoded
				'{"greeting":"Hello, world!"}',
				//decoded
				array('greeting' => 'Hello, world!')
			),
			//set #4
			array(
				//contentType
				'application/javascript',
				//encoded
				'{"greeting":"Hello, world!"}',
				//decoded
				array('greeting' => 'Hello, world!')
			),
			//set #5
			array(
				//contentType
				'text/javascript',
				//encoded
				'{"greeting":"Hello, world!"}',
				//decoded
				array('greeting' => 'Hello, world!')
			),
		);
	}

}
