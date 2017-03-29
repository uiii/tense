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

namespace Tense\Config;

use Tense\Console\QuestionHelper;
use Tense\Helper\Obj;
use Tense\Helper\Path;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\Yaml\Yaml;

class MissingConfigException extends \RuntimeException {
	protected $filePath;

	public function __construct($configFilePath) {
		$this->filePath = $configFilePath;

		parent::__construct(sprintf(
			"Missing config! Please, create %s config file.",
			$configFilePath
		));
	}

	public function getFilePath() {
		return $this->filePath;
	}
}

class MissingLocalConfigException extends MissingConfigException {}

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

class ConfigLoader {
	protected $config;

	public function load($configFilePath) {
		$config = $this->readConfig($configFilePath);
		$schema = $this->readSchema();

		$config = $this->validate($config, $schema);

		$this->setRuntimeConfigOptions($configFilePath, $config);

		return $config;
	}

	protected function readConfig($configFilePath) {
		$configPathInfo = pathinfo($configFilePath);
		$localConfigFilePath = Path::join(
			$configPathInfo['dirname'],
			sprintf("%slocal.%s",
				basename($configPathInfo['basename'], $configPathInfo['extension']),
				$configPathInfo['extension']
			)
		);

		if (! file_exists($configFilePath)) {
			throw new MissingConfigException($configFilePath);
		}

		if (! file_exists($localConfigFilePath)) {
			throw new MissingLocalConfigException($localConfigFilePath);
		}

		$defaults = json_decode(file_get_contents(Path::join(__DIR__, '..', 'file', 'config', 'defaults.json')));
		$projectConfig = Yaml::parse(file_get_contents($configFilePath), Yaml::PARSE_OBJECT | Yaml::PARSE_OBJECT_FOR_MAP);
		$localConfig = Yaml::parse(file_get_contents($localConfigFilePath), Yaml::PARSE_OBJECT | Yaml::PARSE_OBJECT_FOR_MAP);

		$config = Obj::merge($localConfig, $projectConfig, $defaults);

		return $config;
	}

	protected function readSchema() {
		return json_decode(file_get_contents(Path::join(__DIR__, '..', 'file', 'config', 'schema.json')));
	}

	protected function validate($config, $schema) {
		$validator = new Validator;
		$validator->validate($config, $schema);

		if (! $validator->isValid()) {
			throw new InvalidConfigException($validator->getErrors());
		}

		return $config;
	}

	protected function setRuntimeConfigOptions($configFilePath, &$config) {
		$config->workingDir = dirname($configFilePath);
	}
}