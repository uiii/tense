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

require_once __DIR__ . '/util/log.php';
require_once __DIR__ . '/util/path.php';
require_once __DIR__ . '/util/git.php';
require_once __DIR__ . '/util/database.php';
require_once __DIR__ . '/wireshell/wireshell.php';

class Installer {
	public static $pwGithubRepos = [
		'processwire/processwire',
		'processwire/processwire-legacy',
		'ryancramerdesign/ProcessWire'
	];

	protected $availableTags = [];

	protected $config;

	protected $shouldCleanUpFiles;
	protected $shouldcleanUpDatabase;

	public function __construct($config) {
		$this->config = $config;
		$this->wireshell = new Wireshell($config);

		$this->initPWTags();
	}

	public function installProcessWire($tagName, $installPath) {
		$this->shouldCleanUpFiles = false;
		$this->shouldCleanUpDatabase = false;

		$availableTag = $this->getLatestAvailableMatchingTag($tagName);

		if (! $availableTag) {
			throw new \RuntimeException("No matching ProcessWire tag to '$tagName' found");
		}

		Log::info("Using latest matching ProcessWire version: {$availableTag->name}");

		$this->createDatabase();

		if (file_exists($installPath)) {
			throw new \RuntimeException(sprintf("ProcessWire install path already exists: %s", $installPath));
		}

		$this->shouldCleanUpFiles = true;

		$this->wireshell->installProcessWire($availableTag, $installPath);
	}

	public function uninstallProcessWire($processWirePath) {
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

	protected function getLatestAvailableMatchingTag($tagName) {
		foreach ($this->availableTags as $availableTag) {
			if ($tagName === $availableTag->name || strpos($availableTag->name, $tagName . '.') === 0) {
				// available tag's name equals or starts with the tag's name
				return $availableTag;
			}
		}

		return null;
	}

	protected function initPWTags() {
		foreach (self::$pwGithubRepos as $pwRepo) {
			$tags = Git::getTags($pwRepo);

			$this->availableTags = array_merge($this->availableTags, $tags);
		}

		/*var_dump(array_map(
			function ($tag) { return $tag->name . " : " . $tag->sha; },
			$this->availableTags
		));*/
	}
}