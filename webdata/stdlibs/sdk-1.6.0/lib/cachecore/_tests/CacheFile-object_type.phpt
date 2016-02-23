--TEST--
CacheFile - Object type

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', './cache', 60);
	var_dump(get_class($cache));
?>

--EXPECT--
string(9) "CacheFile"
