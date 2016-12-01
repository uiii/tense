<?php

require_once __DIR__ . '/utils.php';

class Wireshell {
	public static $githubUrl = "https://github.com/wireshell/wireshell";

	protected static $pwTagRequiredVersion = [
		"/^3/" => "1.0.0",
		"/^2\.8/" => null,
		"/^2/" => "0.6.0",
	];

	protected $wireshellPath;
	protected $config;

	public function __construct($config) {
		$this->wireshellPath = Path::join($config->tmpDir, "wireshell", "wireshell");

		if (! file_exists($this->wireshellPath)) {
			$this->installSelf(dirname($this->wireshellPath));
		}

		$this->config = $config;
	}

	public function installProcessWire($tag, $path) {
		echo "Installing PW $tag->name ...\n";

		$version = $this->getRequiredVersion($tag->name);

		if (! $version) {
			throw new \Exception("PW cannot be installed because, wireshell doesn't support version $tag->name.");
		}

		// switch to required version
		Cmd::run("git checkout", [$version], dirname($this->wireshellPath));

		$wireshellArgs = [
			"new",
			"--sha $tag->sha",
			"--dbHost {$this->config->db->host}",
			"--dbPort {$this->config->db->port}",
			"--dbName {$this->config->db->name}",
			"--dbUser {$this->config->db->user}",
			"--dbPass \"{$this->config->db->pass}\"",
			"--adminUrl admin",
			"--username admin",
			"--userpass admin01",
			"--useremail admin@example.com",
			"--httpHosts localhost",
			"--timezone Europe/Prague"
		];

		array_push($wireshellArgs, $path);

		Cmd::run("php $this->wireshellPath", $wireshellArgs);
	}

	protected function installSelf($path) {
		echo "Installing wireshell ...\n";

		Cmd::run("git clone", [self::$githubUrl, $path]);
		Cmd::run("composer install", [], dirname($this->wireshellPath));
	}

	protected function getRequiredVersion($pwTag) {
		$wireshellVersion = null;

		foreach (self::$pwTagRequiredVersion as $tagRegex => $wireshellVersion) {
			if (preg_match($tagRegex, $pwTag)) {
				return $wireshellVersion;
			}
		}

		return null;
	}

}