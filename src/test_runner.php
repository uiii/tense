<?php

require_once __DIR__ . '/util/log.php';
require_once __DIR__ . '/util/path.php';
require_once __DIR__ . '/installer.php';

class TestRunner
{
	protected $installer;

	public function __construct($configFile) {
		$this->config = $this->loadConfig($configFile);
		$this->installer = new Installer($this->config);
	}

	public function run() {
		foreach($this->config->testTags as $tagName) {
			$this->runTests($tagName);
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

		return $config;
	}

	protected function runTests($tagName) {
		Log::info("\n::Testing agains PW $tagName");
		
		$processWirePath = null;

		try {
			$processWirePath = $this->installer->installProcessWire($tagName);
			$this->copySourceFiles($processWirePath);
			
			// TODO run tests
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
}

/*PHPUNIT_CMD="${CWD}/../vendor/bin/phpunit"

test_pw() {
	install_params="$@"

	install_pw ${install_params} && ${PHPUNIT_CMD}
	uninstall_pw
}*/
