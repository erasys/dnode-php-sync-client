dnode-php-sync-client
=====================

Minimalistic [dnode](https://github.com/substack/dnode) client for PHP, supports only synchronous calling of methods on remote server.

* It can call method on remote dnode server and it can receive response.
* It does not support any other callbacks.
* It does not support full [dnode-protocol](https://github.com/substack/dnode-protocol) - response
   from remote server must not contain any callbacks or links section.

Look at [dnode-php](https://github.com/bergie/dnode-php) if you are looking
for more complex support of dnode protocol.

Usage
-----

```php
<?php
require_once "dnode-php-sync-client/DnodeSyncClient.php";
$dnode = new \DnodeSyncClient\Dnode();
$connection = $dnode->connect('localhost', 5050);
$response = $connection->call('methodName', array('argument1', 'argument2'));
```

Requirements
------------

* php 5.3 - namespaces are used
* phpunit - tests were written with phpunit 3.6

Run tests
---------

To run all tests, just run 'phpunit .' from the main directory.

_test/DnodeTest.php is integration test which needs dnode echo service running.
Sources for this test service are in _test/node directory. You need to first 
install dnode dependency by running "npm install ." inside that directory.
Once dnode is installed, DnodeTest.php will start the echo service on port
8080 when necessary.

The usual
---------

We are [hiring](http://www.erasys.de/public/front_content.php?idcat=9)!