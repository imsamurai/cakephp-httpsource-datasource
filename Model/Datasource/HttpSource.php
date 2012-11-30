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
     * Contains all possible decoders
     *
     * @var array
     */
    public $decoders = array(
    );

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
     * Chache name for store requests. If null cache not used.
     * Cache works only for read method
     *
     * @var string
     */
    protected $_cacheName = null;

    /**
     * Parameters to map in read request. You can use '+' as part of value
     * and names of other parameters
     * For example to map limit to parameter count:
     * {{{
     *    array(
     *      'count' => 'limit+offset'
     *    );
     * }}}
     *
     * @var array
     */
    protected $_mapReadParams = null;

    /**
     * Fields map. Applied before field filters emulation.
     * Support Hash path notation
     *
     * {{{
     *    array(
     *      'new_field.name' => 'old.field.name',
     *      'new_field.name2' => array(
     *          'field' => 'old.field.name2',
     *          'callback' => function($value) {
     *                          ...
     *                          return $new_value;
     *                          }
     *      )
     *    );
     * }}}
     *
     * @var array
     */
    protected $_mapFields = null;

    /**
     * Conditions map. Applied before request sent.
     * Hash path notation *NOT* supported
     *
     * {{{
     *    array(
     *      'new_param_name' => 'old_param_name',
     *      'new_param_name2' => array(
     *          'condition' => 'old_param_name2',
     *          'callback' => function($value) {
     *                          ...
     *                          return $new_value;
     *                          }
     *      )
     *    );
     * }}}
     *
     * @var array
     */
    protected $_mapConditions = null;

    /**
     * Callback that applied for decoded raw results
     * and extracts actual results
     *
     * @var callcable|string
     */
    protected $_mapResultCallback = null;

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

        if (empty($this->map)) {
            throw new HttpSourceException('Configuration not found!');
        }

        $this->_cacheName = (string) Hash::get($this->map, 'cache_name');
        unset($this->map['cache_name']);


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

        $this->setDecoder(array('application/xml', 'application/atom+xml', 'application/rss+xml'), function(HttpResponse $HttpResponse) {
                    App::uses('Xml', 'Utility');
                    $Xml = new Xml((string) $HttpResponse);
                    $response = $Xml->toArray(false); // Send false to get separate elements
                    $Xml->__destruct();
                    $Xml = null;
                    unset($Xml);

                    return $response;
                }, false);

        $this->setDecoder(array('application/json', 'application/javascript', 'text/javascript'), function(HttpResponse $HttpResponse) {
                    return json_decode((string) $HttpResponse, true);
                }, false);
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
            $request['uri']['host'] = $this->config['host'];
        }

        if (empty($request['uri']['port'])) {
            $request['uri']['port'] = $this->config['port'];
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
            $HttpResponse = $this->Http->request($request);
            $this->error = null;
        } catch (Exception $Exception) {
            $this->error = $Exception->getMessage();
            $HttpResponse = false;
        }

        $timerEnd = microtime(true);

        $this->took = round(($timerEnd - $timerStart) * 1000);

        // Check response status code for success or failure
        if ($HttpResponse && !$HttpResponse->isOk()) {
            if ($model !== null) {
                $model->onError();
            }
            $this->error = $HttpResponse->reasonPhrase;
            $HttpResponse = false;
            $response = false;
        } else if ($HttpResponse && $HttpResponse->isOk()) {
            $response = $this->decode($HttpResponse);
        } else {
            $response = false;
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
     * @param HttpResponse $HttpResponse
     * @return array Decoded response
     * @trows HttpSourceException If content type decoder not found
     */
    public function decode(HttpResponse $HttpResponse) {
        // Extract content type from content type header
        if (preg_match('/^(?P<content_type>[a-z0-9\/\+]+)/i', $HttpResponse->getHeader('Content-Type'), $matches)) {
            $content_type = $matches['content_type'];
        }

        // Decode response according to content type
        return (array) call_user_func($this->getDecoder($content_type), $HttpResponse);
    }

    /**
     * Returns all available sources
     *
     * @param mixed $data
     * @return array Array of sources available in this datasource.
     */
    public function listSources($data = null) {
        return array_keys($this->map[HttpSource::METHOD_READ]) + array_keys($this->map[HttpSource::METHOD_CREATE]) + array_keys($this->map[HttpSource::METHOD_UPDATE]) + array_keys($this->map[HttpSource::METHOD_DELETE]);
    }

    /**
     * Iterates through the tokens (passed or request items) and replaces them into the url
     *
     * @param array $request
     */
    public function swapTokens(array &$request) {
        $query = (array) Hash::get($request, 'uri.query');
        foreach ($query as $token => $value) {
            $count = 0;
            $request['uri']['path'] = preg_replace('/\:' . preg_quote($token, '/') . '\b/', $value, $request['uri']['path'], 1, $count);
            if ($count > 0) {
                unset($request['uri']['query'][$token]);
            }
        }
    }

    /**
     * Tries iterating through the config map of REST commmands to decide which command to use
     *
     * @param string $action
     * @param string $section
     * @param array $fields
     * @return array $path, $required_fields, $optional_fields, $default, $map_fields, $map_conditions, $map_results, $map_read_params
     * @trows HttpSourceException
     */
    public function scanMap($action, $section, $fields = array()) {
        if (!isset($this->map[$action][$section])) {
            throw new HttpSourceException('Section ' . $section . ' not found in Driver Configuration Map - ' . get_class($this));
        }
        $map = $this->map[$action][$section];
        foreach ($map as $path => $conditions) {
            $optional = (array) Hash::get($conditions, 'optional');
            $defaults = (array) Hash::get($conditions, 'defaults');
            $required = (array) Hash::get($conditions, 'required');
            $map_fields = (array) Hash::get($conditions, 'map_fields');
            $map_conditions = (array) Hash::get($conditions, 'map_conditions');
            $map_results = Hash::get($conditions, 'map_results');
            if (!is_callable($map_results) && !is_string($map_results)) {
                $map_results = Hash::get($this->map, 'map_results');
                if (!is_callable($map_results) && !is_string($map_results)) {
                    $map_results = function ($result) {
                                return $result;
                            };
                }
            }
            $map_read_params = (array) Hash::get($conditions, 'map_params');
            if (empty($map_read_params)) {
                $map_read_params = (array) Hash::get($this->map, 'map_read_params');
            }
            //check if all required fields present in $fields or $defaults
            if (count(array_intersect(array_intersect(array_merge($fields, array_keys($defaults)), $required), $required)) === count($required)) {
                return array($path, $required, $optional, $defaults, $map_fields, $map_conditions, $map_results, $map_read_params);
            }
        }
        throw new HttpSourceException('Could not find a match for passed conditions');
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
            $this->_mapResult($result);
            if ($this->numRows === null) {
                $this->numRows = count($result);
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

            $this->_mapFields($model, $result);

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
     * @param string $queryData An array of query data used to find the data you want
     * @return mixed
     * @access public
     */
    public function read(Model $model, $queryData = array(), $recursive = null) {
        $this->_queryData = $queryData;
        if (!isset($model->request)) {
            $model->request = array();
        }
        $model->request = array_merge(array('method' => 'GET'), $model->request);
        if (!isset($queryData['conditions'])) {
            $queryData['conditions'] = array();
        }
        if (empty($model->request['uri']['path']) && !empty($queryData['path'])) {
            $model->request['uri']['path'] = $queryData['path'];
            $model->request['uri']['query'] = $queryData['conditions'];
        } elseif (!empty($this->map[HttpSource::METHOD_READ]) && (is_string($queryData['fields']) || !empty($model->useTable))) {
            list(
                    $path,
                    $required_fields,
                    $optional_fields,
                    $defaults,
                    $this->_mapFields,
                    $this->_mapConditions,
                    $this->_mapResultCallback,
                    $this->_mapReadParams

                    ) = $this->scanMap(HttpSource::METHOD_READ, $model->useTable, array_keys($queryData['conditions']));
            $model->request['uri']['path'] = $path;
            $model->request['uri']['query'] = array();

            $this->_mapReadParams($queryData);
            $this->_mapConditions($queryData['conditions']);

            $usedConditions = array_merge(array_intersect(array_keys($queryData['conditions']), array_merge($required_fields, $optional_fields)), array_keys($defaults));
            $query_conditions = $queryData['conditions'] + $defaults;

            foreach ($usedConditions as $condition) {
                $model->request['uri']['query'][$condition] = $query_conditions[$condition];
            }
        }

        if ($model->cacheQueries) {
            $result = $this->getQueryCache($model->request);
            if ($result !== false) {
                return $result;
            }
        }

        $result = $this->request($model, null, HttpSource::METHOD_READ);

        if ($model->cacheQueries && $result !== false) {
            $this->_writeQueryCache($model->request, $result);
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
        if (!isset($model->request)) {
            $model->request = array();
        }
        if (empty($model->request['body']) && !empty($fields) && !empty($values)) {
            $model->request['body'] = array_combine($fields, $values);
        }
        $model->request = array_merge(array('method' => 'POST'), $model->request);
        $scan = $this->scanMap(HttpSource::METHOD_CREATE, $model->useTable, $fields);
        if ($scan) {
            $model->request['uri']['path'] = $scan[0];
        } else {
            return false;
        }
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
        if (!isset($model->request)) {
            $model->request = array();
        }
        if (empty($model->request['body']) && !empty($fields) && !empty($values)) {
            $model->request['body'] = array_combine($fields, $values);
        }
        $model->request = array_merge(array('method' => 'PUT'), $model->request);
        if (!empty($this->map[HttpSource::METHOD_UPDATE]) && !empty($model->useTable)) {
            $scan = $this->scanMap('write', $model->useTable, $fields);
            if ($scan) {
                $model->request['uri']['path'] = $scan[0];
            } else {
                return false;
            }
        }
        return $this->request($model, null, HttpSource::METHOD_UPDATE);
    }

    /**
     * Sets method = DELETE in request if not already set
     *
     * @param AppModel $model
     * @param mixed $id Unused
     */
    public function delete(Model $model, $id = null) {
        if (!isset($model->request)) {
            $model->request = array();
        }
        $model->request = array_merge(array('method' => 'DELETE'), $model->request);
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
        if (isset($this->_queryCache[$key])) {
            return $this->_queryCache[$key];
        } else if ($this->_cacheName) {
            return Cache::read($key, $this->_cacheName);
        }

        return false;
    }

    /**
     * Applies callback or get value by path from raw decoded result
     * to get actual results
     *
     * @param array $result
     * @throws HttpSourceException
     */
    protected function _mapResult(array &$result) {
        if (is_callable($this->_mapResultCallback)) {
            $result = call_user_func($this->_mapResultCallback, $result);
        } else if (is_string($this->_mapResultCallback)) {
            $result = Hash::get($result, $this->_mapResultCallback);
        } else {
            throw new HttpSourceException('Map Result Callback must be callable or string!');
        }
    }

    /**
     * Map parameters like limit, offset, etc to conditions
     * by config rules
     *
     * @param array $params
     * @return array
     */
    protected function _mapReadParams(array &$params) {
        $conditions = &$params['conditions'];
        foreach ($this->_mapReadParams as $condition => $value) {
            if (!isset($conditions[$condition])) {

                if (strpos($value, '+') === false) {
                    $value_new = Hash::get($this->_queryData, $value);
                    if ($value_new === null) {
                        continue;
                    }
                    $conditions[$condition] = Hash::get($this->_queryData, $value);
                    unset($params[$value]);
                    continue;
                }


                $values = explode('+', $value);
                $conditions[$condition] = 0;
                foreach ($values as $value_name) {
                    $conditions[$condition] += (int) Hash::get($this->_queryData, $value_name);
                    unset($params[$value_name]);
                }
                if ($conditions[$condition] === 0) {
                    unset($conditions[$condition]);
                }
            }
        }
    }

    /**
     * Map conditions to another condition name and apply callback if set
     *
     * @param array $conditions
     */
    protected function _mapConditions(array &$conditions) {
        foreach ($this->_mapConditions as $condition => $value) {
            if (is_array($value)) {
                if (empty($value['condition']) || empty($value['callback'])) {
                    throw new HttpSourceException('Bad condition map value');
                }
                $condition_old = $value['condition'];
                $callback = $value['callback'];
            } else {
                $condition_old = $value;
                $callback = false;
            }

            if (!isset($conditions[$condition_old])) {
                continue;
            }

            if ($callback) {
                $condition_value_new = call_user_func($callback, $conditions[$condition_old]);
            } else {
                $condition_value_new = $conditions[$condition_old];
            }

            unset($conditions[$condition_old]);
            $conditions[$condition] = $condition_value_new;
        }
    }

    /**
     * Move fields from old key to new and apply callback if specified
     *
     * @param Model $model
     * @param array $result
     * @throws HttpSourceException
     */
    protected function _mapFields(Model $model, array &$result) {
        foreach ($this->_mapFields as $field_name_new => $field_old) {
            foreach ($result as &$element) {
                if (is_array($field_old)) {
                    if (empty($field_old['callback']) || empty($field_old['field'])) {
                        throw new HttpSourceException('Bad map_fields format!');
                    }
                    $value = call_user_func($field_old['callback'], Hash::get($element, $field_old['field']), $model);
                    $element = Hash::remove($element, $field_old['field']);
                } else if (is_string($field_old)) {
                    $value = Hash::get($element, $field_old);
                    $element = Hash::remove($element, $field_old);
                } else {
                    throw new HttpSourceException('Bad map_fields format!');
                }

                $element = Hash::insert($element, $field_name_new, $value);
            }
        }
    }

    /**
     * Writes a new key for the in memory query cache and cache specified by $this->_cacheName
     *
     * @param array $request Http request
     * @param mixed $data result of $request query
     */
    protected function _writeQueryCache(array $request, $data) {
        $key = serialize($request);
        $this->_queryCache[$key] = $data;
        if ($this->_cacheName) {
            Cache::write($key, $data, $this->_cacheName);
        }
    }

}