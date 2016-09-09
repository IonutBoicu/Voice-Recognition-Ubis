<?php
include 'dbactions.php';

error_reporting(E_ERROR | E_WARNING | E_PARSE);

function loginAction($read_sock, $conn, $email, $password) {
    $query = "SELECT * FROM best_smart.users"
            . " where email = '$email' and password = '$password' and disable='0'";

    $result = $conn->query($query);

    $arr = $result->fetch_all();

    if (count($arr) == 1) {
        $write_buf = "ACCEPT\n";
    }
    else {
        $write_buf = "REJECT\n";
    }

    socket_write($read_sock, $write_buf, strlen($write_buf));
}

?>