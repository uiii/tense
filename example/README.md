# PW-Test Example

In [example](https://github.com/uiii/pw-test/tree/master/example) directory you can find an example test suite.

## Test suite

> For testing is used `PHPUnit` testing framework (https://phpunit.de).

Tests are very simple. First two tests `HelloWorld` module which is distributed with each `ProcessWire` version.
Last test is designed to fail on `ProcessWire 2.5`.

**tests/ExampleTest.php**
```php
class ExampleTest extends PHPUnit\Framework\TestCase {
	public static function setUpBeforeClass() {
		$modules = wire('modules');

		$modules->install('Helloworld');
		$modules->triggerInit();
	}

	public function testVersion() {
		// this will fail on PW 2.5
		$this->assertRegExp("/^(?!2.5)/", wire('config')->version);
	}

	public function testInstalled() {
		$this->assertTrue(wire('modules')->isInstalled('Helloworld'));
	}

	public function testPageHook() {
		$home = wire('pages')->get('/');
		$this->assertEquals("Hello World", $home->hello());
	}
}
```

## PW-Test configuration

PW-Test is configured to test `ProcessWire` versions `2.5`, `2.7` and `3.0`. In this case no files are copied into ProcessWire installation.

**pw-test.json**
```json
{
	"tmpDir": ".pw-test",
	"db": {
		"host": "localhost",
		"port": 3306,
		"user": "root",
		"pass": "",
		"name": "pw_test"
	},
	"testTags": ["2.5", "2.7", "3.0"],
	"copySources": {},
	"testCmd": "vendor/bin/phpunit --bootstrap tests/bootstrap.php --colors tests/ExampleTest.php",
	"waitAfterTests": "never"
}
```

## How to run

1. Look at the `db` section in `pw-test.json` configuration and update it if necessary.
2. Make sure MySQL is running and corresponds with the configuration.
3. In `PW-Test` **root** directory run:
```
composer install
```
4. In `example` directory run:
```
php ../bin/pw-test
```

## Output

Output may look like this:

![output](video.gif)