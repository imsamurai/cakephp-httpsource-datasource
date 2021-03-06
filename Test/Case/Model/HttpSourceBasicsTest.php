<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 6:06:31 PM
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceAppTest', 'HttpSource.Test');

/**
 * BasicsTest
 * 
 * @package HttpSourceTest
 * @subpackage Model
 */
class HttpSourceBasicsTest extends HttpSourceAppTest {

	/**
	 * Test field() on real server (github)
	 */
	public function testField() {
		$this->HttpModel->setSource('default');
		$result = $this->HttpModel->field('name');

		$this->assertSame('imsamurai', $result);
	}
	
	/**
	 * Test find() on real server (github)
	 */
	public function testFind() {
		$this->HttpModel->setSource('documents');
		$result = $this->HttpModel->find('all', array(
			'conditions' => array(
				'id' => array(1, 2)
			)
		));

		$this->assertCount(2, $result);
	}
	
	/**
	 * Test read() on real server (github)
	 */
	public function testRead() {
		$this->HttpModel->setSource('documents');
		$result = $this->HttpModel->read(null, 1);

		$this->assertSame(1, $result[$this->HttpModel->alias]['id']);
	}
	
	/**
	 * Test find('all') on real server (github)
	 */
	public function testFindAll() {
		$this->HttpModel->setSource('default');
		$result = $this->HttpModel->find('all');

		$this->assertCount(1, $result);
	}

}
