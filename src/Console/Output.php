<?php

namespace Tense\Console;

use Tense\Console\OutputFormatter;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Output extends ConsoleOutput {
	protected $tempMessage = "";

	const MESSAGE_TEMPORARY = 8192;

	/**
	 * Constructor.
	 *
	 * @param int                           $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
	 * @param bool                          $decorated Whether to decorate messages
	 * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
	 */
	public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null) {
		parent::__construct($verbosity, $decorated, new OutputFormatter());
	}

	public function write($message, $newline = false, $options = OutputInterface::OUTPUT_NORMAL) {
		if ($this->tempMessage) {
			$this->overwrite($this->tempMessage);
			$this->tempMessage = null;
		}

		if ($options & self::MESSAGE_TEMPORARY) {
			if ($this->getVerbosity() === OutputInterface::VERBOSITY_NORMAL) {
				$this->tempMessage = $message;
				$newline = false;
			} else {
				$newline = true;
			}
		}

		parent::write($message, $newline, $options);
	}

	protected function overwrite($message) {
		parent::write("\r", false, OutputInterface::OUTPUT_RAW);
		parent::write(str_repeat(" ", strlen($message)));
		parent::write("\r", false, OutputInterface::OUTPUT_RAW);
	}
}