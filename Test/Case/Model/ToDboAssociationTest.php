<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 5:28:22 PM
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */

App::uses('HttpSourceTest', 'HttpSource.Test');

/**
 * HttpSourceToDboAssociationTest
 */
class ToDboAssociationTest extends HttpSourceTest {

	/**
	 * User model
	 *
	 * @var User
	 */
	public $User = null;
	/**
	 * {@inheritdoc}
	 */
    public function setUp(){
        parent::setUp();
		$this->User = new User();
    }

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.HttpSource.User',
		'plugin.HttpSource.UsersDocument'
	);

	public function testHABTM() {
		$this->User->Documents->useDbConfig = 'testHttpSource';
		$results = $this->User->find('all', array('recursive' => 3));
		debug($results);
		foreach ($results as $result) {
			$this->assertNotEmpty($result['Documents']);
		}
	}
}