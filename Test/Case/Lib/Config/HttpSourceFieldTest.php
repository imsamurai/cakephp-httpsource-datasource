<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 15.08.2014
 * Time: 13:10:23
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceField', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceFieldTest
 * 
 * @property HttpSourceField $Field HttpSourceField
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceFieldTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->Field = new HttpSourceField(HttpSourceConfigFactory::instance());
	}

	/**
	 * Test when name is not set
	 */
	public function testNoName() {
		$this->expectException('HttpSourceConfigException');
		$this->Field->name();
	}

	/**
	 * Test name
	 */
	public function testName() {
		$name = 'some_name';
		$this->Field->name($name);
		$this->assertSame($name, $this->Field->name());
		$this->assertSame($name, (string)$this->Field);
	}

	/**
	 * Test map to name
	 */
	public function testMapToName() {
		$name = 'some_name';
		$otherName = 'other_name';

		$this->Field->name($name);
		$this->assertSame($name, $this->Field->mapToName());

		$this->Field->map(null, $otherName);
		$this->assertSame($otherName, $this->Field->mapToName());
	}

	/**
	 * Test map
	 */
	public function testMap() {
		$name = 'some_name';
		$this->Field->name($name);
		list($mapCallback, $mapToName) = $this->Field->map();
		$this->assertSame($mapToName, $this->Field->mapToName());
		$this->assertSame(123, $mapCallback(123));

		$this->Field->map(function() {
			return true;
		});
		list($mapCallback, $mapToName) = $this->Field->map();
		$this->assertSame($mapToName, $this->Field->mapToName());
		$this->assertSame(true, $mapCallback());
	}

	/**
	 * Test default condition values
	 */
	public function testDefaultValues() {
		$this->assertSame(null, $this->Field->mapToName());
		list($callback, $mapToName) = $this->Field->map();
		$this->assertSame(1, $callback(1));
		$this->assertSame(null, $mapToName);
	}

}
