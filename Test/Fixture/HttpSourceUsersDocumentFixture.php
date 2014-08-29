<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 9:18:11 PM
 */

/**
 * UsersDocumentFixture
 * 
 * @package HttpSourceTest
 * @subpackage Test.Fixture
 */
class HttpSourceUsersDocumentFixture extends CakeTestFixture {

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
	public $table = 'users_documents';

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $fields = array(
		'user_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 20),
		'document_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 20),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $records = array(
		array('user_id' => 1, 'document_id' => 1),
		array('user_id' => 2, 'document_id' => 1),
		array('user_id' => 2, 'document_id' => 2)
	);

}
