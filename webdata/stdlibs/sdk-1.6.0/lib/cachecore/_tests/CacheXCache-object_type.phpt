--TEST--
CacheXCache - Object type

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachexcache.class.php';
	$cache = new CacheXCache('test', null, 60);
	var_dump(get_class($cache));
?>

--EXPECT--
string(11) "CacheXCache"
