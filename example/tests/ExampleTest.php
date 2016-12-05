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