<?php

class Row {
    public $num;
    public function __construct($num) {
        $this->num = $num;
    }
    public function isOdd()
    {
        return ($this->num & 1) == 1;
    }
}

class Pix_Array_ArrayTest_TestObject
{
    private $v;
    protected $__time;
    protected $__id;

    public function __construct($time = 0, $id = 0)
    {
	$this->__time = $time;
	$this->__id = $id;
    }

    public function __get($name)
    {
	if ('time' == $name) {
	    return $this->__time;
	} elseif ('id' == $name) {
	    return $this->__id;
	}
    }

    public function get()
    {
	return $this->v;
    }

    public function set($v)
    {
	$this->v = $v;
    }
}

class Pix_Array_ArrayTest extends PHPUnit_Framework_TestCase
{
    public function testFactoryAndCount()
    {
	$arr = Pix_Array::factory();
	$this->assertEquals(count($arr), 0);

	$arr = Pix_Array::factory(array(1,2));
	$this->assertEquals(count($arr), 2);

	$arr = Pix_Array::factory(range(1, 100));
	$this->assertEquals(count($arr), 100);
    }

    public function testOffsetGet()
    {
	$arr = Pix_Array::factory(array(0 => 'a', 1 => 'b', 'test' => 'c', 'foo' => array(1,2,3)));
	$this->assertEquals($arr[0], 'a');
	$this->assertEquals($arr[1], 'b');
        $this->assertEquals(array_key_exists(2, $arr), false);
	$this->assertEquals($arr['test'], 'c');
        $this->assertEquals(array_key_exists('bar', $arr), false);
	$this->assertEquals(count($arr), 4);
	$this->assertEquals(count($arr['foo']), 3);
        $this->assertEquals($arr->first(), 'a');
    }

    public function testOffsetUnset()
    {
	$arr = Pix_Array::factory(array(0 => 'd', 1 => 'e', 'test' => 'f'));
	$this->assertEquals($arr[0], 'd');
	unset($arr[0]);
        $this->assertEquals(array_key_exists(0, $arr), false);
	$this->assertEquals($arr['test'], 'f');
	unset($arr['test']);
        $this->assertEquals(array_key_exists('test', $arr), false);
	unset($arr[0]);
        $this->assertEquals(array_key_exists(0, $arr), false);
        $this->assertEquals(array_key_exists('lala', $arr), false);
	unset($arr['lala']);
        $this->assertEquals(array_key_exists('lala', $arr), false);
	$this->assertEquals(count($arr), 1);
    }

    public function testOffsetSet()
    {
	$arr = Pix_Array::factory(array(0 => 'g', 1 => 'h', 'test' => 'i'));
	$this->assertEquals($arr[0], 'g');
	$arr[0] = 'go';
	$this->assertEquals($arr[0], 'go');

	$this->assertEquals($arr['test'], 'i');
	$arr['test'] = 3;
	$this->assertEquals($arr['test'], 3);

	$arr['test'] = array(1,2,3);
	$this->assertEquals(count($arr['test']), 3);

	$arr[] = 'lala';
	$this->assertEquals($arr[2], 'lala');

	$arr[] = 'haha';
	$this->assertEquals($arr[3], 'haha');

	$obj = new Pix_Array_ArrayTest_TestObject();
	$obj->set('lala');
	$arr['test'] = $obj;
	$this->assertEquals(is_object($arr['test']), true);
	$this->assertEquals(get_class($arr['test']), 'Pix_Array_ArrayTest_TestObject');
	$this->assertEquals($arr['test']->get(), 'lala');
    }

    public function testOffsetIsset()
    {
	$arr = Pix_Array::factory(array(0 => 'd', 1 => 'e', 'test' => 'f'));
	$this->assertEquals(isset($arr[0]), true);
	unset($arr[0]);
	$this->assertEquals(isset($arr[0]), false);

	$this->assertEquals(isset($arr['test']), true);
	unset($arr['test']);
	$this->assertEquals(isset($arr['test']), false);

	$this->assertEquals(isset($arr[123]), false);

	$this->assertEquals(isset($arr['lala']), false);
	unset($arr['lala']);
	$this->assertEquals(isset($arr['lala']), false);
    }

    public function testOffset()
    {
	$arr = Pix_Array::factory(array(0 => 'd', 1 => 'e', 'test' => 'f'));
	$this->assertEquals($arr->offset(3)->getOffset(), 3);
	$this->assertEquals($arr->offset(3)->offset(4)->getOffset(), 4);
	$this->assertEquals($arr->offset()->getOffset(), 0);

	$this->assertEquals($arr->limit("3")->getLimit(), 3);
	$this->assertEquals($arr->offset(3)->limit(4)->getLimit(), 4);
	$this->assertEquals(intval($arr->limit()->getLimit()), 0);
    }

    public function testSort()
    {
	$arr = Pix_Array::factory(
	    array(
		array('name' => 'alice', 'value' => 3), 
		array('name' => 'bob', 'value' => 5),
		array('name' => 'claire', 'value' => 4),
	    )
	);
	$arr = $arr->order(array('value')); // => array('value' => 'asc')
	$order = $arr->getOrder();
	$this->assertEquals(count($order), 1);
	$this->assertEquals($order['value'], 'asc');

	$arr = $arr->order(array('value', 'name')); // => array('value' => 'asc', 'name' => 'asc')
	$order = $arr->getOrder();
	$this->assertEquals(count($order), 2);
	$this->assertEquals($order['value'], 'asc');
	$this->assertEquals($order['name'], 'asc');
	array_shift($order);
        $this->assertEquals(array_key_exists('value', $order), false);
	$this->assertEquals($order['name'], 'asc');

	$arr = $arr->order(array('value', 'name' => 'DESC')); // => array('value' => 'asc', 'name' => 'desc')
	$order = $arr->getOrder();
	$this->assertEquals(count($order), 2);
	$this->assertEquals($order['value'], 'asc');
	$this->assertEquals($order['name'], 'desc');
	array_shift($order);
        $this->assertEquals(array_key_exists('value', $order), false);
	$this->assertEquals($order['name'], 'desc');

	$arr->order("`value`  ,  `name` DESC");
	$order = $arr->getOrder();
	$this->assertEquals(count($order), 2);
	$this->assertEquals($order['value'], 'asc');
	$this->assertEquals($order['name'], 'desc');
	array_shift($order);
        $this->assertEquals(array_key_exists('value', $order), false);
	$this->assertEquals($order['name'], 'desc');
    }

    public function testSum()
    {
	$arr = Pix_Array::factory(array(1,2,3));
	$this->assertEquals($arr->sum(), 6);
    }

    /**
     * @expectedException Pix_Exception
     */
    public function testSumByColumn()
    {
        $items = array(
            array('foo' => 1),
            array('foo' => 3),
            array('foo' => 5),
        );
	$array = Pix_Array::factory($items);
        $this->assertEquals(9, $array->sum('foo'));
    }

    public function testRewind()
    {
	$test_arr = array(4 => 1,5 => 3,9 => 5,1 => 7,11 => 9,21 => 11,100 => 13,'a' => 15);
	$keys = array_keys($test_arr);
	$arr = Pix_Array::factory($test_arr);
	foreach ($arr as $key => $value) {
	    $this->assertEquals($value, $test_arr[$key]);
	    $this->assertEquals($key, array_shift($keys));
	}
    }

    public function testSearch()
    {
        $users = Pix_Array::factory();
        for ($i = 0; $i < 100; $i ++) {
            $users[] = array('name' => $i);
        }

        // limit 不要影響到後面
        $c = 0; foreach ($users as $u) { $c ++; }
        $this->assertEquals($c, 100);
        $c = 0; foreach ($users->limit(20) as $u) { $c ++; }
        $this->assertEquals($c, 20);
        $c = 0; foreach ($users as $u) { $c ++; }
        $this->assertEquals($c, 100);

        // order 不要影響後面
        $users = $users->order(array('name' => 'desc'));
        $this->assertEquals($users->first(), array('name' => 99));
        $this->assertEquals($users->order('name asc')->first(), array('name' => 0));
        $this->assertEquals($users->first(), array('name' => 99));
    }

    public function testOrder()
    {
	$objs = Pix_Array::factory();

	$obj = new Pix_Array_ArrayTest_TestObject(1321321, 3214);
	$objs[] = $obj;
	$obj = new Pix_Array_ArrayTest_TestObject("1321", 214);
	$objs[] = $obj;
	$obj = new Pix_Array_ArrayTest_TestObject(132131, "9123");
	$objs[] = $obj;
	$obj = new Pix_Array_ArrayTest_TestObject(8921321, 4214);
	$objs[] = $obj;

	$objs = $objs->order('time DESC');
	$objs->rewind();
	$this->assertEquals($objs->current()->id, 4214);
	$this->assertEquals($objs->next()->current()->id, 3214);
	$this->assertEquals($objs->next()->current()->id, 9123);
	$this->assertEquals($objs->next()->current()->id, 214);
    }

    public function testIsOddFilter()
    {
        $array = Pix_Array::factory(range(0, 10));
        $isOdd = function($num) {
            return ($num & 1) == 1;
        };
        foreach ($array->filter($isOdd) as $num) {
            $this->assertTrue($isOdd($num));
        }
    }

    public function testIsEvenFilter()
    {
        $isEven = function($num) {
            return !($num & 1) == 0;
        };

        $array = Pix_Array::factory(range(0, 10));
        foreach ($array->filter($isEven) as $num) {
            $this->assertTrue($isEven($num));
        }
    }

    public function testIsOddFilterWithOffset()
    {
        $isOdd = function($num) {
            return ($num & 1) == 1;
        };

        $array = Pix_Array::factory(range(0, 10));
        $i = 0;
        foreach ($array->offset(3)->filter($isOdd) as $num) {
            $i++;
            $this->assertTrue($isOdd($num));
        }
        $this->assertEquals($i, 2);
    }

    public function testIsOddFilterWithLimit()
    {
        $isOdd = function($num) {
            return ($num & 1) == 1;
        };

        $array = Pix_Array::factory(range(0, 10));
        $i = 0;
        foreach ($array->limit(3)->filter($isOdd) as $num) {
            $i++;
            $this->assertTrue($isOdd($num));
        }
        $this->assertEquals($i, 3);
        $this->assertEquals($array->limit(3)->filter($isOdd)->first(), 1);
    }

    public function testIsOddFilterWithOffsetAndLimit()
    {
        $isOdd = function($num) {
            return ($num & 1) == 1;
        };

        $array = Pix_Array::factory(range(0, 10));

        $i = 0;
        $filtered_array = $array->offset(3)->limit(2)->filter($isOdd);
        foreach ($filtered_array as $num) {
            $i++;
            $this->assertTrue($isOdd($num));
        }
        $this->assertEquals($i, 2);
        $this->assertEquals($filtered_array->first(), 7);
        $this->assertEquals(count($filtered_array), 2);

        $i = 0;
        $filtered_array = $array->offset(2)->limit(4)->filter($isOdd);
        foreach ($filtered_array as $num) {
            $i++;
            $this->assertTrue($isOdd($num));
        }
        $this->assertEquals($i, 3);
        $this->assertEquals(count($filtered_array), 3);
    }

    public function testFilterBultInWithRow()
    {
        foreach (range(0, 10) as $num) {
            $items[] = new Row($num);
        }
        $array = Pix_Array::factory($items);
        $filtered_array = $array->filterBuiltIn('Row', array('isOdd'));
        $i = 0;
        foreach ($filtered_array as $row) {
            $i++;
            $this->assertTrue($row->isOdd());
        }
        $this->assertEquals($i, 5);
        $this->assertEquals(count($filtered_array), 5);
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testGetRandByFive($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);
        $count = 5;
        $random_array = $array->getRand($count);
        if (count($original_array) > $count) {
            $this->assertEquals($count, count($random_array));
        } else {
            $this->assertEquals(count($original_array), count($random_array));
        }
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testGetRand($array)
    {
        $array = Pix_Array::factory($array);
        $random_array = $array->getRand();
        $this->assertEquals(1, count($random_array));
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testToArray($array)
    {
        $orignal_array = $array;
        $array = Pix_Array::factory($array);
        $this->assertEquals($array->toArray(), $orignal_array);
    }

    public function testObjectToArray()
    {
        $array = Pix_Array::factory();

        $obj = new StdClass;
        $obj->name = 'alice';
        $obj->age = 12;
        $array[] = $obj;

        $obj = new StdClass;
        $obj->name = 'bob';
        $obj->age = 13;
        $array[] = $obj;

        $this->assertEquals($array->toArray('name'), array('alice', 'bob'));
        $this->assertEquals($array->order('age')->toArray('name'), array('alice', 'bob'));
        $this->assertEquals($array->order('age DESC')->toArray('name'), array('bob', 'alice'));
    }

    public function testToArrayByColumn()
    {
        $items = array(
            array('foo' => 'foo0', 'bar' => 'bar0'),
            array('foo' => 'foo1', 'bar' => 'bar1'),
        );
        $array = Pix_Array::factory($items);
        $this->assertEquals(array(), $array->toArray('none'));
        $this->assertEquals(array('foo0', 'foo1'), $array->toArray('foo'));
    }

    /**
     * @expectedException Pix_Exception
     * @dataProvider arrayProvider
     */
    public function testMax($array)
    {
        $max = max($array);
        $array = Pix_Array::factory($array);
        $this->assertEquals($max, $array->max());
    }

    /**
     * @expectedException Pix_Exception
     * @dataProvider arrayProvider
     */
    public function testMin($array)
    {
        $min = min($array);
        $array = Pix_Array::factory($array);
        $this->assertEquals($min, $array->min());
    }

    /**
     * @expectedException Pix_Exception
     */
    public function testGetPosition()
    {
        $array = Pix_Array::factory(range(0, 10));
        $array->getPosition(NULL);
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testSeek($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);
        $i = 0;
        while ($i++ < 5) {
            $random = rand(0, count($original_array) - 1);
            $this->assertEquals($original_array[$random], $array->seek($random));
        }
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testGetProperty($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);
        foreach ($original_array as $key => $value) {
            $this->assertEquals($value, $array->{$key});
        }
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testPush($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);

        $push_item = 'item';
        array_push($original_array, $push_item);
        $array->push($push_item);
        $this->assertEquals($original_array, $array->toArray());
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testPop($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);

        array_pop($original_array);
        $array->pop();
        $this->assertEquals($original_array, $array->toArray());
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testShift($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);

        array_shift($original_array);
        $array->shift();
        $this->assertEquals($original_array, $array->toArray());
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testUnshift($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);

        $item = 'foo';
        array_unshift($original_array, $item);
        $array->unshift($item);
        $this->assertEquals($original_array, $array->toArray());
    }

    public function testAfter()
    {
        $source_array = array(
            array('name' => 'alice', 'gender' => 'f', 'age' => 24),
            array('name' => 'bob', 'gender' => 'm', 'age' => 27),
            array('name' => 'carole', 'gender' => 'f', 'age' => 30),
            array('name' => 'david', 'gender' => 'm', 'age' => 18),
        );

        $array = Pix_Array::factory($source_array);

        $this->assertEquals($array->order('age')->toArray('name'), array('david', 'alice', 'bob', 'carole'));
        $this->assertEquals($array->order('age')->after(array('age' => 19))->toArray('name'), array('alice', 'bob', 'carole'));
        $this->assertEquals($array->order('age')->after(array('age' => 24))->toArray('name'), array('bob', 'carole'));
        $this->assertEquals($array->order('age')->after(array('age' => 24), true)->toArray('name'), array('alice', 'bob', 'carole'));
        $this->assertEquals($array->order('age, gender DESC')->after(array('age' => 24), true)->toArray('name'), array('alice', 'bob', 'carole'));
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testReverse($array)
    {
        $original_array = $array;
        $array = Pix_Array::factory($array);

        array_reverse($original_array);
        $array->reverse();
        $this->assertEquals($original_array, $array->toArray());
    }

    public static function arrayProvider()
    {
        return array(
            array(
                range(0, 10)
            ),
            array(
                array('eclair', 'donut', 'froyo', 'cupcake', 'honeycomb', 'ice cream sandwich')
            ),
            array(
                array('foo', 'bar', 'hello')
            ),
        );
    }
}
