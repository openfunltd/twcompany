--TEST--
CacheFile::delete() and update()

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	var_dump($cache->create('test data'));
	var_dump($cache->delete());
	var_dump($cache->update('test data updated'));
	var_dump($cache->read());
?>

--EXPECT--
bool(true)
bool(true)
bool(false)
bool(false)