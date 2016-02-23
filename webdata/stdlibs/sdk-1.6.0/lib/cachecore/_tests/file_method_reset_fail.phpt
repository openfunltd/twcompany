--TEST--
CacheFile::reset() fail

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', null, 60);
	var_dump($cache->reset());
?>

--EXPECT--
bool(false)
