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
     * Instance of CakePHP core HttpSocket class
     *
     * @var HttpSocket
     */
    public $Http = null;

    /**
     * Time the last query took
     *
     * @var integer
     */
    public $took = null;

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
     * Contains all possible decoders
     *
     * @var array
     */
    public $decoders = array();

    /**
     * Columns info by type.
     * Used in Model::save, Model::create methods
     *
     * @var array
     */
    public $columns = array(null => array());

    /**
     * Credentials for request, for ex: login, password, token, etc
     *
     * @var array
     */
    public $credentials = array();

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
     * @param HttpSocket $Http
     * @trows HttpSourceException
     */
    public function __construct($config = array(), HttpSocket $Http = null) {
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

        // Store the HttpSocket reference
        if (!$Http) {
            if (!empty($this->map['oauth']['version'])) {
                if ($this->map['oauth']['version'][0] == 2) {
                    $config['method'] = 'OAuthV2';
                } else {
                    $config['method'] = 'OAuth';
                }

                App::import('Vendor', 'HttpSocketOauth/HttpSocketOauth');
                $Http = new HttpSocketOauth($this->config);
            } else {
                App::uses('HttpSocket', 'Network/Http');
                $Http = new HttpSocket($this->config);
            }
        }
        $this->Http = $Http;

        $this->fullDebug = Configure::read('debug') > 1;

        $this->setDecoder(array('application/xml', 'application/atom+xml', 'application/rss+xml'), function(HttpSocketResponse $HttpSocketResponse) {
                    App::uses('Xml', 'Utility');
                    $Xml = Xml::build((string) $HttpSocketResponse);
                    $response = Xml::toArray($Xml);

                    return $response;
                }, false);

        $this->setDecoder(array('application/json', 'application/javascript', 'text/javascript'), function(HttpSocketResponse $HttpSocketResponse) {
                    return json_decode((string) $HttpSocketResponse, true);
                }, false);
    }

    /**
     * Sets credentials data
     *
     * @param array $credentials
     */
    public function setCredentials(array $credentials = array()) {
        $this->credentials = $credentials;
    }

    /**
     * Gets credentials data
     *
     * @return array
     */
    public function getCredentials() {
        return $this->credentials;
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
        $this->error = null;
        $this->affected = null;
        $this->numRows = null;
        $this->took = null;

        if ($model !== null) {
            $request = $model->request;
        } elseif (is_array($request_data)) {
            $request = $request_data;
        } elseif (is_string($request_data)) {
            $request = array('uri' => $request_data);
        }

        if (isset($this->config['method']) && $this->config['method'] == 'OAuth') {
            $request = $this->addOauth($request);
        } elseif (isset($this->config['method']) && $this->config['method'] == 'OAuthV2') {
            $request = $this->addOauthV2($request);
        }

        if (empty($request['uri']['host'])) {
            $request['uri']['host'] = (string)Hash::get($this->config, 'host');
        }

        if (empty($request['uri']['port'])) {
            $request['uri']['port'] = (int)Hash::get($this->config, 'port');
        }

        if (empty($request['uri']['path'])) {
            $request['uri']['path'] = (string)Hash::get($this->config, 'path');;
        }

        if (empty($request['uri']['scheme']) && !empty($this->map['oauth']['scheme'])) {
            $request['uri']['scheme'] = $this->map['oauth']['scheme'];
        }

        // Remove unwanted elements from request array
        $request = array_intersect_key($request, $this->Http->request);


        $this->swapTokens($request);


        $request = $this->beforeRequest($request, $request_method);

        $timerStart = microtime(true);

        try {
            $HttpSocketResponse = $this->Http->request($request);
            $this->error = null;
        } catch (Exception $Exception) {
            $this->error = $Exception->getMessage();
            $HttpSocketResponse = false;
        }

        $timerEnd = microtime(true);

        $this->took = round(($timerEnd - $timerStart) * 1000);

        // Check response status code for success or failure
        if ($HttpSocketResponse && !$HttpSocketResponse->isOk()) {
            if ($model !== null) {
                $model->onError();
            }
            $this->error = $HttpSocketResponse->reasonPhrase;
            $HttpSocketResponse = false;
            $response = false;
        } else if ($HttpSocketResponse && $HttpSocketResponse->isOk()) {
			try {
				$response = $this->decode($HttpSocketResponse);
			} catch (Exception $Exception) {
				$this->error = $Exception->getMessage();
				$response = false;
			}
        } else {
            $response = false;
        }

		if (!empty($this->error)) {
			$this->log(get_class().": ".$this->error." Request: ".str_replace(array("\n", "\r"), ' ', @$this->Http->request['raw']), LOG_ERR);
		}

        if ($model !== null) {
            if ($response !== false) {
                $response = $this->afterRequest($model, $response, $request_method);
            }
            $model->response = $response;
            $model->request = $request;
        }

        // Log the request in the query log
        if ($this->fullDebug) {
            $this->logRequest();
        }

        return $response;
    }

    /**
     * Supplements a request array with oauth credentials
     *
     * @param array $request
     * @return array $request
     */
    public function addOauth($request) {
        if (!empty($this->config['oauth_token']) && !empty($this->config['oauth_token_secret'])) {
            $request['auth']['method'] = 'OAuth';
            $request['auth']['oauth_consumer_key'] = $this->config['login'];
            $request['auth']['oauth_consumer_secret'] = $this->config['password'];
            if (isset($this->config['oauth_token'])) {
                $request['auth']['oauth_token'] = $this->config['oauth_token'];
            }
            if (isset($this->config['oauth_token_secret'])) {
                $request['auth']['oauth_token_secret'] = $this->config['oauth_token_secret'];
            }
        }
        return $request;
    }

    /**
     * Supplements a request array with oauth credentials
     *
     * @param array $request
     * @return array $request
     */
    public function addOauthV2($request) {
        if (!empty($this->config['access_token'])) {
            $request['auth']['method'] = 'OAuth';
            $request['auth']['oauth_version'] = '2.0';
            $request['auth']['client_id'] = $this->config['login'];
            $request['auth']['client_secret'] = $this->config['password'];
            if (isset($this->config['access_token'])) {
                $request['auth']['access_token'] = $this->config['access_token'];
            }
        }
        return $request;
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
            if (!isset($this->decoders[$type]) || $replace) {
                $this->decoders[$type] = $callback;
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
        if (empty($this->decoders[$content_type])) {
            if ($this->fullDebug) {
                $this->logRequest();
            }
            throw new HttpSourceException("Can't decode unknown format: '$content_type'");
        }

        return $this->decoders[$content_type];
    }

    /**
     * Decodes the response based on the content type
     *
     * @param HttpSocketResponse $HttpSocketResponse
     * @return array Decoded response
     * @trows HttpSourceException If content type decoder not found
     */
    public function decode(HttpSocketResponse $HttpSocketResponse) {
        // Extract content type from content type header
        if (preg_match('/^(?P<content_type>[a-z0-9\/\+]+)/i', $HttpSocketResponse->getHeader('Content-Type'), $matches)) {
            $content_type = $matches['content_type'];
        }

        // Decode response according to content type
        return (array) call_user_func($this->getDecoder($content_type), $HttpSocketResponse);
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
     * @param string $sql SQL statement
     * @param array $params Values binded to the query (prepared statements)
     * @return void
     */
    public function logRequest() {
        $this->_requestsCnt++;
        $log = array(
            'query' => $this->Http->request['raw'],
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
        if ($request_method === HttpSource::METHOD_READ) {


            $this->_currentEndpoint->processResult($model, $result);


            if ($this->numRows === null) {
                $this->numRows = count($result);
            }
            //fields emulation
            if (!empty($this->_queryData['fields'])) {
                if ($this->_queryData['fields'] === static::FUNCTION_COUNT) {
                    return array(
                        array($model->name => array(
                                'count' => count($result)
                            )
                        )
                    );
                }
            }

            $this->_currentEndpoint->processFields($model, $result);

            //order emulation
            if (!empty($this->_queryData['order'][0])) {
                App::uses('ArraySort', 'ArraySort.Utility');
                $result = ArraySort::multisort($result, $this->_queryData['order'][0]);
            }

            if (!empty($this->_queryData['fields'])) {
                //remove model name from each field
                $model_name = $model->name;
                $fields = array_map(function($field) use ($model_name) {
                            return str_replace("$model_name.", '', $field);
                        }, (array) $this->_queryData['fields']);

                $fields_keys = array_flip($fields);

                foreach ($result as &$data) {
                    $data = array_intersect_key($data, $fields_keys);
                }
                unset($data);
            }

            //emulate limit and offset
            if (!empty($this->_queryData['limit'])) {
                if (!empty($this->_queryData['offset'])) {
                    $offset = $this->_queryData['offset'];
                } else {
                    $offset = 0;
                }
                $result = array_slice($result, $offset, $this->_queryData['limit']);
            }

            //final structure
            foreach ($result as &$data) {
                $data = array($model->name => $data);
            }
            unset($data);
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

        return $this->request($model, null, HttpSource::METHOD_CREATE);
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

        return $this->request($model, null, HttpSource::METHOD_UPDATE);
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
        return $this->request($model, null, HttpSource::METHOD_DELETE);
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


        $this->_currentEndpoint = $this->Config->findEndpoint($method, $table, array_keys($query_data['conditions']));
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

}