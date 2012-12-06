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

class HttpSourceConfigFactory {

    protected static $_instance = null;

    /**
     *
     * @return HttpSourceConfigFactory
     */
    public static function instance() {
        if (is_null(static::$_instance)) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     *
     * @return HttpSourceConfig
     */
    public function config() {
        return new HttpSourceConfig();
    }

    /**
     *
     * @return HttpSourceCondition
     */
    public function condition() {
        return new HttpSourceCondition();
    }

    /**
     *
     * @return HttpSourceEndpoint
     */
    public function endpoint() {
        return new HttpSourceEndpoint();
    }

    /**
     *
     * @return HttpSourceField
     */
    public function field() {
        return new HttpSourceField();
    }

    /**
     *
     * @return HttpSourceResult
     */
    public function result() {
        return new HttpSourceResult();
    }

    /**
     *
     */
    private function __construct() {}

}