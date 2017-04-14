<?php

/**
 * Dnode Synchronous Client for PHP.
 *
 * @copyright 2012 erasys GmbH - see ./LICENSE.txt for more info
 */

namespace Erasys\DnodeSyncClient\Exception;

/**
 * Thrown if client of this library calls method not declared by remote.
 */
class MethodNotExistsException extends BaseException
{
}
