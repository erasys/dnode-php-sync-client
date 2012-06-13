<?php
/**
 * Dnode Synchronous Client for PHP
 *
 * @copyright 2012 erasys GmbH - see ../LICENSE.txt for more info
 */
namespace DnodeSyncClient;

require_once \dirname(__DIR__). \DIRECTORY_SEPARATOR .'DnodeSyncClient.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'TestStreamWrapper.php';


class ConnectionTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    $this->wrapper = new TestStreamWrapper();
    stream_wrapper_register('testwrapper', '\DnodeSyncClient\TestStreamWrapper');
  }

  public function tearDown() {
    stream_wrapper_unregister('testwrapper');
    TestStreamWrapper::getWrites(); // clear stuff written to wrapper
  }
  
  public function testMethodsAreReadFromRemote() {
    $stream = fopen('testwrapper://', 'rw');

    $this->setExpectedException('\DnodeSyncClient\IOException',
      "Can't read method description from remote");
    new Connection($stream);
  }

  public function testRemoteMethodsAreParsed() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead("invalid json\n");


    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
      "First line is not valid json: invalid json");
    new Connection($stream);
  }
  
  public function testRemoteMethodsMethodFieldIsChecked() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead("{}\n");


    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
      "First line does not have method field: {}");
    new Connection($stream);
  }

  public function testFirstRemoteMethodMustBeMethods() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead('{"method": "not-methods"}' ."\n");


    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
      'First line method must be "methods": {"method": "not-methods"}');
    new Connection($stream);
  }

  public function testRemoteMethodsArgumentsMustNotBeMissing() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead('{"method": "methods"}' ."\n");


    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
      'Methods arguments missing: {"method": "methods"}');
    new Connection($stream);
  }
  
  public function testRemoteMethodsArgumentsMustNotBeEmpty() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead('{"method": "methods", "arguments": []}' ."\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
      'Methods must have single argument: {"method": "methods", "arguments": []}');
    new Connection($stream);
  }
  
  public function testRemoteMustHaveSomeMethods() {
    $stream = fopen('testwrapper://', 'rw');
    $response =  '{"method": "methods", "arguments": [{}]}';
    TestStreamWrapper::addRead("$response\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
      "Remote is expected to have some methods: $response");;
    new Connection($stream);
  }

  public function testMethodsAreSentToRemote() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead('{"method": "methods", '
        .'"arguments": [{"method1": ""}]}' ."\n");

    new Connection($stream);

    $this->assertEquals(
      array('{"method":"methods"}'."\n"),
      TestStreamWrapper::getWrites()
    );
  }
  
  public function testRemoteMethods() {
    $connection = $this->initConnection();

    $this->assertEquals(
      array('method1', 'method2'),
      $connection->getAvailableMethods()
    );
  }

  private function initConnection() {
    $stream = fopen('testwrapper://', 'rw');
    TestStreamWrapper::addRead('{"method": "methods", '
        .'"arguments": [{"method1": "", "method2": ""}]}' ."\n");

    $connection = new Connection($stream);
    TestStreamWrapper::getWrites(); // clear stuff written to wrapper
    return $connection;
  }

  public function testCallMethod() {
    $connection = $this->initConnection();

    TestStreamWrapper::addRead('{"method": 42}'."\n");
    
    $connection->call("method1", array("arg1", 2));

    $this->assertEquals(
      array('{"method":"method1","arguments":["arg1",2],"callbacks":{"42":[2]}}'."\n"),
      TestStreamWrapper::getWrites()
    );
  }
  
  public function testCallbackNumberIncreasedMethod() {
    $connection = $this->initConnection();

    TestStreamWrapper::addRead('{"method": 42}'."\n");
    $connection->call("method1");

    TestStreamWrapper::getWrites(); // clear stuff written to wrapper

    TestStreamWrapper::addRead('{"method": 43}'."\n");
    $connection->call("method1");

    $this->assertEquals(
      array('{"method":"method1","arguments":[],"callbacks":{"43":[0]}}'."\n"),
      TestStreamWrapper::getWrites()
    );
  }
  
  public function testCallMethodResponseMustUseRequestCallback() {
    $connection = $this->initConnection();

    TestStreamWrapper::addRead('{"method": 41}'."\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
        'Response does not call expected callback, expected 42, got {"method": 41}');

    $connection->call("method1");
  }

  public function testCallMethodInvalidJsonResponse() {
    $connection = $this->initConnection();

    TestStreamWrapper::addRead("invalid json\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
        'Response is not valid json: invalid json');

    $connection->call("method1");
  }
  
  public function testCallMethodResponseWithoutMethod() {
    $connection = $this->initConnection();

    TestStreamWrapper::addRead("{}\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
        'Response does not have method field: {}');

    $connection->call("method1");
  }
  
  public function testCallMethodNotDeclaredByRemote() {
    $connection = $this->initConnection();

    $this->setExpectedException('\DnodeSyncClient\MethodNotExistsException',
        'Method invalidMethod does not exists on remote.');

    $connection->call("invalidMethod");
  }

  public function testCallMethodLinksNotPresent() {
    $connection = $this->initConnection();

    TestStreamWrapper::addRead('{"method": 42, "links": [1]}'."\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
        'Response contains links, we do not support that: {"method": 42, "links": [1]}');

    $connection->call("method1");
  }

  public function testCallMethodCallbacksNotPresent() {
    $connection = $this->initConnection();

    $response = '{"method": 42, "callbacks": {"1":[0]}}';
    TestStreamWrapper::addRead($response."\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
        'Response contains callbacks, we do not support that: ' . $response);

    $connection->call("method1");
  }

  public function testCallMethodArgumentsMustBeArray() {
    $connection = $this->initConnection();

    $response = '{"method": 42, "arguments": null}';
    TestStreamWrapper::addRead($response."\n");

    $this->setExpectedException('\DnodeSyncClient\ProtocolException',
        'Response arguments must be array: ' . $response);

    $connection->call("method1");
  }

  
}

