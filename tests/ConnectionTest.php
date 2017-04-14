<?php

namespace Erasys\DnodeSyncClient;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestStreamWrapper
     */
    protected $wrapper;

    public function setUp()
    {
        $this->wrapper = new TestStreamWrapper();
        stream_wrapper_register('testwrapper', TestStreamWrapper::class);
    }

    public function tearDown()
    {
        stream_wrapper_unregister('testwrapper');
        TestStreamWrapper::getWrites(); // clear stuff written to wrapper
    }

    public function testMethodsAreReadFromRemote()
    {
        $stream = fopen('testwrapper://', 'rw');

        $this->setExpectedException(
            Exception\IOException::class,
            "Can't read method description from remote"
        );
        new Connection($stream);
    }

    public function testRemoteMethodsAreParsed()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead("invalid json\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'First line is not valid json: invalid json'
        );
        new Connection($stream);
    }

    public function testRemoteMethodsMethodFieldIsChecked()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead("{}\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'First line does not have method field: {}'
        );
        new Connection($stream);
    }

    public function testFirstRemoteMethodMustBeMethods()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead('{"method": "not-methods"}'."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'First line method must be "methods": {"method": "not-methods"}'
        );
        new Connection($stream);
    }

    public function testRemoteMethodsArgumentsMustNotBeMissing()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead('{"method": "methods"}'."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Methods arguments missing: {"method": "methods"}'
        );
        new Connection($stream);
    }

    public function testRemoteMethodsArgumentsMustNotBeEmpty()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead('{"method": "methods", "arguments": []}'."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Methods must have single argument: {"method": "methods", "arguments": []}'
        );
        new Connection($stream);
    }

    public function testRemoteMustHaveSomeMethods()
    {
        $stream = fopen('testwrapper://', 'rw');
        $response = '{"method": "methods", "arguments": [{}]}';
        TestStreamWrapper::addRead("$response\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            "Remote is expected to have some methods: $response"
        );
        new Connection($stream);
    }

    public function testMethodsAreSentToRemote()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead('{"method": "methods", '
            .'"arguments": [{"method1": ""}]}'."\n");

        new Connection($stream);

        $this->assertEquals(
            array('{"method":"methods"}'."\n"),
            TestStreamWrapper::getWrites()
        );
    }

    public function testRemoteMethods()
    {
        $connection = $this->initConnection();

        $this->assertEquals(
            array('method1', 'method2'),
            $connection->getAvailableMethods()
        );
    }

    private function initConnection()
    {
        $stream = fopen('testwrapper://', 'rw');
        TestStreamWrapper::addRead('{"method": "methods", '
            .'"arguments": [{"method1": "", "method2": ""}]}'."\n");

        $connection = new Connection($stream);
        TestStreamWrapper::getWrites(); // clear stuff written to wrapper
        return $connection;
    }

    public function testCallMethod()
    {
        $connection = $this->initConnection();

        TestStreamWrapper::addRead('{"method": 42}'."\n");

        $connection->call('method1', array('arg1', 2));

        $this->assertEquals(
            array('{"method":"method1","arguments":["arg1",2],"callbacks":{"42":[2]}}'."\n"),
            TestStreamWrapper::getWrites()
        );
    }

    public function testCallbackNumberIncreasedMethod()
    {
        $connection = $this->initConnection();

        TestStreamWrapper::addRead('{"method": 42}'."\n");
        $connection->call('method1');

        TestStreamWrapper::getWrites(); // clear stuff written to wrapper

        TestStreamWrapper::addRead('{"method": 43}'."\n");
        $connection->call('method1');

        $this->assertEquals(
            array('{"method":"method1","arguments":[],"callbacks":{"43":[0]}}'."\n"),
            TestStreamWrapper::getWrites()
        );
    }

    public function testCallMethodResponseMustUseRequestCallback()
    {
        $connection = $this->initConnection();

        TestStreamWrapper::addRead('{"method": 41}'."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Response does not call expected callback, expected 42, got {"method": 41}'
        );

        $connection->call('method1');
    }

    public function testCallMethodInvalidJsonResponse()
    {
        $connection = $this->initConnection();

        TestStreamWrapper::addRead("invalid json\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Response is not valid json: invalid json'
        );

        $connection->call('method1');
    }

    public function testCallMethodResponseWithoutMethod()
    {
        $connection = $this->initConnection();

        TestStreamWrapper::addRead("{}\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Response does not have method field: {}'
        );

        $connection->call('method1');
    }

    public function testCallMethodNotDeclaredByRemote()
    {
        $connection = $this->initConnection();

        $this->setExpectedException(
            Exception\MethodNotExistsException::class,
            'Method invalidMethod does not exists on remote.'
        );

        $connection->call('invalidMethod');
    }

    public function testCallMethodLinksNotPresent()
    {
        $connection = $this->initConnection();

        TestStreamWrapper::addRead('{"method": 42, "links": [1]}'."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Response contains links, we do not support that: {"method": 42, "links": [1]}'
        );

        $connection->call('method1');
    }

    public function testCallMethodCallbacksNotPresent()
    {
        $connection = $this->initConnection();

        $response = '{"method": 42, "callbacks": {"1":[0]}}';
        TestStreamWrapper::addRead($response."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Response contains callbacks, we do not support that: '.$response
        );

        $connection->call('method1');
    }

    public function testCallMethodArgumentsMustBeArray()
    {
        $connection = $this->initConnection();

        $response = '{"method": 42, "arguments": null}';
        TestStreamWrapper::addRead($response."\n");

        $this->setExpectedException(
            Exception\ProtocolException::class,
            'Response arguments must be array: '.$response
        );

        $connection->call('method1');
    }
}
