--TEST--
CacheCore - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	var_dump(class_exists('CacheCore'));
?>

--EXPECT--
bool(true)
