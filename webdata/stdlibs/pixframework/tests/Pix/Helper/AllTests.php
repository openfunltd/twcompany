<?php

class Pix_Helper_AllTests
{
    /**
     * Buffered test suites
     *
     * These tests require no output be sent prior to running as they rely
     * on internal PHP functions.
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suiteBuffered()
    {
        $suite = new PHPUnit_Framework_TestSuite('Pix Framework - Pix - Buffered Test Suites');

        // These tests require no output be sent prior to running as they rely
        // on internal PHP functions

        return $suite;
    }
    /**
     * Regular suite
     *
     * All tests except those that require output buffering.
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Pix Framework - Pix');

	// Start remaining tests...
        $suite->addTestSuite('Pix_Helper_HelperTest');

        return $suite;
    }
}
