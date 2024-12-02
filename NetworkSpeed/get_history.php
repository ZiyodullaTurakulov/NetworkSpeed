<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new mysqli("localhost", "root", "", "local_speed");
    
    if ($conn->connect_error) {
        throw new Exception("Ma'lumotlar bazasiga ulanishda xatolik");
    }

    $conn->set_charset("utf8mb4");
    
    $query = "SELECT * FROM network_logs ORDER BY test_time DESC LIMIT 50";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("So'rovda xatolik");
    }
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $conn->close();
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}