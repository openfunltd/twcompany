--TEST--
CacheAPC::delete() and update()

--SKIPIF--
<?php
	if (!function_exists('apc_add')) print 'skip APC extension not available';
?>

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cacheapc.class.php';
	$cache = new CacheAPC('test', null, 60);
	var_dump($cache->create('test data'));
	var_dump($cache->delete());
	var_dump($cache->update('test data updated'));
	var_dump($cache->read());
?>

--EXPECT--
bool(true)
bool(true)
bool(true)
string(17) "test data updated"
