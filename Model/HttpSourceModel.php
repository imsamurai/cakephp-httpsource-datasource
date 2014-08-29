<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 17:08:18
 * Format: http://book.cakephp.org/2.0/en/models.html
 */
App::uses('AppModel', 'Model');

/**
 * Model with patched methods to wirk with save()
 * 
 * @package HttpSource
 * @subpackage Model
 */
abstract class HttpSourceModel extends AppModel {

	/**
	 * Model name
	 *
	 * @var string
	 */
	public $name = 'HttpSource';

	/**
	 * Sets credentials data
	 *
	 * @param array $credentials
	 */
	public function setCredentials(array $credentials = array()) {
		$this->getDataSource()->setCredentials($credentials);
	}

	/**
	 * Returns true if the supplied field exists in the model's database table.
	 *
	 * @param string|array $name Name of field to look for, or an array of names
	 * @param boolean $checkVirtual checks if the field is declared as virtual
	 * @return mixed If $name is a string, returns a boolean indicating whether the field exists.
	 *               If $name is an array of field names, returns the first field that exists,
	 *               or false if none exist.
	 */
	public function hasField($name, $checkVirtual = false) {
		return true;
	}

	/**
	 * Returns true if a record with particular ID exists.
	 *
	 * If $id is not passed it calls Model::getID() to obtain the current record ID,
	 * and then performs a Model::find('count') on the currently configured datasource
	 * to ascertain the existence of the record in persistent storage.
	 *
	 * @param integer|string $id ID of record to check for existence
	 * @param array $conditions Addiditional conditions
	 * @param bool $force If true will call exists method if datasource, return true otherwise
	 * @return boolean True if such a record exists
	 */
	public function exists($id = null, array $conditions = array(), $force = false) {
		if ($id === null) {
			$id = $this->getID();
		}

		if ($id === false) {
			return false;
		}

		return $force ? $this->getDataSource()->exists($this, array($this->primaryKey => $id) + $conditions) : true;
	}

	/**
	 * Wrapper for save() to use update method datasource
	 *
	 * @param array $data Data to save.
	 * @param boolean|array $validate Either a boolean, or an array.
	 *   If a boolean, indicates whether or not to validate before saving.
	 *   If an array, allows control of validate, callbacks, and fieldList
	 * @param array $fieldList List of fields to allow to be written
	 * @return mixed On success Model::$data if its not empty or true, false on failure
	 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html
	 */
	public function update($data = null, $validate = true, $fieldList = array()) {
		$this->id = true;
		return $this->save($data, $validate, $fieldList);
	}

	/**
	 * Removes record for given ID. If no ID is given, the current ID is used. Returns true on success.
	 *
	 * @param integer|string $id ID of record to delete
	 * @param boolean $cascade Set to true to delete records that depend on this record (NOT USED)
	 * @return boolean True on success
	 * @link http://book.cakephp.org/2.0/en/models/deleting-data.html
	 */
	public function delete($id = null, $cascade = true) {
		return parent::delete($id, false);
	}

	/**
	 * Deletes multiple model records based on a set of conditions.
	 *
	 * @param mixed $conditions Conditions to match
	 * @param boolean $cascade Set to true to delete records that depend on this record (NOT USED)
	 * @param boolean $callbacks Run callbacks (NOT USED)
	 * @return boolean True on success, false on failure
	 * @link http://book.cakephp.org/2.0/en/models/deleting-data.html#deleteall
	 */
	public function deleteAll($conditions, $cascade = true, $callbacks = false) {
		return parent::deleteAll($conditions, false, false);
	}

}
