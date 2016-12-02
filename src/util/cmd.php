<?php

class CmdException extends \Exception {
	public function __construct($result) {
		parent::__construct(implode("\n", $result->output));

		$this->result = $result;
	}

	public function result() {
		return $result;
	}
}

class Cmd {
	public static function run($command, $args = [], $workingDir = null) {
		$cwd = getcwd();

		if ($workingDir) {
			chdir($workingDir);
		}

		$output = null;
		$exitCode = 0;

		$commandString = sprintf("%s %s 2>&1", $command, implode(' ', $args));
		
		Log::debug(sprintf("Running command: %s", $commandString));

		exec($commandString, $output, $exitCode);

		$result = new \stdClass;
		$result->exitCode = $exitCode;
		$result->output = $output;

		if ($exitCode !== 0) {
			throw new CmdException($result);
		}

		if ($workingDir) {
			chdir($cwd);
		}

		return $result;
	}
}