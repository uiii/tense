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

namespace Tense\Console;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class QuestionHelper {
	protected $inputStream;

	protected $input;
	protected $output;

	protected $formats = [
		'question_format' => "<question>%s</question>",
		'default_format' => " [<question>%s</question>]",
		'choice_format' => " [<question>%s</question>] %s"
	];

	public function __construct(InputInterface $input, OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		if ($this->output instanceof ConsoleOutputInterface) {
			$this->output = $this->output->getErrorOutput();
		}
	}

	/**
	 * Asks a question to the user.
	 *
	 * @param Question        $question The question to ask
	 * @param bool            $inline Prefer question and prompt on the same line
	 * @param array           $formats Default formats overload
	 *
	 * @return string The user answer
	 *
	 * @throws RuntimeException If there is no data to read in the input stream
	 */
	public function ask(Question $question, $formats = [])
	{
		if (! $this->input->isInteractive()) {
			return $question->getDefault();
		}

		if ($this->input instanceof StreamableInputInterface && $stream = $this->input->getStream()) {
			$this->inputStream = $stream;
		}

		if (! $question->getValidator()) {
			return $this->doAsk($this->output, $question, $formats);
		}

		$interviewer = function () use ($question, $formats) {
			return $this->doAsk($this->output, $question, $formats);
		};

		return $this->validateAttempts($interviewer, $this->output, $question);
	}

	/**
	 * Asks the question to the user.
	 *
	 * @param OutputInterface $output
	 * @param Question        $question
	 * @param array           $formats Default formats overload
	 *
	 * @return bool|mixed|null|string
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	protected function doAsk(OutputInterface $output, Question $question, $formats = [])
	{
		$this->writePrompt($output, $question, $formats);

		$inputStream = $this->inputStream ?: STDIN;

		$ret = fgets($inputStream, 4096);

		if (false === $ret) {
			throw new RuntimeException('Aborted');
		}

		$ret = trim($ret);
		$ret = strlen($ret) > 0 ? $ret : $question->getDefault();

		if ($question instanceof ChoiceQuestion) {
			$ret = $this->matchChoice($question, $ret);
		}

		if ($normalizer = $question->getNormalizer()) {
			return $normalizer($ret);
		}

		return $ret;
	}

	/**
	 * Try to match choice by provided value.
	 *
	 * @param ChoiceQuestion $question Choice question to choose from
	 * @param string $input Provided value by user input
	 *
	 * @return mixed Matched choice or the input value if nothich matched
	 *
	 * @throws InvalidArgumentException If the provided value matches more then one choice
	 */
	protected function matchChoice(ChoiceQuestion $question, $input) {
		$choices = $question->getChoices();

		if (isset($choices[$input])) {
			return $input;
		}

		$matchedValues = [];
		foreach ($choices as $key => $value) {
			if (stripos($value, $input) === 0) {
				$input = $key;
				$matchedValues[] = $value;
			}
		}

		if (count($matchedValues) > 1) {
			throw new InvalidArgumentException(sprintf(
				'The provided answer is ambiguous. Value should be one of %s.',
				implode(' or ', $matchedValues)
			));
		}

		return $input;
	}

	/**
	 * Outputs the question prompt.
	 *
	 * @param OutputInterface $output
	 * @param Question        $question
	 * @param array           $formats Formats overloads
	 */
	protected function writePrompt(OutputInterface $output, Question $question, $formats = [])
	{
		$formats = array_replace($this->formats, $formats);

		$output->write(sprintf($formats['question_format'], $question->getQuestion()));

		$default = $question->getDefault();
		if (! is_null($default)) {
			if ($question instanceof ConfirmationQuestion) {
				$default = $default ? "yes" : "no";
			}

			$output->write(sprintf($formats['default_format'], $default));
		}

		if ($question instanceof ChoiceQuestion) {
			$messages = [];
			foreach ($question->getChoices() as $key => $value) {
				$messages[] = sprintf($formats['choice_format'], $key, substr($value, 0));
			}

			$output->writeln("");
			$output->writeln("");
			$output->writeln(implode(PHP_EOL, $messages));
			$output->writeln("");
			$output->write("> ");
		} else {
			$output->write(": ");
		}
	}

	/**
	 * Validates an attempt.
	 *
	 * @param callable        $interviewer A callable that will ask for a question and return the result
	 * @param OutputInterface $output      An Output instance
	 * @param Question        $question    A Question instance
	 *
	 * @return string The validated response
	 *
	 * @throws \Exception In case the max number of attempts has been reached and no valid response has been given
	 */
	protected function validateAttempts(callable $interviewer, OutputInterface $output, Question $question)
	{
		$error = null;
		$attempts = $question->getMaxAttempts();
		while (null === $attempts || $attempts--) {
			if (null !== $error) {
				$output->writeln(sprintf("<error>%s</error>", $error->getMessage()));
			}

			try {
				return call_user_func($question->getValidator(), $interviewer());
			} catch (RuntimeException $e) {
				throw $e;
			} catch (\Exception $error) {
			}
		}

		throw $error;
	}
}