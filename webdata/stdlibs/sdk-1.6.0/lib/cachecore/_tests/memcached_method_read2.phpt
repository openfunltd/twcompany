--TEST--
CacheMC::read() without port number

--SKIPIF--
<?php
	if (!class_exists('Memcache') && !class_exists('Memcached')) print 'skip Neither the Memcached nor Memcache extensions are available';
?>

--FILE--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachemc.class.php';
	$cache = new CacheMC('test', array(
		array('host' => '127.0.0.1')
	), 60);
	$cache->create('test data');
	var_dump($cache->read());
?>

--EXPECT--
string(9) "test data"

--CLEAN--
<?php
	require_once dirname(__FILE__) . '/../cachecore.class.php';
	require_once dirname(__FILE__) . '/../cachemc.class.php';
	$cache = new CacheMC('test', array(
		array('host' => '127.0.0.1', 'port' => 11211)
	), 60);
	$cache->delete();
?>
