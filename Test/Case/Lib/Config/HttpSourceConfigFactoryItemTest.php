<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.08.2014
 * Time: 12:40:46
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceConfigFactoryItem', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceConfigFactoryItemTest
 */
class HttpSourceConfigFactoryItemTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test construct
	 */
	public function testConstruct() {
		$Factory = HttpSourceConfigFactory::instance();
		$Item = new HttpSourceConfigFactoryItemiAmExistsANDValid($Factory);
		$this->assertSame($Factory, $Item->getConfigFactory());
	}

}

class HttpSourceConfigFactoryItemiAmExistsANDValid extends HttpSourceConfigFactoryItem {
	
}