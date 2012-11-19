<?

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 19.11.2012
 * Time: 11:07:20
 *
 */
$this_plugin_dir = dirname(dirname(__FILE__)) . DS;
App::build(array(
    'Plugin' => array($this_plugin_dir . 'Plugin' . DS),
));
CakePlugin::load('ArraySort');
require $this_plugin_dir . 'Lib' . DS . 'Error' . DS . 'exceptions.php';