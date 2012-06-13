<?php
/**
 * Dnode Synchronous Client for PHP
 *
 * @copyright 2012 erasys GmbH - see ../LICENSE.txt for more info
 */
namespace DnodeSyncClient;

require_once __DIR__ . \DIRECTORY_SEPARATOR . 'TestStreamWrapper.php';

class TestStreamWrapperTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    $this->wrapper = new TestStreamWrapper();
    stream_wrapper_register('testwrapper', '\DnodeSyncClient\TestStreamWrapper');
  }

  public function tearDown() {
    stream_wrapper_unregister('testwrapper');
  }

  public function testRead() {
    $ch = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead("data\n");

    $line = fgets($ch);

    $this->assertEquals("data\n", $line);
  }

  public function testWrite() {
    $ch = fopen('testwrapper://', 'rw');

    fwrite($ch, 'test line');
    
    $this->assertEquals(array('test line'), TestStreamWrapper::getWrites());
  }

  public function testReadWrite () {
    $ch = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead("read line\n");

    fwrite($ch, "written line\n");
    $line = fgets($ch);
    
    $this->assertEquals("read line\n", $line);
    $this->assertEquals(array("written line\n"), TestStreamWrapper::getWrites());
  }

}

