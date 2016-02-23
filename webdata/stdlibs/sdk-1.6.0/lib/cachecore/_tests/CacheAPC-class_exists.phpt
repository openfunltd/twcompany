--TEST--
CacheAPC - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cacheapc.class.php';
	var_dump(class_exists('CacheAPC'));
?>

--EXPECT--
bool(true)
