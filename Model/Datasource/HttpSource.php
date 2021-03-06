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

/**
 * Abstract data source for network interaction
 * 
 * @package HttpSource
 * @subpackage Model.Datasource
 */
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
	const METHOD_CHECK = 'check';

	/**
	 * Http methods constants
	 */
	const HTTP_METHOD_READ = 'GET';
	const HTTP_METHOD_CREATE = 'PUT';
	const HTTP_METHOD_UPDATE = 'POST';
	const HTTP_METHOD_DELETE = 'DELETE';
	const HTTP_METHOD_CHECK = 'HEAD';

	/**
	 * Maximum log length
	 */
	const LOG_MAX_LENGTH = 1000000;

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
	 * Params for commit transaction
	 *
	 * @var array
	 */
	protected $_transactionParams = array();
	
	/**
	 * Transactions
	 *
	 * @var array
	 */
	protected $_transactions = array();

	/**
	 * List of constants METHOD_*
	 * 
	 * @return array
	 */
	public static function getMethods() {
		$types = array(
			static::METHOD_READ,
			static::METHOD_CREATE,
			static::METHOD_UPDATE,
			static::METHOD_DELETE,
			static::METHOD_CHECK
		);

		return array_combine($types, $types);
	}

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param HttpSourceConnection $Connection
	 * @throws HttpSourceException
	 * @throws NotImplementedException
	 */
	public function __construct($config = array(), HttpSourceConnection $Connection = null) {
		parent::__construct($config);
		if (empty($config['datasource'])) {
			throw new HttpSourceException('Datasource config not found!');
		}
		// Store the API configuration map
		list($plugin, $name) = pluginSplit($config['datasource']);

		if (!$map = Configure::read($plugin)) {
			$this->_loadConfig($plugin);
			$map = Configure::read($plugin);
		}

		if (empty($map)) {
			throw new HttpSourceException('Configuration not found!');
		}

		//must be after map loading
		if ((int)Configure::read($plugin . '.config_version') === 2) {
			$this->_setConfig(Configure::read($plugin . '.config'));
		} else {
			throw new NotImplementedException('Configs with config_version != 2 are not implemented yet!');
		}

		$this->_Connection = $Connection ? $Connection : new HttpSourceConnection($this->config);

		$this->fullDebug = Configure::read('debug') > 1;
	}

	/**
	 * Returns config
	 * 
	 * @return HttpSourceConfig
	 */
	public function getConfig() {
		return $this->Config;
	}

	/**
	 * Returns connection
	 * 
	 * @return HttpSourceConnection
	 */
	public function getConnection() {
		return $this->_Connection;
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
	 * Add decoder for given $contentType
	 *
	 * @param string|array $contentType Content type
	 * @param callable $callback Function used for decoding
	 * @param bool $replace Replace decoder if already set or not. Default false
	 */
	public function setDecoder($contentType, callable $callback, $replace = false) {
		$this->_Connection->setDecoder($contentType, $callback, $replace);
	}

	/**
	 * Get decoder for given $contentType
	 *
	 * @param string $contentType Content type
	 */
	public function getDecoder($contentType) {
		return $this->_Connection->getDecoder($contentType);
	}

	/**
	 * Http query abstraction
	 *
	 * @param mixed $request Array of request or string uri
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
	 * Execute single or multiple requests
	 * 
	 * @param array $request
	 * @return array
	 */
	public function execute($request) {
		if (is_numeric(implode('', array_keys($request)))) {
			$result = array();
			foreach ($request as $query) {
				$result[] = $this->query($query);
			}
			return $result;
		}
		return $this->query($request);
	}

	/**
	 * Sends HttpSocket requests. Builds your uri and formats the response too.
	 *
	 * @param Model $model Model object
	 * @param mixed $requestData Array of request or string uri
	 * @param string $requestMethod read, create, update, delete
	 *
	 * @return array|false $response
	 * @throws HttpSourceException
	 */
	public function request(Model $model = null, $requestData = null, $requestMethod = HttpSource::METHOD_READ) {
		if ($model !== null) {
			$request = $model->request;
		} elseif (is_array($requestData)) {
			$request = $requestData;
		} elseif (is_string($requestData)) {
			$request = array('uri' => $requestData);
		} else {
			throw new HttpSourceException('Can\t find data for request!');
		}
		$responses = array();
		foreach ($this->_splitRequest($request) as $subRequest) {
			$responses[] = $this->_singleRequest($subRequest, $requestMethod, $model);
		}

		if ($model) {
			$response = $this->afterRequest($model, $this->_joinResponses($responses), $requestMethod);
		} else {
			$response = $this->_joinResponses($responses);
		}

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

	/**
	 * {@inheritdoc}
	 * 
	 * @param Model $model
	 * @return array
	 */
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
		isset($request['uri']) ? $uri = &$request['uri'] : null;
		if (empty($uri['query']) || !is_array($uri['query']) || empty($uri['path'])) {
			return;
		}

		foreach ($uri['query'] as $token => $value) {
			if (is_array($value)) {
				continue;
			}
			$count = 0;
			$uri['path'] = preg_replace('/\:' . preg_quote($token, '/') . '\b/', $value, $uri['path'], 1, $count);
			if ($count > 0) {
				unset($uri['query'][$token]);
			}
		}
	}

	/**
	 * Log given query.
	 *
	 * @return void
	 */
	public function logRequest() {
		$this->_requestsCnt++;

		$log = $this->getRequestLog();
		$this->_requestsLog[] = $log;

		$this->_requestsTime += $log['took'];
		if (count($this->_requestsLog) > $this->_requestsLogMax) {
			array_shift($this->_requestsLog);
		}

		if ($log['error']) {
			$this->log(get_class($this) . ': ' . $log['error'] . "\n" . $log['query'], LOG_ERR);
		} else {
			$query = str_replace(array("\n", "\r"), array('\n', '\r'), $log['query']);
			$this->log("$query | took: {$log['took']} | affected: {$log['affected']} | numRows: {$log['numRows']}", get_class($this) . 'Query');
		}
	}

	/**
	 * Returns record for log
	 * 
	 * @return array
	 */
	public function getRequestLog() {
		return array(
			'query' => mb_strlen($this->query) > static::LOG_MAX_LENGTH ? mb_substr($this->query, 0, static::LOG_MAX_LENGTH) . ' ' . static::LOG_TRUNCATED : $this->query,
			'error' => $this->error,
			'affected' => $this->affected,
			'numRows' => $this->numRows,
			'took' => $this->took
		);
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
	 * @param boolean $html True for html output, null for auto
	 * @return void
	 */
	public function showLog($sorted = false, $html = null) {
		$log = $this->getLog($sorted, false);
		if (empty($log['log'])) {
			return;
		}
		if (is_null($html)) {
			$html = (PHP_SAPI != 'cli');
		}
		if ($html) {
			App::uses('View', 'View');
			$View = new View();
			$View->set(array(
				'logs' => array(get_class($this) => $log),
				'sqlLogs' => array(get_class($this) => $log)
			));
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
	 * @param string $requestMethod Create, update, read or delete
	 * @return array $request
	 */
	public function beforeRequest($request, $requestMethod) {
		return $request;
	}

	/**
	 * After request callback
	 * Filter data by fields, emulate limit, offset, order etc.
	 * Override this method for your DataSource.
	 *
	 * @param Model $model
	 * @param array $result
	 * @param string $requestMethod Create, update, read or delete
	 * @return array
	 */
	public function afterRequest(Model $model, array $result, $requestMethod) {
		if ($requestMethod === static::METHOD_READ) {

			if ($this->numRows === null) {
				$this->numRows = count($result);
			}
			//fields emulation
			if ($this->_emulateFunctions($model, $result)) {
				return $result;
			}

			$this->_getCurrentEndpoint()->processFields($model, $result);

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
	 * @throws NotImplementedException
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
	 * {inheritdoc}
	 *
	 * @param string $model The model being read.
	 * @param string $queryData An array of query data used to find the data you want
	 * @param integer $recursive
	 * @return mixed
	 * @access public
	 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		$this->_queryData = $queryData;

		$model->request = array('method' => static::HTTP_METHOD_READ);

		$this->_buildRequest(static::METHOD_READ, $model, $queryData, null, null, null, $recursive);

		$request = $model->request;
		if ($model->cacheQueries) {
			$result = $this->getQueryCache($request);
			if ($result !== false) {
				return $result;
			}
		}

		$result = $this->request($model, null, static::METHOD_READ);

		if ($model->cacheQueries && $result !== false) {
			$this->_writeQueryCache($request, $result);
		}

		return $result;
	}

	/**
	 * {inheritdoc}
	 *
	 * @param Model $model
	 * @param array $fields Unused
	 * @param array $values Unused
	 */
	public function create(Model $model, $fields = null, $values = null) {
		$model->request = array('method' => static::HTTP_METHOD_CREATE);

		$this->_buildRequest(static::METHOD_CREATE, $model, array(), $fields, $values);
		if ($this->_addTransaction(static::METHOD_CREATE, $model->request)) {
			return true;
		}

		return (bool)$this->request($model, null, static::METHOD_CREATE);
	}

	/**
	 * {inheritdoc}
	 *
	 * @param Model $model
	 * @param array $fields
	 * @param array $values
	 * @param array $conditions
	 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		$model->request = array('method' => static::HTTP_METHOD_UPDATE);

		$this->_buildRequest(static::METHOD_UPDATE, $model, array(), $fields, $values, $conditions);
		if ($this->_addTransaction(static::METHOD_UPDATE, $model->request)) {
			return true;
		}

		return (bool)$this->request($model, null, static::METHOD_UPDATE);
	}

	/**
	 * {inheritdoc}
	 *
	 * @param Model $model
	 * @param array $conditions
	 */
	public function delete(Model $model, $conditions = null) {
		$model->request = array('method' => static::HTTP_METHOD_DELETE);

		$this->_buildRequest(static::METHOD_DELETE, $model, array(), null, null, $conditions);
		if ($this->_addTransaction(static::METHOD_DELETE, $model->request)) {
			return true;
		}
		return (bool)$this->request($model, null, static::METHOD_DELETE);
	}

	/**
	 * Check if record identified by conditions is exists
	 *
	 * @param Model $model
	 * @param array $conditions
	 */
	public function exists(Model $model, $conditions = array()) {
		$model->request = array('method' => static::HTTP_METHOD_CHECK);
		try {
			$this->_buildRequest(static::METHOD_CHECK, $model, array(), null, null, $conditions);
		} catch (Exception $Exception) {
			return true;
		}
		return (bool)$this->request($model, null, static::METHOD_CHECK);
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
		$cacheName = $this->_getCurrentEndpoint()->cacheName();
		if ($cacheName) {
			return Cache::read(md5($key), $cacheName);
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
	 * @throws NotImplementedException
	 */
	public function queryAssociation(Model $model, &$linkModel, $type, $association, $assocData, &$queryData, $external, &$resultSet, $recursive, $stack) {
		$assocQuery = $this->_scrubQueryData($assocData);
		$query = $this->_scrubQueryData($queryData);

		foreach ($resultSet as &$result) {
			switch ($type) {
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

				default:
					throw new NotImplementedException("Sorry, but type $type is not implemented yet, check out https://github.com/imsamurai/cakephp-httpsource-datasource/issues/18");
			}
		}
	}

	/**
	 * Returns requests array for create schema(s)
	 * 
	 * @param CakeSchema $Schema
	 * @param string $tableName
	 * @return array
	 */
	public function createSchema(CakeSchema $Schema, $tableName = null) {
		$options = array(
			'method' => static::METHOD_CREATE,
			'httpMethod' => static::HTTP_METHOD_CREATE
		);
		return $this->_schemaToRequests($Schema, $tableName, $options);
	}

	/**
	 * Returns requests array for drop schema(s)
	 * 
	 * @param CakeSchema $Schema
	 * @param string $tableName
	 * @return array
	 */
	public function dropSchema(CakeSchema $Schema, $tableName = null) {
		$options = array(
			'method' => static::METHOD_DELETE,
			'httpMethod' => static::HTTP_METHOD_DELETE
		);
		return $this->_schemaToRequests($Schema, $tableName, $options);
	}
	
	/**
	 * Set parameters for endpoint wich handle transactions
	 * 
	 * @param string $table
	 * @param array $params
	 * @param string $transactionsField
	 * @param string $method
	 */
	public function setTransactionParams($table, array $params, $transactionsField, $method) {
		$this->_transactionParams = compact('table', 'params', 'transactionsField', 'method');
	}
	
	/**
	 * Return transaction params
	 * 
	 * @return array
	 */
	public function getTransactionParams() {
		return $this->_transactionParams;
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @return bool Returns true if a transaction is not in progress
	 */
	public function begin() {
		if ($this->_transactionStarted) {
			return $this->_transactionStarted;
		}
		$this->_transactions = array();
		return $this->_transactionStarted = (bool)$this->_transactionParams;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool Returns true if a transaction is in progress
	 */
	public function rollback() {
		if (!$this->_transactionStarted) {
			return false;
		}
		$this->_transactionStarted = false;
		$this->_transactions = array();
		$this->_transactionParams = array();
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool Returns true if a transaction is in progress
	 */
	public function commit() {
		if (!$this->_transactionStarted) {
			$this->_transactionParams = array();
			return false;
		}
		$this->_transactionStarted = false;
		if (!$this->_transactions) {
			$this->_transactionParams = array();
			return true;
		}
		
		$queryData = $this->_transactionParams['params'];
		$queryData['method'] = $this->_transactionParams['method'];
		$queryData['fields'] = $this->_transactionParams['table'];
		$queryData['transactionsField'] = $this->_transactionParams['transactionsField'];
		$this->_transactionParams = array();
		
		switch ($queryData['method']) {
			case static::METHOD_CHECK: 
				$httpMethod = static::HTTP_METHOD_CHECK; 
				break;
			case static::METHOD_READ: 
				$httpMethod = static::HTTP_METHOD_READ; 
				break;
			case static::METHOD_CREATE: 
				$httpMethod = static::HTTP_METHOD_CREATE; 
				break;
			case static::METHOD_UPDATE: 
				$httpMethod = static::HTTP_METHOD_UPDATE; 
				break;
			case static::METHOD_DELETE: 
				$httpMethod = static::HTTP_METHOD_DELETE; 
				break;
		}
		
		$queryData['httpMethod'] = $httpMethod;
		$queryData['conditions'][$queryData['transactionsField']] = $this->_transactions;
		$this->_transactions = array();
		$Model = $this->_requestToModel(array('method' => $httpMethod));
		$this->_buildRequest($queryData['method'], $Model, $queryData);
		return (bool)$this->execute($Model->request);
	}
	
	/**
	 * Store request transaction
	 * 
	 * @param string $method
	 * @param array $request
	 * @return bool
	 */
	protected function _addTransaction($method, array $request) {
		if (!$this->_transactionStarted) {
			return false;
		}
		$this->_transactions[] = array($method, $request);
		return true;
	}

	/**
	 * Make requests based on schema
	 * 
	 * @param CakeSchema $Schema
	 * @param string $tableName
	 * @param array $options
	 * @return array
	 */
	protected function _schemaToRequests(CakeSchema $Schema, $tableName, array $options) {
		$out = array();

		foreach ($Schema->tables as $table => $columns) {
			if ($tableName && $tableName != $table) {
				continue;
			}
			$tableParameters = array('conditions' => (array)Hash::get((array)$columns, 'tableParameters'));
			$tableParameters['fields'] = $table;
			$model = $this->_requestToModel(array('method' => $options['httpMethod']));
			$this->_buildRequest($options['method'], $model, $tableParameters);
			$out[] = $model->request;
		}
		return $out;
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
		return (array)$data + $base;
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
		if ($this->_getQueryData('fields') === static::FUNCTION_COUNT) {
			$result = array(array('count' => count($result)));
			$this->_formatResult($Model, $result);
			return true;
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
		$order = $this->_getQueryData('order.0');
		if ($order) {
			$result = ArraySort::multisort($result, $order);
		}
	}

	/**
	 * Remove not specified fields from result
	 *
	 * @param Model $Model
	 * @param array $result
	 */
	protected function _emulateFields(Model $Model, array &$result) {
		$fields = $this->_getQueryData('fields');
		if ($fields) {
			//remove model name from each field
			$modelName = $Model->name;
			$fields = array_map(function($field) use ($modelName) {
				return str_replace("$modelName.", '', $field);
			}, (array)$fields);

			$fieldsKeys = array_flip($fields);

			foreach ($result as &$data) {
				$data = array_intersect_key($data, $fieldsKeys);
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
		$limit = (int)$this->_getQueryData('limit');
		$offset = (int)$this->_getQueryData('offset');
		if ($limit) {
			$result = array_slice($result, $offset, $limit);
		}
	}

	/**
	 * Single request
	 *
	 * @param array $request
	 * @param string $requestMethod
	 * @param Model $model
	 * @return array|bool
	 */
	protected function _singleRequest(array $request, $requestMethod, Model $model = null) {
		if (empty($request['uri']['host']) && !empty($this->config['host'])) {
			$request['uri']['host'] = (string)$this->config['host'];
		}

		if (empty($request['uri']['port']) && !empty($this->config['port'])) {
			$request['uri']['port'] = (int)$this->config['port'];
		}

		if (empty($request['uri']['path']) && !empty($this->config['path'])) {
			$request['uri']['path'] = (string)$this->config['path'];
		}

		if (empty($request['uri']['scheme']) && !empty($this->config['scheme'])) {
			$request['uri']['scheme'] = (string)$this->config['scheme'];
		}

		$this->swapTokens($request);
		$request = $this->beforeRequest($request, $requestMethod);

		$response = $this->_Connection->request($request);
		$this->error = $this->_Connection->getError();
		$this->took = $this->_Connection->getTook();
		$this->query = $this->_Connection->getQuery();
		$this->affected = $this->_Connection->getAffected();

		if ($model) {
			if (!$response || $this->error) {
				$model->onError();
			}
			if ($response) {
				$response = $this->_extractResult($model, $response, $requestMethod);
			}
			$model->response = $response;
			$model->request = $request;
		}

		$this->numRows = ($requestMethod === static::METHOD_CHECK && $response) ? 1 : $this->_Connection->getNumRows($response);
		// Log the request in the query log
		if ($this->fullDebug) {
			$this->logRequest();
		} elseif (!empty($this->error)) {
			$this->log(get_class() . ": " . $this->error . " Request: " . $this->query, LOG_ERR);
		}
		return $response;
	}

	/**
	 * Build request depends on parameters and config and store it in $model->request
	 *
	 * @param string $method create/read/update/delete
	 * @param Model $model The model being read.
	 * @param array $queryData Query data for read
	 * @param array $fields Fields for save/create
	 * @param array $values Fields values for update/create
	 * @param array $conditions Conditions for update
	 * @param int $recursive Number of levels of association. NOT USED YET
	 * @throws HttpSourceException
	 */
	protected function _buildRequest($method, Model $model, array $queryData = array(), array $fields = null, array $values = null, array $conditions = null, $recursive = null) {
		$queryFields = Hash::get($queryData, 'fields');
		$table = (is_string($queryFields) && empty($model->useTable)) ? $queryFields : $model->useTable;

		if (empty($table)) {
			throw new HttpSourceException('Empty table name!');
		}

		if (!empty($conditions)) {
			$queryData['conditions'] = (array)$conditions;
		} elseif (!empty($fields) && !empty($values)) {
			$queryData['conditions'] = array_combine($fields, $values);
		} elseif (empty($queryData['conditions'])) {
			$queryData['conditions'] = array();
		}

		$conditions = array();
		foreach (array_keys($queryData['conditions']) as $_condition) {
			$conditions[] = str_replace($model->alias . '.', '', $_condition);
		}
		
		$queryData['conditions'] = array_combine($conditions, array_values($queryData['conditions']));

		$this->_currentEndpoint = $this->Config->findEndpoint($method, $table, $conditions);
		$this->_currentEndpoint->buildRequest($model, $queryData);
	}

	/**
	 * Writes a new key for the in memory query cache and cache specified by current endpoint
	 *
	 * @param array $request Http request
	 * @param mixed $data result of $request query
	 */
	protected function _writeQueryCache(array $request, $data) {
		$key = serialize($request);
		$cacheName = $this->_getCurrentEndpoint()->cacheName();
		if ($cacheName) {
			Cache::write(md5($key), $data, $cacheName);
		}
	}

	/**
	 * Extract data from decoded response
	 *
	 * @param Model $model
	 * @param array $result
	 * @param string $requestMethod
	 * @param bool $force
	 * @return array
	 */
	protected function _extractResult(Model $model, array $result, $requestMethod, $force = false) {
		if ($force || $requestMethod === static::METHOD_READ) {
			$this->_getCurrentEndpoint()->processResult($model, $result);
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
		$splitter = $this->_getCurrentEndpoint()->requestSplitter();
		return $splitter($request);
	}

	/**
	 * Join responses into single response
	 *
	 * @param array $responses
	 * @return array
	 */
	protected function _joinResponses(array $responses) {
		$joiner = $this->_getCurrentEndpoint()->responseJoiner();
		return $joiner($responses);
	}

	/**
	 * Fill request parameter of the model, if no model specified - create basic model
	 * 
	 * @param array $request
	 * @param Model $Model
	 * @return AppModel
	 */
	protected function _requestToModel(array $request, Model $Model = null) {
		if (is_null($Model)) {
			$Model = ClassRegistry::init(array('class' => 'AppModel', 'table' => false));
			$Model->useTable = false;
		}
		$Model->request = $request;
		return $Model;
	}

	/**
	 * Load config wrapper
	 * 
	 * @param string $plugin
	 */
	protected function _loadConfig($plugin) {
		Configure::load($plugin . '.' . $plugin);
	}

	/**
	 * Set config
	 * 
	 * @param HttpSourceConfig $Config
	 * @throws HttpSourceException
	 */
	protected function _setConfig($Config) {
		if (!($Config instanceof HttpSourceConfig)) {
			throw new HttpSourceException('Unknown config type!');
		}
		$this->Config = $Config;
	}

	/**
	 * Return current endpoint or create new
	 * 
	 * @return HttpSourceEndpoint
	 */
	protected function _getCurrentEndpoint() {
		return $this->_currentEndpoint ? $this->_currentEndpoint : $this->getConfig()
						->getConfigFactory()
						->endpoint()
						->id('__default__');
	}

	/**
	 * Return current query data
	 * 
	 * @param string $path
	 * @return mixed
	 */
	protected function _getQueryData($path = null) {
		return is_null($path) ? $this->_queryData : Hash::get($this->_queryData, $path);
	}

}
