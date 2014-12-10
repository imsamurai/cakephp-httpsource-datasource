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