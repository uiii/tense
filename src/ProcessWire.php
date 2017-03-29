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

use Tense\Helper\Path;
use Tense\Helper\Git;
use Tense\Helper\Database;
use Tense\Wireshell;

use Tense\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

class ProcessWire {
	public static $pwGithubRepos = [
		'processwire/processwire',
		'processwire/processwire-legacy',
		'ryancramerdesign/ProcessWire'
	];

	protected static $availableTags = [];

	protected $tagName;
	protected $config;

	protected $output;

	protected $shouldCleanUpFiles;
	protected $shouldcleanUpDatabase;

	public function __construct($tagName, $config, OutputInterface $output) {
		$this->tagName = $tagName;
		$this->config = $config;

		$this->output = $output;
	}

	public function install($installPath) {
		$this->shouldCleanUpFiles = false;
		$this->shouldCleanUpDatabase = false;

		$this->output->write("<info>Initializing ... </info>", false, Output::MESSAGE_TEMPORARY);

		$availableTag = $this->getLatestAvailableMatchingTag();

		if (! $availableTag) {
			throw new \RuntimeException("No matching ProcessWire tag to '$this->tagName' found");
		}

		$this->output->writeln("<comment>Using latest matching ProcessWire version: {$availableTag->name}</comment>");
		$this->output->writeln("");

		$this->createDatabase();

		if (file_exists($installPath)) {
			throw new \RuntimeException(sprintf("ProcessWire install path already exists: %s", $installPath));
		}

		$this->shouldCleanUpFiles = true;

		$wireshell = new Wireshell($this->output);
		$wireshell->installProcessWire($availableTag, $installPath, $this->config);
	}

	public function uninstall($processWirePath) {
		if ($this->shouldCleanUpFiles && file_exists($processWirePath)) {
			Path::remove($processWirePath);
		}

		if ($this->shouldCleanUpDatabase) {
			$this->dropDatabase();
		}
	}

	protected function createDatabase() {
		try {
			Database::query($this->config->db, "create database {$this->config->db->name}");
			$this->shouldCleanUpDatabase = true;
		} catch (DatabaseException $e) {
			throw new \RuntimeException("Cannot create database: %s", $e->getMessage());
		}
	}

	protected function dropDatabase() {
		try {
			Database::query($this->config->db, "drop database if exists {$this->config->db->name}");
		} catch (DatabaseException $e) {
			throw new \RuntimeException("Cannot drop database: %s", $e->getMessage());
		}
	}

	protected function getLatestAvailableMatchingTag() {
		if (empty(self::$availableTags)) {
			self::$availableTags = self::getAvailableTags();
		}

		foreach (self::$availableTags as $availableTag) {
			if ($this->tagName === $availableTag->name || strpos($availableTag->name, $this->tagName . '.') === 0) {
				// available tag's name equals or starts with the tag's name
				return $availableTag;
			}
		}

		return null;
	}

	protected static function getAvailableTags() {
		$availableTags = [];

		foreach (self::$pwGithubRepos as $pwRepo) {
			$tags = Git::getTags($pwRepo);

			$availableTags = array_merge($availableTags, $tags);
		}

		/*var_dump(array_map(
			function ($tag) { return $tag->name . " : " . $tag->sha; },
			$availableTags
		));*/

		return $availableTags;
	}
}