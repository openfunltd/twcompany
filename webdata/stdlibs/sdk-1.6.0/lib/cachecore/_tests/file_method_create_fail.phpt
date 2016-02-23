--TEST--
CacheFile::create() fail

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', null, 60);
	var_dump($cache->create('test data'));
?>

--EXPECT--
bool(false)
