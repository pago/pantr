<?php
/**
 * Test case for class Example.
 *
 * @package     bovigo_vfs
 * @subpackage  examples
 * @version     $Id: ExampleTestCaseWithVfsStream.php 124 2009-07-12 18:06:04Z google@frankkleine.de $
 */
require_once 'PHPUnit/Framework.php';
require_once 'vfsStream/vfsStream.php';
require_once 'Example.php';
/**
 * Test case for class Example.
 *
 * @package     bovigo_vfs
 * @subpackage  examples
 */
class ExampleTestCaseWithVfsStream extends PHPUnit_Framework_TestCase
{
    /**
     * set up test environmemt
     */
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(vfsStream::newDirectory('exampleDir'));
    }

    /**
     * test that the directory is created
     */
    public function testDirectoryIsCreated()
    {
        $example = new Example('id');
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('id'));
        $example->setDirectory(vfsStream::url('exampleDir'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('id'));
    }
}
?>