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

namespace Tense\Command;

use Tense\Config\ConfigLoader;
use Tense\Config\ConfigInitializer;
use Tense\Config\MissingLocalConfigException;
use Tense\Console\BoxOutputFormatterStyle;
use Tense\Console\Output;
use Tense\Console\OutputFormatter;
use Tense\Console\QuestionHelper;
use Tense\Helper\Path;
use Tense\TestRunner;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RunCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('run')
			->setDescription('Run tests');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$configFilePath = Path::join(getcwd(), "tense.yml");
		$questionHelper = new QuestionHelper($input, $output);

		$output->writeln($this->getApplication()->getLogo());
		$output->writeln("");

		$configLoader = new ConfigLoader();
		$config = null;

		try {
			$config = $configLoader->load($configFilePath);
		} catch (MissingLocalConfigException $e) {
			$this->handleMissingLocalConfig($e->getFilePath(), $output, $questionHelper);
			$config = $configLoader->load($configFilePath);

			$output->writeln("");
		}

		$runner = new TestRunner($config, $output, $questionHelper);
		return $runner->run();
	}

	protected function handleMissingLocalConfig($localConfigFilePath, OutputInterface $output, QuestionHelper $questionHelper) {
		$output->writeln(sprintf("<warning>Local config file %s is not yet created.</warning>", $localConfigFilePath));
		$output->writeln("Local config is required before continuing.");

		$question = new ConfirmationQuestion("Do you want to initialize it?", true);
		$answer = $questionHelper->ask($question);

		if (! $answer) {
			return;
		}

		$output->writeln("");

		$configInitializer = new ConfigInitializer($output, $questionHelper);
		$configInitializer->initialize($localConfigFilePath, 'local');
	}
}