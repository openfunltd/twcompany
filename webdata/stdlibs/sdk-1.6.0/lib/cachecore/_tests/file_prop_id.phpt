--TEST--
CacheFile::id

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', './cache', 60);
	var_dump($cache->id);
?>

--EXPECT--
string(18) "./cache/test.cache"
