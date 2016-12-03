<?php

require_once __DIR__ . '/util/log.php';
require_once __DIR__ . '/util/path.php';
require_once __DIR__ . '/installer.php';

class TestRunner
{
	protected $installer;

	public function __construct($configFile) {
		Log::info("Initializing ...");

		$this->config = $this->loadConfig($configFile);
		$this->installer = new Installer($this->config);
	}

	public function run() {
		foreach($this->config->testTags as $tagName) {
			$this->testProcessWire($tagName);
		}
	}

	protected function loadConfig($configFile) {
		if (! file_exists($configFile)) {
			Log::error("Missing config! Please, create pw-test.json config file.");
			die();
		}

		$config = json_decode(file_get_contents($configFile));
		
		$config->_file = $configFile;

		// absolutize tmpDir
		if (! Path::isAbsolute($config->tmpDir)) {
			// make the tmpDir relative to the directory where the config file is stored
			$config->tmpDir = Path::join(dirname($configFile), $config->tmpDir);
		}
		
		// TODO validation

		return $config;
	}

	protected function testProcessWire($tagName) {
		Log::info(PHP_EOL . "::: Testing agains PW $tagName :::" . PHP_EOL);
		
		$processWirePath = null;

		try {
			$processWirePath = $this->installer->installProcessWire($tagName);
			$this->copySourceFiles($processWirePath);
			
			$this->runTests($processWirePath);
		} catch (\Exception $e) {
			Log::error($e->getMessage());
		} finally {
			$this->installer->uninstallProcessWire($processWirePath);
		}
	}
	
	protected function copySourceFiles($processWirePath) {
		foreach ($this->config->copySources as $destination => $sources) {
			foreach($sources as $source) {
				Path::copy(
					Path::join(dirname($this->config->_file), $source),
					Path::join($processWirePath, $destination, basename($source))
				);
			}
		}
	}
	
	protected function runTests($processWirePath) {
		Log::info("Running tests ..." . PHP_EOL);
		
		list($cmdExecutable, $args) = preg_split("/\s+/", $this->config->testCmd . " ", 2);
		
		if (strpbrk($cmdExecutable, "/\\") !== false) {
			// cmd executable is a path, so make it absolute
			$cmdExecutable = Path::join(dirname($this->config->_file), $cmdExecutable);
		}
		
		$env = [
			"PW_PATH" => $processWirePath
		];

		$result = Cmd::run($cmdExecutable, preg_split("/\s+/", $args), [
			'env' => $env, 
			'throw_on_error' => false,
			'print_output' => true
		]);
		
		Log::info(PHP_EOL);
	}
}

/*PHPUNIT_CMD="${CWD}/../vendor/bin/phpunit"

test_pw() {
	install_params="$@"

	install_pw ${install_params} && ${PHPUNIT_CMD}
	uninstall_pw
}*/
