<?php

namespace Erasys\DnodeSyncClient;

class TestStreamWrapper
{
    public function stream_open()
    {
        return true;
    }

    private static $writeQueue = array();
    private static $readQueue = array();

    public static function addRead($data)
    {
        self::$readQueue[] = $data;
    }

    public function stream_read()
    {
        return array_shift(self::$readQueue);
    }

    public function stream_eof()
    {
        return (bool) self::$readQueue;
    }

    public function stream_write($data)
    {
        self::$writeQueue[] = $data;

        return strlen($data);
    }

    public static function getWrites()
    {
        $writes = self::$writeQueue;
        self::$writeQueue = array();

        return $writes;
    }
}
