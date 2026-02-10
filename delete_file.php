<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$warranty_id = intval($_POST['id'] ?? 0);
$file_to_delete = $_POST['file'] ?? '';

if ($warranty_id <= 0 || !$file_to_delete) {
    echo json_encode(['success' => false, 'error' => 'invalid']);
    exit;
}

$stmt = $conn->prepare("SELECT files FROM warranties WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $warranty_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'not-found']);
    exit;
}

$row = $result->fetch_assoc();
$current_files = json_decode($row['files'], true);

if (!is_array($current_files)) {
    $current_files = [];
}

$updated_files = array_values(array_filter($current_files, function($f) use ($file_to_delete) {
    return $f !== $file_to_delete;
}));

$fullPath = realpath(__DIR__ . '/' . $file_to_delete);
$uploadsBase = realpath(__DIR__ . '/uploads');

if ($fullPath && $uploadsBase && strpos($fullPath, $uploadsBase) === 0 && file_exists($fullPath)) {
    unlink($fullPath);
}

$new_json = json_encode($updated_files, JSON_UNESCAPED_SLASHES);

$stmt = $conn->prepare("UPDATE warranties SET files = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_json, $warranty_id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
$conn->close();
