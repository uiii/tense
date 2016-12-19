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

require_once __DIR__ . '/Log.php';

abstract class Path {
	public static function isAbsolute($path) {
		return (bool) preg_match("#([a-z]:)?[/\\\\]#Ai", $path);
	}

	public static function join(/* $paths */) {
		return array_reduce(func_get_args(), function ($output, $path) {
			$path = rtrim($path, "\\/");

			if (self::isAbsolute($path) || ! $output) {
				return $path;
			}

			return $output . DIRECTORY_SEPARATOR . $path;
		}, "");
	}

	public static function copy($source, $destination) {
		if (is_file($source)) {
			Log::debug(sprintf("Copy file: %s", $source), 1);
			Log::debug(sprintf("To file:   %s", $destination), 1);

			copy($source, $destination);

			return;
		}

		Log::debug(sprintf("Create directory: %s", $destination), 1);
		mkdir($destination, 0777, true);

		$directoryIterator = new \RecursiveDirectoryIterator(
			$source, \RecursiveDirectoryIterator::SKIP_DOTS);

		$recursiveIterator = new \RecursiveIteratorIterator(
			$directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

		foreach($recursiveIterator as $item) {
			if ($item->isDir()) {
				$directoryPath = self::join($destination, $recursiveIterator->getSubPathName());

				Log::debug(sprintf("Create directory: %s", $directoryPath), 1);

				mkdir($directoryPath, 0777);
			} else {
				$sourceFilePath = $item->getPathname();
				$destinationFilePath = self::join($destination, $recursiveIterator->getSubPathName());

				Log::debug(sprintf("Copy file: %s", $sourceFilePath), 1);
				Log::debug(sprintf("To file:   %s", $destinationFilePath), 1);

				copy($sourceFilePath, $destinationFilePath);
			}
		}
	}

	public static function remove($path) {
		if (is_file($path)) {
			Log::debug(sprintf("Remove file: %s", $path), 1);

			unlink($path);

			return;
		}

		$directoryIterator = new \RecursiveDirectoryIterator(
			$path, \RecursiveDirectoryIterator::SKIP_DOTS);

		$recursiveIterator = new \RecursiveIteratorIterator(
			$directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);

		foreach($recursiveIterator as $item) {
			$itemPath = $item->getPathname();

			if ($item->isDir() && ! $item->isLink()) {
				Log::debug(sprintf("Remove directory: %s", $itemPath), 1);

				rmdir($itemPath);
			} else {
				Log::debug(sprintf("Remove file: %s", $itemPath), 1);

				unlink($itemPath);
			}
		}

		Log::debug(sprintf("Remove directory: %s", $path), 1);
		rmdir($path);
	}
}
