<?php
// http://cvs.php.net/viewvc.cgi/pear/Console_CommandLine/tests/AllTests.php?view=co&revision=1.1&content-type=text%2Fplain
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Console_CommandLine
 * @since     File available since release 1.0.0
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Console_CommandLine_AllTests::main');
}

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Extensions/PhptTestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * Console_CommandLine phpt test suite.
 *
 * Run all tests from the package root directory:
 * $ phpunit Console_CommandLine_AllTests tests/AllTests.php
 * or
 * $ php tests/AllTests.php
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Console_CommandLine
 * @since     Class available since release 1.0.0
 */
class Console_CommandLine_AllTests
{
    /**
     * Runs the test suite
     *
     * @return void
     * @static
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Return the phpt test suite
     *
     * @return object the PHPUnit_Framework_TestSuite object
     * @static
     */
    public static function suite()
    {
        return new PHPUnit_Extensions_PhptTestSuite(dirname(__FILE__));
    }
}

if (PHPUnit_MAIN_METHOD == 'Console_CommandLine_AllTests::main') {
    Console_CommandLine_AllTests::main();
}

?>