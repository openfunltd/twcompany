--TEST--
CacheFile::reset()

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	$cache->create('test data');
	$start = $cache->timestamp();
	sleep(2);
	var_dump($cache->reset());
	$end = $cache->timestamp();
	var_dump($start < $end);
	var_dump($end - $start);
?>

--EXPECT--
bool(true)
bool(true)
int(2)

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachefile.class.php';
	$cache = new CacheFile('test', dirname(__FILE__) . '/cache', 60);
	$cache->delete();
?>
