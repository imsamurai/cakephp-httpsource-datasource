<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 16.10.2013
 * Time: 15:23:15
 */
App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * Base Fixture for using in HttpSource child plugins
 * 
 * @package HttpSource
 * @subpackage Test.Fixture
 */
class HttpSourceTestFixture extends CakeTestFixture {

	/**
	 * Model name
	 *
	 * @var string
	 */
	public $model = null;

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = null;

	/**
	 * Model
	 *
	 * @var Model
	 */
	protected $_Model = null;

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		if ($this->name === null) {
			if (preg_match('/^(.*)Fixture$/', get_class($this), $matches)) {
				$this->name = $matches[1];
			} else {
				$this->name = get_class($this);
			}
		}
		$this->init();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return void
	 * @throws MissingModelException Whe importing from a model that does not exist.
	 */
	public function init() {
		list($plugin, $modelClass) = pluginSplit($this->model, true);
		App::uses($modelClass, $plugin . 'Model');
		if (!class_exists($modelClass)) {
			throw new MissingModelException(array('class' => $modelClass));
		}

		$this->_Model = new $modelClass(false, null, $this->useDbConfig);
		ClassRegistry::flush();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param DboSource $db An instance of the database object used to create the fixture table
	 * @return boolean True on success, false on failure
	 */
	public function create($db) {
		$this->created[] = $db->configKeyName;
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param DboSource $db An instance of the database object used to create the fixture table
	 * @return boolean True on success, false on failure
	 */
	public function drop($db) {
		$this->created = array();
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param DboSource $db An instance of the database into which the records will be inserted
	 * @return boolean on success or if there are no records to insert, or false on failure
	 */
	public function insert($db) {
		foreach ($this->records as $record) {
			$this->_Model->create();
			$this->_Model->save($record);
		}
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param DboSource $db A reference to a db instance
	 * @return boolean
	 */
	public function truncate($db) {
		foreach ($this->_Model->find('all', array('limit' => 100000000)) as $record) {
			$this->_truncateOne($record[$this->_Model->alias]);
		}
		return true;
	}

	/**
	 * Delete one record
	 *
	 * @param array $record
	 * @return bool
	 */
	protected function _truncateOne(array $record) {
		return (bool)$this->_Model->delete($record[$this->_Model->primaryKey]);
	}

}
