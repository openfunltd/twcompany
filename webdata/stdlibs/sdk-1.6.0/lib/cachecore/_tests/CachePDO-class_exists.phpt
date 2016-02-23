--TEST--
CachePDO - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachepdo.class.php';
	var_dump(class_exists('CachePDO'));
?>

--EXPECT--
bool(true)
