<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.08.2014
 * Time: 12:40:46
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceResultTest
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceResultTest extends CakeTestCase {

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
		$Result = new HttpSourceResult($Factory);
		$map = $Result->map();
		$this->assertSame(123, $map(123));
		$map = $Result->map(function($v) {
								return $v * 3;
		})->map();
		$this->assertSame(333, $map(111));
	}

}
