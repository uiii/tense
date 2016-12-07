<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once getenv('PW_PATH') . '/index.php';

if (function_exists("\\ProcessWire\\wire")) {
	// ProcessWire 3.x - 'wire'' function is
	// in a namespace so define a wrapper.

	function wire() {
		return call_user_func_array("\\ProcessWire\\wire", func_get_args());
	}
}