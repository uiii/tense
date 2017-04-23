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

namespace Tense;

use Tense\Config\ConfigLoader;
use Tense\Console\Output;
use Tense\Console\QuestionHelper;
use Tense\Helper\Cmd;
use Tense\Helper\Path;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;

class TestRunner {
	const ACTION_CONTINUE = "Continue";
	const ACTION_STOP = "Stop";
	const ACTION_REPEAT = "Repeat";

	const RESULT_PASS = "PASS";
	const RESULT_FAILURE = "FAILURE";

	protected $config;

	protected $output;
	protected $questionHelper;

	public function __construct($config, OutputInterface $output, QuestionHelper $questionHelper) {
		$this->config = $config;
		$this->output = $output;
		$this->questionHelper = $questionHelper;
	}

	public function run() {
		$nextAction = self::ACTION_CONTINUE;

		foreach ($this->config->testTags as $tagName) {
			$results[$tagName] = null;

			if ($nextAction === self::ACTION_STOP) {
				continue;
			}

			do {
				list($testResult, $nextAction) = $this->testProcessWire($tagName);
			} while ($nextAction === self::ACTION_REPEAT);

			$results[$tagName] = $testResult;
		}

		return $this->processResults($results);
	}

	protected function testProcessWire($tagName) {
		$this->output->writeln("<heading>:: Testing against ProcessWire $tagName ::</heading>");
		$this->output->writeln("");

		$processWirePath = Path::join($this->config->workingDir, $this->config->tmpDir, "pw");
		$testResult = self::RESULT_FAILURE;

		$processWire = new ProcessWire($tagName, $this->config, $this->output);

		try {
			$processWire->install($processWirePath);
			$this->copySourceFiles($processWirePath);
			$this->executeBeforeCmd($processWirePath);

			$testResult = $this->executeTestCmd($processWirePath);
		} catch (\Exception $e) {
			$this->output->writeln(sprintf("<error>%s</error>", trim($e->getMessage())));
			$this->output->writeln("");
		}

		$nextAction = $this->askForAction($testResult, $processWirePath);

		$this->output->write("<info>Cleaning up ... </info>", false, Output::MESSAGE_TEMPORARY);

		$processWire->uninstall($processWirePath);

		return [$testResult, $nextAction];
	}

	protected function copySourceFiles($processWirePath) {
		$this->output->write("<info>Copying source files ... </info>", false, Output::MESSAGE_TEMPORARY);

		foreach ($this->config->copySources as $item) {
			if (is_array($item->source)) {
				foreach($item->source as $source) {
					$source = trim($source);

					Path::copy(
						Path::join($this->config->workingDir, $source),
						Path::join($processWirePath, trim($item->destination), basename($source))
					);
				}
			} else {
				Path::copy(
					Path::join($this->config->workingDir, trim($item->source)),
					Path::join($processWirePath, $item->destination)
				);
			}
		}
	}

	protected function executeBeforeCmd($processWirePath) {
		$this->output->write("<info>Executing before command ... </info>", false, Output::MESSAGE_TEMPORARY);

		$cmd = $this->config->beforeCmd;

		if (! $cmd) {
			return;
		}

		Cmd::run($cmd, [], [
			'cwd' => $this->config->workingDir,
			'env' => [
				'PW_PATH' => $processWirePath
			],
		]);
	}

	protected function executeTestCmd($processWirePath) {
		$result = Cmd::run($this->config->testCmd, [], [
			'cwd' => $this->config->workingDir,
			'env' => [
				'PW_PATH' => $processWirePath
			],
			'throw_on_error' => false,
			'print_prefix' => "<comment>▌</comment> "
		], $this->output);

		$this->output->writeln("");

		return $result->exitCode === 0 ? self::RESULT_PASS : self::RESULT_FAILURE;
	}

	protected function askForAction($testResult, $processWirePath) {
		$pause = $this->config->pause;

		$neverPause = $pause === "never";
		$pauseOnFailureButSuccess = $pause === "onFailure" && $testResult === self::RESULT_PASS;

		if ($neverPause || $pauseOnFailureButSuccess) {
			return self::ACTION_CONTINUE;
		}

		$this->output->writeln(sprintf(
			"<comment>Test runner is now halted (configured to pause after %s tests, see 'pause' option)</comment>",
			$pause === "always" ? "all" : "failed"
		));

		if ($processWirePath) {
			$this->output->writeln("<comment>Tested ProcessWire instance is installed in '$processWirePath'</comment>");
		}

		$choices = array(
			"y" => "yes",
			"n" => "no"
		);

		$choiceActions = array(
			"y" => self::ACTION_CONTINUE,
			"n" => self::ACTION_STOP,
		);

		if ($testResult === self::RESULT_FAILURE) {
			$choices["r"] = "repeat";
			$choiceActions["r"] = self::ACTION_REPEAT;
		}

		$question = new ChoiceQuestion(
			"Do you want to continue?",
			$choices,
			$testResult === self::RESULT_PASS ? $choices["y"] : $choices["n"]
		);

		$question->setAutocompleterValues(null);

		$answer = $this->questionHelper->ask($question);

		$this->output->writeln("");

		return $choiceActions[$answer];
	}

	protected function processResults($results) {
		$this->output->writeln("<heading>:: Results ::</heading>");
		$this->output->writeln("");

		$passedCount = 0;
		$failedCount = 0;
		$skippedCount = 0;

		foreach ($results as $tagName => $result) {
			$format = $result === self::RESULT_PASS ? 'success' : ($result === self::RESULT_FAILURE ? 'error' : 'warning');
			$this->output->writeln(sprintf("<info>%s: </info><%s>%s</%s> ", $tagName, $format, $result ?: 'SKIP', $format));

			if ($result === self::RESULT_PASS) {
				++$passedCount;
			} elseif ($result === self::RESULT_FAILURE) {
				++$failedCount;
			} else {
				++$skippedCount;
			}
		}

		$this->output->writeln("");

		if (count($results) === $passedCount) {
			$this->output->writeln(sprintf("<success>OK (%s tested)</success>", count($results)));
		} else {
			$this->output->writeln(sprintf("<error>FAILURE (%s tested, %s passed, %s failed, %s skipped)</error>", count($results), $passedCount, $failedCount, $skippedCount));
		}

		return count($results) === $passedCount ? 0 : 1;
	}
}