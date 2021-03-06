<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.08.2014
 * Time: 12:40:46
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceEndpointItem', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceEndpointItemTest
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceEndpointItemTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test map
	 */
	public function testMap() {
		$Factory = HttpSourceConfigFactory::instance();
		$Item = new HttpSourceEndpointItemiAmExistsANDValid($Factory);
		$map = $Item->map();
		$this->assertSame(123, $map(123));
		$map = $Item->map(function($v) {
					return $v * 3;
		})->map();
		$this->assertSame(333, $map(111));
	}

}

/**
 * HttpSourceEndpointItemiAmExistsANDValid
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceEndpointItemiAmExistsANDValid extends HttpSourceEndpointItem {
	
}
