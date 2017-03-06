<?php

namespace Tense;

use Tense\Helper\Log;
use Tense\Command\RunCommand;
use Tense\Console\Output;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication {
	public function __construct() {
		parent::__construct("Tense", "dev-master");

		$this->add(new RunCommand);
		$this->setDefaultCommand("run", true);
	}

	public function getLogo() {
		$logo =
<<<LOGO
 _______ ___ __ __ _____ _____
|__   __| __|  \  |  ___/  ___|
  |   |  __|      |___  |  __|
  |___|_____|__\__|_____|_____|

<fg=blue>Test environment setup & execution</fg=blue>   <fg=green>%s</fg=green>
LOGO;

		return sprintf($logo, $this->getVersion());
	}

	public function getHelp() {
		return $this->getLogo();
	}

	public function run(InputInterface $input = null, OutputInterface $output = null)
	{
		if (null === $output) {
			$output = new Output();
		}

		Log::setOutput($output);

		parent::run($input, $output);
	}
}
