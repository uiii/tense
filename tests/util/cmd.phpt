<?php

require_once __DIR__ . "/../bootstrap.php";

require_once SRC_DIR . "/util/cmd.php";

use Tester\Assert;

Assert::equal(Cmd::getEnv(), $_ENV);

if (preg_match("/WIN/Ai", PHP_OS)) {
	Assert::equal(Cmd::run("cd")->output[0], __DIR__);	
	
	$cwd = realpath(__DIR__ . '/../');
	Assert::equal(Cmd::run("cd", [], ['cwd' => $cwd])->output[0], $cwd);	

	$env = ["some_var" => "some_value"];
	Assert::equal(trim(Cmd::run("echo", ["%some_var%"], ['env' => $env])->output[0]), $env["some_var"]);	

	Assert::match("#^PHP#", Cmd::run('php --version')->output[0]);

	# run php (or other executable from PATH) with modified environment
	Assert::match("#^PHP#", Cmd::run('php --version', [], ['env' => []])->output[0]);

	# no linebreak at the end
	Assert::equal(trim(Cmd::run("echo|set /p=neco", [], ['throw_on_error' => false])->output[0]), "neco");	
}

//Assert::fail();