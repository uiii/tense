<?php

require_once __DIR__ . "/bootstrap.php";

require_once SRC_DIR . "/Helper/Path.php";
require_once SRC_DIR . "/Config.php";

use Tester\Assert;
use PWTest\Helper\Path;
use PWTest\Config;

class ConfigTest extends Tester\TestCase {
	public function testNonExisting() {
		$e = Assert::exception(function() {
			new Config(Path::join(FIXTURE_DIR, "non-existing.yml"), FIXTURE_DIR);
		}, 'PWTest\MissingConfigException');
	}

	public function testInvalid() {
		$e = Assert::exception(function() {
			new Config(Path::join(FIXTURE_DIR, "invalid_config.yml"), FIXTURE_DIR);
		}, 'PWTest\InvalidConfigException');

		$invalidProperties = array_map(function($error) {
			return $error['property'];
		}, $e->getErrors());

		Assert::equal($invalidProperties, [
			"tmpDir",
			"db.host", "db.port", "db.user", "db.pass", "db.name",
			"testTags[0]",
			"testTags[1]",
			"copySources[0]",
			"copySources[1]",
			"copySources[2].source",
			"copySources[3].destination",
			"waitAfterTests"
		]);
	}

	public function testValidPartial() {
		$config = new Config(Path::join(FIXTURE_DIR, "valid_partial_config.yml"), FIXTURE_DIR);
		$expected = json_decode(file_get_contents(Path::join(FIXTURE_DIR, "valid_partial_config.json")));

		foreach ($expected as $key => $value) {
			Assert::equal($config->{$key}, $value);
		}
	}

	public function testValidFull() {
		$config = new Config(Path::join(FIXTURE_DIR, "valid_full_config.yml"), FIXTURE_DIR);
		$expected = json_decode(file_get_contents(Path::join(FIXTURE_DIR, "valid_full_config.json")));

		foreach ($expected as $key => $value) {
			Assert::equal($config->{$key}, $value);
		}
	}
}

$testCase = new ConfigTest;
$testCase->run();

//Assert::equal($config->tmpDir, ".pw-test");