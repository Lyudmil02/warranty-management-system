<?php
session_start();
if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}
?>

<!DOCTYPE html>
<html lang="bg">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Добави гаранция</title>
		<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
		<link rel="stylesheet" href="Styles/add_warranty.css">
	</head>
	<body>

		<div class="form-container">
			<h1><i class='bx bx-plus-circle'></i> Добави нова гаранция</h1>
			<form action="save_warranty.php" method="post" enctype="multipart/form-data">
				<label>Име на продукт:</label>
				<input type="text" name="product_name" required>

				<label>Дата на покупка:</label>
				<input type="date" name="purchase_date" required>

				<label>Продължителност на гаранцията (в месеци):</label>
				<input type="number" name="warranty_months" min="1" required>

				<label>Цена (лв.) :</label>
				<input type="number" step="0.01" name="price">

				<label>Доставчик / Магазин:</label>
				<input type="text" name="supplier">

				<label>Качи файлове (PDF / изображения):</label>
				<input type="file" name="receipt_files[]" multiple accept=".pdf,.jpg,.jpeg,.png" data-max="5">

				<div id="fileList"></div>
				
				<label>Качи гаранционна карта (PDF / изображения):</label>
				<input type="file" name="warranty_files[]" multiple accept=".pdf,.jpg,.jpeg,.png" data-max="5">

				<div id="warrantyFileList"></div>

				<label>Забележка:</label>
				<textarea name="note"></textarea>

				<label>Известяване преди изтичане (в дни):</label>
				<input type="number" name="notify_days_before" value="30" min="1">

				<input type="submit" value="Запиши гаранцията">
			</form>

			<a href="my_warranties.php" class="back-link">← Всички гаранции</a>
		</div>
		<script src="Scripts/add_warranty.js" defer></script>
	</body>
</html>
