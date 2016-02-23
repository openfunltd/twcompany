--TEST--
Test a sample response from DynamoDB.

--FILE--
<?php
require_once '../Transmogrifier.php';

$data = array(
	'ConsumedCapacityUnits' => 0.5,
	'Count' => 1,
	'Items' => array(
		array(
			'id' => array(
				'N' => '-1399722082',
			),
			'name' => array(
				'S' => 'test 456',
			),
			'range' => array(
				'N' => '456',
			),
			'strings' => array(
				'SS' => array(
					0 => 'one',
					1 => 'two',
				),
			),
		),
	),
	'ScannedCount' => 12,
);

echo Transmogrifier::to_xml($data);
?>

--EXPECT--
<?xml version="1.0"?>
<root>
  <ConsumedCapacityUnits>0.5</ConsumedCapacityUnits>
  <Count>1</Count>
  <Items>
    <id>
      <N>-1399722082</N>
    </id>
    <name>
      <S><![CDATA[test 456]]></S>
    </name>
    <range>
      <N>456</N>
    </range>
    <strings>
      <SS><![CDATA[one]]></SS>
      <SS><![CDATA[two]]></SS>
    </strings>
  </Items>
  <ScannedCount>12</ScannedCount>
</root>
