--TEST--
CacheMC::response_manager() with reset()

--SKIPIF--
<?php
	if (!class_exists('Memcache') && !class_exists('Memcached')) print 'skip Neither the Memcached nor Memcache extensions are available';
?>

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachemc.class.php';

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

	$cache = new CacheMC('test', array(
		array('host' => '127.0.0.1', 'port' => 11211)
	), 2);
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
NULL
bool(false)

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachemc.class.php';
	$cache = new CacheMC('test', array(
		array('host' => '127.0.0.1', 'port' => 11211)
	), 60);
	$cache->delete();
?>
