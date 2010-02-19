<?php
/**
 * Test case for class FilemodeExample.
 *
 * @package     stubbles_vfs
 * @subpackage  examples
 * @version     $Id: FilemodeExampleTestCaseWithVfsStream.php 124 2009-07-12 18:06:04Z google@frankkleine.de $
 */
require_once 'PHPUnit/Framework.php';
require_once 'vfsStream/vfsStream.php';
require_once 'FilemodeExample.php';
/**
 * Test case for class Example.
 *
 * @package     stubbles_vfs
 * @subpackage  examples
 */
class FilemodeExampleTestCaseWithVfsStream extends PHPUnit_Framework_TestCase
{
    /**
     * set up test environmemt
     */
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('exampleDir'));
    }

    /**
     * test that the directory is created
     */
    public function testDirectoryIsCreatedWithDefaultPermissions()
    {
        $example = new FilemodeExample('id');
        $example->setDirectory(vfsStream::url('exampleDir'));
        $this->assertEquals(0700, vfsStreamWrapper::getRoot()->getChild('id')->getPermissions());
    }

    /**
     * test that the directory is created
     */
    public function testDirectoryIsCreatedWithGivenPermissions()
    {
        $example = new FilemodeExample('id', 0755);
        $example->setDirectory(vfsStream::url('exampleDir'));
        $this->assertEquals(0755, vfsStreamWrapper::getRoot()->getChild('id')->getPermissions());
    }
}
?>