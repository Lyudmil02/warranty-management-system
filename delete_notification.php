<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Not logged in";
    exit;
}

if (!isset($_POST['id'])) {
    http_response_code(400);
    echo "Missing ID";
    exit;
}

$notifId = (int)$_POST['id'];
$userId  = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notifId, $userId);
$stmt->execute();

echo "OK";
