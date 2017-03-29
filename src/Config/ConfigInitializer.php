<?php

namespace Tense\Config;

use Tense\Console\QuestionHelper;
use Tense\Helper\Obj;
use Tense\Helper\Path;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

class ConfigInitializer {
	protected $output;
	protected $questionHelper;

	protected $indent = 0;

	public function __construct(OutputInterface $output, QuestionHelper $questionHelper) {
		$this->output = $output;
		$this->questionHelper = $questionHelper;
	}

	/**
	* @param $context Which context to initialize (project, local)
	*/
	public function initialize($configFilePath, $context = 'project') {
		$configFilePath = Path::join(getcwd(), $configFilePath);

		if (file_exists($configFilePath)) {
			$this->output->writeln(sprintf("<warning>File %s already exists.</warning>", $configFilePath));
			$question = new ConfirmationQuestion("Do you want to overwrite it?", false);
			$answer = $this->questionHelper->ask($question);

			if (! $answer) {
				return;
			}

			$this->output->writeln("");
		}

		return $this->doInitialize($configFilePath, $context);
	}

	protected function doInitialize($configFilePath, $context) {
		$this->output->writeln("<comment>Now, the guide will ask you to set the configuration properties.</comment>");
		$this->output->writeln("");

		$schema = $this->loadSchema($context);
		$defaults = $this->loadDefaults();

		$config = $this->getObject(null, null, $schema, $defaults);
		$yaml = Yaml::dump($config, 2, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

		$this->output->writeln("<heading>:: Generated config ::</heading>");
		$this->output->writeln("");

		$this->output->writeln(preg_replace("/(^|\n(?!$))/", "$1<comment>▌</comment> ", $yaml));

		$this->output->writeln(sprintf("This configuration will be written to a file %s.", $configFilePath));
		$question = new ConfirmationQuestion("Do you agree?", true);
		$answer = $this->questionHelper->ask($question);

		if ($answer) {
			file_put_contents($configFilePath, $yaml);
			return true;
		}

		$question = new ConfirmationQuestion("Do you want to repeat initialization?", true);
		$answer = $this->questionHelper->ask($question);

		if ($answer) {
			$this->output->writeln("");
			return $this->doInitialize($configFilePath, $context);
		}

		return false;
	}

	protected function loadSchema($context) {
		$schema = json_decode(file_get_contents(Path::join(__DIR__, '..', 'file', 'config', 'schema.json')));

		$propertiesContext = $this->loadPropertiesContext();

		$schema->required = $propertiesContext->{$context}->required;
		$schema->optional = $propertiesContext->{$context}->optional;

		return $schema;
	}

	protected function loadPropertiesContext() {
		return json_decode(file_get_contents(Path::join(__DIR__, '..', 'file', 'config', 'context.json')));
	}

	protected function loadDefaults() {
		$globalDefaults = json_decode(file_get_contents(Path::join(__DIR__, '..', 'file', 'config', 'defaults.json')));
		$initializerDefaults = json_decode(file_get_contents(Path::join(__DIR__, '..', 'file', 'config', 'initializer_defaults.json')));

		return Obj::merge($initializerDefaults, $globalDefaults);
	}

	protected function getString($label, $parentSchema, $schema, $default) {
		$enum = Obj::get($schema, 'enum');

		$question = $enum
			? new ChoiceQuestion($label, $enum, $default)
			: new Question($label, $default);

		$answer = $this->questionHelper->ask($question, ['question_format' => '%s']);

		return $answer ?: "";
	}

	protected function getNumber($label, $parentSchema, $schema, $default) {
		$answer = $this->getString($label, $parentSchema, $schema, $default);

		switch ($schema->type) {
			case 'integer':
				return intval($answer);
			case 'number':
				return floatval($answer);
			default:
				return null;
		}
	}

	protected function getValue($label, $parentSchema, $schema, $default) {
		$schema = $this->normalizeSchema($schema);
		$type = Obj::get($schema, 'type');

		switch ($schema->type) {
			case 'string':
				return $this->getString($label, $parentSchema, $schema, $default);
			case 'number':
			case 'integer':
				return $this->getNumber($label, $parentSchema, $schema, $default);
			case 'object':
				return $this->getObject($label, $parentSchema, $schema, $default);
			case 'array':
			case 'array-or-single':
				return $this->getArray($label, $parentSchema, $schema, $default);
			default:
				return null;
		}
	}

	protected function getArray($label, $parentSchema, $schema, $default) {
		$array = [];

		$index = 0;
		$value = null;

		$this->output->writeln(sprintf("%s:", $label));

		++$this->indent;

		while (true) {
			if ($index > Obj::get($schema, 'minItems', 0) - 1) {
				if ($index > 0) {
					$this->output->writeln("");
				}

				$question = new ConfirmationQuestion($this->indent("Add item?"));
				$answer = $this->questionHelper->ask($question);

				if (! $answer) {
					break;
				}
			}

			$value = $this->getValue($this->indent(sprintf("%s. item", $index + 1)), $schema, $schema->items, null);
			array_push($array, $value);

			++$index;
		}

		--$this->indent;

		if ($schema->type === 'array-or-single' && count($array) === 1) {
			return $array[0];
		}

		return $array;
	}

	protected function getObject($label, $parentSchema, $schema, $defaults) {
		$object = [];

		if ($label) {
			$this->output->writeln(sprintf("%s:", $label));
			++$this->indent;
		}

		$previousSchema = null;
		$currentOrPreviousIsBlock = false;

		foreach (Obj::get($schema, 'required', []) as $property) {
			$propertySchema = $this->normalizeSchema($schema->properties->{$property});
			$propertyDefault = Obj::get($defaults, $property);

			$notFirstProperty = ! is_null($previousSchema);
			$currentOrPreviousIsBlock = $notFirstProperty && ! empty(array_intersect(
				['object', 'array', 'array-or-single'],
				[$previousSchema->type, $propertySchema->type]
			));

			if ($currentOrPreviousIsBlock) {
				$this->output->writeln("");
			}

			$object[$property] = $this->getValue($this->indent($property), $schema, $propertySchema, $propertyDefault);

			$previousSchema = $propertySchema;
		}

		$optionalProperties = Obj::get($schema, 'optional', []);
		$propertiesToAsk = $optionalProperties;

		while (! empty($propertiesToAsk)) {
			$this->output->writeln("");

			$choices = $propertiesToAsk;
			$choices['n'] = "no";

			$question = new ChoiceQuestion($this->indent("Do you want to set other optional property?"), $choices, $choices['n']);
			$answer = $this->questionHelper->ask($question);

			if ($answer === 'n') {
				break;
			}

			$this->output->writeln("");

			$property = $propertiesToAsk[$answer];
			unset($propertiesToAsk[$answer]);

			$propertySchema = $this->normalizeSchema($schema->properties->{$property});
			$propertyDefault = Obj::get($defaults, $property);
			$object[$property] = $this->getValue($this->indent($property), $schema, $propertySchema, $propertyDefault);
		}

		if ($currentOrPreviousIsBlock || ! empty($optionalProperties)) {
			$this->output->writeln("");
		}

		if ($label) {
			--$this->indent;
		}

		return $object;
	}

	protected function normalizeSchema($schema) {
		$oneOf = Obj::get($schema, 'oneOf');

		if ($oneOf) {
			$itemType = null;

			foreach ($oneOf as $oneOfSchema) {
				if ($oneOfSchema->type === 'array') {
					$schema = $oneOfSchema;
					$schema->type = 'array-or-single';

					$oneOfSchema = $oneOfSchema->items;
				}

				if (! $itemType) {
					$itemType = $oneOfSchema->type;
					continue;
				}

				if ($itemType !== $oneOfSchema->type) {
					return null;
				}
			}
		}

		return $schema;
	}

	protected function indent($str) {
		return str_repeat("    ", $this->indent) . $str;
	}
}