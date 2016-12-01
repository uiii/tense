<?php

class Path {
	public static function isAbsolute($path) {
		return (bool) preg_match("#([a-z]:)?[/\\\\]#Ai", $path);
	}

	public static function join(/* $paths */) {
		$paths = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $paths);
	}
}

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

		//var_dump(sprintf("%s %s 2>&1", $command, implode(' ', $args)));
		exec(sprintf("%s %s 2>&1", $command, implode(' ', $args)), $output, $exitCode);

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