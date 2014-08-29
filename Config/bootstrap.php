<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 19.11.2012
 * Time: 11:07:20
 */
App::uses('HttpSourceConfigFactory', 'HttpSource.Lib/Config');

$thisPluginDir = dirname(dirname(__FILE__)) . DS;
App::build(array(
	'Plugin' => array($thisPluginDir . 'Plugin' . DS),
	'Vendor' => array($thisPluginDir . 'Vendor' . DS),
));
CakePlugin::load('ArraySort');
CakePlugin::load('HttpSocketOauth');
require_once $thisPluginDir . 'Lib' . DS . 'Error' . DS . 'exceptions.php';
