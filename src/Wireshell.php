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
use Tense\Helper\Url;
use Tense\Helper\Cmd;
use Tense\Helper\CmdException;

use Tense\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

class WireshellException extends \RuntimeException {}

class Wireshell {
	public static $githubUrl = "https://github.com/wireshell/wireshell";

	protected $wireshellVersions = [
		"2f62313" => ["supportsPW" => ["/^3/"]],
		"0.6.0" => [
			"supportsPW" => ["/^2.[4-7](\..*)?$/"],
			// 0.6.0 version needs to be patched
			// to be able to install from pre-downloaded zip
			// and to accept empty db password
			"needsPatch" => "0.6.0.patch"
		]
	];

	protected $output;

	public function __construct(OutputInterface $output) {
		$this->output = $output;
	}

	public function installProcessWire($tag, $installPath, $config) {
		$version = $this->getRequiredVersion($tag->name);

		if (! $version) {
			throw new \RuntimeException("Wireshell (needed for ProcessWire installation) doesn't support version $tag->name.");
		}

		$tmpDir = Path::join($config->workingDir, $config->tmpDir);

		// install required wireshell version if not installed
		$wireshellPath = $this->installSelf($version, $tmpDir);

		// download PW source if missing
		$pwZipPath = $this->downloadProcessWire($tag, $tmpDir);

		// install PW
		$this->output->write("<info>Installing ProcessWire $tag->name ... </info>", false, Output::MESSAGE_TEMPORARY);

		$wireshellArgs = [
			"new",
			"--src $pwZipPath",
			"--dbHost {$config->db->host}",
			"--dbPort {$config->db->port}",
			"--dbName {$config->db->name}",
			"--dbUser {$config->db->user}",
			"--dbPass \"{$config->db->pass}\"",
			"--username admin",
			"--userpass admin01",
			"--useremail admin@example.com",
			"--httpHosts localhost",
			"--timezone Europe/Prague",
			"--chmodDir 777",
			"--chmodFile 666",
			"--no-ansi",
			$installPath
		];

		try {
			$result = Cmd::run("php", array_merge([$wireshellPath], $wireshellArgs));
			$this->checkErrorsInCmdResult($result);
		} catch (CmdException $e) {
			throw new WireshellException($this->getWireshellErrorMessage($e->getMessage()));
		}

		return $installPath;
	}

	protected function downloadProcessWire($tag, $dir) {
		$zipPath = Path::join($dir, "pw-{$tag->name}.zip");

		if (! file_exists($zipPath)) {
			$this->output->write("<info>Downloading ProcessWire $tag->name ... </info>", false, Output::MESSAGE_TEMPORARY);

			Url::get($tag->zip, $zipPath);
		}

		return $zipPath;
	}

	protected function installSelf($version, $installDir) {
		$installPath = Path::join($installDir, "wireshell-$version");

		if (! file_exists($installPath)) {
			$this->output->write("<info>Downloading wireshell $version ... </info>", false, Output::MESSAGE_TEMPORARY);

			// clone repo
			Git::cloneRepo(self::$githubUrl, $installPath);

			$this->output->write("<info>Installing wireshell $version ... </info>", false, Output::MESSAGE_TEMPORARY);

			// switch to required version
			Git::checkout($installPath, $version);

			// apply patch if needed
			$versionInfo = $this->wireshellVersions[$version];
			$needsPatch = array_key_exists("needsPatch", $versionInfo) ? $versionInfo["needsPatch"] : null;
			if ($needsPatch) {
				$patchPath = Path::join(__DIR__, "file/wireshell_$needsPatch");
				Git::apply($installPath, $patchPath);
			}
		}

		// install dependencies (runs always because might fail during installation)
		Cmd::run("composer install", [], ['cwd' => $installPath]);

		// return path to wireshell executable
		return Path::join($installPath, "wireshell");
	}

	protected function getRequiredVersion($pwTag) {
		foreach ($this->wireshellVersions as $wireshellVersion => $info) {
			foreach ($info['supportsPW'] as $tagRegex) {
				if (preg_match($tagRegex, $pwTag)) {
					return $wireshellVersion;
				}
			}
		}

		return null;
	}

	/**
	* HACK: wireshell doesn't always stop on error but print it
	*/
	protected function checkErrorsInCmdResult($result) {
		$errors = preg_grep("/ERROR/", $result->output);

		if ($errors) {
			$message = array_map(function ($error) {
				return preg_replace("/^.*ERROR: (.*) \[\] \[\]$/", "$1", $error);
			}, $errors);

			$result->errorCode = 1;
			$result->output = $message;

			throw new CmdException($result);
		}
	}

	/**
	* Process wireshell error output.
	* Extracts error message if possible.
	*/
	protected function getWireshellErrorMessage($wireshellOutput) {
		$message = $wireshellOutput;

		return $message;

		// convert wireshell exception output to error message
		$match = null;
		if (preg_match("/\[[A-Za-z]+Exception\](.+)\R\R/s", $message, $match)) {
			// Remove '[*Exception]' info
			//$message = preg_replace("/^\s+\[[A-Za-z]+Exception\]/", "", $message);
			//$message = trim($message);

			$message = $match[1];

			// Remove command usage info (separated from exception by multiple empty lines)
			$message = substr($message, 0, strpos($message, PHP_EOL . PHP_EOL));
			$message = trim($message);

			// trim lines
			$message = implode(PHP_EOL, array_map(function ($line) {
				return trim($line);
			}, explode(PHP_EOL, $message)));
		}

		return $message;
	}
}