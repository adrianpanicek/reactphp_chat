<?php

require_once "vendor/autoload.php";

use React\Socket\ConnectionInterface;

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server('127.0.0.1:8080', $loop);

$mainPool = new \App\Connection\ConnectionPool();

$socket->on('connection', function(ConnectionInterface $connection) use ($mainPool) {
	$mainPool->add($connection);
});

echo "Listening on {$socket->getAddress()}\n";

$loop->run();