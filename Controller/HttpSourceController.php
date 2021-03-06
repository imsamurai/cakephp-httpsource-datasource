<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 02.09.2014
 * Time: 11:25:37
 * Format: http://book.cakephp.org/2.0/en/controllers.html
 */
App::uses('AppController', 'Controller');

/**
 * HttpSourceController
 * 
 * @property HttpSourceModel $HttpSourceModel HttpSource Model
 * 
 * @package HttpSource
 * @subpackage Controller
 */
class HttpSourceController extends AppController {
	
	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $uses = array('HttpSource.HttpSourceModel');

	/**
	 * Run explain/profiling on queries. 
	 *
	 * @throws BadRequestException
	 */
	public function explain() {
		$this->_checkRequest();
		$result = $this->HttpSourceModel->explain($this->request->data['log']['ds'], $this->request->data['log']['sql']);
		$this->set(compact('result'));
	}

	/**
	 * Checks the hash + the hashed queries,
	 * if there is mismatch a 404 will be rendered. If debug == 0 a 404 will also be
	 * rendered. No explain will be run if a 404 is made.
	 * 
	 * @throws BadRequestException
	 */
	protected function _checkRequest() {
		if (
				!$this->request->is('post') ||
				empty($this->request->data['log']['sql']) ||
				empty($this->request->data['log']['ds']) ||
				empty($this->request->data['log']['hash']) ||
				Configure::read('debug') == 0
		) {
			throw new BadRequestException('Invalid parameters');
		}
		$hash = Security::hash($this->request->data['log']['sql'] . $this->request->data['log']['ds'], 'sha1', true);
		if ($hash !== $this->request->data['log']['hash']) {
			throw new BadRequestException('Invalid parameters');
		}
	}

}
