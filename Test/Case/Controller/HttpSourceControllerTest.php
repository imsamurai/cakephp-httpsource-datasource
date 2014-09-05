<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 02.09.2014
 * Time: 11:23:36
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */

App::uses('Security', 'Utility');
App::uses('HttpSourceController', 'HttpSource.Controller');

/**
 * HttpSourceControllerTest
 * 
 * @package HttpSourceTest
 * @subpackage Controller
 */
class HttpSourceControllerTest extends ControllerTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test explain() action
	 * 
	 * @param string $method
	 * @param array $logData
	 * @param int $debugLevel
	 * @param string $exception
	 * 
	 * @dataProvider explainProvider
	 */
	public function testExplain($method, array $logData, $debugLevel, $exception) {
		$explaination = 'this is explaination';
		if ($exception) {
			$this->expectException($exception);
		}
		Configure::write('debug', $debugLevel);
		
		$Controller = $this->generate('HttpSource.HttpSource', array(
			'models' => array(
				'HttpSource.HttpSourceModel' => array('explain')
			)
		));

		$Controller->HttpSourceModel
				->expects($this->exactly($exception ? 0 : 1))
				->method('explain')
				->with($logData['ds'], $logData['sql'])
				->willReturn($explaination);

		$view = $this->testAction('/httpsource/', array(
			'method' => $method,
			'data' => array(
				'log' => $logData
			),
			'return' => 'view'
		));
		
		$this->assertStringMatchesFormat('%w<div class="sql-log-query-explain debug-table" style="white-space:pre;">%w' . $explaination . '%w</div>%w', $view);
	}

	/**
	 * Data provider for testExplain
	 * 
	 * @return array
	 */
	public function explainProvider() {
		return array(
			//set #0
			array(
				//method
				'GET',
				//logData
				array(
					'sql' => 'sql',
					'ds' => 'ds',
					'hash' => 'hash'
				),
				//debugLevel
				1,
				//exception
				'BadRequestException'
			),
			//set #1
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => '',
					'ds' => 'ds',
					'hash' => 'hash'
				),
				//debugLevel
				1,
				//exception
				'BadRequestException'
			),
			//set #2
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => 'sql',
					'ds' => '',
					'hash' => 'hash'
				),
				//debugLevel
				1,
				//exception
				'BadRequestException'
			),
			//set #3
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => 'sql',
					'ds' => 'ds',
					'hash' => ''
				),
				//debugLevel
				1,
				//exception
				'BadRequestException'
			),
			//set #4
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => 'sql',
					'ds' => 'ds',
					'hash' => 'hash'
				),
				//debugLevel
				0,
				//exception
				'BadRequestException'
			),
			//set #5
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => 'sql',
					'ds' => 'ds',
					'hash' => Security::hash('sql' . 'ds', 'sha1', true)
				),
				//debugLevel
				0,
				//exception
				'BadRequestException'
			),
			//set #6
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => 'sql',
					'ds' => 'ds',
					'hash' => 'hash'
				),
				//debugLevel
				1,
				//exception
				'BadRequestException'
			),
			//set #7
			array(
				//method
				'POST',
				//logData
				array(
					'sql' => 'sql',
					'ds' => 'ds',
					'hash' => Security::hash('sql' . 'ds', 'sha1', true)
				),
				//debugLevel
				1,
				//exception
				''
			),
		);
	}

}
