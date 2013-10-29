<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 6:02:17 PM
 * Format: http://book.cakephp.org/2.0/en/models.html
 */

App::uses('HttpSourceModel', 'HttpSource.Model');

/**
 * TestHttpSourceModel Model
 */
class TestHttpSourceModel extends HttpSourceModel {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $name = 'TestHttpSourceModel';

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $useTable = 'default';

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $useDbConfig = 'http';

}