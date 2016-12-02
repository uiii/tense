<?php

require_once __DIR__ . '/log.php';

class Path {
	public static function isAbsolute($path) {
		return (bool) preg_match("#([a-z]:)?[/\\\\]#Ai", $path);
	}

	public static function join(/* $paths */) {
		$paths = array_map(function ($path) { 
			return trim($path, "\\/"); 
		}, func_get_args());

		return implode(DIRECTORY_SEPARATOR, $paths);
	}
	
	public static function copy($source, $destination) {
		if (is_file($source)) {
			Log::debug(sprintf("Copy file: %s", $source));
			Log::debug(sprintf("To file:   %s", $destination));

			copy($source, $destination);

			return;
		}
		
		Log::debug(sprintf("Create directory: %s", $destination));
		mkdir($destination, 0777, true);

		$directoryIterator = new \RecursiveDirectoryIterator(
			$source, RecursiveDirectoryIterator::SKIP_DOTS);

		$recursiveIterator = new \RecursiveIteratorIterator(
			$directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

		foreach($recursiveIterator as $item) {
			if ($item->isDir()) {
				$directoryPath = self::join($destination, $recursiveIterator->getSubPathName());

				Log::debug(sprintf("Create directory: %s", $directoryPath));
				
				mkdir($directoryPath, 0777);
			} else {
				$sourceFilePath = $item->getPathname();
				$destinationFilePath = self::join($destination, $recursiveIterator->getSubPathName());

				Log::debug(sprintf("Copy file: %s", $sourceFilePath));
				Log::debug(sprintf("To file:   %s", $destinationFilePath));

				copy($sourceFilePath, $destinationFilePath);
			}
		}
	}

	public static function remove($path) {
		if (is_file($path)) {
			Log::debug(sprintf("Remove file: %s", $path));

			unlink($path);

			return;
		}

		$directoryIterator = new \RecursiveDirectoryIterator(
			$path, RecursiveDirectoryIterator::SKIP_DOTS);

		$recursiveIterator = new \RecursiveIteratorIterator(
			$directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);

		foreach($recursiveIterator as $item) {
			$itemPath = $item->getPathname();

			if ($item->isDir()) {
				Log::debug(sprintf("Remove directory: %s", $itemPath));
				
				rmdir($itemPath);
			} else {
				Log::debug(sprintf("Remove file: %s", $itemPath));

				unlink($itemPath);
			}
		}

		Log::debug(sprintf("Remove directory: %s", $path));
		rmdir($path);
	}
}