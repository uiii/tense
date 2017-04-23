# Tense Example

In [example](https://github.com/uiii/tense/tree/master/example) directory you can find an example test suite.

## Test suite

> For testing is used `PHPUnit` testing framework (https://phpunit.de).

Tests are very simple. First two tests a presence of a files and directories which are created by a before script. Third and fourth tests `HelloWorld` module which is distributed with each `ProcessWire` version.
Last test is designed to fail on `ProcessWire 2.5`.

**tests/ExampleTest.php**
```php
public static function setUpBeforeClass() {
	$modules = wire('modules');

	$modules->install('Helloworld');
	$modules->triggerInit();
}

public function testTestFile() {
	$this->assertFileExists(getenv('PW_PATH') . '/testfile');
}

public function testLogInstalled() {
	$this->assertDirectoryExists(getenv('PW_PATH') . '/vendor/psr/log');
}

public function testHelloworldInstalled() {
	$this->assertTrue(wire('modules')->isInstalled('Helloworld'));
}

public function testHelloworldPageHook() {
	$home = wire('pages')->get('/');
	$this->assertEquals("Hello World", $home->hello());
}

public function testVersion() {
	// this will fail on PW 2.5
	$this->assertRegExp("/^(?!2.5)/", wire('config')->version);
}
```

## Tense configuration

Tense is configured to test `ProcessWire` versions `2.5`, `2.7` and `3.0`. In this case no files are copied into ProcessWire installation. There is also specified a before script `tests/before.php` which creates a test file and installs test composer dependency.

**tense.yml**
```yaml
testTags:
    - "2.5"
    - "2.7"
    - "3.0"

beforeCmd: "php tests/before.php"

testCmd: "../vendor/bin/phpunit --bootstrap tests/bootstrap.php --colors tests/ExampleTest.php"
```

## How to run

1. In `Tense` **root** directory run:

	```
	composer install
	```

2. In `example` directory run:

	```
	php ../tense run
	```

## Output

Output may look like this:

[![video](asciicast.gif)](https://asciinema.org/a/109559)