<?php

namespace Erasys\DnodeSyncClient;

use Symfony\Component\Process\Process;

class DnodeTest extends \PHPUnit_Framework_TestCase
{
    const DNODE_TEST_HOST = '127.0.0.1';
    const DNODE_TEST_PORT = 8080;
    const NODE_BINARY = 'node';

    /**
     * @var Dnode
     */
    protected $dnode;

    /**
     * @var Process
     */
    protected $process;

    public static function setUpBeforeClass()
    {
        self::setUpNode();
    }

    public function setUp()
    {
        $this->dnode = new Dnode();

        $this->startNodeEchoService();
    }

    public function tearDown()
    {
        $this->stopNodeEchoService();
    }

    private static function setUpNode()
    {
        $process = new Process('npm install', __DIR__.DIRECTORY_SEPARATOR.'node');
        $process->setTimeout(null);
        $process->mustRun();
    }

    private function startNodeEchoService()
    {
        $this->process = new Process(
            sprintf(
                '%s %s %s',
                (DIRECTORY_SEPARATOR === '/' ? 'exec ' : '').self::NODE_BINARY,
                escapeshellarg(__DIR__.DIRECTORY_SEPARATOR.'node'.DIRECTORY_SEPARATOR.'echo.js'),
                self::DNODE_TEST_PORT
            )
        );
        $this->process->setTimeout(null);
        $this->process->start();

        $failMessage = "Unable to start test dnode service.\n"
            .'To debug, try to run this: '.$this->process->getCommandLine()."\n"
            ."Maybe the dnode is not installed? run 'npm install .' in node directory.";

        if (!$this->process->isRunning()) {
            $this->fail($failMessage);
        }

        $start = microtime(true);
        do {
            $line = trim($this->process->getOutput());
        } while (!$line && microtime(true) - $start < 5);

        $this->assertEquals(
            'echo service started on port '.self::DNODE_TEST_PORT,
            $line,
            $failMessage
        );
    }

    private function stopNodeEchoService()
    {
        if ($this->process) {
            $this->process->stop();
            $this->process = null;
        }
    }

    public function testRemoteAvailableMethods()
    {
        $connection = $this->dnode->connect(self::DNODE_TEST_HOST, self::DNODE_TEST_PORT);

        $response = $connection->call('echo', array('argument'));

        $this->assertEquals(array(null, 'argument'), $response);
    }

    public function testEchoService()
    {
        $connection = $this->dnode->connect(self::DNODE_TEST_HOST, self::DNODE_TEST_PORT);

        $response = $connection->call('echo', array('argument'));

        $this->assertEquals(array(null, 'argument'), $response);
    }

    public function testIoExceptionThrownIfRemoteNotAvailable()
    {
        $connection = $this->dnode->connect(self::DNODE_TEST_HOST, self::DNODE_TEST_PORT);

        $this->stopNodeEchoService();

        $this->setExpectedException(
            Exception\IOException::class,
            "Can't read response from remote"
        );

        $connection->call('echo');
    }

    public function testConnectionClosedException()
    {
        $connection = $this->dnode->connect(self::DNODE_TEST_HOST, self::DNODE_TEST_PORT);

        $connection->close();

        $this->setExpectedException(Exception\ConnectionClosedException::class);

        $connection->call('echo');
    }

    public function testConnectionClosedOnFailedConnection()
    {
        $connection = $this->dnode->connect(self::DNODE_TEST_HOST, self::DNODE_TEST_PORT);

        $this->stopNodeEchoService();

        try {
            $connection->call('echo');
            $this->fail('Call should fail with IOException');
        } catch (Exception\IOException $e) {
            // ignoring, this is expected
        }

        $this->setExpectedException(Exception\ConnectionClosedException::class);

        $connection->call('echo');
    }

    public function testIOExceptionOnConnectToStoppedService()
    {
        $this->stopNodeEchoService();

        $this->setExpectedException(
            Exception\IOException::class,
            "Can't create socket to tcp://".self::DNODE_TEST_HOST.":".self::DNODE_TEST_PORT
        );

        $this->dnode->connect(self::DNODE_TEST_HOST, self::DNODE_TEST_PORT);
    }
}
