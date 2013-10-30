<?php

/**
 * Http DataSource
 *
 * HttpSource is abstract class for all datasources that using http protocol
 *
 * @author imsamurai <im.samuray@gmail.com>
 */
App::uses('ArraySort', 'ArraySort.Utility');
App::uses('DataSource', 'Model/Datasource');
App::uses('HttpSourceConnection', 'HttpSource.Model/Datasource');

abstract class HttpSource extends DataSource {

	/**
	 * Count function constant
	 */
	const FUNCTION_COUNT = 'COUNT()';

	/**
	 * CRUD constants
	 */
	const METHOD_READ = 'read';
	const METHOD_CREATE = 'create';
	const METHOD_UPDATE = 'update';
	const METHOD_DELETE = 'delete';

	/**
	 * Http methods constants
	 */
	const HTTP_METHOD_READ = 'GET';
	const HTTP_METHOD_CREATE = 'PUT';
	const HTTP_METHOD_UPDATE = 'POST';
	const HTTP_METHOD_DELETE = 'DELETE';

	/**
	 * Maximum log length
	 */
	const LOG_MAX_LENGTH = 500;

	/**
	 * String to replace truncated part of log
	 */
	const LOG_TRUNCATED = '*TRUNCATED*';

	/**
	 * The description of this data source
	 *
	 * @var string
	 */
	public $description = 'Http DataSource';

	/**
	 * Print full query debug info?
	 *
	 * @var boolean
	 */
	public $fullDebug = false;

	/**
	 * Holds the datasource configuration
	 *
	 * @var array
	 */
	public $config = array();

	/**
	 * Time the last query took
	 *
	 * @var integer
	 */
	public $took = null;

	/**
	 * Last query
	 *
	 * @var string
	 */
	public $query = null;

	/**
	 * Rows affected
	 *
	 * @var integer
	 */
	public $affected = null;

	/**
	 * Rows number
	 *
	 * @var integer
	 */
	public $numRows = null;

	/**
	 * Time the last query error
	 *
	 * @var string
	 */
	public $error = null;

	/**
	 * Holds a configuration map
	 *
	 * @var array
	 */
	public $map = array();

	/**
	 * Configuration object
	 *
	 * @var HttpSourceConfig
	 */
	public $Config = null;

	/**
	 * Columns info by type.
	 * Used in Model::save, Model::create methods
	 *
	 * @var array
	 */
	public $columns = array(null => array());

	/**
	 * Instance of HttpSourceConnection class
	 *
	 * @var HttpSourceConnection
	 */
	protected $_Connection = null;

	/**
	 * Current requested endpoint
	 *
	 * @var HttpSourceEndpoint
	 */
	protected $_currentEndpoint = null;

	/**
	 * Queries count.
	 *
	 * @var integer
	 */
	protected $_requestsCnt = 0;

	/**
	 * Total duration of all queries.
	 *
	 * @var integer
	 */
	protected $_requestsTime = null;

	/**
	 * Log of queries executed by this DataSource
	 *
	 * @var array
	 */
	protected $_requestsLog = array();

	/**
	 * Maximum number of items in query log
	 *
	 * This is to prevent query log taking over too much memory.
	 *
	 * @var integer Maximum number of queries in the queries log.
	 */
	protected $_requestsLogMax = 200;

	/**
	 * Caches serialized results of executed queries
	 *
	 * @var array Cache of results from executed queries.
	 */
	protected $_requestCache = array();

	/**
	 * Query data passed in read method
	 *
	 * @var array
	 */
	protected $_queryData = array();

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param HttpSourceConnection $Connection
	 * @trows HttpSourceException
	 */
	public function __construct($config = array(), HttpSourceConnection $Connection = null) {
		parent::__construct($config);
		// Store the API configuration map
		list($plugin, $name) = pluginSplit($config['datasource']);

		if (!$this->map = Configure::read($plugin)) {
			Configure::load($plugin . '.' . $plugin);
			$this->map = Configure::read($plugin);
		}
		//must be after map loading
		if ((int) Configure::read($plugin . '.config_version') === 2) {
			$this->Config = Configure::read($plugin . '.config');
		} else {
			throw new NotImplementedException('Configs with config_version != 2 are not implemented yet!');
		}

		if (empty($this->map)) {
			throw new HttpSourceException('Configuration not found!');
		}

		$this->_Connection = $Connection ? $Connection : new HttpSourceConnection($this->config);

		$this->fullDebug = Configure::read('debug') > 1;
	}

	/**
	 * Sets credentials data
	 *
	 * @param array $credentials
	 */
	public function setCredentials(array $credentials = array()) {
		$this->_Connection->setCredentials($credentials);
	}

	/**
	 * Gets credentials data
	 *
	 * @return array
	 */
	public function getCredentials() {
		return $this->_Connection->getCredentials();
	}

	/**
	 * Add decoder for given $content_type
	 *
	 * @param string|array $content_type Content type
	 * @param callable $callback Function used for decoding
	 * @param bool $replace Replace decoder if already set or not. Default false
	 */
	public function setDecoder($content_type, callable $callback, $replace = false) {
		$this->_Connection->setDecoder($content_type, $callback, $replace);
	}

	/**
	 * Http query abstraction
	 *
	 * @param @param mixed $request Array of request or string uri
	 * @return array|false
	 * @throws NotImplementedException
	 */
	public function query($request) {
		if (func_num_args() > 1) {
			throw new NotImplementedException('HttpSource can take only one argument in query');
		}
		return $this->request(null, $request);
	}

	/**
	 * Sends HttpSocket requests. Builds your uri and formats the response too.
	 *
	 * @param Model $model Model object
	 * @param mixed $request_data Array of request or string uri
	 * @param string $request_method read, create, update, delete
	 *
	 * @return array|false $response
	 */
	public function request(Model $model = null, $request_data = null, $request_method = HttpSource::METHOD_READ) {

		if ($model !== null) {
			$request = $model->request;
		} elseif (is_array($request_data)) {
			$request = $request_data;
		} elseif (is_string($request_data)) {
			$request = array('uri' => $request_data);
		}
		$responses = array();
		foreach ($this->_splitRequest($request) as $subRequest) {
			$responses[] = $this->_singleRequest($subRequest, $request_method, $model);
		}

		$response = $this->afterRequest($model, $this->_joinResponses($responses), $request_method);

		return $response;
	}

	/**
	 * Returns all available sources
	 *
	 * @param mixed $data
	 * @return array Array of sources available in this datasource.
	 */
	public function listSources($data = null) {
		return $this->Config->listSources();
	}

	public function describe($model) {
		if ($model instanceof Model) {
			return $this->Config->describe($model);
		}
		return parent::describe($model);
	}

	/**
	 * Iterates through the tokens (passed or request items) and replaces them into the url
	 *
	 * @param array $request
	 */
	public function swapTokens(array &$request) {
		$query = (array) Hash::get($request, 'uri.query');
		foreach ($query as $token => $value) {
			if (is_array($value)) {
				continue;
			}
			$count = 0;
			$request['uri']['path'] = preg_replace('/\:' . preg_quote($token, '/') . '\b/', $value, $request['uri']['path'], 1, $count);
			if ($count > 0) {
				unset($request['uri']['query'][$token]);
			}
		}
	}

	/**
	 * Log given query.
	 *
	 * @param array $params Values binded to the query (prepared statements)
	 * @return void
	 */
	public function logRequest() {
		$this->_requestsCnt++;
		$log = array(
			'query' => strlen($this->query) > static::LOG_MAX_LENGTH ? substr($this->query, 0, static::LOG_MAX_LENGTH) . ' ' . static::LOG_TRUNCATED : $this->query,
			'error' => $this->error,
			'affected' => $this->affected,
			'numRows' => $this->numRows,
			'took' => $this->took,
		);
		$this->_requestsLog[] = $log;
		$this->_requestsTime += $this->took;
		if (count($this->_requestsLog) > $this->_requestsLogMax) {
			array_shift($this->_requestsLog);
		}

		if (!empty($this->error)) {
			$this->log(get_class($this) . ': ' . $this->error . "\n" . $log['query'], LOG_ERR);
		}
	}

	/**
	 * Get the query log as an array.
	 *
	 * @param boolean $sorted Get the queries sorted by time taken, defaults to false.
	 * @param boolean $clear If True the existing log will cleared.
	 * @return array Array of queries run as an array
	 */
	public function getLog($sorted = false, $clear = true) {
		if ($sorted) {
			$log = sortByKey($this->_requestsLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_requestsLog;
		}
		if ($clear) {
			$this->_requestsLog = array();
		}
		return array('log' => $log, 'count' => $this->_requestsCnt, 'time' => $this->_requestsTime);
	}

	/**
	 * Outputs the contents of the queries log. If in a non-CLI environment the sql_log element
	 * will be rendered and output.  If in a CLI environment, a plain text log is generated.
	 *
	 * @param boolean $sorted Get the queries sorted by time taken, defaults to false.
	 * @return void
	 */
	public function showLog($sorted = false) {
		$log = $this->getLog($sorted, false);
		if (empty($log['log'])) {
			return;
		}
		if (PHP_SAPI != 'cli') {
			$controller = null;
			$View = new View($controller, false);
			$View->set('logs', array($this->configKeyName => $log));
			echo $View->element('sql_dump', array('_forced_from_dbo_' => true));
		} else {
			foreach ($log['log'] as $k => $i) {
				print (($k + 1) . ". {$i['query']}\n");
			}
		}
	}

	/**
	 * Just-In-Time callback for any last-minute request modifications
	 *
	 * @param array $request
	 * @param string $request_method Create, update, read or delete
	 * @return array $request
	 */
	public function beforeRequest($request, $request_method) {
		return $request;
	}

	/**
	 * After request callback
	 * Filter data by fields, emulate limit, offset, order etc.
	 * Override this method for your DataSource.
	 *
	 * @param Model $model
	 * @param array $result
	 * @param string $request_method Create, update, read or delete
	 * @return array
	 */
	public function afterRequest(Model $model, array $result, $request_method) {
		if ($request_method === static::METHOD_READ) {

			if ($this->numRows === null) {
				$this->numRows = count($result);
			}
			//fields emulation
			if ($this->_emulateFunctions($model, $result)) {
				return $result;
			}

			$this->_currentEndpoint->processFields($model, $result);

			//order emulation
			$this->_emulateOrder($model, $result);
			$this->_emulateFields($model, $result);
			//emulate limit and offset
			$this->_emulateLimit($model, $result);

			//final structure
			$this->_formatResult($model, $result);
		}
		return $result;
	}

	/**
	 * Returns an calculation, i.e. COUNT
	 *
	 * @param Model $model
	 * @param string $func Lowercase name of function, i.e. 'count'
	 * @param array $params Function parameters (any values must be quoted manually)
	 * @return string An calculation function
	 */
	public function calculate(Model $model, $func, $params = array()) {
		switch (strtolower($func)) {
			case 'count':
				return static::FUNCTION_COUNT;
			default:
				throw new NotImplementedException("Can't make calculation of function '$func'!");
		}
	}

	/**
	 * Uses standard find conditions.
	 *
	 * @param string $model The model being read.
	 * @param string $query_data An array of query data used to find the data you want
	 * @return mixed
	 * @access public
	 */
	public function read(Model $model, $query_data = array(), $recursive = null) {
		$this->_queryData = $query_data;

		$model->request = array('method' => static::HTTP_METHOD_READ);

		$this->_buildRequest(HttpSource::METHOD_READ, $model, $query_data, null, null, null, $recursive);

		$request = $model->request;
		if ($model->cacheQueries) {
			$result = $this->getQueryCache($request);
			if ($result !== false) {
				return $result;
			}
		}

		$result = $this->request($model, null, HttpSource::METHOD_READ);

		if ($model->cacheQueries && $result !== false) {
			$this->_writeQueryCache($request, $result);
		}

		return $result;
	}

	/**
	 * Sets method = POST in request if not already set
	 *
	 * @param AppModel $model
	 * @param array $fields Unused
	 * @param array $values Unused
	 */
	public function create(Model $model, $fields = null, $values = null) {
		$model->request = array('method' => static::HTTP_METHOD_CREATE);

		$this->_buildRequest(HttpSource::METHOD_CREATE, $model, array(), $fields, $values);

		return (bool) $this->request($model, null, HttpSource::METHOD_CREATE);
	}

	/**
	 * Sets method = PUT in request if not already set
	 *
	 * @param AppModel $model
	 * @param array $fields Unused
	 * @param array $values Unused
	 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		$model->request = array('method' => static::HTTP_METHOD_UPDATE);

		$this->_buildRequest(HttpSource::METHOD_UPDATE, $model, array(), $fields, $values, $conditions);

		return (bool) $this->request($model, null, HttpSource::METHOD_UPDATE);
	}

	/**
	 * Sets method = DELETE in request if not already set
	 *
	 * @param Model $model
	 * @param mixed $id
	 */
	public function delete(Model $model, $conditions = null) {
		$model->request = array('method' => static::HTTP_METHOD_DELETE);


		$this->_buildRequest(HttpSource::METHOD_DELETE, $model, array(), null, null, $conditions);
		return (bool) $this->request($model, null, HttpSource::METHOD_DELETE);
	}

	/**
	 * Returns the result for a query if it is already cached
	 *
	 * @param array $request query
	 *
	 * @return mixed results for query if it is cached, false otherwise
	 */
	public function getQueryCache(array $request) {
		$key = serialize($request);
		$cache_name = $this->_currentEndpoint->cacheName();
		if (isset($this->_queryCache[$key])) {
			return $this->_queryCache[$key];
		} else if ($cache_name) {
			return Cache::read(md5($key), $cache_name);
		}

		return false;
	}

	/**
	 * Queries associations. Used to fetch results on recursive models.
	 *
	 * @param Model $model Primary Model object
	 * @param Model $linkModel Linked model that
	 * @param string $type Association type, one of the model association types ie. hasMany
	 * @param string $association
	 * @param array $assocData
	 * @param array $queryData
	 * @param boolean $external Whether or not the association query is on an external datasource.
	 * @param array $resultSet Existing results
	 * @param integer $recursive Number of levels of association
	 * @param array $stack
	 * @return mixed
	 * @throws CakeException when results cannot be created.
	 */
	public function queryAssociation(Model $model, &$linkModel, $type, $association, $assocData, &$queryData, $external, &$resultSet, $recursive, $stack) {
		$assocQuery = $this->_scrubQueryData($assocData);
		$query = $this->_scrubQueryData($queryData);

		foreach ($resultSet as &$result) {
			switch($type) {
				case 'hasAndBelongsToMany': {
					$JoinModel = ClassRegistry::init($assocData['with']);
					$assocQuery['fields'] = array($assocData['associationForeignKey']);
					$assocQuery['conditions'][$assocData['foreignKey']] = $result[$model->alias][$model->primaryKey];
					$joinData = $JoinModel->find('all', array_filter($assocQuery));
					$query['conditions'][$linkModel->primaryKey] = Hash::extract($joinData, "{n}.{$JoinModel->alias}.{$assocData['associationForeignKey']}");
					$assocResults = $linkModel->find('all', array_filter($query));

					$result[$association] = array();
					foreach ($assocResults as $assocResult) {
						$result[$association][] = $assocResult[$linkModel->alias];
					}

					break;
				}

				default: throw new NotImplementedException("Sorry, but type $type is not implemented yet, check out https://github.com/imsamurai/cakephp-httpsource-datasource/issues/18");
			}
		}
	}

	/**
	 * Private helper method to remove query metadata in given data array.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function _scrubQueryData($data) {
		static $base = null;
		if ($base === null) {
			$base = array_fill_keys(array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group'), array());
			$base['callbacks'] = null;
		}
		return (array) $data + $base;
	}

	/**
	 * Format result to match Cake conventions
	 *
	 * @param Model $Model
	 * @param array $result
	 */
	protected function _formatResult(Model $Model, array &$result) {
		foreach ($result as &$data) {
			$data = array($Model->alias => $data);
		}
	}

	/**
	 * Apply function if specified in fields
	 *
	 * @param Model $Model
	 * @param array $result
	 * @return boolean True if function applied
	 */
	protected function _emulateFunctions(Model $Model, array &$result) {
		if (!empty($this->_queryData['fields'])) {
			if ($this->_queryData['fields'] === static::FUNCTION_COUNT) {
				$result = array(array('count' => count($result)));
				$this->_formatResult($Model, $result);
				return true;
			}
		}
		return false;
	}

	/**
	 * Sort result
	 *
	 * @param Model $Model
	 * @param array $result
	 */
	protected function _emulateOrder(Model $Model, array &$result) {
		if (!empty($this->_queryData['order'][0])) {
			$result = ArraySort::multisort($result, $this->_queryData['order'][0]);
		}
	}

	/**
	 * Remove not specified fields from result
	 *
	 * @param Model $Model
	 * @param array $result
	 */
	protected function _emulateFields(Model $Model, array &$result) {
		if (!empty($this->_queryData['fields'])) {
			//remove model name from each field
			$model_name = $Model->name;
			$fields = array_map(function($field) use ($model_name) {
				return str_replace("$model_name.", '', $field);
			}, (array) $this->_queryData['fields']);

			$fields_keys = array_flip($fields);

			foreach ($result as &$data) {
				$data = array_intersect_key($data, $fields_keys);
			}
			unset($data);
		}
	}

	/**
	 * Slice result
	 *
	 * @param Model $Model
	 * @param array $result
	 */
	protected function _emulateLimit(Model $Model, array &$result) {
		if (!empty($this->_queryData['limit'])) {
			if (!empty($this->_queryData['offset'])) {
				$offset = $this->_queryData['offset'];
			} else {
				$offset = 0;
			}
			$result = array_slice($result, $offset, $this->_queryData['limit']);
		}
	}

	/**
	 * Single request
	 *
	 * @param array $request
	 * @param string $request_method
	 * @param Model $model
	 * @return array|bool
	 */
	protected function _singleRequest(array $request, $request_method, Model $model = null) {
		if (empty($request['uri']['host'])) {
			$request['uri']['host'] = (string) Hash::get($this->config, 'host');
		}

		if (empty($request['uri']['port'])) {
			$request['uri']['port'] = (int) Hash::get($this->config, 'port');
		}

		if (empty($request['uri']['path'])) {
			$request['uri']['path'] = (string) Hash::get($this->config, 'path');
		}

		if (empty($request['uri']['scheme']) && Hash::get($this->config, 'scheme')) {
			$request['uri']['scheme'] = (string) Hash::get($this->config, 'scheme');
		}

		$this->swapTokens($request);
		$request = $this->beforeRequest($request, $request_method);

		$response = $this->_Connection->request($request);
		$this->error = $this->_Connection->getError();
		$this->took = $this->_Connection->getTook();
		$this->query = $this->_Connection->getQuery();

		if ($model) {
			if (!$response || $this->error) {
				$model->onError();
			}
			if ($response) {
				$response = $this->_extractResult($model, $response, $request_method);
			}
			$model->response = $response;
			$model->request = $request;
		}

		$this->numRows = is_array($response) ? count($response) : 0;

		if (!empty($this->error)) {
			$this->log(get_class() . ": " . $this->error . " Request: " . $this->query, LOG_ERR);
		}

		// Log the request in the query log
		//if ($this->fullDebug) {
		$this->logRequest();
		//}
		return $response;
	}

	/**
	 * Build request depends on parameters and config and store it in $model->request
	 *
	 * @param string $method create/read/update/delete
	 * @param Model $model The model being read.
	 * @param array $query_data Query data for read
	 * @param array $fields Fields for save/create
	 * @param array $values Fields values for update/create
	 * @param type $conditions Conditions for update
	 * @param int $recursive Number of levels of association. NOT USED YET
	 * @throws HttpSourceException
	 */
	protected function _buildRequest($method, Model $model, array $query_data = array(), array $fields = null, array $values = null, array $conditions = null, $recursive = null) {
		$query_fields = Hash::get($query_data, 'fields');
		$table = (is_string($query_fields) && empty($model->useTable)) ? $query_fields : $model->useTable;

		if (empty($table)) {
			throw new HttpSourceException('Empty table name!');
		}

		if (!empty($conditions)) {
			$query_data['conditions'] = (array) $conditions;
		} else if (!empty($fields) && !empty($values)) {
			$query_data['conditions'] = array_combine($fields, $values);
		} else if (empty($query_data['conditions'])) {
			$query_data['conditions'] = array();
		}

		$conditions = array();
		foreach (array_keys($query_data['conditions']) as $_condition) {
			$conditions[] = str_replace($model->alias.'.', '', $_condition);
		}

		$this->_currentEndpoint = $this->Config->findEndpoint($method, $table, $conditions);
		$this->_currentEndpoint->buildRequest($model, $query_data);
	}

	/**
	 * Writes a new key for the in memory query cache and cache specified by current endpoint
	 *
	 * @param array $request Http request
	 * @param mixed $data result of $request query
	 */
	protected function _writeQueryCache(array $request, $data) {
		$key = serialize($request);
		$this->_queryCache[$key] = $data;
		$cache_name = $this->_currentEndpoint->cacheName();
		if ($cache_name) {
			Cache::write(md5($key), $data, $cache_name);
		}
	}

	/**
	 * Extract data from decoded response
	 *
	 * @param Model $model
	 * @param array $result
	 * @param string $request_method
	 * @param bool $force
	 * @return array
	 */
	protected function _extractResult(Model $model, array $result, $request_method, $force = false) {
		if ($force || $request_method === static::METHOD_READ) {
			$this->_currentEndpoint->processResult($model, $result);
		}
		return $result;
	}

	/**
	 * Split request into subequests
	 *
	 * @param array $request
	 * @return array
	 */
	protected function _splitRequest(array $request) {
		$splitter = $this->_currentEndpoint->requestSplitter();
		return $splitter($request);
	}

	/**
	 * Join responses into single response
	 *
	 * @param array $responses
	 * @return array
	 */
	protected function _joinResponses(array $responses) {
		$joiner = $this->_currentEndpoint->responseJoiner();
		return $joiner($responses);
	}

}
