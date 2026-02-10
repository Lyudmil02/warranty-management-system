<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Невалиден достъп.");
}

$warranty_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT product_name, purchase_date, warranty_months, price, supplier, note, notify_days_before, files
    FROM warranties 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $warranty_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Гаранцията не е намерена.");
}

$data = $result->fetch_assoc();
$stmt->close();

$existing_files = [];
if (!empty($data['files'])) {
    $decoded = json_decode($data['files'], true);
    if (is_array($decoded)) {
        $existing_files = $decoded;
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Редакция на гаранция</title>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="Styles/edit_warranty.css">
</head>
<body>

<div class="form-container">
    <h1><i class='bx bx-edit-alt'></i> Редакция на гаранция</h1>

    <form action="update_warranty.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $warranty_id ?>">

        <label>Име на продукт:</label>
        <input type="text" name="product_name" value="<?= htmlspecialchars($data['product_name']) ?>" required>

        <label>Дата на покупка:</label>
        <input type="date" name="purchase_date" value="<?= $data['purchase_date'] ?>" required>

        <label>Продължителност на гаранцията (в месеци):</label>
        <input type="number" name="warranty_months" value="<?= $data['warranty_months'] ?>" min="1" required>

        <label>Цена (лв) :</label>
        <input type="number" step="0.01" name="price" value="<?= $data['price'] ?>">

        <label>Доставчик / Магазин:</label>
        <input type="text" name="supplier" value="<?= htmlspecialchars($data['supplier']) ?>">

        <label>Забележка:</label>
        <textarea name="note"><?= htmlspecialchars($data['note']) ?></textarea>

        <label>Известяване преди изтичане (в дни):</label>
        <input type="number" name="notify_days_before" value="<?= $data['notify_days_before'] ?>" min="1">

        <label>Добави нови файлове (PDF / изображения):</label>
        <input type="file" name="receipt_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.heic" data-max="5">

        <label>Добави нова гаранционна карта:</label>
        <input type="file" name="warranty_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.heic" data-max="5">

        <?php if (!empty($existing_files)) : ?>
        <div class="file-preview">
            <strong>Текущи файлове:</strong>
            <?php foreach ($existing_files as $file): 
                $encoded = urlencode($file);
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $icon = ($ext === 'pdf') ? 'bx bx-file' : 'bx bx-image';
                $filename = basename($file);
                $short = (strlen($filename) > 40) ? substr($filename, 0, 37) . "..." : $filename;
            ?>
                <div class="file-item" data-path="<?= htmlspecialchars($file) ?>">
                    <a href="view_file.php?path=<?= $encoded ?>" target="_blank" title="<?= $filename ?>">
                        <i class="<?= $icon ?>"></i> <?= htmlspecialchars($short) ?>
                    </a>
                    <i class='bx bx-trash' title="Изтрий файл"></i>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <input type="submit" value="Запази промените">
    </form>

    <a href="my_warranties.php" class="back-link">← Всички гаранции</a>
</div>

<script>
	const warrantyId = <?= $warranty_id ?>;
</script>

<script src="Scripts/edit_warranty.js" defer></script>


</body>
</html>
