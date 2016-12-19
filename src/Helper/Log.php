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

namespace PWTest\Helper;

abstract class Log {
	/**
	* Log level
	*
	* 0 - quiet (no output)
	* 1 - errors only
	* 2 - errors & warnings
	* 3 - errors & warnings & info (default)
	* 4 - debug (all)
	**/
	public static $level = 3;

	public static function error($message) {
		self::write(1, $message, "[ERROR]");
	}

	public static function warn($message) {
		self::write(2, $message, "[WARNING]");
	}

	public static function info($message) {
		self::write(3, $message);
	}

	public static function debug($message, $debugLevel = 0) {
		self::write(4 + $debugLevel, $message, "[DEBUG]");
	}

	protected static function write($minLevel, $message, $prefix = "") {
		if (self::$level < $minLevel) {
			return;
		}

		if ($prefix) {
			$prefix .= " ";
		}

		echo sprintf("%s%s" . PHP_EOL, $prefix, $message);
	}
}