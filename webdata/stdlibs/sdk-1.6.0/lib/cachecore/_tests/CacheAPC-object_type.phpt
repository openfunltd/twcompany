--TEST--
CacheAPC - Object type

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cacheapc.class.php';
	$cache = new CacheAPC('test', null, 60);
	var_dump(get_class($cache));
?>

--EXPECT--
string(8) "CacheAPC"
