<?php

require_once __DIR__ . '/log.php';

class Url {
	public static function get($url, $outputFile = null) {
		Log::debug(sprintf("Downloading URL: %s", $url));
		Log::debug(sprintf("To file: %s", $outputFile));

		$curl = curl_init();
		$outputFileHandle = null;

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'uiii/pw-test');
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		if ($outputFile) {
			$outputFileHandle = fopen($outputFile, 'w+');
			curl_setopt($curl, CURLOPT_FILE, $outputFileHandle); 
		}

		$content = curl_exec($curl);

		curl_close($curl);
		
		if ($outputFile) {
			fclose($outputFileHandle);
		}

		return $content;
	}
}