<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.08.2014
 * Time: 12:45:23
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');
App::uses('HttpSourceCondition', 'HttpSource.Lib/Config');
App::uses('HttpSourceField', 'HttpSource.Lib/Config');

/**
 * HttpSourceEndpointTest
 * 
 * @property HttpSourceEndpoint $Endpoint HttpSourceEndpoint
 * 
 * @package HttpSourceTest
 * @subpackage Lib.Config
 */
class HttpSourceEndpointTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->Endpoint = new HttpSourceEndpoint(HttpSourceConfigFactory::instance());
	}

	/**
	 * Test CRUD method
	 * 
	 * @param string $method
	 * @dataProvider methodProvider
	 */
	public function testMethod($method) {
		$this->Endpoint->{"method" . Inflector::camelize($method)}();
		$Reflection = new ReflectionClass($this->Endpoint);
		$this->assertSame($Reflection->getConstant("METHOD_" . strtoupper($method)), $this->Endpoint->method());
	}

	/**
	 * Data provider for testMethod
	 * 
	 * @return array
	 */
	public function methodProvider() {
		return array(
			//set #0
			array(
				//method
				'create'
			),
			//set #1
			array(
				//method
				'update'
			),
			//set #2
			array(
				//method
				'delete'
			),
			//set #3
			array(
				//method
				'check'
			),
			//set #4
			array(
				//method
				'read'
			),
		);
	}

	/**
	 * Test id
	 */
	public function testId() {
		$id = 123;
		$this->assertSame($this->Endpoint->id($id)->id(), $id);
	}

	/**
	 * Test table
	 */
	public function testTable() {
		$table = 'some_table';
		$this->assertSame($this->Endpoint->table($table)->table(), $table);
	}

	/**
	 * Test path
	 */
	public function testPath() {
		$table = 'some_table';
		$path = 'some_path';
		$this->Endpoint->table($table);
		$this->assertSame($this->Endpoint->path(), $table);
		$this->assertSame($this->Endpoint->path($path)->path(), $path);
	}

	/**
	 * Test cache name
	 */
	public function testCacheName() {
		$cacheName = 'some_cache_name';
		$this->assertSame($this->Endpoint->cacheName($cacheName)->cacheName(), $cacheName);
	}

	/**
	 * Test result
	 */
	public function testResult() {
		$Result = new HttpSourceResult(HttpSourceConfigFactory::instance());
		$this->assertSame($this->Endpoint->result($Result)->result(), $Result);
	}

	/**
	 * Test requestSplitter
	 */
	public function testRequestSplitter() {
		$requestSplitter = function(array $request) {
			
		};
		$this->assertSame($this->Endpoint->requestSplitter($requestSplitter)->requestSplitter(), $requestSplitter);
	}

	/**
	 * Test responseJoiner
	 */
	public function testResponseJoiner() {
		$responseJoiner = function(array $responses) {
			
		};
		$this->assertSame($this->Endpoint->responseJoiner($responseJoiner)->responseJoiner(), $responseJoiner);
	}

	/**
	 * Test queryBuilder
	 */
	public function testQueryBuilder() {
		$queryBuilder = function(Model $model, array $usedConditions, array $queryData) {
			
		};
		$this->assertSame($this->Endpoint->queryBuilder($queryBuilder)->queryBuilder(), $queryBuilder);
	}

	/**
	 * Test condition
	 */
	public function testCondition() {
		$name1 = 'condition1';
		$Consition1 = $this->Endpoint->condition($name1);
		$this->assertSame('HttpSourceCondition', get_class($Consition1));
		$this->assertSame($name1, $Consition1->name());

		$name2 = 'condition2';
		$Consition2 = (new HttpSourceCondition(HttpSourceConfigFactory::instance()))->name($name2);
		$this->Endpoint->addCondition($Consition2);
		$Consition2Founded = $this->Endpoint->condition($name2);
		$this->assertSame($Consition2, $Consition2Founded);
		$this->assertSame('HttpSourceCondition', get_class($Consition2Founded));
		$this->assertSame($name2, $Consition2Founded->name());
	}

	/**
	 * Test field
	 */
	public function testField() {
		$name1 = 'field1';
		$Field1 = $this->Endpoint->field($name1);
		$this->assertSame('HttpSourceField', get_class($Field1));
		$this->assertSame($name1, $Field1->name());

		$name2 = 'field2';
		$Field2 = (new HttpSourceField(HttpSourceConfigFactory::instance()))->name($name2);
		$this->Endpoint->addField($Field2);
		$Field2Founded = $this->Endpoint->field($name2);
		$this->assertSame($Field2, $Field2Founded);
		$this->assertSame('HttpSourceField', get_class($Field2Founded));
		$this->assertSame($name2, $Field2Founded->name());
	}

	/**
	 * Test read params
	 */
	public function testReadParams() {
		$readParams = array(
			'fields' => 'fields',
			'count' => 'limit',
		);
		$this->Endpoint->readParams($readParams);
		$this->assertSame($readParams, $this->Endpoint->readParams());
	}

	/**
	 * Test required/optional/defaults conditions
	 * 
	 * @param array $conditions
	 * @param array $required
	 * @param array $optional
	 * @param array $defaults
	 * @dataProvider requiredOptionalDefaultsConditionsProvider
	 */
	public function testRequiredOptionalDefaultsConditions(array $conditions, array $required, array $optional, array $defaults) {
		foreach ($conditions as $Condition) {
			$this->Endpoint->addCondition($Condition);
		}
		$this->assertSame($this->Endpoint->requiredConditions(), $required, 'required');
		$this->assertSame($this->Endpoint->optionalConditions(), $optional, 'optional');
		$this->assertSame($this->Endpoint->conditionsDefaults(), $defaults, 'defaults');
	}

	/**
	 * Data provider for testRequiredOptionalDefaultsConditions
	 * 
	 * @return type
	 */
	public function requiredOptionalDefaultsConditionsProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//conditions
				array(),
				//required
				array(),
				//optional
				array(),
				//defaults
				array()
			),
			//set #1
			array(
				//conditions
				array(
					$CF->condition()->name('c1')->required(),
					$CF->condition()->name('c2')->defaults('v2'),
					$CF->condition()->name('c3')->map(null, '_c3')->defaults('v3'),
					$CF->condition()->name('c4')->map(null, '_c4')->null(false),
				),
				//required
				array(
					'c1',
					'c4',
				),
				//optional
				array(
					'c2',
					'c3',
				),
				//defaults
				array(
					'c2' => 'v2',
					'c3' => 'v3',
				)
			),
		);
	}

	/**
	 * Test schema
	 * 
	 * @param array $conditions
	 * @param array $schema
	 * @dataProvider schemaProvider
	 */
	public function testSchema(array $conditions, array $schema) {
		foreach ($conditions as $Condition) {
			$this->Endpoint->addCondition($Condition);
		}
		$this->assertSame($this->Endpoint->schema(), $schema);
	}

	/**
	 * Data provider for testSchema
	 * 
	 * @return array
	 */
	public function schemaProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//conditions
				array(),
				//schema
				array()
			),
			//set #1
			array(
				//conditions
				array(
					$CF->condition()->name('c1')->length(10)->typeInt()->required()->keyPrimary(),
					$CF->condition()->name('c2')->defaults('v2')->length(20)->typeString()->map(null, '_c2'),
				),
				//schema
				array(
					'c1' => array(
						'type' => 'integer',
						'null' => false,
						'length' => 10,
						'key' => 'primary',
						'default' => null,
					),
					'c2' => array(
						'type' => 'string',
						'null' => true,
						'length' => 20,
						'default' => 'v2',
					),
				)
			),
		);
	}

	/**
	 * Test buildRequest
	 * 
	 * @param array $conditions
	 * @param string $method
	 * @param string $path
	 * @param array $readParams
	 * @param array $queryData
	 * @param array $request
	 * @dataProvider buildRequestProvider
	 */
	public function testBuildRequest(array $conditions, $method, $path, array $readParams, array $queryData, array $request) {
		$methods = array(
			HttpSourceEndpoint::METHOD_CREATE => 'PUT',
			HttpSourceEndpoint::METHOD_UPDATE => 'POST',
			HttpSourceEndpoint::METHOD_READ => 'GET',
			HttpSourceEndpoint::METHOD_DELETE => 'DELETE',
			HttpSourceEndpoint::METHOD_CHECK => 'HEAD',
		);
		foreach ($conditions as $Condition) {
			$this->Endpoint->addCondition($Condition);
		}
		$Model = new AppModel;
		$Model->request = array(
			'method' => $methods[$method]
		);
		$this->Endpoint
				->path($path)
				->readParams($readParams)
				->{"method" . ucfirst($method)}()
				->buildRequest($Model, $queryData);

		$this->assertSame($Model->request, $request);
	}

	/**
	 * Data provider for testBuildRequest
	 * 
	 * @return array
	 */
	public function buildRequestProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//conditions
				array(),
				//method
				HttpSourceEndpoint::METHOD_READ,
				//path
				'path',
				//readParams
				array(),
				//queryData
				array(),
				//request
				array(
					'method' => 'GET',
					'uri' => array(
						'path' => 'path',
						'query' => array()
					),
					'body' => array(),
					'virtual' => array()
				),
			),
			//set #1
			array(
				//conditions
				array(
					$CF->condition()->name('c1')->map(null, '_c1')->required()->sendInQuery(),
					$CF->condition()->name('c2')->map(function($v) {
						return '1_' . $v;
					}, '_c2')->sendInBody(),
					$CF->condition()->name('c3')->map(function($v) {
						return '2_' . $v;
					})->sendInVirtual(),
					$CF->condition()->name('c4')->defaults('v4')->sendInQuery(),
					$CF->condition()->name('c5')->defaults('v5')->map(null, '_c5')->sendInQuery(),
					$CF->condition()->name('c6')->defaults('v6')->map(null, '_c6')->sendInQuery(),
				),
				//method
				HttpSourceEndpoint::METHOD_READ,
				//path
				'path_x',
				//readParams
				array(),
				//queryData
				array(
					'conditions' => array(
						'c1' => 'v1',
						'c2' => 'v2',
						'c3' => 'v3',
						'c6' => 'v666',
					)
				),
				//request
				array(
					'method' => 'GET',
					'uri' => array(
						'path' => 'path_x',
						'query' => array(
							'_c1' => 'v1',
							'_c6' => 'v666',
							'c4' => 'v4',
							'_c5' => 'v5',
						)
					),
					'body' => array(
						'_c2' => '1_v2'
					),
					'virtual' => array(
						'c3' => '2_v3'
					)
				),
			),
			//set #2
			array(
				//conditions
				array(
					$CF->condition()->name('limit')->sendInQuery(),
					$CF->condition()->name('offset')->sendInQuery()
				),
				//method
				HttpSourceEndpoint::METHOD_READ,
				//path
				'path',
				//readParams
				array(
					'limit' => 'limit',
					'offset' => 'offset'
				),
				//queryData
				array(
					'limit' => 30,
					'offset' => 20
				),
				//request
				array(
					'method' => 'GET',
					'uri' => array(
						'path' => 'path',
						'query' => array(
							'limit' => 30,
							'offset' => 20
						)
					),
					'body' => array(),
					'virtual' => array()
				),
			),
			//set #3
			array(
				//conditions
				array(
					$CF->condition()->name('count')->sendInQuery(),
				),
				//method
				HttpSourceEndpoint::METHOD_READ,
				//path
				'path',
				//readParams
				array(
					'count' => 'limit+offset',
				),
				//queryData
				array(
					'limit' => 30,
					'offset' => 20
				),
				//request
				array(
					'method' => 'GET',
					'uri' => array(
						'path' => 'path',
						'query' => array(
							'count' => 50
						)
					),
					'body' => array(),
					'virtual' => array()
				),
			),
			//set #4
			array(
				//conditions
				array(
					$CF->condition()->name('count')->sendInQuery(),
					$CF->condition()->name('x')->sendInQuery(),
					$CF->condition()->name('y')->sendInQuery(),
				),
				//method
				HttpSourceEndpoint::METHOD_READ,
				//path
				'path',
				//readParams
				array(
					'count' => 'anything+offset',
					'x' => 'limit',
					'y' => 'offset'
				),
				//queryData
				array(
					'limit' => 0,
					'conditions' => array(
						'x' => 2
					)
				),
				//request
				array(
					'method' => 'GET',
					'uri' => array(
						'path' => 'path',
						'query' => array(
							'x' => 2
						)
					),
					'body' => array(),
					'virtual' => array()
				),
			)
		);
	}

	/**
	 * Test processFields
	 * 
	 * @param array $fields
	 * @param array $results
	 * @param array $expectedResults
	 * @dataProvider processFieldsProvider
	 */
	public function testProcessFields(array $fields, array $results, array $expectedResults) {
		foreach ($fields as $Field) {
			$this->Endpoint->addField($Field);
		}
		$Model = new AppModel;
		$this->Endpoint->processFields($Model, $results);
		$this->assertSame($expectedResults, $results);
	}

	/**
	 * Data provider for testProcessFields
	 * 
	 * @return array
	 */
	public function processFieldsProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//fields
				array(),
				//results
				array(),
				//expectedResults
				array()
			),
			//set #1
			array(
				//fields
				array(),
				//results
				array(
					array(
						'id' => 1
					),
					array(
						'id' => 2
					)
				),
				//expectedResults
				array(
					array(
						'id' => 1
					),
					array(
						'id' => 2
					)
				)
			),
			//set #2
			array(
				//fields
				array(
					$CF->field()->name('title'),
					$CF->field()->name('link')->map(null, '_link'),
					$CF->field()->name('name')->map(function($v) {
						return $v . $v;
					}, 'other_name'),
				),
				//results
				array(
					array(
						'id' => 1,
						'name' => 'Jack'
					),
					array(
						'id' => 2,
						'name' => 'London'
					)
				),
				//expectedResults
				array(
					array(
						'id' => 1,
						'other_name' => 'JackJack',
						'title' => null,
						'_link' => null,
					),
					array(
						'id' => 2,
						'other_name' => 'LondonLondon',
						'title' => null,
						'_link' => null,
					)
				)
			),
		);
	}

	/**
	 * Test process result
	 * 
	 * @param HtpSourceResult $Result
	 * @param array $result
	 * @param array $expectedResult
	 * @dataProvider processResultProvider
	 */
	public function testProcessResult(HttpSourceResult $Result, array $result, array $expectedResult) {
		$this->Endpoint->result($Result);
		$this->Endpoint->processResult(new AppModel, $result);
		$this->assertSame($expectedResult, $result);
	}

	/**
	 * Data provider for testProcessResult
	 * 
	 * @return array
	 */
	public function processResultProvider() {
		$CF = HttpSourceConfigFactory::instance();
		return array(
			//set #0
			array(
				//Result
				$CF->result(),
				//result
				array(),
				//expectedResult
				array(),
			),
			//set #1
			array(
				//Result
				$CF->result(),
				//result
				array(
					'key' => 'value'
				),
				//expectedResult
				array(
					'key' => 'value'
				),
			),
			//set #2
			array(
				//Result
				$CF->result()->map(function($v) {
					return $v + array('key2' => 'value2');
				}),
				//result
				array(
					'key' => 'value'
				),
				//expectedResult
				array(
					'key' => 'value',
					'key2' => 'value2',
				),
			),
		);
	}

	/**
	 * Test default values
	 */
	public function testDefaultValues() {
		$this->assertSame(HttpSourceEndpoint::METHOD_READ, $this->Endpoint->method());
		$this->assertSame(null, $this->Endpoint->id());
		$this->assertSame(null, $this->Endpoint->table());
		$this->assertSame(null, $this->Endpoint->path());
		$this->assertSame(null, $this->Endpoint->cacheName());
		$this->assertSame(null, $this->Endpoint->result());
		$this->assertSame(null, $this->Endpoint->readParams());
		$requestSplitter = $this->Endpoint->requestSplitter();
		$this->assertSame(array(array(1)), $requestSplitter(array(1)));
		$responseJoiner = $this->Endpoint->responseJoiner();
		$this->assertSame(array('a' => 1, 'b' => 2), $responseJoiner(array(
			array('a' => 1), array('b' => 2), 'c' => 3, 4
		)));

		$model = new AppModel;
		$usedConditions = array('a' => 1);
		$queryData = array('b' => 2);
		$Endpoint = $this->getMock('HttpSourceEndpoint', array('_buildQuery'), array(HttpSourceConfigFactory::instance()));
		$Endpoint->expects($this->once())->method('_buildQuery')->with($model, $usedConditions, $queryData)->will($this->returnValue(123));
		$queryBuilder = $Endpoint->queryBuilder();
		$this->assertSame(123, $queryBuilder($model, $usedConditions, $queryData));
	}

}
