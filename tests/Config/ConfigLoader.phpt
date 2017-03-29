<?php

require_once __DIR__ . "/../bootstrap.php";

require_once SRC_DIR . "/Helper/Path.php";
require_once SRC_DIR . "/Config/ConfigLoader.php";

use Tester\Assert;

use Tense\Helper\Path;
use Tense\Config\ConfigLoader;

class ConfigLoaderTest extends Tester\TestCase {
	public function testMissing() {
		$loader = new ConfigLoader();

		$e = Assert::exception(function() use($loader) {
			$loader->load(Path::join(FIXTURE_DIR, "missing.yml"));
		}, 'Tense\Config\MissingConfigException');
	}

	public function testMissingLocal() {
		$loader = new ConfigLoader();

		$e = Assert::exception(function() use($loader) {
			$loader->load(Path::join(FIXTURE_DIR, "missing_local.yml"));
		}, 'Tense\Config\MissingLocalConfigException');
	}

	public function testInvalid() {
		$loader = new ConfigLoader();

		$e = Assert::exception(function() use($loader) {
			$loader->load(Path::join(FIXTURE_DIR, "invalid_config.yml"));
		}, 'Tense\Config\InvalidConfigException');

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
		$loader = new ConfigLoader();
		$config = $loader->load(Path::join(FIXTURE_DIR, "valid_partial_config.yml"));
		$expected = json_decode(file_get_contents(Path::join(FIXTURE_DIR, "valid_partial_config.json")));

		foreach ($expected as $key => $value) {
			Assert::equal($config->{$key}, $value);
		}
	}

	public function testValidFull() {
		$loader = new ConfigLoader();
		$config = $loader->load(Path::join(FIXTURE_DIR, "valid_full_config.yml"));
		$expected = json_decode(file_get_contents(Path::join(FIXTURE_DIR, "valid_full_config.json")));

		foreach ($expected as $key => $value) {
			Assert::equal($config->{$key}, $value);
		}
	}
}

$testCase = new ConfigLoaderTest;
$testCase->run();

//Assert::equal($config->tmpDir, ".tense");