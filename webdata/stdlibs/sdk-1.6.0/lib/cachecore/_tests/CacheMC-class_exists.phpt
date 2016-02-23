--TEST--
CacheMC - Exists

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachemc.class.php';
	var_dump(class_exists('CacheMC'));
?>

--EXPECT--
bool(true)
