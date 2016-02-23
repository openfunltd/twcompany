--TEST--
Array of hashes where hashes are strings.

--FILE--
<?php
require_once '../Transmogrifier.php';

$data = json_decode('{
  "ConsumedCapacityUnits": 0.5,
  "Count": 2,
  "Items": [
    {
      "a5": {
        "S": "value2"
      },
      "a2": {
        "N": "2"
      },
      "a1": {
        "S": "key2"
      },
      "a4": {
        "S": "value12"
      }
    }
  ],
  "ScannedCount": 2
}', true);

echo Transmogrifier::to_xml($data);
?>

--EXPECT--
<?xml version="1.0"?>
<root>
  <ConsumedCapacityUnits>0.5</ConsumedCapacityUnits>
  <Count>2</Count>
  <Items>
    <a5>
      <S><![CDATA[value2]]></S>
    </a5>
    <a2>
      <N>2</N>
    </a2>
    <a1>
      <S><![CDATA[key2]]></S>
    </a1>
    <a4>
      <S><![CDATA[value12]]></S>
    </a4>
  </Items>
  <ScannedCount>2</ScannedCount>
</root>
