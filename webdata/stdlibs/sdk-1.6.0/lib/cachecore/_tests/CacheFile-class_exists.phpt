--TEST--
CacheFile - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	var_dump(class_exists('CacheFile'));
?>

--EXPECT--
bool(true)
