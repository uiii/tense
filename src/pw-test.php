<?php

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/pw_installer.php';

$cwd = getcwd();

class PWTest
{
	public static $pwGithubRepos = [
		'processwire/processwire',
		'processwire/processwire-legacy',
		'ryancramerdesign/ProcessWire'
	];

	protected $availableTags = [];

	protected $pwInstaller;

	public function __construct($configFile) {
		$this->config = $this->loadConfig($configFile);
		$this->pwInstaller = new PWInstaller($this->config);
		$this->initPWTags();
	}

	public function run() {
		$this->runTests($this->config);
	}

	protected function loadConfig($configFile) {
		if (! file_exists($configFile)) {
			echo "[ERROR] Missing config! Please, create pw-test.json config file.";
			die();
		}

		$config = json_decode(file_get_contents($configFile));

		// absolutize tmpDir
		if (! Path::isAbsolute($config->tmpDir)) {
			// make the tmpDir relative to the directory where the config file is stored
			$config->tmpDir = Path::join(dirname($configFile), $config->tmpDir);
		}

		return $config;
	}

	protected function runTests($config) {
		foreach($config->testTags as $testTagName) {
			echo "\n::Testing agains PW $testTagName\n";

			$availableTag = $this->getLatestAvailableMatchingTag($testTagName);

			if (! $availableTag) {
				echo "[ERROR] No matching PW tag to '$testTagName' found. Skipping ...\n";
				continue;
			}

			echo "Using latest matching PW version: {$availableTag->name}\n";

			$pwPath = Path::join($config->tmpDir, "pw-{$availableTag->sha}");

			try {
				$this->pwInstaller->install($availableTag, $pwPath);
			} catch (\Exception $e) {
				echo sprintf("[ERROR] PW %s cannot be installed: %s\n", $availableTag->name, $e->getMessage());
			}

			/*chmod 777 -R "${PW_PATH}"

			MODULE_DIR="${PW_PATH}/site/modules/ProcessWire-FieldtypePDF"
			mkdir "${MODULE_DIR}"
			cp -r FieldtypePDF "${MODULE_DIR}"
			cp FieldtypePDF.module "${MODULE_DIR}"
			cp InputfieldPDF.module "${MODULE_DIR}"
			cp InputfieldPDF.css "${MODULE_DIR}*/
		}
	}
	
	protected function getLatestAvailableMatchingTag($tagName) {
		foreach ($this->availableTags as $availableTag) {
			if ($tagName === $availableTag->name || strpos($availableTag->name, $tagName . '.') === 0) {
				// available tag's name equals or starts with the tag's name
				return $availableTag;
			}
		}

		return null;
	}

	protected function initPWTags() {
		foreach (self::$pwGithubRepos as $pwRepo) {
			$tags = $this->getTags($pwRepo);

			$this->availableTags = array_merge($this->availableTags, $tags);
		}

		/*var_dump(array_map(
			function ($tag) { return $tag->name . " : " . $tag->sha; }, 
			$this->availableTags
		));*/
	}

	protected function getUrl($url) {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'uiii/pw-test');

		$content = curl_exec($curl);

		curl_close($curl);

		return $content;
	}

	protected function getTags($repo) {
		$tags = [];

		$result = Cmd::run("git ls-remote --tags --refs https://github.com/$repo");

		foreach (array_reverse($result->output) as $line) {
			list($sha, $ref) = explode("\t", $line);

			$tag = new \stdClass;
			$tag->name = explode('/', $ref)[2];
			$tag->sha = $sha;

			array_push($tags, $tag);
		}

		return $tags;
	}
}


$configFile = $cwd . '/pw-test.json';

$pwTest = new PWTest($configFile);
$pwTest->run();

//file_get_contents("https://api.github.com/repos/")


/*HASH_LIST="$(git ls-remote --tags "$PW_REPO" | sed -e 's/	/;/')"

WIRESHELL_CMD="${CWD}/../vendor/bin/wireshell"
PHPUNIT_CMD="${CWD}/../vendor/bin/phpunit"

install_pw() {
	wireshell_params="$@"

	echo "create database ${DB_NAME}" | mysql -h "${DB_HOST}" -P ${DB_PORT} -u ${DB_USER} -p"${DB_PASS}" || return 1

	export PW_PATH="${CWD}/../test/.tmp/pw-${final_tag}"

	echo "Installing ProcessWire"
	${WIRESHELL_CMD} new ${wireshell_params} \
		--dbHost ${DB_HOST} --dbPort ${DB_PORT} --dbName ${DB_NAME} --dbUser ${DB_USER} --dbPass "${DB_PASS}" \
		--adminUrl admin --username admin --userpass admin01 --useremail admin@example.com \
		--httpHosts localhost --timezone Europe/Prague \
		"${PW_PATH}" > /dev/null 2>&1

	chmod 777 -R "${PW_PATH}"

	MODULE_DIR="${PW_PATH}/site/modules/ProcessWire-FieldtypePDF"
	mkdir "${MODULE_DIR}"
	cp -r FieldtypePDF "${MODULE_DIR}"
	cp FieldtypePDF.module "${MODULE_DIR}"
	cp InputfieldPDF.module "${MODULE_DIR}"
	cp InputfieldPDF.css "${MODULE_DIR}"
}

uninstall_pw() {
	rm -rf "${CWD}/../test/.tmp"
	echo "drop database if exists ${DB_NAME}" | mysql -h "${DB_HOST}" -P ${DB_PORT} -u ${DB_USER} -p"${DB_PASS}"
}

test_pw() {
	install_params="$@"

	install_pw ${install_params} && ${PHPUNIT_CMD}
	uninstall_pw
}

for tag in ${TEST_TAGS[*]}; do
	for hash_line in ${HASH_LIST}; do
		current_hash=$(echo "$hash_line" | cut -f1 -d";")
		current_tag=$(echo "$hash_line" | cut -f2 -d";" | sed -e 's/refs\/tags\///')
		if [[ ${current_tag} == ${tag}* ]]; then
			final_hash=${current_hash}
			final_tag=${current_tag}
		fi
	done

	echo "Test against PW ${final_tag}"
	test_pw --sha ${final_hash}
done

if [ "${TEST_MASTER}" -eq "1" ]; then
	echo "Test against PW master"
	test_pw
fi
*/