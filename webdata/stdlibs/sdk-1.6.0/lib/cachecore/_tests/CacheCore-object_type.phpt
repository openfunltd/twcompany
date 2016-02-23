--TEST--
CacheCore - Object type

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	$cache = new CacheCore(null, null, null);
	var_dump(get_class($cache));
?>

--EXPECT--
string(9) "CacheCore"
