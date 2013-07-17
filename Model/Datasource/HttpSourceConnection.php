<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 17.07.2013
 * Time: 12:42:22
 *
 */
class HttpSourceConnection {

	/**
	 * Request error
	 *
	 * @var string
	 */
	protected $_error = null;

	/**
	 * Request time
	 *
	 * @var int|string
	 */
	protected $_took = null;

	/**
	 * Response object
	 *
	 * @var HttpSocketResponse
	 */
	protected $_Response = null;

	/**
	 * Object for requests
	 *
	 * @var HttpSocket
	 */
	protected $_Transport = null;

	/**
	 * Connection config
	 *
	 * @var array
	 */
	protected $_config = array(
		'maxAttempts' => 3,
		'retryCodes' => array(429),
		'retryDelay' => 5 //in seconds
	);

	/**
	 * Credentials for request, for ex: login, password, token, etc
	 *
	 * @var array
	 */
	protected $_credentials = array();

	/**
	 * Contains all possible decoders
	 *
	 * @var array
	 */
	protected $_decoders = array();

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param HttpSocket $Transport
	 */
	public function __construct(array $config, HttpSocket $Transport = null) {
		if (is_null($Transport)) {
			if (Hash::get($config, 'auth.name') === 'oauth') {
				if ($config['auth']['version'][0] == 2) {
					$config['auth']['method'] = 'OAuthV2';
				} else {
					$config['auth']['method'] = 'OAuth';
				}

				App::import('Vendor', 'HttpSocketOauth/HttpSocketOauth');
				$Transport = new HttpSocketOauth($config);
			} else {
				App::uses('HttpSocket', 'Network/Http');
				$Transport = new HttpSocket($config);
			}
		}
		$this->_config = (array) Hash::get($config, 'connection') + $this->_config;
		$this->_Transport = $Transport;

		$this->setDecoder(array('application/xml', 'application/atom+xml', 'application/rss+xml'), function(HttpSocketResponse $Response) {
					App::uses('Xml', 'Utility');
					$Xml = Xml::build((string) $Response);
					$response = Xml::toArray($Xml);

					return $response;
				}, false);

		$this->setDecoder(array('application/json', 'application/javascript', 'text/javascript'), function(HttpSocketResponse $Response) {
					return json_decode((string) $Response, true);
				}, false);
	}

	/**
	 * Returns last Response
	 *
	 * @return HttpSocketResponse
	 */
	public function getResponse() {
		return $this->_Response;
	}

	/**
	 * Returns last error
	 *
	 * @return string
	 */
	public function getError() {
		return $this->_error;
	}

	/**
	 * Returns lst request took time
	 *
	 * @return int|string
	 */
	public function getTook() {
		return $this->_took;
	}

	/**
	 * Returns lst request query
	 *
	 * @return string
	 */
	public function getQuery() {
		return str_replace(array("\n", "\r"), ' ', $this->_Transport->request['raw']);
	}

	/**
	 * Add decoder for given $content_type
	 *
	 * @param string|array $content_type Content type
	 * @param callable $callback Function used for decoding
	 * @param bool $replace Replace decoder if already set or not. Default false
	 */
	public function setDecoder($content_type, callable $callback, $replace = false) {
		$content_types = (array) $content_type;
		foreach ($content_types as $type) {
			if (!isset($this->_decoders[$type]) || $replace) {
				$this->_decoders[$type] = $callback;
			}
		}
	}

	/**
	 * Get decoder by given $content_type.
	 * If decoder not found writes log and throw exception.
	 *
	 * @param string $content_type
	 * @return callable
	 * @throws HttpSourceException
	 */
	public function getDecoder($content_type) {
		if (empty($this->_decoders[$content_type])) {
			throw new HttpSourceException("Can't decode unknown format: '$content_type'");
		}

		return $this->_decoders[$content_type];
	}
	/**
	 * Sets credentials data
	 *
	 * @param array $credentials
	 */
	public function setCredentials(array $credentials = array()) {
		$this->_credentials = $credentials;
	}

	/**
	 * Gets credentials data
	 *
	 * @return array
	 */
	public function getCredentials() {
		return $this->_credentials;
	}

	/**
	 * Supplements a request array with oauth credentials
	 *
	 * @param array $request
	 * @return array $request
	 */
	public function addOauth($request) {
		if (empty($this->_Transport->config['auth']['oauth_consumer_key']) || empty($this->_Transport->config['auth']['oauth_consumer_secret'])) {
			throw new HttpSourceConfigException('You must specify oauth_consumer_key and oauth_consumer_secret!');
		}

		$request['auth']['method'] = 'OAuth';
		$request['auth']['oauth_consumer_key'] = $this->_Transport->config['auth']['oauth_consumer_key'];
		$request['auth']['oauth_consumer_secret'] = $this->_Transport->config['auth']['oauth_consumer_secret'];
		$request['auth'] += $this->getCredentials();

		return $request;
	}

	/**
	 * Supplements a request array with oauth credentials
	 *
	 * @param array $request
	 * @return array $request
	 */
	public function addOauthV2($request) {
		$request['uri']['query'] = (array) Hash::get($request, 'uri.query') + $this->getCredentials();
		return $request;
	}

	/**
	 * Quote data
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function quote($data, $type = PDO::PARAM_STR) {
		return $data;
	}

	/**
	 * Issue the specified request and decode it on success
	 *
	 * @param array $request
	 * @return mixed false on error, decoded response array on success
	 */
	public function request($request = array()) {
		$this->reset();
		// Remove unwanted elements from request array
		$request = array_intersect_key($request, $this->_Transport->request);

		if (isset($this->_Transport->config['auth']['method'])) {
			$authMethod = 'add' . $this->_Transport->config['auth']['method'];
			if (!method_exists($this, $authMethod)) {
				throw new HttpSourceConfigException('No such authorization method: ' . $authMethod);
			}

			$request = $this->{$authMethod}($request);
		}
		$timerStart = microtime(true);
		$this->_Response = $this->_request($request);
		$this->_took = round((microtime(true) - $timerStart) * 1000);

		if (!$this->_Response) {
			$response = false;
		} else if (!$this->_Response->isOk()) {
			$this->_error = $this->_extractRemoteError();
			$response = false;
		} else if ($this->_Response->isOk()) {
			try {
				$response = $this->_decode();
			} catch (Exception $Exception) {
				$this->_error = $Exception->getMessage();
				$response = false;
			}
		}

		return $response;
	}

	/**
	 * Reset state variables
	 */
	public function reset() {
		$this->_error = null;
		$this->_query = null;
		$this->_took = null;
		$this->_Response = null;
	}

	/**
	 * Disconnect
	 */
	public function disconnect() {
		return $this->_Transport->disconnect();
	}

	/**
	 * Make attempts to request
	 *
	 * @param array $request
	 * @param int $currentAttempt
	 * @return boolean
	 */
	protected function _request(array $request, $currentAttempt = 1) {debug("try $currentAttempt");
		try {
			$Response = $this->_Transport->request($request);
			$this->_error = null;
		} catch (Exception $Exception) {
			$this->_error = $Exception->getMessage();
			$Response = false;
		}

		if ($Response && !$Response->isOk() && $this->_canRetryRequest($Response, $currentAttempt)) {
			$this->_requestDelay($Response, $currentAttempt);
			return $this->_request($request, ++$currentAttempt);
		}
		return $Response;
	}

	/**
	 * Checks if we can retry request
	 *
	 * @param HttpSocketResponse $Response
	 * @param int $currentAttempt
	 * @return boolean
	 */
	protected function _canRetryRequest(HttpSocketResponse $Response, $currentAttempt) {
		if ($currentAttempt >= $this->_config['maxAttempts']) {
			return false;
		}
debug($Response->code);
		if (!in_array((int)$Response->code, $this->_config['retryCodes'], true)) {
			return false;
		}

		return true;
	}

	/**
	 * Delay before newt request
	 *
	 * @param HttpSocketResponse $Response
	 * @param int $currentAttempt
	 */
	protected function _requestDelay(HttpSocketResponse $Response, $currentAttempt) {
		sleep($this->_config['retryDelay']);
	}

	/**
	 * Extract remote error from response
	 *
	 * @return string
	 */
	protected function _extractRemoteError() {
		return $this->_Response->reasonPhrase;
	}

	/**
	 * Decodes the response based on the content type
	 *
	 * @return array Decoded response
	 * @trows HttpSourceException If content type decoder not found
	 */
	protected function _decode() {
		// Extract content type from content type header
		if (preg_match('/^(?P<content_type>[a-z0-9\/\+]+)/i', $this->_Response->getHeader('Content-Type'), $matches)) {
			$content_type = $matches['content_type'];
		}

		// Decode response according to content type
		return (array) call_user_func($this->getDecoder($content_type), $this->_Response);
	}

}