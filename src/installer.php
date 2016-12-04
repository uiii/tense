<?php

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

	public function installProcessWire($tagName) {
		$this->shouldCleanUpFiles = false;
		$this->shouldCleanUpDatabase = false;

		$availableTag = $this->getLatestAvailableMatchingTag($tagName);

		if (! $availableTag) {
			throw new \RuntimeException("No matching ProcessWire tag to '$tagName' found");
		}

		Log::info("Using latest matching ProcessWire version: {$availableTag->name}");

		$this->createDatabase();

		$installPath = Path::join($this->config->tmpDir, "pw");

		if (file_exists($installPath)) {
			throw new \RuntimeException(sprintf("ProcessWire install path already exists: %s", $installPath));
		}

		$this->shouldCleanUpFiles = true;

		$this->wireshell->installProcessWire($availableTag, $installPath);

		return $installPath;
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