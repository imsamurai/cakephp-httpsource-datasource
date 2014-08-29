<?php
/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 17.07.2013
 * Time: 12:42:22
 */

App::uses('HttpSocketOauth', 'HttpSocketOauth.');

/**
 * Default HttpSource connection class
 * 
 * @package HttpSource
 * @subpackage Model.Datasource
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
	 * Affected rows
	 *
	 * @var int|string
	 */
	protected $_affected = 0;

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
	 * Last decoded response
	 *
	 * @var array
	 */
	protected $_lastResponse = null;

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param HttpSocket $Transport
	 */
	public function __construct(array $config = array(), HttpSocket $Transport = null) {
		if (is_null($Transport)) {
			if (Hash::get($config, 'auth.name') === 'oauth') {
				if ($config['auth']['version'][0] == 2) {
					$config['auth']['method'] = 'OAuthV2';
				} else {
					$config['auth']['method'] = 'OAuth';
				}

				$Transport = new HttpSocketOauth($config);
			} else {
				App::uses('HttpSocket', 'Network/Http');
				$Transport = new HttpSocket($config);
			}
		}
		$this->_config = (array)Hash::get($config, 'connection') + $this->_config;
		$this->_Transport = $Transport;

		$this->_initDefaultDecoders();
	}
	
	/**
	 * Return current transport
	 * 
	 * @return HttpSocket
	 */
	public function getTransport() {
		return $this->_Transport;
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
	 * Returns last request took time
	 *
	 * @return int|string
	 */
	public function getTook() {
		return $this->_took;
	}
	
	/**
	 * Returns affected rows
	 *
	 * @return int|string
	 */
	public function getAffected() {
		return $this->_affected;
	}
	
	/**
	 * Returns number of rows in result
	 * 
	 * @param mixed $result
	 * @return int|string
	 */
	public function getNumRows($result) {
		return is_array($result) ? count($result) : 0;
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
	 * Add decoder for given $contentType
	 *
	 * @param string|array $contentType Content type
	 * @param callable $callback Function used for decoding
	 * @param bool $replace Replace decoder if already set or not. Default false
	 */
	public function setDecoder($contentType, callable $callback, $replace = false) {
		$contentTypes = (array)$contentType;
		foreach ($contentTypes as $type) {
			if (!isset($this->_decoders[$type]) || $replace) {
				$this->_decoders[$type] = $callback;
			}
		}
	}

	/**
	 * Get decoder by given $contentType.
	 * If decoder not found writes log and throw exception.
	 *
	 * @param string $contentType
	 * @return callable
	 * @throws HttpSourceException
	 */
	public function getDecoder($contentType) {
		if (empty($this->_decoders[$contentType])) {
			throw new HttpSourceException("Can't decode unknown format: '$contentType'");
		}

		return $this->_decoders[$contentType];
	}
	
	/**
	 * Get decoders
	 *
	 * @return array
	 */
	public function getDecoders() {
		return $this->_decoders;
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
	 * @throws HttpSourceConfigException
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
		$request['uri']['query'] = (array)Hash::get($request, 'uri.query') + $this->getCredentials();
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
	 * @throws HttpSourceConfigException
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
		} elseif (!$this->_Response->isOk()) {
			$this->_error = $this->_extractRemoteError();
			$response = false;
		} elseif ($this->_Response->isOk() && $request['method'] === HttpSource::HTTP_METHOD_CHECK) {
			$response = array('ok' => true);
		} elseif ($this->_Response->isOk()) {
			try {
				$response = $this->_decode();
			} catch (Exception $Exception) {
				$this->_error = $Exception->getMessage();
				$response = false;
			}
		}
		$this->_lastResponse = $response;
		return $response;
	}

	/**
	 * Reset state variables
	 */
	public function reset() {
		$this->_error = '';
		$this->_took = 0;
		$this->_affected = 0;
		$this->_Response = false;
	}

	/**
	 * Disconnect
	 */
	public function disconnect() {
		return $this->_Transport->disconnect();
	}
	
	/**
	 * Initialize default decoders
	 */
	protected function _initDefaultDecoders() {
		$this->setDecoder(array('application/xml', 'application/atom+xml', 'application/rss+xml'), function(HttpSocketResponse $Response) {
			App::uses('Xml', 'Utility');
			$Xml = Xml::build((string)$Response);
			$response = Xml::toArray($Xml);

			return $response;
		}, false);

		$this->setDecoder(array('application/json', 'application/javascript', 'text/javascript'), function(HttpSocketResponse $Response) {
			return json_decode((string)$Response, true);
		}, false);
	}

	/**
	 * Make attempts to request
	 *
	 * @param array $request
	 * @param int $currentAttempt
	 * @return boolean
	 */
	protected function _request(array $request, $currentAttempt = 1) {
		try {
			$Response = $this->_Transport->request($request);
			$this->_error = (string)$this->_Transport->lastError();
		} catch (Exception $Exception) {
			$this->_error = $Exception->getMessage() ? $Exception->getMessage() : 'Unknown exception';
			$Response = false;
		}

		if ($this->_error) {
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
	 * @throws HttpSourceException If content type decoder not found or response is not an object
	 */
	protected function _decode() {
		if (!method_exists($this->_Response, 'getHeader')) {
			throw new HttpSourceException('Response is not an object');
		}
		// Extract content type from content type header
		if (preg_match('/^(?P<content_type>[a-z0-9\/\+]+)/i', $this->_Response->getHeader('Content-Type'), $matches)) {
			$contentType = $matches['content_type'];
		} else {
			$contentType = 'unknown/unknown';
		}

		// Decode response according to content type
		return (array)call_user_func($this->getDecoder($contentType), $this->_Response);
	}

}
