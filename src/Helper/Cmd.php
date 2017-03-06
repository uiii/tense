<?php

/*
 * The MIT License
 *
 * Copyright 2016 Richard Jedlička <jedlicka.r@gmail.com> (http://uiii.cz)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Tense\Helper;

use Tense\Helper\Path;
use Tense\Helper\Log;
use Tense\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

class CmdException extends \RuntimeException {
	protected $result;

	public function __construct($result) {
		$message = $result ? implode(PHP_EOL, (array) $result->output) : "unknown error";
		parent::__construct($message);

		$this->result = $result;
	}

	public function result() {
		return $result;
	}
}

abstract class Cmd {
	public static $defaultOptions = [
		'cwd' => null,
		'env' => null,
		'throw_on_error' => true,
		'print_prefix' => ""
	];

	public static function run($command, $args = [], $options = [], Output $outputWriter = null) {
		$options = array_merge(self::$defaultOptions, $options);

		if (strpbrk($command, "/\\") !== false) {
			// command executable is a path, so make it absolute
			$command = Path::join($options['cwd'] ?: getcwd(), $command);
		}

		$commandString = sprintf("%s %s 2>&1", $command, implode(' ', $args));

		$descriptorspec = array(
			1 => array("pipe", "w") // stdout
		);

		if ($options['env'] !== null) {
			// merge passed environment with current environment
			$options['env'] = array_merge(self::getEnv(), $options['env']);
		}

		Log::info(sprintf("Running command: %s", $commandString));

		$process = proc_open($commandString, $descriptorspec, $pipes, $options['cwd'], $options['env']);

		if (! is_resource($process)) {
			throw new CmdException(null);
		}

		$previousCharacter = null;
		$line = "";
		$output = [];

		while (! feof($pipes[1])) {
			$char = fgetc($pipes[1]);

			$line .= $char;

			$eol = $char === "\n";
			$eofAndLine = $char === false && $line;

			if ($eol || $eofAndLine) {
				$line = rtrim($line, "\r\n");
				array_push($output, $line);

				$line = "";
			}

			if ($outputWriter && $char !== false) {
				if (is_null($previousCharacter) || $previousCharacter === "\n") {
					$outputWriter->write($options['print_prefix']);
				}

				$outputWriter->write($char, false, OutputInterface::OUTPUT_RAW);
			}

			$previousCharacter = $char;
		}

		fclose($pipes[1]);

		$exitCode = proc_close($process);

		$result = new \stdClass;
		$result->exitCode = $exitCode;
		$result->output = $output;

		Log::debug(sprintf("Command output:%s%s", PHP_EOL, implode(PHP_EOL, $output)));
		Log::debug(sprintf("Command exit code: %s", $exitCode));

		if ($exitCode !== 0 && $options['throw_on_error']) {
			throw new CmdException($result);
		}

		return $result;
	}

	/**
	* Get all environment variables or
	* a value of specified variable.
	*
	* Using workaround because $_ENV could be empty
	* (depends on variables_order directive in PHP.ini)
	*
	* @param string $variable (optional) Variable name
	* @return array|string
	*/
	public static function getEnv($variable = null) {
		$cmd = preg_match("/WIN/Ai", PHP_OS) ? 'set' : 'printenv';
		$output = self::run($cmd)->output;

		$env = [];

		foreach ($output as $line) {
			list($key, $value) = explode("=", $line, 2);

			if ($variable && strtolower($key) === strtolower($variable)) {
				return $value;
			}

			$env[$key] = $value;
		}

		return $env;
	}
}