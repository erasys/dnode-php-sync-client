<?php

namespace uuf6429\DnodeSyncClient;

class TestStreamWrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestStreamWrapper
     */
    private $wrapper;

    public function setUp()
    {
        $this->wrapper = new TestStreamWrapper();
        stream_wrapper_register('testwrapper', TestStreamWrapper::class);
    }

    public function tearDown()
    {
        stream_wrapper_unregister('testwrapper');
    }

    public function testRead()
    {
        $ch = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead("data\n");

        $line = fgets($ch);

        $this->assertEquals("data\n", $line);
    }

    public function testWrite()
    {
        $ch = fopen('testwrapper://', 'rw');

        fwrite($ch, 'test line');

        $this->assertEquals(array('test line'), TestStreamWrapper::getWrites());
    }

    public function testReadWrite()
    {
        $ch = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead("read line\n");

        fwrite($ch, "written line\n");
        $line = fgets($ch);

        $this->assertEquals("read line\n", $line);
        $this->assertEquals(array("written line\n"), TestStreamWrapper::getWrites());
    }
}
