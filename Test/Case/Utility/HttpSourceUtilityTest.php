<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 01.09.2014
 * Time: 15:21:37
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('HttpSourceUtility', 'HttpSource.Utility');

/**
 * HttpSourceUtilityTest
 * 
 * @package HttpSourceTest
 * @subpackage Utility
 */
class HttpSourceUtilityTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test parse raw http query
	 * 
	 * @param string $query
	 * @param array|bool $result
	 * @dataProvider parseQueryProvider
	 */
	public function testParseQuery($query, $result) {
		$parsed = HttpSourceUtility::parseQuery($query);
		$this->assertSame($result, $parsed);
	}

	/**
	 * Data provider for testParseQuery
	 * 
	 * @return array
	 */
	public function parseQueryProvider() {
		return array(
			//set #0
			array(
				//query
				"GET /documents/document/_search?size=10 HTTP/1.1" .
				"\n" .
				"Host: localhost:9200" .
				"\n" .
				"Connection: close" .
				"\n" .
				"User-Agent: CakePHP" .
				"\n" .
				"Content-Type: application/x-www-form-urlencoded" .
				"\n" .
				"Content-Length: 162" .
				"\n\n" .
				'{"facets":{"tag":{"terms":{"field":"terms","size":4000}}},"query":{"filtered":{"filter":{"bool":{"must":[{"terms":{"labels":["Criminal"],"execution":"and"}}]}}}}}',
				//result
				array(
					'method' => 'GET',
					'path' => '/documents/document/_search?size=10',
					'httpVer' => 'HTTP/1.1',
					'host' => 'localhost',
					'port' => 9200,
					'headers' => array(
						'Connection: close',
						'User-Agent: CakePHP',
						'Content-Type: application/x-www-form-urlencoded',
						'Content-Length: 162'
					),
					'body' => '{"facets":{"tag":{"terms":{"field":"terms","size":4000}}},"query":{"filtered":{"filter":{"bool":{"must":[{"terms":{"labels":["Criminal"],"execution":"and"}}]}}}}}'
				)
			),
			//set #1
			array(
				//query
				"GET /documents/document/_search?size=10 HTTP/1.1" .
				"\n" .
				"Host: localhost" .
				"\n" .
				"Connection: close" .
				"\n" .
				"User-Agent: CakePHP" .
				"\n" .
				"Content-Type: application/x-www-form-urlencoded" .
				"\n" .
				"Content-Length: 3" .
				"\n\n" .
				'ccc',
				//result
				array(
					'method' => 'GET',
					'path' => '/documents/document/_search?size=10',
					'httpVer' => 'HTTP/1.1',
					'host' => 'localhost',
					'port' => 80,
					'headers' => array(
						'Connection: close',
						'User-Agent: CakePHP',
						'Content-Type: application/x-www-form-urlencoded',
						'Content-Length: 3'
					),
					'body' => 'ccc'
				)
			),
			//set #2
			array(
				//query
				"DELETE / HTTP/1.1" .
				"\n" .
				"Host: localhost" .
				"\n" .
				"Connection: close" .
				"\n" .
				"User-Agent: CakePHP" .
				"\n" .
				"Content-Type: application/x-www-form-urlencoded" .
				"\n" .
				"Content-Length: 0" .
				"\n\n" .
				'',
				//result
				array(
					'method' => 'DELETE',
					'path' => '/',
					'httpVer' => 'HTTP/1.1',
					'host' => 'localhost',
					'port' => 80,
					'headers' => array(
						'Connection: close',
						'User-Agent: CakePHP',
						'Content-Type: application/x-www-form-urlencoded',
						'Content-Length: 0'
					),
					'body' => ''
				)
			),
			//set #3
			array(
				//query
				'the good, the bad, the ugly',
				//result
				false
			),
		);
	}

	/**
	 * Test explain raw http query
	 * 
	 * @param string $query
	 * @param array $explain
	 * @dataProvider explainQueryProvider
	 */
	public function testExplainQuery($query, array $explain) {
		$this->skipIf(`which nc.traditional` === '', 'Netcat must be installed');
		$this->skipUnless(class_exists('Symfony\Component\Process\Process'), 'You must install symfony/process for this test');
		$Process = new Symfony\Component\Process\Process("printf 'HTTP/1.1 200 OK\nContent-length: 6\n\nanswer' | nc.traditional -l -p 12345");
		$Process->start();
		sleep(1);
		$result = HttpSourceUtility::explainQuery($query);
		foreach ($explain as $pattern) {
			$this->assertContains($pattern, $result, '', true);
		}
	}

	/**
	 * Data provider for testExplainQuery
	 * 
	 * @return array
	 */
	public function explainQueryProvider() {
		return array(
			//set #0
			array(
				//query
				"GET /documents/document/_search?size=10 HTTP/1.1" .
				"\n" .
				"Host: localhost:12345" .
				"\n" .
				"Connection: close" .
				"\n" .
				"User-Agent: CakePHP" .
				"\n" .
				"Content-Type: application/x-www-form-urlencoded" .
				"\n" .
				"Content-Length: 162" .
				"\n\n" .
				'{"facets":{"tag":{"terms":{"field":"terms","size":4000}}},"query":{"filtered":{"filter":{"bool":{"must":[{"terms":{"labels":["Criminal"],"execution":"and"}}]}}}}}',
				//explain
				array(
					'GET /documents/document/_search?size=10',
					'Host: localhost:12345',
					'User-Agent: CakePHP',
					'Content-Type: application/x-www-form-urlencoded',
					'Content-Length: 162'
				)
			),
			//set #1
			array(
				//query
				"GET /documents/document/_search?size=10 HTTP/1.1" .
				"\n" .
				"Host: localhost:54321" .
				"\n" .
				"Connection: close" .
				"\n" .
				"User-Agent: CakePHP" .
				"\n" .
				"Content-Type: application/x-www-form-urlencoded" .
				"\n" .
				"Content-Length: 162" .
				"\n\n" .
				'{"facets":{"tag":{"terms":{"field":"terms","size":4000}}},"query":{"filtered":{"filter":{"bool":{"must":[{"terms":{"labels":["Criminal"],"execution":"and"}}]}}}}}',
				//explain
				array(
					'Connection refused'
				)
			),
			//set #2
			array(
				//query
				'the good, the bad, the ugly',
				//explain
				array(
					'Can\'t parse query'
				)
			),
		);
	}

}
