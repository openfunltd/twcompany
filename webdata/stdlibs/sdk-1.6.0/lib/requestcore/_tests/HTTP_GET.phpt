--TEST--
HTTP_GET

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	echo RequestCore::HTTP_GET;
?>

--EXPECT--
GET
