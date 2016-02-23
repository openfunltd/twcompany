--TEST--
CacheXCache::timestamp()

--SKIPIF--
<?php
	if (!function_exists('xcache_set')) print 'skip XCache extension not available';
?>

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachexcache.class.php';
	$cache = new CacheXCache('test', null, 60);
	$cache->create('test data');
	var_dump(gettype($cache->timestamp()));
?>

--EXPECT--
string(7) "boolean"

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachexcache.class.php';
	$cache = new CacheXCache('test', null, 60);
	$cache->delete();
?>
