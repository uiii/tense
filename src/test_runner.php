<?php

require_once __DIR__ . '/util/log.php';
require_once __DIR__ . '/util/path.php';
require_once __DIR__ . '/installer.php';

class TestRunner
{
	const ACTION_CONTINUE = "Continue";
	const ACTION_STOP = "Stop";
	const ACTION_REPEAT = "Repeat";

	protected $installer;

	public function __construct($configFile) {
		Log::info("Initializing ...");

		$this->config = $this->loadConfig($configFile);
		$this->installer = new Installer($this->config);
	}

	public function run() {
		foreach($this->config->testTags as $tagName) {
			$nextAction = $this->testProcessWire($tagName);

			while ($nextAction === self::ACTION_REPEAT) {
				$nextAction = $this->testProcessWire($tagName);
			}

			if ($nextAction === self::ACTION_STOP) {
				break;
			}
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
		Log::info(PHP_EOL . "::: Testing against ProcessWire $tagName :::" . PHP_EOL);

		$processWirePath = null;
		$testSuccess = false;

		try {
			$processWirePath = $this->installer->installProcessWire($tagName);
			$this->copySourceFiles($processWirePath);

			$testSuccess = $this->runTests($processWirePath);
		} catch (\Exception $e) {
			Log::error($e->getMessage() . PHP_EOL);
		}

		$action = $this->askForAction($testSuccess, $processWirePath);

		Log::info(sprintf("Clean up & %s", $action));

		$this->installer->uninstallProcessWire($processWirePath);

		return $action;
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

		$success = $result->exitCode === 0;

		if (! $success) {
			Log::info("Tests failed" . PHP_EOL);
		}

		return $success;
	}

	protected function askForAction($testSuccess, $processWirePath) {
		$waitAfterTests = $this->config->waitAfterTests;

		$neverWait = $waitAfterTests === "never";
		$waitOnFailureButSuccess = $waitAfterTests === "onFailure" && $testSuccess;

		if ($neverWait || $waitOnFailureButSuccess) {
			return self::ACTION_CONTINUE;
		}

		Log::info(sprintf(
			"Test runner is now halted (configured to wait after %s tests, see 'waitAfterTests' option)",
			$waitAfterTests === "always" ? "all" : "failed"
		));

		if ($processWirePath) {
			Log::info("Tested ProcessWire instance is installed in '$processWirePath'");
		}

		$options = [
			self::ACTION_CONTINUE => "Yes",
			self::ACTION_STOP => "No"
		];

		$defaultAction = self::ACTION_CONTINUE;

		if (! $testSuccess) {
			$options[self::ACTION_REPEAT] = "Repeat";
			$defaultAction = self::ACTION_STOP;
		}

		$selectedAction = null;

		while (! $selectedAction) {
			echo sprintf(
				"Do you want to continue? %s (default is [%s]): ",
				implode("  ", array_map(function ($option) {
					return preg_replace("/^./", "[$0]", $option);
				}, $options)),
				$options[$defaultAction][0]
			);

			$input = trim(fgets(STDIN));

			if (! $input) {
				$selectedAction = $defaultAction;
				break;
			}

			foreach ($options as $action => $option) {
				if (stripos($option, $input) === 0) {
					$selectedAction = $action;
				}
			}

			if (! $selectedAction) {
				Log::error(sprintf("Unknown option: %s" . PHP_EOL, $input));
			}
		}

		echo PHP_EOL;

		return $selectedAction;
	}
}