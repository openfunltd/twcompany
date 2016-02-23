--TEST--
Array of hashes where hashes are numeric.

--FILE--
<?php
require_once '../Transmogrifier.php';

$data = json_decode('{
  "ConsumedCapacityUnits": "0.5",
  "Count": "2",
  "Items": {
    "Items": [
      {
        "S": "value2"
      },
      {
        "N": "2"
      },
      {
        "S": "key2"
      },
      {
        "S": "value12"
      }
    ]
  },
  "ScannedCount": "2"
}
', true);

echo Transmogrifier::to_xml($data);
?>

--EXPECT--
<?xml version="1.0"?>
<root>
  <ConsumedCapacityUnits>0.5</ConsumedCapacityUnits>
  <Count>2</Count>
  <Items>
    <Items>
      <S><![CDATA[value2]]></S>
    </Items>
    <Items>
      <N>2</N>
    </Items>
    <Items>
      <S><![CDATA[key2]]></S>
    </Items>
    <Items>
      <S><![CDATA[value12]]></S>
    </Items>
  </Items>
  <ScannedCount>2</ScannedCount>
</root>
