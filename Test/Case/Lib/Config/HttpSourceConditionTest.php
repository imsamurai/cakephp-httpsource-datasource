<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 15.08.2014
 * Time: 13:10:23
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceCondition', 'HttpSource.Lib/Config');
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

/**
 * HttpSourceConditionTest
 * 
 * @property HttpSourceCondition $Condition HttpSourceCondition
 * 
 * @package HttpSourceTest
 * @subpackage Config
 */
class HttpSourceConditionTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->Condition = new HttpSourceCondition(HttpSourceConfigFactory::instance());
	}

	/**
	 * Test send in
	 * 
	 * @param string $where
	 * @dataProvider sendInProvider
	 */
	public function testSendIn($where) {
		$where = ucfirst($where);
		$this->Condition->{"sendIn$where"}();
		$this->assertTrue($this->Condition->{"mustSendIn$where"}(), $where);
		$others = HttpSourceCondition::getSendInTypes();
		foreach ($others as $other) {
			$other = ucfirst($other);
			if ($other === $where) {
				continue;
			}
			$this->assertFalse($this->Condition->{"mustSendIn$other"}(), $other);
		}
	}

	/**
	 * Data provider for testSendIn
	 * 
	 * @return array
	 */
	public function sendInProvider() {
		return array_map(function($type) {
			return array($type);
		}, array_values(HttpSourceCondition::getSendInTypes()));
	}

	/**
	 * Test data type
	 * 
	 * @param string $where
	 * @dataProvider typeProvider
	 */
	public function testType($where) {
		$whereMethodPart = ucfirst($where);
		$this->Condition->{"type$whereMethodPart"}();
		$this->assertSame($where, $this->Condition->type(), $where);
	}

	/**
	 * Data provider for testType
	 * 
	 * @return array
	 */
	public function typeProvider() {
		return array_map(function($type) {
			return array($type);
		}, array_values(HttpSourceCondition::getDataTypes()));
	}

	/**
	 * Test require/null
	 */
	public function testRequire() {
		$this->Condition->required();
		$this->assertFalse($this->Condition->null());
		$this->Condition->null(true);
		$this->assertTrue($this->Condition->null());
		$this->Condition->null(false);
		$this->assertFalse($this->Condition->null());
	}

	/**
	 * Test length
	 */
	public function testLength() {
		$length = 345;
		$this->Condition->length($length);
		$this->assertSame($length, $this->Condition->length());
	}

	/**
	 * Test key
	 */
	public function testKey() {
		$this->Condition->keyPrimary();
		$this->assertSame(HttpSourceCondition::KEY_PRIMARY, $this->Condition->key());
	}

	/**
	 * Test defaults
	 */
	public function testDefaults() {
		$default = 345;
		$this->Condition->defaults($default);
		$this->assertSame($default, $this->Condition->defaults());
	}

	/**
	 * Test when name is not set
	 */
	public function testNoName() {
		$this->expectException('HttpSourceConfigException');
		$this->Condition->name();
	}

	/**
	 * Test name
	 */
	public function testName() {
		$name = 'some_name';
		$this->Condition->name($name);
		$this->assertSame($name, $this->Condition->name());
		$this->assertSame($name, (string)$this->Condition);
	}

	/**
	 * Test map to name
	 */
	public function testMapToName() {
		$name = 'some_name';
		$otherName = 'other_name';

		$this->Condition->name($name);
		$this->assertSame($name, $this->Condition->mapToName());

		$this->Condition->map(null, $otherName);
		$this->assertSame($otherName, $this->Condition->mapToName());
	}

	/**
	 * Test map
	 */
	public function testMap() {
		$name = 'some_name';
		$this->Condition->name($name);
		list($mapCallback, $mapToName) = $this->Condition->map();
		$this->assertSame($mapToName, $this->Condition->mapToName());
		$this->assertSame(123, $mapCallback(123));

		$this->Condition->map(function() {
			return true;
		});
		list($mapCallback, $mapToName) = $this->Condition->map();
		$this->assertSame($mapToName, $this->Condition->mapToName());
		$this->assertSame(true, $mapCallback());
	}

	/**
	 * Test types getter
	 */
	public function testTypesGetters() {
		$this->assertNotEmpty(HttpSourceCondition::getDataTypes());
		$this->assertNotEmpty(HttpSourceCondition::getSendInTypes());
	}

	/**
	 * Test default condition values
	 */
	public function testDefaultValues() {
		$this->assertSame(false, $this->Condition->mustSendInQuery());
		$this->assertSame(false, $this->Condition->mustSendInBody());
		$this->assertSame(true, $this->Condition->mustSendInAny());
		$this->assertSame(false, $this->Condition->mustSendInVirtual());
		$this->assertSame(HttpSourceCondition::TYPE_STRING, $this->Condition->type());
		$this->assertSame(true, $this->Condition->null());
		$this->assertSame(null, $this->Condition->length());
		$this->assertSame(null, $this->Condition->key());
		$this->assertSame(null, $this->Condition->defaults());
		$this->assertSame(false, $this->Condition->extract());
		$this->assertSame(null, $this->Condition->mapToName());
		list($callback, $mapToName) = $this->Condition->map();
		$this->assertSame(1, $callback(1));
		$this->assertSame(null, $mapToName);
	}

	/**
	 * Test extract
	 */
	public function testExtract() {
		$this->Condition->extract(true);
		$this->assertSame(true, $this->Condition->extract());
		$this->Condition->extract(false);
		$this->assertSame(false, $this->Condition->extract());
	}
}
