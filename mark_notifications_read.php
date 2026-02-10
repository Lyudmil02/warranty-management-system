<?php
session_start();
include 'db_connection.php';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = ? AND is_read = 0
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

echo "OK";
