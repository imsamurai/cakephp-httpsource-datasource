<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 29.10.2013
 * Time: 21:50:00
 */

/**
 * All HttpSource test suite
 * 
 * @package HttpSourceTest
 * @subpackage Test
 */
class AllHttpSourceTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite define the tests for this suite
	 *
	 * @return void
	 */
	public static function suite() {
		$suite = new CakeTestSuite('All Http Source Tests');
		$path = App::pluginPath('HttpSource') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($path);
		return $suite;
	}

}
