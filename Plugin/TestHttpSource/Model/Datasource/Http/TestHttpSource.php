<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 5:44:49 PM
 *
 */

App::uses('HttpSource', 'HttpSource.Model/Datasource');
App::uses('TestHttpSourceConnection', 'TestHttpSource.Model/Datasource');

class TestHttpSource extends HttpSource {

	/**
	 * The description of this data source
	 *
	 * @var string
	 */
	public $description = 'Test DataSource';

	/**
	 * {@inheritdoc}
	 *
	 * @param array $config
	 * @param HttpSourceConnection $Connection
	 */
	public function __construct($config = array(), HttpSourceConnection $Connection = null) {
		parent::__construct($config, new TestHttpSourceConnection($config));
	}
}
