--TEST--
CacheXCache::update() then read()

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
	$cache->update('test data updated');
	var_dump($cache->read());
?>

--EXPECT--
string(17) "test data updated"

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachexcache.class.php';
	$cache = new CacheXCache('test', null, 60);
	$cache->delete();
?>
