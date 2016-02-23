--TEST--
CacheFile::response_manager() with no/invalid callback params.

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';

	function fetch_data($url)
	{
		$http = curl_init($url);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($http, CURLOPT_BINARYTRANSFER, true);

		if ($output = curl_exec($http))
		{
			return $output;
		}

		return null;
	}

	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 2);
	var_dump($cache->response_manager('fetch_data'));
?>

--EXPECT--
NULL

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	$cache->delete();
?>
