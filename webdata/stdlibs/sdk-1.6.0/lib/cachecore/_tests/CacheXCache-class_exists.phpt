--TEST--
CacheXCache - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachexcache.class.php';
	var_dump(class_exists('CacheXCache'));
?>

--EXPECT--
bool(true)
