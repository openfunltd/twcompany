--TEST--
CacheFile - class_implements

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	$cache = new CacheCore('test', './cache', 60);
	var_dump(class_implements($cache));
?>

--EXPECT--
array(0) {
}
