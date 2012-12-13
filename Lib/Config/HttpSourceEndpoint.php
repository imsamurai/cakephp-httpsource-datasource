<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 22:52:50
 *
 */
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');
App::uses('HttpSourceField', 'HttpSource.Lib/Config');
App::uses('HttpSourceCondition', 'HttpSource.Lib/Config');

/**
 * Http source endpoint
 */
class HttpSourceEndpoint {

    /**
     * Endpoint CRUD method
     *
     * @var string
     */
    protected $_method = HttpSourceEndpoint::METHOD_READ;

    /**
     * Table name
     *
     * @var string
     */
    protected $_table = null;

    /**
     * Endpoint path
     *
     * @var string
     */
    protected $_path = null;

    /**
     * Endpoint fields
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Endpoint conditions
     *
     * @var array
     */
    protected $_conditions = array();

    /**
     * Endpoint result handler
     *
     * @var HttpSourceResult
     */
    protected $_result = null;

    /**
     * Mapped read params (limit, offset, etc)
     *
     * @var array
     */
    protected $_readParams = null;

    /**
     * Endpoint id
     *
     * @var int
     */
    protected $_id = null;

    /**
     * CRUD constants
     */

    const METHOD_READ = 'read';
    const METHOD_CREATE = 'create';
    const METHOD_UPDATE = 'update';
    const METHOD_DELETE = 'delete';

    /**
     * Set endpoint method to 'read'
     *
     * @return HttpSourceEndpoint
     */
    public function methodRead() {
        $this->_method = static::METHOD_READ;
        return $this;
    }

    /**
     * Set endpoint method to 'create'
     *
     * @return HttpSourceEndpoint
     */
    public function methodCreate() {
        $this->_method = static::METHOD_CREATE;
        return $this;
    }

    /**
     * Set endpoint method to 'update'
     *
     * @return HttpSourceEndpoint
     */
    public function methodUpdate() {
        $this->_method = static::METHOD_UPDATE;
        return $this;
    }

    /**
     * Set endpoint method to 'delete'
     *
     * @return HttpSourceEndpoint
     */
    public function methodDelete() {
        $this->_method = static::METHOD_DELETE;
        return $this;
    }

    /**
     * Get endpoint method
     *
     * @return HttpSourceEndpoint
     */
    public function method() {
        return $this->_method;
    }

    /**
     * Set or get endpoint id
     *
     * @param int $id
     * @return HttpSourceEndpoint
     * @return int Current id
     */
    public function id($id = null) {
        if (is_null($id)) {
            return $this->_id;
        }
        $this->_id = $id;
        return $this;
    }

    /**
     * Set or get endpoint table
     *
     * @param string $name
     * @return HttpSourceEndpoint
     * @return string Current table name
     */
    public function table($name = null) {
        if (is_null($name)) {
            return $this->_table;
        }
        $this->_table = $name;
        return $this;
    }

    /**
     * Set or get endpoint path
     *
     * @param string $name
     * @return HttpSourceEndpoint
     * @return string Current path
     */
    public function path($name = null) {
        if (is_null($name)) {
            return is_null($this->_path) ? $this->table() : $this->_path;
        }
        $this->_path = $name;
        return $this;
    }

    /**
     * Set or get endpoint result handler
     *
     * @param HttpSourceResult $Result
     * @return HttpSourceEndpoint
     * @return HttpSourceResult Current result handler
     */
    public function result(HttpSourceResult $Result = null) {
        if (is_null($Result)) {
            return $this->_result;
        }
        $this->_result = $Result;
        return $this;
    }

    /**
     * Get condition by name, if condition not exists
     * create, add and return new
     *
     * @param string $name
     * @return HttpSourceCondition
     */
    public function condition($name) {
        if (!isset($this->_conditions[$name])) {
            $this->_conditions[$name] = HttpSourceConfigFactory::instance()->condition()->name($name);
        }
        return $this->_conditions[$name];
    }

    /**
     * Get field by name, if field not exists
     * create, add and return new
     *
     * @param string $name
     * @return HttpSourceCondition
     */
    public function field($name) {
        if (!isset($this->_fields[$name])) {
            $this->_fields[$name] = HttpSourceConfigFactory::instance()->field()->name($name);
        }
        return $this->_fields[$name];
    }

    /**
     * Set or get endpoint mapped read param
     *
     * @param array $params
     * @return HttpSourceEndpoint
     * @return array
     */
    public function readParams(array $params = null) {
        if (is_null($params)) {
            return $this->_readParams;
        }
        $this->_readParams = $params;
        return $this;
    }

    /**
     * Add new field
     *
     * @param HttpSourceField $Field
     * @return HttpSourceEndpoint
     */
    public function addField(HttpSourceField $Field) {
        $this->_fields[(string) $Field] = $Field;
        return $this;
    }

    /**
     * Add new conditions
     *
     * @param HttpSourceCondition $Condition
     * @return HttpSourceEndpoint
     */
    public function addCondition(HttpSourceCondition $Condition) {
        $this->_conditions[(string) $Condition] = $Condition;
        return $this;
    }

    /**
     * Returns list of required condition names
     *
     * @return array
     */
    public function requiredConditions() {
        $conditions_list = array();
        foreach ($this->_conditions as $Condition) {
            if ($Condition->null() === false) {
                $conditions_list[] = $Condition->name();
            }
        }

        return $conditions_list;
    }

    /**
     * Returns list of optional condition names
     *
     * TODO: change method using requiredConditions()
     *
     * @return array
     */
    public function optionalConditions() {
        $conditions_list = array();
        foreach ($this->_conditions as $Condition) {
            if ($Condition->null() !== false) {
                $conditions_list[] = $Condition->name();
            }
        }

        return $conditions_list;
    }

    /**
     * Returns associative list of default condition names
     * and values
     *
     * @return array
     */
    public function conditionsDefaults() {
        $conditions_list = array();
        foreach ($this->_conditions as $Condition) {
            if (!is_null($Condition->defaults())) {
                $conditions_list[$Condition->name()] = $Condition->defaults();
            }
        }

        return $conditions_list;
    }

    /**
     * Build schema array to describe endpoint
     *
     * @return array
     */
    public function schema() {
        $schema = array();
        foreach ($this->_conditions as $Condition) {

            $col = array(
                'type' => $Condition->type(),
                'null' => $Condition->null()
            );

            if (!is_null($Condition->length())) {
                $col['length'] = $Condition->length();
            }

            if (!is_null($Condition->key())) {
                $col['key'] = $Condition->key();
            }

            if (!is_null($Condition->defaults())) {
                $col['default'] = $Condition->defaults();
            }

            $schema[$Condition->name()] = $col;
        }

        return $schema;
    }

    /**
     * Build request and store it in model->request
     *
     * @param Model $model
     * @param array $query_data
     */
    public function buildRequest(Model $model, array $query_data) {
        $model->request['uri']['path'] = $this->path();


        if ($this->method() === static::METHOD_READ) {
            $this->_processReadParams($model, $query_data);
        }

        $conditions_defaults = $this->conditionsDefaults();

        $this->_processConditions($model, $query_data['conditions']);
        $this->_processConditions($model, $conditions_defaults);

        $usedConditions = array_unique(
                array_merge(
                        array_intersect(
                                array_keys($query_data['conditions']), array_merge($this->requiredConditions(), $this->optionalConditions())
                        ), array_keys($conditions_defaults)
                )
        );

        $query_conditions = $query_data['conditions'] + $conditions_defaults;

        if (in_array($this->method(), array(static::METHOD_READ, static::METHOD_DELETE), true)) {
            $model->request['uri']['query'] = array();
            foreach ($usedConditions as $condition) {
                $model->request['uri']['query'][$condition] = $query_conditions[$condition];
            }
        } else {
            foreach ($usedConditions as $condition) {
                $model->request['body'][$condition] = $query_conditions[$condition];
            }
        }
    }

    /**
     * Process field mappings as set by field->map() method
     *
     * @param Model $model
     * @param array $results
     */
    public function processFields(Model $model, array &$results) {
        foreach ($results as &$result) {
            $result+=array_fill_keys(array_keys($this->_fields), null);
            $result = $this->_process($result, $this->_fields, $model);
        }
        unset($result);
    }

    /**
     * Process results with result handler
     *
     * @param Model $model
     * @param array $result
     */
    public function processResult(Model $model, array &$result) {
        $Result = $this->result();
        if (!is_null($Result)) {
            $callback = $Result->map();
            $result = $callback($result, $model);
        }
    }

    /**
     * Process read parameters. Eq: map limit parameter to some condition
     *
     * @param Model $model
     * @param array $params
     */
    protected function _processReadParams(Model $model, array &$params) {
        $conditions = &$params['conditions'];
        foreach ($this->readParams() as $condition => $value) {
            if (isset($conditions[$condition])) {
                continue;
            }

            if (strpos($value, '+') === false) {
                $value_new = Hash::get($params, $value);
                if ($value_new === null) {
                    continue;
                }
                $conditions[$condition] = Hash::get($params, $value);
                unset($params[$value]);
                continue;
            }


            $values = explode('+', $value);
            $conditions[$condition] = 0;
            foreach ($values as $value_name) {
                $conditions[$condition] += (int) Hash::get($params, $value_name);
                unset($params[$value_name]);
            }
            if ($conditions[$condition] === 0) {
                unset($conditions[$condition]);
            }
        }
    }

    /**
     * Process conditions mappings as set by condition->map() method
     *
     * @param Model $model
     * @param array $conditions
     */
    protected function _processConditions(Model $model, array &$conditions) {
        $conditions = $this->_process($conditions, $this->_conditions, $model);
    }

    protected function _process($items, $storage, Model $model) {
        $data = $items;

        foreach ($items as $item => &$value) {
            if (!isset($storage[$item])) {
                continue;
            }

            list($callback, $to_field_name) = $storage[$item]->map();
            unset($data[$item]);
            $data = Hash::insert($data, $to_field_name, $callback($value, $model));
        }

        return $data;
    }

}