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

Lets first start simple node.js server exposing `echo` method over dnode:

```javascript
var dnode = require('dnode');
var port = process.argv[2] || 8080;
dnode({
  echo: function (data, callback) {
    callback(null, data);
  }
}).listen(port);
```

Now, we can call this echo method from PHP like this:

```php
<?php
require_once "dnode-php-sync-client/DnodeSyncClient.php";
$dnode = new \DnodeSyncClient\Dnode();
$connection = $dnode->connect('localhost', 8080);
$response = $connection->call('echo', array('Hello, world!'));
var_dump($response);
```

Result:

```
array(2) {
  [0] =>
  NULL
  [1] =>
  string(13) "Hello, world!"
}
```

Requirements
------------

* php 5.3 - namespaces are used
* phpunit - tests were written with phpunit 3.6

Run tests
---------

To run all tests, just run `phpunit .` from the main directory.

`_test/DnodeTest.php` is integration test which needs dnode echo server running.
Sources for this test server are in `_test/node` directory. You need to first 
install dnode dependency by running `npm install .` from directory `_test/dnode`.
Once dnode is installed, `DnodeTest.php` will start the echo service on port
8080 when necessary.

The usual
---------

We are [hiring](http://www.erasys.de/public/front_content.php?idcat=9)!