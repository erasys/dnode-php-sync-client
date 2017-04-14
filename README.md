dnode-php-sync-client
=====================

[![Build Status (TravisCI)](https://travis-ci.org/uuf6429/dnode-php-sync-client.svg?branch=master)](https://travis-ci.org/uuf6429/dnode-php-sync-client)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.5-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/erasys/dnode-php-sync-client/blob/master/LICENSE.txt)
[![Packagist](https://img.shields.io/packagist/v/erasys/dnode-php-sync-client.svg)](https://packagist.org/packages/erasys/dnode-php-sync-client)


Minimalistic [dnode](https://github.com/substack/dnode) client for PHP, supports only synchronous calling of methods on remote server.

* It can call method on remote dnode server and it can receive response.
* It does not support any other callbacks.
* It does not support full [dnode-protocol](https://github.com/substack/dnode-protocol) - response
   from remote server must not contain any callbacks or links section.

Look at [dnode-php](https://github.com/bergie/dnode-php) if you are looking
for more complex support of dnode protocol.

Usage
-----

Let's first start with a simple node.js server exposing `echo` method over dnode:

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
require_once 'vendor/autoload.php';
$dnode = new \Erasys\DnodeSyncClient\Dnode();
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

Run tests
---------

To run all tests, just run `./vendor/bin/phpunit` from the main directory.

`tests/DnodeTest.php` is integration test which needs dnode echo server running.
Sources for this test server are in `tests/node` directory.
The test suite will automatically install npm dependencies and start the echo service on port 8080 when necessary.

The usual
---------

We are [hiring](http://www.erasys.de/jobs/)!