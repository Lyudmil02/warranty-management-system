<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}

$user_id            = $_SESSION['user_id'];
$product_name       = $_POST['product_name']        ?? '';
$purchase_date      = $_POST['purchase_date']       ?? '';
$warranty_months    = isset($_POST['warranty_months']) ? (int)$_POST['warranty_months'] : 0;
$price              = ($_POST['price'] !== '' ? (float)$_POST['price'] : null);
$supplier           = $_POST['supplier']            ?? '';
$note               = $_POST['note']                ?? '';
$notify_days_before = isset($_POST['notify_days_before']) ? (int)$_POST['notify_days_before'] : 30;

$warranty_end = date('Y-m-d', strtotime("+$warranty_months months", strtotime($purchase_date)));

$uploadDirRel = 'uploads/';
$uploadDirFs  = rtrim(__DIR__, '/\\') . '/' . $uploadDirRel;

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0777, true);
}

$MAX_RECEIPT_FILES  = 5;
$MAX_WARRANTY_FILES = 5;

$receipt_count  = isset($_FILES['receipt_files']['name'])  ? count(array_filter($_FILES['receipt_files']['name']))  : 0;
$warranty_count = isset($_FILES['warranty_files']['name']) ? count(array_filter($_FILES['warranty_files']['name'])) : 0;

if ($receipt_count > $MAX_RECEIPT_FILES) {
    die("Грешка: Може да качите максимум $MAX_RECEIPT_FILES файла за секцията 'Качи файлове'.");
}
if ($warranty_count > $MAX_WARRANTY_FILES) {
    die("Грешка: Може да качите максимум $MAX_WARRANTY_FILES файла за секцията 'Качи гаранционна карта'.");
}

function uploadFiles(string $fieldName, string $uploadDirFs, string $uploadDirRel): array {
    $out = [];
    if (!isset($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        return $out;
    }

    $allowed = ['pdf','png','jpg','jpeg','webp','gif','heic'];

    foreach ($_FILES[$fieldName]['name'] as $i => $origName) {
        if ($_FILES[$fieldName]['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        $safeBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $newName  = uniqid() . '_' . $safeBase . '.' . $ext;
        $destFs   = $uploadDirFs . $newName;
        $destRel  = $uploadDirRel . $newName;

        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'][$i], $destFs)) {
            $out[] = str_replace('\\', '/', $destRel);
        }
    }
    return $out;
}

$receipt_files  = uploadFiles('receipt_files',  $uploadDirFs, $uploadDirRel);
$warranty_files = uploadFiles('warranty_files', $uploadDirFs, $uploadDirRel);

if (count($receipt_files) > $MAX_RECEIPT_FILES) {
    die("Грешка: Прекалено много файлове качени (receipt_files).");
}
if (count($warranty_files) > $MAX_WARRANTY_FILES) {
    die("Грешка: Прекалено много файлове качени (warranty_files).");
}

$all_files = array_merge($receipt_files, $warranty_files);
$files_json = !empty($all_files) ? json_encode($all_files, JSON_UNESCAPED_SLASHES) : null;

$reminder_stage = 0;

$stmt = $conn->prepare("
    INSERT INTO warranties
        (user_id, product_name, purchase_date, warranty_months, warranty_end, price, supplier, note, notify_days_before, reminder_stage, files)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issisdsssis",
    $user_id,
    $product_name,
    $purchase_date,
    $warranty_months,
    $warranty_end,
    $price,
    $supplier,
    $note,
    $notify_days_before,
    $reminder_stage,
    $files_json
);

if ($stmt->execute()) {
    header('Location: my_warranties.php');
    exit;
} else {
    echo 'Грешка: ' . $stmt->error;
}

$stmt->close();
$conn->close();
?>
