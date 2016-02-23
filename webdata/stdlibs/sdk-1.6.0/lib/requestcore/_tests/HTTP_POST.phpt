--TEST--
HTTP_POST

--FILE--
<?php
	require_once dirname(__FILE__) . '/../requestcore.class.php';
	echo RequestCore::HTTP_POST;
?>

--EXPECT--
POST
