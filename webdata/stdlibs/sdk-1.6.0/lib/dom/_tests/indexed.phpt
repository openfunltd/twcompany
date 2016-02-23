--TEST--
An indexed array.

--FILE--
<?php
require_once '../Transmogrifier.php';

$data = json_decode('[1, 2, 3, 4, 5]', true);
echo Transmogrifier::to_xml($data);
?>

--EXPECT--
<?xml version="1.0"?>
<root>
  <member>1</member>
  <member>2</member>
  <member>3</member>
  <member>4</member>
  <member>5</member>
</root>
