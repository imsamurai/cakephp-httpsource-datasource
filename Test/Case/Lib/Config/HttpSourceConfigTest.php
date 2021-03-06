<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 15.08.2014
 * Time: 15:13:46
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceConfig', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');

/**
 * HttpSourceConfigTest
 * 
 * @property HttpSourceConfig $Config HttpSourceConfig
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConfigTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->Config = new HttpSourceConfig(HttpSourceConfigFactory::instance());
	}

	/**
	 * Test cache name
	 */
	public function testCacheName() {
		$cache = 'default';
		$this->Config->cacheName($cache);
		$this->assertSame($cache, $this->Config->cacheName());
	}

	/**
	 * Test result
	 */
	public function testResult() {
		$Result = new HttpSourceResult(HttpSourceConfigFactory::instance());
		$this->Config->result($Result);
		$this->assertSame($Result, $this->Config->result());
	}

	/**
	 * Test read params
	 */
	public function testReadParams() {
		$readParams = array(
			'fields' => 'fields',
			'count' => 'limit'
		);
		$this->Config->readParams($readParams);
		$this->assertSame($readParams, $this->Config->readParams());
	}

	/**
	 * Test add endpoint
	 * 
	 * @param array $Endpoints
	 * @param bool $exception
	 * @dataProvider addProvider
	 */
	public function testAdd(array $Endpoints, $exception) {
		if ($exception) {
			$this->expectException('HttpSourceConfigException');
		}
		foreach ($Endpoints as $Endpoint) {
			$this->Config->add($Endpoint);
		}
	}

	/**
	 * Data provider for testAdd
	 * 
	 * @return array
	 */
	public function addProvider() {
		return array(
			//set #0
			array(
				//Endpoints
				array(
					new HttpSourceEndpoint(HttpSourceConfigFactory::instance()),
				),
				//exception
				true
			),
			//set #1
			array(
				//Endpoint
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1),
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1)
				),
				//exception
				true
			),
			//set #2
			array(
				//Endpoint
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1),
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(2)
				),
				//exception
				false
			),
		);
	}

	/**
	 * Test get endpoint by id
	 * 
	 * @param int|string $id
	 * @param array $Endpoints
	 * @param bool $exception
	 * @dataProvider endpointProvider
	 */
	public function testEndpoint($id, array $Endpoints, $exception) {
		foreach ($Endpoints as $Endpoint) {
			$this->Config->add($Endpoint);
		}
		if ($exception) {
			$this->expectException('HttpSourceConfigException');
		}
		$FoundEndpoint = $this->Config->endpoint($id);
		$this->assertSame($id, $FoundEndpoint->id());
	}

	/**
	 * Data provider for testEndpoint
	 * 
	 * @return array
	 */
	public function endpointProvider() {
		return array(
			//set #0
			array(
				//id
				1,
				//Endpoints
				array(),
				//exception
				true
			),
			//set #1
			array(
				//id
				2,
				//Endpoint
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1)
				),
				//exception
				true
			),
			//set #2
			array(
				//id
				2,
				//Endpoint
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1),
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(2)
				),
				//exception
				false
			),
		);
	}

	/**
	 * Test find endpoint by method, table, conditions
	 * 
	 * @param int|string $id
	 * @param string $method
	 * @param string $table
	 * @param array $fields
	 * @param array $Endpoints
	 * @param bool $exception
	 * @dataProvider findEndpointProvider
	 */
	public function testFindEndpoint($id, $method, $table, array $fields, array $Endpoints, $exception) {
		foreach ($Endpoints as $Endpoint) {
			$this->Config->add($Endpoint);
		}
		if ($exception) {
			$this->expectException('HttpSourceConfigException');
		}
		$FoundEndpoint = $this->Config->findEndpoint($method, $table, $fields);
		$this->assertSame($id, $FoundEndpoint->id());
	}

	/**
	 * Data provider for testFindEndpoint
	 * 
	 * @return array
	 */
	public function findEndpointProvider() {
		return array(
			//set #0
			array(
				//id
				1,
				//method
				'method',
				//table
				'table',
				//fields
				array(),
				//Endpoints
				array(),
				//exception
				true
			),
			//set #1
			array(
				//id
				1,
				//method
				HttpSourceEndpoint::METHOD_CHECK,
				//table
				'table',
				//fields
				array(),
				//Endpoints
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1)->methodCheck()->table('table')
				),
				//exception
				false
			),
			//set #2
			array(
				//id
				1,
				//method
				HttpSourceEndpoint::METHOD_CHECK,
				//table
				'table',
				//fields
				array(),
				//Endpoints
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1)->methodCheck()->table('table'),
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(2)->methodCheck()->table('table2'),
				),
				//exception
				false
			),
			//set #3
			array(
				//id
				2,
				//method
				HttpSourceEndpoint::METHOD_CHECK,
				//table
				'table',
				//fields
				array(),
				//Endpoints
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1)->methodCheck()->table('table'),
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(2)->methodCheck()->table('table'),
				),
				//exception
				false
			),
			//set #4
			array(
				//id
				1,
				//method
				HttpSourceEndpoint::METHOD_CHECK,
				//table
				'table',
				//fields
				array(),
				//Endpoints
				array(
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(1)->methodCheck()->table('table')->path('path1'),
					(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))->id(2)->methodCheck()->table('table')->path('path2'),
				),
				//exception
				false
			),
			//set #5
			array(
				//id
				2,
				//method
				HttpSourceEndpoint::METHOD_CHECK,
				//table
				'table',
				//fields
				array('condition1'),
				//Endpoints
				array(
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(2)
							->methodCheck()
							->table('table')
							->path('path2')
							->addCondition(
									(new HttpSourceCondition(HttpSourceConfigFactory::instance()))
									->name('condition1')->required()
							),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(1)
							->methodCheck()
							->table('table')
							->path('path1'),
				),
				//exception
				false
			),
			//set #6
			array(
				//id
				2,
				//method
				HttpSourceEndpoint::METHOD_CHECK,
				//table
				'table',
				//fields
				array('unknown_condition'),
				//Endpoints
				array(
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(2)
							->methodCheck()
							->table('table')
							->path('path2')
							->addCondition(
									(new HttpSourceCondition(HttpSourceConfigFactory::instance()))
									->name('condition1')->required()
							)
				),
				//exception
				true
			),
		);
	}

	/**
	 * Test describe
	 * 
	 * @param array $Endpoints
	 * @param string $table
	 * @param array $schema
	 * @dataProvider describeProvider
	 */
	public function testDescribe(array $Endpoints, $table, $schema) {
		foreach ($Endpoints as $Endpoint) {
			$this->Config->add($Endpoint);
		}
		$Model = new AppModel;
		$Model->useTable = $table;
		$this->assertSame($schema, $this->Config->describe($Model));
	}
	
	/**
	 * Data provider for testDescribe
	 * 
	 * @return array
	 */
	public function describeProvider() {
		return array(
			//set #0
			array(
				//Endpoints
				array(),
				//table
				false,
				//schema
				array()
			),
			//set #1
			array(
				//Endpoints
				array(
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(2)
							->methodCheck()
							->table('table')
							->path('path2')
							->addCondition(
									(new HttpSourceCondition(HttpSourceConfigFactory::instance()))
									->name('condition1')->required()
							)
							->addCondition(
									(new HttpSourceCondition(HttpSourceConfigFactory::instance()))
									->name('id')->keyPrimary()
							),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(1)
							->methodCheck()
							->table('table')
							->path('path1')
							->addCondition(
									(new HttpSourceCondition(HttpSourceConfigFactory::instance()))
									->name('condition2')
							)
							->addCondition(
									(new HttpSourceCondition(HttpSourceConfigFactory::instance()))
									->name('id')->keyPrimary()
							),
				),
				//table
				'table',
				//schema
				array(
					'condition2' => array(
						'type' => 'string',
						'null' => true,
						'default' => null
					),
					'id' => array(
						'type' => 'string',
						'null' => true,
						'key' => 'primary',
						'default' => null
					),
					'condition1' => array(
						'type' => 'string',
						'null' => false,
						'default' => null
					),
				)
			),
		);
	}
	
	/**
	 * Test list sources
	 * 
	 * @param array $Endpoints
	 * @param array $sources
	 * @dataProvider listSourcesProvider
	 */
	public function testListSources(array $Endpoints, array $sources) {
		foreach ($Endpoints as $Endpoint) {
			$this->Config->add($Endpoint);
		}
		$this->assertSame($sources, $this->Config->listSources());
	}
	
	/**
	 * Data provider for testListSources 
	 * 
	 * @return array
	 */
	public function listSourcesProvider() {
		return array(
			//set #0
			array(
				//Endpoints
				array(),
				//sources
				array(),
			),
			//set #1
			array(
				//Endpoints
				array(
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(2)
							->methodCheck()
							->table('table')
							->path('path1')
				),
				//sources
				array(
					'table'
				)
			),
			//set #2
			array(
				//Endpoints
				array(
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(1)
							->methodCheck()
							->table('table')
							->path('path1'),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(2)
							->methodCheck()
							->table('table')
							->path('path2'),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(3)
							->methodRead()
							->table('table')
							->path('path2'),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(4)
							->methodRead()
							->table('table2'),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(5)
							->methodDelete()
							->table('table3'),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(6)
							->methodCreate()
							->table('table4'),
							(new HttpSourceEndpoint(HttpSourceConfigFactory::instance()))
							->id(7)
							->methodUpdate()
							->table('table4'),
				),
				//sources
				array(
					'table', 'table2', 'table4', 'table3'
				)
			),
		);
	}

}
