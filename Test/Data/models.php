<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 9:18:11 PM
 * Format: http://book.cakephp.org/2.0/en/views.html
 *
 */
App::uses('HttpSourceModel', 'HttpSource.Model');

class HttpSourceDocument extends HttpSourceModel {

	public $name = 'HttpSourceDocument';
	public $useTable = 'documents';

}

class HttpSourceUser extends AppModel {

	public $name = 'HttpSourceUser';
	public $useTable = 'users';
	public $useDbConfig = 'test';
	public $hasAndBelongsToMany = array(
		'Documents' => array(
			'className' => 'HttpSourceDocument',
			'joinTable' => 'users_documents',
			'dependent' => false
		)
	);

}
