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

namespace PWTest;

require_once __DIR__ . '/Helper/Log.php';
require_once __DIR__ . '/Helper/Path.php';
require_once __DIR__ . '/Helper/Cmd.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Installer.php';

use PWTest\Helper\Log;
use PWTest\Helper\Path;
use PWTest\Helper\Cmd;

class TestRunner {
	const ACTION_CONTINUE = "Continue";
	const ACTION_STOP = "Stop";
	const ACTION_REPEAT = "Repeat";

	protected $installer;

	public function __construct($workingDir) {
		Log::info("Initializing ...");

		$this->config = new Config(Path::join($workingDir, "pw-test.yml"), $workingDir);
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

	protected function testProcessWire($tagName) {
		Log::info(PHP_EOL . "::: Testing against ProcessWire $tagName :::" . PHP_EOL);

		$processWirePath = Path::join($this->config->workingDir, $this->config->tmpDir, "pw");
		$testSuccess = false;

		try {
			$this->installer->installProcessWire($tagName, $processWirePath);
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
			if (is_array($sources)) {
				foreach($sources as $source) {
					$source = trim($source);

					Path::copy(
						Path::join(dirname($this->config->workingDir), $source),
						Path::join($processWirePath, trim($destination), basename($source))
					);
				}
			} else {
				Path::copy(
					Path::join(dirname($this->config->workingDir), trim($sources)),
					Path::join($processWirePath, $destination)
				);
			}
		}
	}

	protected function runTests($processWirePath) {
		Log::info("Running tests ..." . PHP_EOL);

		list($cmdExecutable, $args) = preg_split("/\s+/", trim($this->config->testCmd) . " ", 2);

		if (strpbrk($cmdExecutable, "/\\") !== false) {
			// cmd executable is a path, so make it absolute
			$cmdExecutable = Path::join($this->config->workingDir, $cmdExecutable);
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