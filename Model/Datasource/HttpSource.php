<?php

/**
 * Http DataSource
 *
 * HttpSource is abstract class for all datasources that using http protocol
 *
 * @package default
 * @author Dean Sofer
 * */
App::uses('DataSource', 'Model/Datasource');

abstract class HttpSource extends DataSource {

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
    // TODO: Relocate to a dedicated schema file
    public $_schema = array();

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
     * Time the last query error
     *
     * @var string
     */
    public $error = null;

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
     * @var array Cache of results from executed sql queries.
     */
    protected $_requestCache = array();

    /**
     * Holds a configuration map
     *
     * @var array
     */
    public $map = array();

    /**
     * API options
     * @var array
     */
    public $options = array(
        'format' => 'json',
        'ps' => '&', // param separator
        'kvs' => '=', // key-value separator
    );

    /**
     * Loads HttpSocket class
     *
     * @param array $config
     * @param HttpSocket $Http
     */
    public function __construct($config, $Http = null) {
        if (!isset($this->config['database'])) {
            $this->config['database'] = '';
        }
        // Store the API configuration map
        list($plugin, $name) = pluginSplit($config['datasource']);

        if (!$this->map = Configure::read($plugin)) {
            Configure::load($plugin . '.' . $plugin);
            $this->map = Configure::read($plugin);
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
                $Http = new HttpSocketOauth();
            } else {
                App::uses('HttpSocket', 'Network/Http');
                $Http = new HttpSocket();
            }
        }
        $this->Http = $Http;
        parent::__construct($config);
        $this->fullDebug = Configure::read('debug') > 1;
    }

    public function describe($model) {
        return array();
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
     *
     * @return array|false $response
     */
    public function request(Model $model = null, $request_data = null) {
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
            $request['uri']['host'] = $this->map['hosts']['rest'];
        }

        if (empty($request['uri']['scheme']) && !empty($this->map['oauth']['scheme'])) {
            $request['uri']['scheme'] = $this->map['oauth']['scheme'];
        }

        // Remove unwanted elements from request array
        $request = array_intersect_key($request, $this->Http->request);

        if (!empty($this->tokens)) {
            $request['uri']['path'] = $this->swapTokens($request['uri']['path'], $this->tokens);
        }

        $request = $this->beforeRequest($request);

        $timerStart = microtime(true);

        try {
            $HttpResponse = $this->Http->request($request);
            $this->error = null;
        } catch (Exception $Exception) {
            $this->error = $Exception->getMessage();
            $HttpResponse = false;
        }

        $timerEnd = microtime(true);

        $this->took = $timerEnd - $timerStart;

        // Check response status code for success or failure
        if ($HttpResponse && !$HttpResponse->isOk()) {
            if ($model !== null) {
                $model->onError();
            }
            $this->error = $HttpResponse->reasonPhrase;
            $HttpResponse = false;
        }
        else if ($HttpResponse && $HttpResponse->isOk()) {
            $response = $this->decode($HttpResponse);
        }
        else {
            $response = false;
        }

        if ($model !== null) {
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
     * Decodes the response based on the content type
     *
     * @param string $response
     * @return void
     * @author Dean Sofer
     */
    public function decode(HttpResponse $HttpResponse) {
        // Extract content type from content type header
        if (preg_match('/^(?P<content_type>[a-z0-9\/\+]+);\s*charset=(?P<charset>[a-z0-9\-]+)/i', $HttpResponse->getHeader('Content-Type'), $matches)) {
            $content_type = $matches['content_type'];
            //$charset = $matches['charset'];
        }

        // Decode response according to content type
        switch ($content_type) {
            case 'application/xml':
            case 'application/atom+xml':
            case 'application/rss+xml':
                // If making multiple requests that return xml, I found that using the
                // same Xml object with Xml::load() to load new responses did not work,
                // consequently it is necessary to create a whole new instance of the
                // Xml class. This can use a lot of memory so we have to manually
                // garbage collect the Xml object when we've finished with it, i.e. got
                // it to transform the xml string response into a php array.
                App::uses('Xml', 'Utility');
                $Xml = new Xml((string) $HttpResponse);
                $response = $Xml->toArray(false); // Send false to get separate elements
                $Xml->__destruct();
                $Xml = null;
                unset($Xml);
                break;
            case 'application/json':
            case 'application/javascript':
            case 'text/javascript':
                $response = json_decode((string) $HttpResponse, true);
                break;
            default: throw new HttpSourceException("Can't decode unknown format: '$content_type'");
        }
        return $response;
    }

    /* public function listSources() {
      return array_keys($this->_schema);
      } */

    /**
     * Iterates through the tokens (passed or request items) and replaces them into the url
     *
     * @param string $url
     * @param array $tokens optional
     * @return string $url
     * @author Dean Sofer
     */
    public function swapTokens($url, $tokens = array()) {
        $formattedTokens = array();
        foreach ($tokens as $token => $value) {
            $formattedTokens[':' . $token] = $value;
        }
        $url = strtr($url, $formattedTokens);
        return $url;
    }

    /**
     * Generates a conditions section of the url
     *
     * @param array $params permitted conditions
     * @param array $queryData passed conditions in key => value form
     * @return string
     * @author Dean Sofer
     */
    public function buildQuery($params = array(), $data = array()) {
        $query = array();
        foreach ($params as $param) {
            if (!empty($data[$param]) && $this->options['kvs']) {
                $query[] = $param . $this->options['kvs'] . $data[$param];
            } elseif (!empty($data[$param])) {
                $query[] = $data[$param];
            }
        }
        return implode($this->options['ps'], $query);
    }

    /**
     * Tries iterating through the config map of REST commmands to decide which command to use
     *
     * @param string $action
     * @param string $section
     * @param array $fields
     * @return boolean $found
     * @author Dean Sofer
     */
    public function scanMap($action, $section, $fields = array()) {
        if (!isset($this->map[$action][$section])) {
            throw new HttpSourceException('Section ' . $section . ' not found in Driver Configuration Map - ' . get_class($this));
        }
        $map = $this->map[$action][$section];
        foreach ($map as $path => $conditions) {
            $optional = (isset($conditions['optional'])) ? $conditions['optional'] : array();
            unset($conditions['optional']);
            if (array_intersect($fields, $conditions) == $conditions) {
                return array($path, $conditions, $optional);
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
        $this->_requestsLog[] = array(
            'query' => $this->Http->request['raw'],
            'error' => '',
            'affected' => '',
            'numRows' => '',
            'took' => $this->took,
        );
        $this->_requestsTime += $this->took;
        if (count($this->_requestsLog) > $this->_requestsLogMax) {
            array_shift($this->_requestsLog);
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
     * @return array $request
     * @author Dean Sofer
     */
    public function beforeRequest($request) {
        return $request;
    }

    /**
     * Uses standard find conditions. Use find('all', $params). Since you cannot pull specific fields,
     * we will instead use 'fields' to specify what table to pull from.
     *
     * @param string $model The model being read.
     * @param string $queryData An array of query data used to find the data you want
     * @return mixed
     * @access public
     */
    public function read(Model $model, $queryData = array(), $recursive = null) {
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
        } elseif (!empty($this->map['read']) && (is_string($queryData['fields']) || !empty($queryData['section']))) {
            if (!empty($queryData['section'])) {
                $section = $queryData['section'];
            } else {
                $section = $queryData['fields'];
            }
            $scan = $this->scanMap('read', $section, array_keys($queryData['conditions']));
            $model->request['uri']['path'] = $scan[0];
            $model->request['uri']['query'] = array();
            $usedConditions = array_intersect(array_keys($queryData['conditions']), array_merge($scan[1], $scan[2]));
            foreach ($usedConditions as $condition) {
                $model->request['uri']['query'][$condition] = $queryData['conditions'][$condition];
            }
        }
        return $this->request($model);
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
        $scan = $this->scanMap('create', $model->useTable, $fields);
        if ($scan) {
            $model->request['uri']['path'] = $scan[0];
        } else {
            return false;
        }
        return $this->request($model);
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
        if (!empty($this->map['update']) && in_array('section', $fields)) {
            $scan = $this->scanMap('write', $fields['section'], $fields);
            if ($scan) {
                $model->request['uri']['path'] = $scan[0];
            } else {
                return false;
            }
        }
        return $this->request($model);
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
        return $this->request($model);
    }

    public function calculate($model, $func, $params = array()) {

    }

    public function getColumnType() {
        return true;
    }

}
