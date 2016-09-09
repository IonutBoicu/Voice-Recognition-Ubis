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

function getEventsAction($read_sock, $conn) {

    $write_buf_arr = [];

    $ev_result = getAllEvents($conn);

    while ($ev_res = $ev_result->fetch_assoc()) {
        $write_buf =
                $ev_res["rname"] . "#" .
                $ev_res["id"] . "#" .
                $ev_res["name"] . "#" .
                $ev_res["start_date"] . "#" .
                $ev_res["end_date"] . "\n";

        $write_buf_arr[] = $write_buf;
    }

    $no = (string)count($write_buf_arr) . "\n";
//    echo $no;

    socket_write($read_sock, $no, strlen($no));

    foreach ($write_buf_arr as $write) {
        socket_write($read_sock, $write, strlen($write));
//        echo $write;
    }
}

function getEventsTimeframeAction($read_sock, $conn,
                        $start_date, $end_date) {
    $write_buf = [];

    $ev_result = getEventsByTimeframe($conn, $start_date, $end_date);

    while ($ev_res = $ev_result->fetch_assoc()) {
        $write_buf =
                $ev_res["rname"] . "#" .
                $ev_res["id"] . "#" .
                $ev_res["name"] . "#" .
                $ev_res["start_date"] . "#" .
                $ev_res["end_date"] . "#" . 
                $ev_res["details"] . "\n";

        $write_buf_arr[] = $write_buf;
    }

    $no = (string)count($write_buf_arr) . "\n";
//    echo $no;

    socket_write($read_sock, $no, strlen($no));

    foreach ($write_buf_arr as $write) {
        socket_write($read_sock, $write, strlen($write));
//        echo $write;
    }
}

function addEventAction($read_sock, $conn,
        $email, $room, $name, $start_date,
        $duration, $smart) {

    $id_room = getRoomByName($conn, $room);
    
    $int = explode(":", $duration);
    
    $end = new DateTime($start_date);
    
    $end->add(new DateInterval("PT" . $int[0] . "H" . $int[1] . "M"));
    
    $end_date = $end->format('Y-m-d H:i');
    
    if(checkAvailability($conn, $start_date, $end_date, $id_room)) {
        $query = "INSERT INTO best_smart.events "
                . "(name, start_date, end_date, id_room, host_email) "
                . "VALUES('$name', '$start_date', '$end_date', '$id_room', '$email')";
        
        if($conn->query($query)) {
            $msg = "SUCCESS\n";
        } else {
            echo $conn->error;
            $msg = "FAIL\n";
        }
    } else if(isset($smart) && $smart){
        $conn->autocommit(false);
        
        $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
        
        $query = "INSERT INTO best_smart.events "
                . "(name, start_date, end_date, id_room, host_email) "
                . "VALUES('$name', '$start_date', '$end_date', '$id_room', '$email')";
        $conn->query($query);
        
        $col_events = getColidingEvents($conn, $start_date, $end_date, $id_room);
        $res = getAvailableRoom($conn, $end_date);
        
        if($res) {
            $av_room = $res[0];
            
            
        } else {
            $av_room = null;
        }
        
        while ($av_room != null) {
            $ev = $col_events[0];
            
            unset($col_events[0]);
            
            changeEventRoom($conn, $ev["id"], $av_room["id_room"]);
            
            if (count($col_events) > 0) {
                $start_date = $col_events[1]["start_date"];
                $end_date = $col_events[1]["end_date"];
                $id_room = $col_events[1]["id_room"];
            } else {
                break;
            }
            
            $aux = getColidingEvents($conn, $start_date, $end_date, $id_room);
            
            $col_events = array_merge(array_values($col_events), $aux);
            
            $res = getAvailableRoom($conn, $end_date);
        
            if($res) {
                $av_room = $res[0];
            } else {
                $av_room = null;
            }
        }
        
        if (count($col_events) == 0) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
        
        $conn->autocommit(true);
    } else {
        $msg = "FAIL\n";
    }
    socket_write($read_sock, $msg, strlen($msg));
}

function updateEventAction($read_sock, $conn,
    $id, $rname, $start_date, $end_date) {
    
    $upd = [];
    
    if ($rname != " ") {
        $id_room = getRoomByName($conn, $rname);
        
        $upd[] = "id_room = '$id_room'";
    }
    
    if ($start_date != " ") {
        $upd[] = "start_date = '$start_date'";
    }
    
    if ($end_date != " ") {
        $upd[] = "end_date = '$end_date'";
    }
    
    $query = "UPDATE best_smart.events "
            . "SET " . implode(", ", $upd) . " WHERE id = '$id'";

    if($conn->query($query)) {
        $msg = "SUCCESS\n";
    } else {
        echo $conn->error;
        $msg = "FAIL\n";
    }
    socket_write($read_sock, $msg, strlen($msg));
}

function delEventAction($read_sock, $conn, $id) {
    $query = "DELETE FROM best_smart.events "
            . "WHERE id = '$id'";
    
    if($conn->query($query)) {
        $msg = "SUCCESS\n";
    } else {
        echo $conn->error;
        $msg = "FAIL\n";
    }
    socket_write($read_sock, $msg, strlen($msg));
}
?>