<?php

/**
 * Dnode Synchronous Client for PHP.
 *
 * @copyright 2012 erasys GmbH - see ./LICENSE.txt for more info
 */

namespace Erasys\DnodeSyncClient;

/**
 * Connection to dnode service.
 */
class Connection
{
    private $stream;
    private $methods;
    private $callbackNumber = 41;   // lets start from some higher number to make
                                    // sure that remote is using our callback numbers
    private $closed = false;

    /**
     * Initializes connect on given stream.
     *
     * Do not use directly if you know host and port of dnode service, rather use
     * \DnodeSyncClient\Dnode::connect
     *
     * @param resource $stream
     *
     * @throws Exception\IOException
     * @throws Exception\ProtocolException
     */
    public function __construct($stream)
    {
        $this->stream = $stream;

        // write our (empty) methods description
        @fwrite($this->stream, json_encode(array('method' => 'methods'))."\n");

        // read remote methods
        $line = fgets($this->stream);
        if ($line === false) {
            throw new Exception\IOException("Can't read method description from remote");
        }
        $line = trim($line);
        $methods = json_decode($line, true);
        if ($methods === null) {
            throw new Exception\ProtocolException("First line is not valid json: $line");
        }
        if (!isset($methods['method'])) {
            throw new Exception\ProtocolException("First line does not have method field: $line");
        }
        if ($methods['method'] !== 'methods') {
            throw new Exception\ProtocolException("First line method must be \"methods\": $line");
        }
        if (!isset($methods['arguments'])) {
            throw new Exception\ProtocolException("Methods arguments missing: $line");
        }
        if (count($methods['arguments']) != 1) {
            throw new Exception\ProtocolException("Methods must have single argument: $line");
        }

        $this->methods = array_keys($methods['arguments'][0]);
        if (count($this->methods) == 0) {
            throw new Exception\ProtocolException("Remote is expected to have some methods: $line");
        }
    }

    /**
     * Calls method on this dnode connection.
     *
     * @param string $method    Method name
     * @param array  $arguments Arguments
     *
     * @return array response arguments as array
     *
     * @throws Exception\MethodNotExistsException thrown if remote does not declare called method
     * @throws Exception\IOException              Thrown in case of network error
     * @throws Exception\ProtocolException        thrown if remote answer does not have supported format
     */
    public function call($method, array $arguments = array())
    {
        if ($this->closed) {
            throw new Exception\ConnectionClosedException();
        }

        if (!in_array($method, $this->methods)) {
            throw new Exception\MethodNotExistsException("Method $method does not exists on remote.");
        }

        $callbacks = new \stdclass();
        $callbacks->{++$this->callbackNumber} = array(count($arguments));

        @fwrite($this->stream, json_encode(array(
                'method' => $method,
                'arguments' => $arguments,
                'callbacks' => $callbacks,
            ))."\n");

        // this will block the stream until response is read
        $line = fgets($this->stream);
        if ($line === false) {
            $this->close();
            throw new Exception\IOException("Can't read response from remote");
        }

        $line = trim($line);
        $message = json_decode($line, true);

        if ($message === null) {
            throw new Exception\ProtocolException("Response is not valid json: $line");
        }
        if (!isset($message['method'])) {
            throw new Exception\ProtocolException("Response does not have method field: $line");
        }
        if ($message['method'] !== $this->callbackNumber) {
            throw new Exception\ProtocolException('Response does not call expected callback, expected '
                .$this->callbackNumber.", got $line");
        }
        if (isset($message['links']) && $message['links']) {
            throw new Exception\ProtocolException("Response contains links, we do not support that: $line");
        }
        if (isset($message['callbacks']) && $message['callbacks']) {
            throw new Exception\ProtocolException("Response contains callbacks, we do not support that: $line");
        }
        if (!array_key_exists('arguments', $message)) {
            return array();
        }
        if (!is_array($message['arguments'])) {
            throw new Exception\ProtocolException("Response arguments must be array: $line");
        }

        return $message['arguments'];
    }

    /**
     * Lists methods available by remote dnode service.
     *
     * @return array
     */
    public function getAvailableMethods()
    {
        return $this->methods;
    }

    /**
     * Closes this connection.
     */
    public function close()
    {
        fclose($this->stream);
        $this->closed = true;
    }

    /**
     * Returns whether connection has been closed.
     *
     * @return bool
     */
    public function isClosed()
    {
        return $this->closed;
    }
}
