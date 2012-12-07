<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:27:31
 *
 */
App::uses('HttpSourceConfig', 'HttpSource.Lib/Config');
App::uses('HttpSourceCondition', 'HttpSource.Lib/Config');
App::uses('HttpSourceEndpoint', 'HttpSource.Lib/Config');
App::uses('HttpSourceField', 'HttpSource.Lib/Config');
App::uses('HttpSourceResult', 'HttpSource.Lib/Config');

/**
 * Factory to make HttpSource configuration
 */
class HttpSourceConfigFactory {

    protected static $_instance = null;

    /**
     * Singleton instance
     * By setting HttpSource.factory to your class you can use your own,
     * you must use same interface
     *
     * @return HttpSourceConfigFactory
     */
    public static function instance() {
        if (is_null(static::$_instance)) {
            $factory_class = Configure::read('HttpSource.factory');
            if (!empty($factory_class)) {
                static::$_instance = new $factory_class();
            } else {
                static::$_instance = new static();
            }
        }

        return static::$_instance;
    }

    /**
     * Create config
     *
     * @return HttpSourceConfig
     */
    public function config() {
        return new HttpSourceConfig();
    }

    /**
     * Create endpoint
     *
     * @return HttpSourceEndpoint
     */
    public function endpoint() {
        return new HttpSourceEndpoint();
    }

    /**
     * Create endpoint condition
     *
     * @return HttpSourceCondition
     */
    public function condition() {
        return new HttpSourceCondition();
    }

    /**
     * Create endpoint field
     *
     * @return HttpSourceField
     */
    public function field() {
        return new HttpSourceField();
    }

    /**
     * Create endpoint result
     *
     * @return HttpSourceResult
     */
    public function result() {
        return new HttpSourceResult();
    }

    /**
     * Load config by source name
     *
     * @param string $source_name
     * @return HttpSourceConfig
     */
    public function load($source_name) {
        return Configure::read($source_name.'.config');
    }

    /**
     * For single object
     */
    private function __construct() {

    }

}