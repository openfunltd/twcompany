--TEST--
CacheFile::delete() and read()

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	var_dump($cache->create('test data'));
	var_dump($cache->read());
	var_dump($cache->delete());
	var_dump($cache->read());
?>

--EXPECT--
bool(true)
string(9) "test data"
bool(true)
bool(false)
