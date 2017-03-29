<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tense\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatter as SymfonyOutputFormatter;

class OutputFormatter extends SymfonyOutputFormatter {
	/**
	 * Initializes console output formatter.
	 *
	 * @param bool                            $decorated Whether this formatter should actually decorate strings
	 * @param OutputFormatterStyleInterface[] $styles    Array of "name => FormatterStyle" instances
	 */
	public function __construct($decorated = false, array $styles = array()) {
		parent::__construct($decorated, $styles);

		$this->setStyle('debug', new BoxOutputFormatterStyle('magenta'));
		$this->setStyle('info', new BoxOutputFormatterStyle('white'));
		$this->setStyle('em', new BoxOutputFormatterStyle('light-white'));
		$this->setStyle('warning', new BoxOutputFormatterStyle('black', 'yellow', [], array(0, 1)));
		$this->setStyle('error', new BoxOutputFormatterStyle('white', 'red', [], array(0, 1)));
		$this->setStyle('success', new BoxOutputFormatterStyle('black', 'green', [], array(0, 1)));
		$this->setStyle('comment', new BoxOutputFormatterStyle('light-black'));
		$this->setStyle('question', new BoxOutputFormatterStyle('yellow'));
		$this->setStyle('heading', new BoxOutputFormatterStyle('light-cyan'));
	}
}