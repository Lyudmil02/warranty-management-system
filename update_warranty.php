<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}

$user_id = $_SESSION['user_id'];

$id                 = intval($_POST['id']);
$product_name       = $_POST['product_name'] ?? '';
$purchase_date      = $_POST['purchase_date'] ?? '';
$warranty_months    = isset($_POST['warranty_months']) ? (int)$_POST['warranty_months'] : 0;
$price              = ($_POST['price'] !== '' ? (float)$_POST['price'] : null);
$supplier           = $_POST['supplier'] ?? '';
$note               = $_POST['note'] ?? '';
$notify_days_before = isset($_POST['notify_days_before']) ? (int)$_POST['notify_days_before'] : 30;

$warranty_end = date('Y-m-d', strtotime("+$warranty_months months", strtotime($purchase_date)));


$uploadDirRel = 'uploads/';
$uploadDirFs  = rtrim(__DIR__, '/\\') . '/' . $uploadDirRel;
if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0777, true);
}

function uploadFiles(string $fieldName, string $uploadDirFs, string $uploadDirRel): array {
    $out = [];
    if (!isset($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) return $out;

    $allowed = ['pdf','png','jpg','jpeg','webp','gif','heic'];
    $names = $_FILES[$fieldName]['name'];
    $tmps  = $_FILES[$fieldName]['tmp_name'];
    $errs  = $_FILES[$fieldName]['error'];

    if (!is_array($names)) { $names = [$names]; $tmps = [$tmps]; $errs = [$errs]; }

    foreach ($names as $i => $origName) {
        if ($origName === '' || $errs[$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        $safeBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $newName  = uniqid() . '_' . $safeBase . ($ext ? '.'.$ext : '');
        $destFs   = $uploadDirFs . $newName;
        $destRel  = $uploadDirRel . $newName;

        if (move_uploaded_file($tmps[$i], $destFs)) {
            $out[] = str_replace('\\', '/', $destRel);
        }
    }
    return $out;
}

$current_files = [];

$stmtOld = $conn->prepare("SELECT files FROM warranties WHERE id = ? AND user_id = ?");
$stmtOld->bind_param("ii", $id, $user_id);
$stmtOld->execute();
$resOld = $stmtOld->get_result();

if ($row = $resOld->fetch_assoc()) {
    if (!empty($row['files'])) {
        $decoded = json_decode($row['files'], true);
        if (is_array($decoded)) $current_files = $decoded;
    }
}
$stmtOld->close();

$receipt_files  = uploadFiles('receipt_files',  $uploadDirFs, $uploadDirRel);
$warranty_files = uploadFiles('warranty_files', $uploadDirFs, $uploadDirRel);

$MAX_RECEIPT = 5;
$MAX_WARRANTY = 5;
$MAX_TOTAL = 10;

if (count($receipt_files) > $MAX_RECEIPT) {
    die("Грешка: Максимумът е $MAX_RECEIPT файла за секцията 'Качи файлове'.");
}
if (count($warranty_files) > $MAX_WARRANTY) {
    die("Грешка: Максимумът е $MAX_WARRANTY файла за секцията 'Качи гаранционна карта'.");
}

$current_count = count($current_files);
$new_count = count($receipt_files) + count($warranty_files);

if ($current_count + $new_count > $MAX_TOTAL) {

	$error_message = "Може да имате максимум $MAX_TOTAL файла общо. Вече имате $current_count и опитвате да добавите още $new_count.";
	include "error_view.php";
	exit;
}

$existing_count = count($current_files);

if (count($receipt_files) > $MAX_RECEIPT) {
    $error_message = "Може да качите максимум $MAX_RECEIPT файла за секцията 'Качи файлове'.";
	include "error_view.php";
	exit;
}
if (count($warranty_files) > $MAX_WARRANTY) {
    die("Грешка: Може да качите максимум $MAX_WARRANTY файла за секцията 'Качи гаранционна карта'.");
}

$new_files = array_merge($receipt_files, $warranty_files);

if (count($new_files) > 0) {
    $merged = array_values(array_unique(array_merge($current_files, $new_files)));
    $files_json = json_encode($merged, JSON_UNESCAPED_SLASHES);
    $have_new_files = true;
} else {
    $have_new_files = false;
}

if ($have_new_files) {
    $sql = "UPDATE warranties 
            SET product_name = ?, purchase_date = ?, warranty_months = ?, warranty_end = ?, 
                price = ?, supplier = ?, note = ?, notify_days_before = ?, 
                reminder_stage = 0, files = ?
            WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($sql);

    $price_str = isset($price) ? (string)$price : null;

    $stmt->bind_param(
        "ssissssissi",
        $product_name,
        $purchase_date,
        $warranty_months,
        $warranty_end,
        $price_str,
        $supplier,
        $note,
        $notify_days_before,
        $files_json,
        $id,
        $user_id
    );
} else {
    $sql = "UPDATE warranties 
            SET product_name = ?, purchase_date = ?, warranty_months = ?, warranty_end = ?, 
                price = ?, supplier = ?, note = ?, notify_days_before = ?, 
                reminder_stage = 0
            WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($sql);

    $price_str = isset($price) ? (string)$price : null;

    $stmt->bind_param(
        "ssissssiii",
        $product_name,
        $purchase_date,
        $warranty_months,
        $warranty_end,
        $price_str,
        $supplier,
        $note,
        $notify_days_before,
        $id,
        $user_id
    );
}

if ($stmt->execute()) {
    header("Location: my_warranties.php");
    exit();
} else {
    echo "Грешка при обновяване: " . $stmt->error;
}

$stmt->close();
$conn->close();
