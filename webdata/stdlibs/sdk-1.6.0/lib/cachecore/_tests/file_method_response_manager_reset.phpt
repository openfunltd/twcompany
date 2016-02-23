--TEST--
CacheFile::response_manager() with reset()

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';

	$time = time();

	function fetch_data($url, $pass)
	{
		$http = curl_init($url);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($http, CURLOPT_BINARYTRANSFER, true);

		if ($output = curl_exec($http))
		{
			if ($pass !== false)
			{
				return $output;
			}
		}

		return null;
	}

	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 2);
	var_dump($cache->response_manager('fetch_data', array('http://github.com/skyzyx/cachecore/raw/master/_tests/test_request.txt', true)));
	$start = $cache->timestamp();
	sleep(3);
	var_dump($cache->response_manager('fetch_data', array('http://github.com/skyzyx/cachecore/raw/master/_tests/test_request.txt', false)));
	$end = $cache->timestamp();
	var_dump($start < $end);
?>

--EXPECT--
string(48) "abcdefghijklmnopqrstuvwxyz
0123456789
!@#$%^&*()"
string(48) "abcdefghijklmnopqrstuvwxyz
0123456789
!@#$%^&*()"
bool(true)

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	$cache->delete();
?>
