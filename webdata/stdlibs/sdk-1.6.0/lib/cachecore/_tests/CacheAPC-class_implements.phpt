--TEST--
CacheAPC - class_implements

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cacheapc.class.php';
	$cache = new CacheAPC('test', null, 60);
	var_dump(class_implements($cache));
?>

--EXPECT--
array(1) {
  ["ICacheCore"]=>
  string(10) "ICacheCore"
}
