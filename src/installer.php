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

	public function __construct($config) {
		$this->config = $config;
		$this->wireshell = new Wireshell($config);
		
		$this->initPWTags();
	}

	public function installProcessWire($tagName) {
		$availableTag = $this->getLatestAvailableMatchingTag($tagName);

		if (! $availableTag) {
			throw new \Exception("No matching ProcessWire tag to '$tagName' found");
		}

		Log::info("Using latest matching ProcessWire version: {$availableTag->name}");
		
		$this->createDatabase();
		$installPath = $this->wireshell->installProcessWire($availableTag);
		
		return $installPath;
	}
	
	public function uninstallProcessWire($processWirePath) {
		if (file_exists($processWirePath)) {
			Path::remove($processWirePath);
		}
		
		$this->dropDatabase();
	}

	protected function createDatabase() {
		try {
			Database::query($this->config->db, "create database {$this->config->db->name}");
		} catch (DatabaseException $e) {
			throw new \Exception("Cannot create database: %s", $e->getMessage());
		}
	}
	
	protected function dropDatabase() {
		try {
			Database::query($this->config->db, "drop database if exists {$this->config->db->name}");
		} catch (DatabaseException $e) {
			throw new \Exception("Cannot drop database: %s", $e->getMessage());
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