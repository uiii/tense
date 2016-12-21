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

namespace PWTest;

require_once __DIR__ . '/Helper/Path.php';

use Symfony\Component\Yaml\Yaml;
use PWTest\Helper\Path;

class MissingConfigException extends \RuntimeException {
	protected $filePath;

	public function __construct($configFilePath) {
		$this->filePath = $configFilePath;

		parent::__construct(sprintf(
			"Missing config! Please, create %s config file.",
			$configFilePath
		));
	}

	public function filePath() {
		return $this->filePath;
	}
}

class InvalidConfigException extends \RuntimeException {
	protected $errors;

	public function __construct($errors) {
		$this->errors = $errors;

		$errorMessages = [];

		foreach ($errors as $error) {
			$errorMessage = $error['message'];

			if ($error['property']) {
				$errorMessage = sprintf("%s: %s", $error['property'], $errorMessage);
			}

			array_push($errorMessages, $errorMessage);
		}


		$message = sprintf(
			"Configuration file contains errors: %s%s",
			PHP_EOL,
			implode(PHP_EOL, $errorMessages)
		);

		parent::__construct($message);
	}

	public function getErrors() {
		return $this->errors;
	}
}

class Config {
	protected $config;

	public function __construct($configFilePath, $workingDir) {
		$this->config = $this->load($configFilePath, $workingDir);
	}

	public function __get($name) {
		if (isset($this->config->{$name})) {
			return $this->config->{$name};
		}

		throw new \Exception("Configuration key '$name' doesn't exist.");
	}

	public function load($configFilePath, $workingDir) {
		$defaultConfigFilePath = Path::join(__DIR__, "..", "pw-test.yml");

		if (! file_exists($configFilePath)) {
			throw new MissingConfigException($configFilePath);
		}

		$defaultConfig = Yaml::parse(file_get_contents($defaultConfigFilePath));
		$config = Yaml::parse(file_get_contents($configFilePath));

		$config = array_replace_recursive($defaultConfig, (array) $config);

		// convert associative array to object recursively
		$config = $this->arrayToObject($config);

		$this->validate($config, Path::join(__DIR__, 'file', 'config', 'schema.json'));

		$config->workingDir = $workingDir;

		return $config;
	}

	protected function validate($config, $schemaFilePath) {
		$validator = new \JsonSchema\Validator;
		$schema = (object)['$ref' => 'file://' . $schemaFilePath];

		$validator->check($config, $schema);

		if (! $validator->isValid()) {
			throw new InvalidConfigException($validator->getErrors());
		}
	}

	/**
	* Convert associative array to object recursively.
	*
	* @param array
	* @return \stdClass
	*/
	protected function arrayToObject($array) {
		return json_decode(json_encode($array));
	}
}