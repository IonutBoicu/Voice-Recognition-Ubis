<?php

function getEventsByTimeframe($conn, $start_date, $end_date) {
    
    $query =
            "SELECT e.id, e.name, r.name rname, r.details details, "
            . "e.start_date, "
            . "e.end_date "
            . "FROM best_smart.events e, best_smart.rooms r "
            . "WHERE start_date > '$start_date' and "
            . "end_date < '$end_date' and r.id = e.id_room "
            . "ORDER BY start_date ASC";
    
    return $conn->query($query);
}

function checkAvailability($conn, $start_date, $end_date, $id_room) {
    $query = "SELECT * FROM best_smart.events "
            . "WHERE start_date >= '$start_date' AND "
            . "end_date <= '$end_date' and id_room = '$id_room'";
    
    $result = $conn->query($query);
    
    return count($result->fetch_all()) == 0;
}

function getColidingEvents($conn, $start_date, $end_date, $id_room) {
    $query = "SELECT id, id_room FROM best_smart.events "
            . "WHERE id_room = '$id_room' AND "
            . "(start_date BETWEEN '$start_date' AND '$end_date' OR "
            . "end_date BETWEEN '$start_date' AND '$end_date') "
            . "ORDER BY start_date ASC";
    
    return $conn->query($query)->fetch_all();
}
?>