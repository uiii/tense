<?php

require_once __DIR__ . '/cmd.php';

class Git {
	public static function cloneRepo($repoUrl, $repoPath) {
		Cmd::run("git clone", [$repoUrl, $repoPath]);
	}
	
	public static function checkout($repoPath, $target) {
		Cmd::run("git checkout -f", [$target], ['cwd' => $repoPath]);
	}

	public static function apply($repoPath, $patchPath) {
		Cmd::run("git apply", [$patchPath], ['cwd' => $repoPath]);
	}

	public static function getTags($repo) {
		$repoUrl = "https://github.com/$repo";
		
		$result = Cmd::run("git ls-remote --tags --refs $repoUrl");

		$tags = [];
		
		foreach (array_reverse($result->output) as $line) {
			list($sha, $ref) = explode("\t", $line);

			$tag = new \stdClass;
			$tag->name = explode('/', $ref)[2];
			$tag->sha = $sha;
			$tag->zip = $repoUrl . "/archive/$sha.zip";

			array_push($tags, $tag);
		}

		return $tags;
	}
}