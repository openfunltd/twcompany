--TEST--
CacheFile::timestamp()

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	$cache->create('test data');
	var_dump(gettype($cache->timestamp()));
?>

--EXPECT--
string(7) "integer"

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	$cache->delete();
?>
