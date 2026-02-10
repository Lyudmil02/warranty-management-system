<?php
session_start();
include "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Not logged in");
}

if (!isset($_POST['id'])) {
    http_response_code(400);
    exit("No ID provided");
}

$id = (int)$_POST['id'];
$user = $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $id, $user);
$stmt->execute();
$stmt->close();

echo "OK";
