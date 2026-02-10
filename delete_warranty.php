<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM warranties WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Гаранцията е изтрита успешно.";
    } else {
        echo "Невалидна гаранция или достъп отказан.";
    }

    $stmt->close();
}

$conn->close();

header("Location: my_warranties.php");
exit();
?>