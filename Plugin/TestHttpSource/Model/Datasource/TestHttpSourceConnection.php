<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Oct 29, 2013
 * Time: 5:45:06 PM
 *
 */
App::uses('HttpSourceConnection', 'HttpSource.Model/Datasource');

class TestHttpSourceConnection extends HttpSourceConnection {

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param HttpSocket $Transport
	 */
	public function __construct(array $config = array(), HttpSocket $Transport = null) {
		parent::__construct($config, $Transport);

		$this->setDecoder(array('text/plain'), function(HttpSocketResponse $Response) {
			return json_decode((string)$Response, true);
		}, false);
	}

}
