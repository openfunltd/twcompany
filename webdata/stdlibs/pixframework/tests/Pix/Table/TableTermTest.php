<?php

class Pix_Table_TableTermTest_Table extends Pix_Table
{
    public function init()
    {
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int');
    }
}

/**
 * Test class for Pix_Table_Search_Term.
 */
class Pix_Table_TableTermTest extends PHPUnit_Framework_TestCase
{
    public function testSearchTerm()
    {
        $result_set = Pix_Table_TableTermTest_Table::search(1)->searchTerm('term_id', 'a', 'b');
        $search = $result_set->getSearchObject();

        $conditions = $search->getSearchCondictions('term');
        $this->assertEquals(count($conditions), 1);
        list($type, $search_term) = $conditions[0];
        $this->assertEquals($search_term->getType(), 'term_id');
        $this->assertEquals($search_term->getArguments(), array('a', 'b'));
    }

    public function testSearchTerm2()
    {
        $term = new Pix_Table_Search_Term('term_id', 'a', 'b');
        $result_set = Pix_Table_TableTermTest_Table::search($term);
        $search = $result_set->getSearchObject();

        $conditions = $search->getSearchCondictions('term');
        $this->assertEquals(count($conditions), 1);
        list($type, $search_term) = $conditions[0];
        $this->assertEquals($search_term->getType(), 'term_id');
        $this->assertEquals($search_term->getArguments(), array('a', 'b'));
    }
}
