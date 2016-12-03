<?php

require_once __DIR__ . '/log.php';

class CmdException extends \Exception {
	public function __construct($result) {
		$message = $result ? implode(PHP_EOL, (array) $result->output) : "unknown error";
		parent::__construct($message);

		$this->result = $result;
	}

	public function result() {
		return $result;
	}
}

class Cmd {
	public static $defaultOptions = [
		'cwd' => null,
		'env' => null,
		'throw_on_error' => true,
		'print_output' => false
	];

	public static function run($command, $args = [], $options = []) {
		$options = array_merge(self::$defaultOptions, $options);

		$commandString = sprintf("%s %s 2>&1", $command, implode(' ', $args));
		
		$descriptorspec = array(
			1 => array("pipe", "w") // stdout
		);

		if ($options['env'] !== null) {
			// merge passed environment with current environment
			$options['env'] = array_merge(self::getEnv(), $options['env']);
		}
		
		Log::debug(sprintf("Running command: %s", $commandString));
		
		$process = proc_open($commandString, $descriptorspec, $pipes, $options['cwd'], $options['env']);

		if (! is_resource($process)) {
			throw new CmdException(null);
		}

		if ($options['print_output']) {
			echo "> ";
		}

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

			if ($options['print_output']) {
				echo $char;
				
				if ($eol) {
					echo "> ";
				}
			}
		}
		
		fclose($pipes[1]);

		$exitCode = proc_close($process);

		$result = new \stdClass;
		$result->exitCode = $exitCode;
		$result->output = $output;

		Log::debug(sprintf("Command exit code: %s", $exitCode));

		if ($exitCode !== 0 && $options['throw_on_error']) {
			throw new CmdException($result);
		}
		
		return $result;
	}

	/**
	* Get environment variables
	*
	* Using workaround because $_ENV could be empty
	* (depends on variables_order directive in PHP.ini)
	*/
	public static function getEnv() {
		$cmd = preg_match("/WIN/Ai", PHP_OS) ? 'set' : 'printenv';
		$output = self::run($cmd)->output;

		$env = [];
		
		foreach ($output as $line) {
			list($key, $value) = explode("=", $line, 2);
			$env[$key] = $value;
		}
		
		return $env;
	}
}