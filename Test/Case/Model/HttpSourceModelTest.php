<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 19.08.2014
 * Time: 14:38:46
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceModel', 'HttpSource.Model');

/**
 * HttpSourceModelTest
 * 
 * @package HttpSourceTest
 * @subpackage Model
 */
class HttpSourceModelTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$CF = HttpSourceConfigFactory::instance();
		Configure::write('HttpSource', array(
			'config_version' => 2,
			'config' => $CF->config()
					->add(
							$CF->endpoint()
							->id(1)
							->methodCreate()
							->table('simple_table')
							->addCondition($CF->condition()->name('q1')->sendInQuery()->required())
							->addCondition($CF->condition()->name('b1')->sendInBody())
					)
					->add(
							$CF->endpoint()
							->id(2)
							->methodCreate()
							->table('simple_table')
							->addCondition($CF->condition()->name('q1')->sendInQuery())
							->addCondition($CF->condition()->name('b1')->sendInBody())
					)
					->add(
							$CF->endpoint()
							->id(3)
							->methodDelete()
							->table('simple_table')
							->addCondition($CF->condition()->name('q2')->sendInQuery())
							->addCondition($CF->condition()->name('b2')->sendInBody())
					)
					->add(
							$CF->endpoint()
							->id(4)
							->methodUpdate()
							->table('simple_table')
							->addCondition($CF->condition()->name('q3')->sendInQuery())
							->addCondition($CF->condition()->name('b3')->sendInBody())
					)
					->add(
							$CF->endpoint()
							->id(5)
							->methodCheck()
							->table('simple_table')
							->addCondition($CF->condition()->name('q4')->sendInQuery())
							->addCondition($CF->condition()->name('b4')->sendInBody())
					)
					->add(
							$CF->endpoint()
							->id(6)
							->methodRead()
							->table('simple_table')
							->addCondition($CF->condition()->name('q5')->sendInQuery())
							->addCondition($CF->condition()->name('b5')->sendInBody())
					)
					->add(
							$CF->endpoint()
							->id(7)
							->methodCreate()
							->table('transactions_table')
							->addCondition($CF->condition()->name('transactions')->sendInBody())
							->addCondition($CF->condition()->name('q6')->sendInQuery())
					)
					
		));
	}
	
	
	/**
	 * Test transactions
	 * 
	 * @param array $params
	 * @param array $transactions
	 * @param array $result
	 * @param bool $autoTransactions
	 * 
	 * @dataProvider transactionsProvider
	 */
	public function testTransactions($params, $transactions, $result, $autoTransactions) {		
		$HttpSource = $this->getMock('HttpSource', array('execute'), array(array('datasource' => 'HttpSource.Http/HttpSource')));
		$HttpSource->expects($this->once())->method('execute')->with($result);
		$Model = $this->getMockForAbstractClass('HttpSourceModel', array(false, false, null), '', true, true, true, array('getDataSource'));
		$Model->expects($this->atLeastOnce())->method('getDataSource')->will($this->returnValue($HttpSource));
		$Model->useTable = 'simple_table';
		$Model->setTransactionParams($params['table'], $params['params'], $params['transactionsField'], $params['method']);
		if (!$autoTransactions) {
			$Model->getDataSource()->begin();
		}
		foreach ($transactions as $transaction) {
			list($method, $options) = $transaction;
			call_user_func_array(array($Model, $method), $options);
		}
		if (!$autoTransactions) {
			$Model->getDataSource()->commit();
		}
		$this->assertEmpty($Model->getDataSource()->getTransactionParams());
	}
	
	/**
	 * Data source for testTransactions
	 * 
	 * @return array
	 */
	public function transactionsProvider() {
		return array(
			//set #0
			array(
				//params
				array(
					'table' => 'transactions_table',
					'params' => array(
						'q6' => '123'
					),
					'transactionsField' => 'transactions',
					'method' => HttpSource::METHOD_CREATE
				),
				//transactions
				array(
					array('saveAll', array(
							array(
								array('q1' => '1', 'b1' => '_1'),
								array('q1' => '11', 'b1' => '_11'),
								array('q1' => '111', 'b1' => '_111'),
							)
						))),
				//result
				array(
					'method' => 'PUT',
					'uri' => array(
						'path' => 'transactions_table',
						'query' => array()
					),
					'body' => array(
						'transactions' => array(
							(int)0 => array(
								(int)0 => 'create',
								(int)1 => array(
									'method' => 'PUT',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q1' => '1'
										)
									),
									'body' => array(
										'b1' => '_1'
									),
									'virtual' => array()
								)
							),
							(int)1 => array(
								(int)0 => 'create',
								(int)1 => array(
									'method' => 'PUT',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q1' => '11'
										)
									),
									'body' => array(
										'b1' => '_11'
									),
									'virtual' => array()
								)
							),
							(int)2 => array(
								(int)0 => 'create',
								(int)1 => array(
									'method' => 'PUT',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q1' => '111'
										)
									),
									'body' => array(
										'b1' => '_111'
									),
									'virtual' => array()
								)
							)
						)
					),
					'virtual' => array()
				),
				//autoTransactions
				true
			),
			//set #1
			array(
				//params
				array(
					'table' => 'transactions_table',
					'params' => array(
						'q6' => '123'
					),
					'transactionsField' => 'transactions',
					'method' => HttpSource::METHOD_CREATE
				),
				//transactions
				array(
					array('create', array(array())),
					array('save', array(array('q1' => '1', 'b1' => '_1'))),
					array('save', array(array('b1' => '_2'))),
					array('save', array(array('id' => 1, 'q3' => '3'))),
					array('update', array(array('q3' => '3'))),
					array('delete', array(1)),
					array('deleteAll', array(array('q2' => '2', 'b2' => '_2'))),
					array('exists', array(1, array('q4' => '4', 'b4' => '_4'), true)),
					array('find', array('first', array('conditions' => array('q5' => '5', 'b5' => '_5')))),
				),
				//result
				array(
					'method' => 'PUT',
					'uri' => array(
						'path' => 'transactions_table',
						'query' => array()
					),
					'body' => array(
						'transactions' => array(
							(int)0 => array(
								(int)0 => 'create',
								(int)1 => array(
									'method' => 'PUT',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q1' => '1'
										)
									),
									'body' => array(
										'b1' => '_1'
									),
									'virtual' => array()
								)
							),
							(int)1 => array(
								(int)0 => 'create',
								(int)1 => array(
									'method' => 'PUT',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array()
									),
									'body' => array(
										'b1' => '_2'
									),
									'virtual' => array()
								)
							),
							(int)2 => array(
								(int)0 => 'update',
								(int)1 => array(
									'method' => 'POST',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q3' => '3'
										)
									),
									'body' => array(),
									'virtual' => array()
								)
							),
							(int)3 => array(
								(int)0 => 'update',
								(int)1 => array(
									'method' => 'POST',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q3' => '3'
										)
									),
									'body' => array(),
									'virtual' => array()
								)
							),
							(int)4 => array(
								(int)0 => 'delete',
								(int)1 => array(
									'method' => 'DELETE',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array()
									),
									'body' => array(),
									'virtual' => array()
								)
							),
							(int)5 => array(
								(int)0 => 'delete',
								(int)1 => array(
									'method' => 'DELETE',
									'uri' => array(
										'path' => 'simple_table',
										'query' => array(
											'q2' => '2'
										)
									),
									'body' => array(
										'b2' => '_2'
									),
									'virtual' => array()
								)
							)
						)
					),
					'virtual' => array()
				),
				//autoTransactions
				false
			)
		);
	}

	/**
	 * Test setCredentials
	 */
	public function testSetCredentials() {
		$credentials = array(
			'login' => 'user',
			'password' => 'pwd'
		);
		$HttpSource = $this->getMock('HttpSource_', array('setCredentials'));
		$HttpSource->expects($this->once())->method('setCredentials')->with($credentials);
		$Model = $this->getMockForAbstractClass('HttpSourceModel', array(false, false, null), '', true, true, true, array('getDataSource'));
		$Model->expects($this->once())->method('getDataSource')->will($this->returnValue($HttpSource));
		$Model->setCredentials($credentials);
	}
	
	/**
	 * Test setTransactionParams
	 */
	public function testTransactionParams() {
		$params = array(
			'table' => 'transactions_table', 
			'params' => array(
				'param1' => true
			), 
			'transactionsField' => 'transactions', 
			'method' => HttpSource::METHOD_CREATE
		);
		$HttpSource = $this->getMock('HttpSource_', array('setTransactionParams'));
		$HttpSource->expects($this->once())->method('setTransactionParams')->with($params['table'], $params['params'], $params['transactionsField'], $params['method']);
		$Model = $this->getMockForAbstractClass('HttpSourceModel', array(false, false, null), '', true, true, true, array('getDataSource'));
		$Model->expects($this->once())->method('getDataSource')->will($this->returnValue($HttpSource));
		$Model->setTransactionParams($params['table'], $params['params'], $params['transactionsField'], $params['method']);
	}

	/**
	 * Test hasField
	 * 
	 * @param string $name
	 * @param bool $checkVirtual
	 * @dataProvider hasFieldProvider
	 */
	public function testHasField($name, $checkVirtual) {
		$Model = new HttpSourceTestModel;
		$this->assertSame(true, $Model->hasField($name, $checkVirtual));
	}

	/**
	 * Data provider for testHasField
	 * 
	 * @return array
	 */
	public function hasFieldProvider() {
		return array(
			//set #0
			array(
				//name
				'somename',
				//checkVirtual
				true
			),
			//set #1
			array(
				//name
				'othername',
				//checkVirtual
				false
			),
		);
	}

	/**
	 * Test exists
	 * 
	 * @param string|id $id
	 * @param string|id $modelId
	 * @param array $conditions
	 * @param bool $force
	 * @param bool $exists
	 * 
	 * @dataProvider existsProvider
	 */
	public function testExists($id, $modelId, array $conditions, $force, $exists) {
		$Model = $this->getMockForAbstractClass('HttpSourceModel', array(false, false, null), '', true, true, true, array('getDataSource', 'getID'));
		$Model->primaryKey = 'id';

		$HttpSource = $this->getMock('HttpSource_', array('exists'));
		if ($force && ($id || $modelId)) {
			$HttpSource
					->expects($this->once())
					->method('exists')
					->with($Model, array($Model->primaryKey => ($id ? $id : $modelId)) + $conditions)
					->will($this->returnValue($exists));
			$Model
					->expects($this->once())
					->method('getDataSource')
					->will($this->returnValue($HttpSource));
		}

		if (!$id) {
			$Model
					->expects($this->once())
					->method('getID')
					->will($this->returnValue($modelId));
		}

		$this->assertSame($exists, $Model->exists($id, $conditions, $force));
	}

	/**
	 * Data provider for testExists
	 * 
	 * @return array
	 */
	public function existsProvider() {
		return array(
			//set #0
			array(
				//id
				1,
				//modelId
				2,
				//conditions
				array(),
				//force
				false,
				//exists
				true
			),
			//set #1
			array(
				//id
				null,
				//modelId
				2,
				//conditions
				array(),
				//force
				false,
				//exists
				true
			),
			//set #2
			array(
				//id
				null,
				//modelId
				false,
				//conditions
				array(),
				//force
				false,
				//exists
				false
			),
			//set #3
			array(
				//id
				1,
				//modelId
				false,
				//conditions
				array(
					'a' => 1
				),
				//force
				true,
				//exists
				true
			),
			//set #4
			array(
				//id
				1,
				//modelId
				false,
				//conditions
				array(
					'a' => 1
				),
				//force
				true,
				//exists
				false
			),
		);
	}

	/**
	 * Test update
	 * 
	 * @param array $data
	 * @param bool $validate
	 * @param array $fieldList
	 * @dataProvider updateProvider
	 */
	public function testUpdate($data, $validate, $fieldList) {
		$Model = $this->getMockForAbstractClass('HttpSourceModel', array(false, false, null), '', true, true, true, array('save'));
		$Model
				->expects($this->once())
				->method('save')
				->with($data, $validate, $fieldList)
				->will($this->returnValue(true));

		$this->assertTrue($Model->update($data, $validate, $fieldList));
		$this->assertTrue($Model->id);
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
				//data
				null,
				//validate
				false,
				//fieldList
				array()
			),
			//set #1
			array(
				//data
				array('a' => 1),
				//validate
				false,
				//fieldList
				array('b' => 2)
			),
			//set #3
			array(
				//data
				array('a' => 1),
				//validate
				true,
				//fieldList
				array('b' => 2)
			),
		);
	}

}

/**
 * HttpSourceTestModel
 * 
 * @package HttpSourceTest
 * @subpackage Model
 */
class HttpSourceTestModel extends HttpSourceModel {
	
}
