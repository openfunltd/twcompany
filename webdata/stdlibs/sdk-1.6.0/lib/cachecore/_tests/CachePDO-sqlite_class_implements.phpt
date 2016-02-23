--TEST--
CachePDO - class_implements (SQLite)

--SKIPIF--
<?php
	if (!class_exists('PDO')) print 'skip PDO extension not available';
	if (!in_array('sqlite', PDO::getAvailableDrivers())) print 'skip PDO_SQLITE driver not available';
?>

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachepdo.class.php';
	$cache = new CachePDO('test', 'sqlite://user:pass@hostname:80/table', 60);
	var_dump(class_implements($cache));
?>

--EXPECT--
array(1) {
  ["ICacheCore"]=>
  string(10) "ICacheCore"
}
