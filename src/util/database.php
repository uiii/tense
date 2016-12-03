<?php

require_once __DIR__ . '/cmd.php';

class DatabaseException extends \Exception {
	public function __construct($message) {
		parent::__construct($message);
	}
}

class Database {
	public static function query($dbConfig, $query) {
		$mysqlArgs = [
			"-h {$dbConfig->host}",
			"-P {$dbConfig->port}",
			"-u {$dbConfig->user}"
		];

		if ($dbConfig->pass) {
			array_push($mysqlArgs, "-p\"{$dbConfig->pass}\"");
		}

		array_push($mysqlArgs, "-e \"$query\"");

		$result = Cmd::run("mysql", $mysqlArgs);

		if ($result->exitCode !== 0) {
			throw new DatabaseException(implode(PHP_EOL, $result->output));
		}
	}
}