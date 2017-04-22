<?php

/**
 * Dnode Synchronous Client for PHP.
 *
 * @copyright 2012 erasys GmbH - see ./LICENSE.txt for more info
 */

namespace uuf6429\DnodeSyncClient;

/**
 * Main Dnode client class.
 *
 * This is the only class you should instantiate directly from your code.
 */
class Dnode
{
    /**
     * Creates new dnode connection to given host and port.
     *
     * @param string     $host
     * @param string     $port
     * @param float|null $connectTimeout Number of seconds until `connect()` should timeout.
     *                                   Default: `ini_get("default_socket_timeout")`
     *
     * @return Connection
     *
     * @throws Exception\IOException
     * @throws Exception\ProtocolException
     */
    public function connect($host, $port, $connectTimeout = null)
    {
        return $this->connectToAddress("tcp://$host:$port", $connectTimeout);
    }

    /**
     * Creates new dnode connection to given address.
     *
     * @param string     $address
     * @param float|null $connectTimeout Number of seconds until `connect()` should timeout.
     *                                   Default: `ini_get("default_socket_timeout")`
     *
     * @return Connection
     *
     * @throws Exception\IOException
     * @throws Exception\ProtocolException
     */
    public function connectToAddress($address, $connectTimeout = null)
    {
        $stream = @stream_socket_client($address, $error, $errorMessage, $connectTimeout);

        if (!$stream) {
            throw new Exception\IOException("Can't create socket to $address. Error: $error $errorMessage");
        }

        return new Connection($stream);
    }
}
