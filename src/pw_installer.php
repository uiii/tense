<?php

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/wireshell.php';

class PWInstaller {
	protected $config;

	public function __construct($config) {
		$this->config = $config;
		$this->wireshell = new Wireshell($config);
	}

	public function install($tag, $path) {
		$this->createDatabase($this->config->db);
		$this->wireshell->installProcessWire($tag, $path);
	}

	protected function createDatabase($dbConfig) {
		$mysqlArgs = [
			"-h {$dbConfig->host}",
			"-P {$dbConfig->port}",
			"-u {$dbConfig->user}"
		];

		if ($dbConfig->pass) {
			array_push($mysqlArgs, "-p\"{$dbConfig->pass}\"");
		}

		array_push($mysqlArgs, "-e \"create database {$dbConfig->name}\"");

		$result = Cmd::run("mysql", $mysqlArgs);

		if ($result->exitCode !== 0) {
			echo sprintf("[ERROR] Cannot create database: %s\n", implode("\n\t", $result->output));
			continue;
		}
	}
}