<?php
include 'actions.php';

$host = "localhost";
$user = "root";
$password = "root";
$conn = new mysqli($host, $user, $password);
error_reporting(0);
date_default_timezone_set('Europe/Bucharest');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully\n";

$address = '0.0.0.0';
$port = 15660;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
}

$socks = [$sock];

do {
    $read = $socks;
    $write = NULL;
    $except = NULL;
    $sec = NULL;
    if (socket_select($read, $write, $except, $sec) == false) {
        break;
    }
    
    if (in_array($sock, $read)) {
        $socks[] = $newsock = socket_accept($sock);
        $key = array_search($sock, $read);
        unset($read[$key]);
        
        echo "New client connected " . $newsock . "\n";
    }
    
    foreach ($read as $read_sock) {
        $buf = '';
        $buf = socket_read($read_sock, 2048, PHP_NORMAL_READ);
        
        if ($buf == false) {
            $key = array_search($read_sock, $socks);
            
            socket_close($read_sock);

            unset($socks[$key]);
            echo "Client disconnected " . $read_sock . "\n";
            continue;
        }
        if (!$buf = trim($buf)) {
            continue;
        }
	}
} while (true);
socket_close($sock);
?>