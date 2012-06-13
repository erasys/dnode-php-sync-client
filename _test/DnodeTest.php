<?php

namespace DnodeSyncClient;

require_once dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'DnodeSyncClient.php';

/**
 * This testcase requires "node test.js"
 */
class DnodeTest extends \PHPUnit_Framework_TestCase {

  const DNODE_TEST_PORT = 8080;
  const NODE_BINARY = 'node';

  public function setUp() {
    $this->dnode = new Dnode();

    $this->startNodeEchoService();
  }

  public function tearDown() {
    $this->stopNodeEchoService();

  }

  public function startNodeEchoService() {
    $command = self::NODE_BINARY . ' ' . __DIR__
        . DIRECTORY_SEPARATOR . 'node'
        . DIRECTORY_SEPARATOR .'echo.js '. self::DNODE_TEST_PORT;
    $failMessage = "Unable to start test dnode service.\n"
      . "To debug, try to run this: $command\n"
      . "Maybe the dnode is not installed? run 'npm install .' in node directory.";

    $descriptors = array(
      array("pipe", "r"),  // stdin is a pipe that the child will read from
      array("pipe", "w"),  // stdout is a pipe that the child will write to
    );
    $pipes = array();
    $this->process = proc_open($command, $descriptors, $pipes);
    if (!is_resource($this->process)) {
      $this->fail($failMessage);
    }

    $line = fgets($pipes[1]);
    
    $this->assertEquals(
      "echo service started on port ". self::DNODE_TEST_PORT,
      trim($line),
      $failMessage
    );
  }

  public function stopNodeEchoService() {
    if (!$this->process) {
      return;
    }
    $processStatus = proc_get_status($this->process);
    if (!$processStatus['running']) {
      $this->process = null;
      return;
    }
    // kill the process, otherwise PHP will hang on it
    exec('kill -9 '.$processStatus['pid']);
    proc_close($this->process);
    $this->process = null;
  }

  public function testRemoteAvailableMethods() {
    $connection = $this->dnode->connect('localhost', self::DNODE_TEST_PORT);

    $response = $connection->call("echo", array("argument"));

    $this->assertEquals(array(null, "argument"), $response);
  }

  public function testEchoService() {
    $connection = $this->dnode->connect('localhost', self::DNODE_TEST_PORT);

    $response = $connection->call("echo", array("argument"));

    $this->assertEquals(array(null, "argument"), $response);
  }

  public function testIoExceptionThrownIfRemoteNotAvailable() {
    $connection = $this->dnode->connect('localhost', self::DNODE_TEST_PORT);

    $this->stopNodeEchoService();

    $this->setExpectedException('\DnodeSyncClient\IOException',
      "Can't read response from remote");
    
    $connection->call("echo");
  }

  public function testConnectionClosedException() {
    $connection = $this->dnode->connect('localhost', self::DNODE_TEST_PORT);

    $connection->close();

    $this->setExpectedException('\DnodeSyncClient\ConnectionClosedException');

    $connection->call("echo");
  }

  public function testConnectionClosedOnFailedConnection() {
    $connection = $this->dnode->connect('localhost', self::DNODE_TEST_PORT);

    $this->stopNodeEchoService();
    try {
      $connection->call("echo");
      $this->fail("Call should fail with IOException");
    } catch (IOException $e) {
      // ignoring, this is expected
    }

    $this->setExpectedException('\DnodeSyncClient\ConnectionClosedException');

    $connection->call("echo");
  }


  public function testIOExceptionOnConnectToStoppedService() {
    $this->stopNodeEchoService();

    $this->setExpectedException('\DnodeSyncClient\IOException',
      "Can't create socket to tcp://localhost:".self::DNODE_TEST_PORT);

    $this->dnode->connect('localhost', self::DNODE_TEST_PORT);
  }
}

