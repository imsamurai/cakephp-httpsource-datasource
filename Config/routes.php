<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 02.09.2014
 * Time: 11:25:37
 * 
 * Routes configuration
 */
Router::connect('/httpsource', array('controller' => 'httpsource', 'action' => 'explain', 'plugin' => 'HttpSource'));
