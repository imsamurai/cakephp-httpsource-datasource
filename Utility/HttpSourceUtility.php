<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 01.09.2014
 * Time: 13:47:56
 */
use Symfony\Component\Process\Process;

/**
 * HttpSource Utility
 * 
 * @package HttpSource
 * @subpackage Utility
 */
class HttpSourceUtility {

	/**
	 * Explain query by curl verbose
	 * 
	 * @param string $query
	 * @param int $verboseLevel
	 * @param int $timeout
	 * @return string
	 */
	public static function explainQuery($query, $verboseLevel = 4, $timeout = 5) {
		$verboseLevel = $verboseLevel > 4 ? 4 : ($verboseLevel < 1 ? 1 : $verboseLevel);

		$data = static::parseQuery($query);
		if (!$data) {
			return 'Can\'t parse query';
		}

		$command = "curl '{$data['host']}:{$data['port']}{$data['path']}'" .
				' -s' .
				//' --connect-timeout ' . (int)$timeout .
				" -" . str_repeat('v', $verboseLevel) .
				" -X{$data['method']}" .
				' ' . implode('', array_map(function($h) { 
					return " -H '$h'"; 
				}, $data['headers'])) .
				" -d '" . str_replace("'", '\\\'', $data['body']) . "' 2>&1 1>/dev/null";
		try {
			$Process = new Process($command, null, null, null, $timeout);
		} catch (Exception $E) {
			return 'You must install symfony/process for expain query';
		}

		$Process->run();
		return $Process->getOutput();
	}

	/**
	 * Parse raw http query generated by HttpSocket
	 * 
	 * @param string $query
	 * @return boolean
	 */
	public static function parseQuery($query) {
		$success = preg_match("/^(?P<method>GET|POST|PUT|DELETE|HEAD)\s(?P<path>\S+)\s(?P<httpVer>\S+)\sHost:\s(?P<host>[^\s:]+)(:(?P<port>[0-9]+)|)\s(?P<headers>.*)\n\n(?P<body>.*)$/ims", ltrim($query), $matches);
		if (!$success) {
			return false;
		}
		return array(
			'method' => strtoupper($matches['method']),
			'path' => $matches['path'],
			'httpVer' => $matches['httpVer'],
			'host' => $matches['host'],
			'port' => empty($matches['port']) ? 80 : (int)$matches['port'],
			'headers' => explode("\n", $matches['headers']),
			'body' => $matches['body']
		);
	}

}
