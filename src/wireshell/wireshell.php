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

require_once __DIR__ . '/../util/log.php';
require_once __DIR__ . '/../util/path.php';
require_once __DIR__ . '/../util/git.php';
require_once __DIR__ . '/../util/url.php';
require_once __DIR__ . '/../util/cmd.php';

class Wireshell {
	public static $githubUrl = "https://github.com/wireshell/wireshell";

	protected static $wireshellVersions = [
		"2f62313" => ["supportsPW" => ["/^3/"]],
		"0.6.0" => [
			"supportsPW" => ["/^2.[4-7](\..*)?$/"],
			// 0.6.0 version needs to be patched
			// to be able to install from pre-downloaded zip
			// and to accept empty db password
			"needsPatch" => "0.6.0.patch"
		]
	];

	protected $wireshellPath;
	protected $config;

	public function __construct($config) {
		$this->config = $config;
	}

	public function installProcessWire($tag, $installPath) {
		$version = $this->getRequiredVersion($tag->name);

		if (! $version) {
			throw new \RuntimeException("Wireshell (needed for ProcessWire installation) doesn't support version $tag->name.");
		}

		// install required wireshell version if not installed
		$wireshellPath = $this->installSelf($version);

		// download PW source if missing
		$pwZipPath = $this->downloadProcessWire($tag);

		// install PW
		Log::info("Installing ProcessWire $tag->name ...");

		$wireshellArgs = [
			"new",
			"--src $pwZipPath",
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
			"--timezone Europe/Prague",
			"--chmodDir 777",
			"--chmodFile 666",
			$installPath
		];

		$result = Cmd::run("php $wireshellPath", $wireshellArgs);

		// HACK: wireshell doesn't stop on error but print it
		$errors = preg_grep("/ERROR/", $result->output);
		if ($errors) {
			$result->exitCode = 1;
			$result->output = array_map(function ($error) {
				return preg_replace("/^.*ERROR: (.*) \[\] \[\]$/", "$1", $error);
			}, $errors);
			throw new CmdException($result);
		}

		return $installPath;
	}

	protected function downloadProcessWire($tag) {
		$zipPath = Path::join($this->config->tmpDir, "pw-{$tag->name}.zip");

		if (! file_exists($zipPath)) {
			Log::info("Downloading ProcessWire $tag->name ...");

			Url::get($tag->zip, $zipPath);
		}

		return $zipPath;
	}

	protected function installSelf($version) {
		$installPath = Path::join($this->config->tmpDir, "wireshell-$version");

		if (! file_exists($installPath)) {
			Log::info("Downloading wireshell $version ...");

			// clone repo
			Git::cloneRepo(self::$githubUrl, $installPath);

			Log::info("Installing wireshell $version ...");

			// switch to required version
			Git::checkout($installPath, $version);

			// apply patch if needed
			$versionInfo = self::$wireshellVersions[$version];
			$needsPatch = array_key_exists("needsPatch", $versionInfo) ? $versionInfo["needsPatch"] : null;
			if ($needsPatch) {
				$patchPath = Path::join(__DIR__, $needsPatch);
				Git::apply($installPath, $patchPath);
			}
		}

		// install dependencies (runs always because might fail during installation)
		Cmd::run("composer install", [], ['cwd' => $installPath]);

		// return path to wireshell executable
		return Path::join($installPath, "wireshell");
	}

	protected function getRequiredVersion($pwTag) {
		foreach (self::$wireshellVersions as $wireshellVersion => $info) {
			foreach ($info['supportsPW'] as $tagRegex) {
				if (preg_match($tagRegex, $pwTag)) {
					return $wireshellVersion;
				}
			}
		}

		return null;
	}

}