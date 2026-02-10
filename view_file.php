<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Достъп само за влезли потребители.");
}

if (empty($_GET['path'])) {
    die("Невалиден файл.");
}

$path = str_replace('\\', '/', urldecode($_GET['path']));
$path = ltrim($path, '/');

$base_dir = realpath(__DIR__ . '/uploads');
$real_path = realpath(__DIR__ . '/' . $path);

if (!$real_path || strpos($real_path, $base_dir) !== 0) {
    die("Нямате достъп до този файл.");
}

if (!file_exists($real_path)) {
    die("Файлът не съществува.");
}

$mime_type = mime_content_type($real_path);
header("Content-Type: $mime_type");

header("Content-Disposition: inline; filename=\"" . basename($real_path) . "\"");
header("Content-Length: " . filesize($real_path));

readfile($real_path);
exit;
?>
