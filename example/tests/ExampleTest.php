<?php

/**
 * Example ProcessWire PHPUnit test
 *
 * @backupGlobals disabled
 */
class ExampleTest extends PHPUnit\Framework\TestCase {
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
}