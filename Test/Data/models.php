<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 9:18:11 PM
 * Format: http://book.cakephp.org/2.0/en/views.html
 */
App::uses('HttpSourceModel', 'HttpSource.Model');

/**
 * Test Document model
 * 
 * @package HttpSourceTest
 * @subpackage Model
 */
class HttpSourceDocument extends HttpSourceModel {

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $name = 'HttpSourceDocument';

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $useTable = 'documents';

}

/**
 * Test User model
 * 
 * @package HttpSourceTest
 * @subpackage Model
 */
class HttpSourceUser extends AppModel {

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $name = 'HttpSourceUser';

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $useTable = 'users';

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $useDbConfig = 'test';

	/**
	 * {@inheritdoc}
	 *
	 * @var array 
	 */
	public $hasAndBelongsToMany = array(
		'Documents' => array(
			'className' => 'HttpSourceDocument',
			'joinTable' => 'users_documents',
			'dependent' => false
		)
	);

}
