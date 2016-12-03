<?php

require_once __DIR__ . '/log.php';

class Path {
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
			$source, RecursiveDirectoryIterator::SKIP_DOTS);

		$recursiveIterator = new \RecursiveIteratorIterator(
			$directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

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
			$path, RecursiveDirectoryIterator::SKIP_DOTS);

		$recursiveIterator = new \RecursiveIteratorIterator(
			$directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);

		foreach($recursiveIterator as $item) {
			$itemPath = $item->getPathname();

			if ($item->isDir()) {
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