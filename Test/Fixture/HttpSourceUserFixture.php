<?php

/**
 * User Fixture
 */
class HttpSourceUserFixture extends CakeTestFixture {

	public $useDbConfig = 'test';
	public $table = 'users';

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 20, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'length' => 100),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array('id' => 1, 'name' => 'dan'),
		array('id' => 2, 'name' => 'robert')
	);

}
