/**
 * Provides a simple node.js dnode echo service
 * to test the dnode-php-sync-client
 *
 * @copyright 2012 erasys GmbH - see ../LICENSE.txt for more info
 */
var dnode = require('dnode');

var port = process.argv[2];


dnode({
  echo: function (data, callback) {
    callback(null, data);
  }
}).listen(port);

console.log("echo service started on port " + port + "\n");

