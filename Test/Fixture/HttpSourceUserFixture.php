<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 9:18:11 PM
 */

/**
 * User Fixture
 * 
 * @package HttpSourceTest
 * @subpackage Test.Fixture
 */
class HttpSourceUserFixture extends CakeTestFixture {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $useDbConfig = 'test';

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $table = 'users';

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 20, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'length' => 100),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $records = array(
		array('id' => 1, 'name' => 'dan'),
		array('id' => 2, 'name' => 'robert')
	);

}
