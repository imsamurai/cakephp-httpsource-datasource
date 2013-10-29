<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 9:18:11 PM
 * Format: http://book.cakephp.org/2.0/en/views.html
 *
 */
App::uses('HttpSourceModel', 'HttpSource.Model');

class Document extends HttpSourceModel {

	public $name = 'Document';
	public $useTable = 'documents';

}

class User extends AppModel {

	public $name = 'User';
	public $useTable = 'users';
	public $useDbConfig = 'test';
	public $hasAndBelongsToMany = array(
		'Documents' => array(
			'joinTable' => 'users_documents',
			'dependent' => false
		)
	);

}
