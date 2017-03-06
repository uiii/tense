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

namespace Tense\Console;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class BoxOutputFormatterStyle extends OutputFormatterStyle {
	protected static $availableForegroundColors = array(
		'black' => array('set' => 30, 'unset' => 39),
		'red' => array('set' => 31, 'unset' => 39),
		'green' => array('set' => 32, 'unset' => 39),
		'yellow' => array('set' => 33, 'unset' => 39),
		'blue' => array('set' => 34, 'unset' => 39),
		'magenta' => array('set' => 35, 'unset' => 39),
		'cyan' => array('set' => 36, 'unset' => 39),
		'white' => array('set' => 37, 'unset' => 39),
		'default' => array('set' => 39, 'unset' => 39),

		'light-black' => array('set' => 90, 'unset' => 39),
		'light-red' => array('set' => 91, 'unset' => 39),
		'light-green' => array('set' => 92, 'unset' => 39),
		'light-yellow' => array('set' => 93, 'unset' => 39),
		'light-blue' => array('set' => 94, 'unset' => 39),
		'light-magenta' => array('set' => 95, 'unset' => 39),
		'light-cyan' => array('set' => 96, 'unset' => 39),
		'light-white' => array('set' => 97, 'unset' => 39),
	);

	protected static $availableBackgroundColors = array(
		'black' => array('set' => 40, 'unset' => 49),
		'red' => array('set' => 41, 'unset' => 49),
		'green' => array('set' => 42, 'unset' => 49),
		'yellow' => array('set' => 43, 'unset' => 49),
		'blue' => array('set' => 44, 'unset' => 49),
		'magenta' => array('set' => 45, 'unset' => 49),
		'cyan' => array('set' => 46, 'unset' => 49),
		'white' => array('set' => 47, 'unset' => 49),
		'default' => array('set' => 49, 'unset' => 49),
	);

	protected static $availableOptions = array(
		'bold' => array('set' => 1, 'unset' => 22),
		'underscore' => array('set' => 4, 'unset' => 24),
		'blink' => array('set' => 5, 'unset' => 25),
		'reverse' => array('set' => 7, 'unset' => 27),
		'conceal' => array('set' => 8, 'unset' => 28),
	);

	private $padding;
	private $margin;

	/**
	 * Initializes output formatter style.
	 *
	 * @param string|null $foreground The style foreground color name
	 * @param string|null $background The style background color name
	 * @param array       $options    The style options
	 * @param array|int   $padding    The box padding
	 * @param array|int   $margin     The box margin
	 */
	public function __construct($foreground = null, $background = null, array $options = array(), $padding = 0, $margin = 0)
	{
		parent::__construct($foreground, $background, $options);

		$this->setPadding(0);
		$this->setPadding($padding);

		$this->setMargin(0);
		$this->setMargin($margin);
	}

	/**
	 * Sets style padding.
	 *
	 * @param array|int $padding The padding value/s
	 */
	public function setPadding($padding = 0) {
		$padding = $this->parseSizes($padding);

		foreach ($padding as $side => $value) {
			if (! is_int($value) || $value < 0) {
				throw new InvalidArgumentException(sprintf(
					'Invalid %s padding: "%s". Must be a positive integer value.',
					$side, $value
				));
			}

			$this->padding[$side] = $value;
		}
	}

	/**
	 * Sets style margin.
	 *
	 * @param array|int $margin The margin value/s
	 */
	public function setMargin($margin = 0) {
		$margin = $this->parseSizes($margin);

		foreach ($margin as $side => $value) {
			if (! is_int($value) || $value < 0) {
				throw new InvalidArgumentException(sprintf(
					'Invalid %s margin: "%s". Must be a positive integer value.',
					$side, $value
				));
			}

			$this->margin[$side] = $value;
		}
	}

	/**
	 * Applies the style to a given text.
	 *
	 * @param string $text The text to style
	 *
	 * @return string
	 */
	public function apply($text) {
		$lines = preg_split("/\\r\\n|\\n|\\r/", $text);

		$lines = $this->wrapLines($lines, $this->padding);

		$lines = array_map(function ($line) {
			return parent::apply($line);
		}, $lines);

		$lines = $this->wrapLines($lines, $this->margin);

		return implode(PHP_EOL, $lines);;
	}

	protected function wrapLines($lines, $sizes) {
		$width = max(array_map('strlen', $lines));
		$emptyLine = str_repeat(" ", $sizes['left'] + $width + $sizes['right']);

		$lines = array_merge(
			array_fill(0, $sizes['top'], $emptyLine),
			array_map(function ($line) use ($sizes, $width) {
				return $this->wrapLine($line, $sizes, $width);
			}, $lines),
			array_fill(0, $sizes['bottom'], $emptyLine)
		);

		return $lines;
	}

	protected function wrapLine($line, $sizes, $width) {
		return str_repeat(" ", $sizes['left']) . str_pad($line, $width, " ") . str_repeat(" ", $sizes['right']);
	}

	protected function parseSizes($sizes) {
		$sizes = (array) $sizes;

		$keys = array_keys($sizes);
		if ($keys === range(0, count($keys) - 1)) {
			// not associative
			while (count($sizes) < 4) {
				$sizes = array_merge($sizes, $sizes);
			}

			$sizes = array(
				'top' => $sizes[0],
				'right' => $sizes[1],
				'bottom' => $sizes[2],
				'left' => $sizes[3]
			);
		}

		return $sizes;
	}
}