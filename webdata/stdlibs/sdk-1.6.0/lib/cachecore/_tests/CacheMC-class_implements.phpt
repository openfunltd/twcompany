--TEST--
CacheMC - class_implements

--SKIPIF--
<?php
	if (!class_exists('Memcache')) print 'skip Memcache extension not available';
?>

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachemc.class.php';
	$cache = new CacheMC('test', null, 60);
	var_dump(class_implements($cache));
?>

--EXPECT--
array(1) {
  ["ICacheCore"]=>
  string(10) "ICacheCore"
}
