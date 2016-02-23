--TEST--
CacheFile::name

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', './cache', 60);
	var_dump($cache->name);
?>

--EXPECT--
string(4) "test"