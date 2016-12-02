<?php

class Log {
	/**
	* Log level
	*
	* 0 - quiet (no output)
	* 1 - errors only
	* 2 - errors & warnings
	* 3 - errors & warnings & info (default)
	* 4 - debug (all)
	**/
	public static $level = 3;

	public static function error($message) {
		self::write(1, $message, "[ERROR]");
	}	

	public static function warn($message) {
		self::write(2, $message, "[WARNING]");
	}	

	public static function info($message) {
		self::write(3, $message);
	}

	public static function debug($message) {
		self::write(4, $message, "[DEBUG]");
	}	
	
	protected static function write($minLevel, $message, $prefix = "") {
		if (self::$level < $minLevel) {
			return;
		}

		if ($prefix) {
			$prefix .= " ";
		}

		echo sprintf("%s%s\n", $prefix, $message);
	}
}