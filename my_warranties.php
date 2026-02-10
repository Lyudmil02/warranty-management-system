<?php
include 'db_connection.php';
session_start();
include 'update_statuses.php';

if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}


$user_id      = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? "";
$sort_column   = $_GET['sort']  ?? "";
$sort_order    = (($_GET['order'] ?? "asc") === "desc") ? "DESC" : "ASC";

$search = trim($_GET['search'] ?? "");

$allowed_columns = ['product_name','purchase_date','warranty_months','warranty_end','price','supplier','status','note'];
$has_explicit_sort = in_array($sort_column, $allowed_columns);
$where_sql   = "WHERE user_id = ?";
$bind_types  = "i";
$bind_values = [$user_id];

if ($status_filter && in_array($status_filter, ['ACTIVE','EXPIRED','EXPIRES_SOON'])) {
    $where_sql .= " AND status = ?";
    $bind_types .= "s";
    $bind_values[] = $status_filter;
}

if ($search !== "") {
    $where_sql .= " AND product_name LIKE ?";
    $bind_types .= "s";
    $bind_values[] = "%$search%";
}


if ($has_explicit_sort) {
    if ($sort_column === 'price') {
        $order_by = "CAST(price AS DECIMAL(18,2)) $sort_order";
    }
    elseif ($sort_column === 'note') {
        $order_by = "
            (note IS NULL OR TRIM(note) = '') ASC,
            note $sort_order
        ";
    }
    else {
        $order_by = "$sort_column $sort_order";
    }
} else {
    $order_by = "
        CASE 
          WHEN status = 'EXPIRES_SOON' THEN 1
          WHEN status = 'ACTIVE'       THEN 2
          WHEN status = 'EXPIRED'      THEN 3
          ELSE 4
        END,
        warranty_end ASC
    ";
}

$sql = "
    SELECT id, product_name, purchase_date, warranty_months, warranty_end,
           price, supplier, status, note, files
    FROM warranties
    $where_sql
    ORDER BY $order_by
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($bind_types, ...$bind_values);
$stmt->execute();
$result = $stmt->get_result();

$current_sort = $has_explicit_sort ? $sort_column : "";
$next_order   = ($sort_order === "ASC") ? "desc" : "asc";

function sort_icon($column, $current_sort, $current_order) {
    if ($column !== $current_sort) return "⇅";
    return ($current_order === "ASC") ? "↑" : "↓";
}
function sort_link($column, $label, $status_filter, $current_sort, $current_order, $next_order) {
    $link = "my_warranties.php?sort=$column&order=$next_order";
    if ($status_filter) $link .= "&status=$status_filter";
    return "$label <a href=\"$link\">" . sort_icon($column, $current_sort, $current_order) . "</a>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $stmt1 = $conn->prepare("DELETE FROM warranties WHERE user_id = ?");
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $stmt2->close();

    session_unset();
    session_destroy();

    header("Location: auth.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Моите гаранции</title>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Styles/my_warranties.css">
</head>
<body>
	<div class="main">
		<div class="top-actions">
			<div class="left-buttons">
				<a href="dashboard.php" class="dash-btn">
					<i class='bx bx-chevron-left'></i> Назад
				</a>

				<a href="my_warranties.php" class="dash-btn">
					<i class='bx bx-refresh'></i> Обнови
				</a>
				
				<a href="#" class="dash-btn" id="currency-toggle">
					<i class='bx bx-transfer'></i> EUR
				</a>
			</div>

			<h1>Моите гаранции</h1>

			<div class="action-buttons">
				<a href="add_warranty.php" class="add-btn">
					<i class='bx bx-plus'></i> Нова гаранция
				</a>

				<form method="get" class="filter-inline" action="my_warranties.php">
					<select name="status">
						<option value="" <?= $status_filter == "" ? "selected" : "" ?>>Всички</option>
						<option value="ACTIVE" <?= $status_filter == "ACTIVE" ? "selected" : "" ?>>Активни</option>
						<option value="EXPIRES_SOON" <?= $status_filter == "EXPIRES_SOON" ? "selected" : "" ?>>Изтичащи</option>
						<option value="EXPIRED" <?= $status_filter == "EXPIRED" ? "selected" : "" ?>>Изтекли</option>
					</select>
					<button type="submit"><i class='bx bx-filter-alt'></i></button>
				</form>
			</div>
		</div>

		<div class="table-container">
			<?php
			$status_labels = [
				"ACTIVE" => "Активна",
				"EXPIRES_SOON" => "Изтича скоро",
				"EXPIRED" => "Изтекла"
			];
			?>

			<div class="table-view">
				<table>
					<tr>
						<th><?= sort_link("product_name", "Продукт", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th><?= sort_link("purchase_date", "Дата на покупка", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th><?= sort_link("warranty_months", "Гаранция (мес)", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th><?= sort_link("warranty_end", "Край на гаранцията", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th><?= sort_link("price", "Цена", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th><?= sort_link("supplier", "Доставчик", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th class="status-col"><?= sort_link("status", "Статус", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th><?= sort_link("note", "Забележка", $status_filter, $sort_column, $sort_order, $next_order) ?></th>
						<th>Файлове</th>
						<th>Действия</th>
					</tr>

					<?php while ($row = $result->fetch_assoc()) { ?>
						<tr class="<?= $row['status'] ?>">
							<td><?= htmlspecialchars($row['product_name']) ?></td>
							<td><?= htmlspecialchars($row['purchase_date']) ?></td>
							<td><?= $row['warranty_months'] ?></td>
							<td><?= $row['warranty_end'] ?></td>
							<td class="price-cell" data-raw="<?= $row['price'] ?>">
								<?= $row['price'] ?> лв.
							</td>
							<td><?= htmlspecialchars($row['supplier']) ?></td>
							<td class="status-col status <?= $row['status'] ?>">
								<?= $status_labels[$row['status']] ?? $row['status'] ?>
							</td>
							<td><?= $row['note'] ?></td>
							<td class="files">
								<?php
								$fileLinks = [];

								if (!empty($row['files'])) {
									$decoded = json_decode($row['files'], true);

									if (is_array($decoded) && count($decoded) > 0) {
										foreach ($decoded as $file) {
											$encoded = urlencode($file);
											$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

											if (in_array($ext, ['pdf'])) {
												$icon = "bx bx-file";
												$title = "PDF документ";
											} elseif (in_array($ext, ['jpg','jpeg','png','webp','gif','heic'])) {
												$icon = "bx bx-image";
												$title = "Изображение";
											} else {
												$icon = "bx bx-paperclip";
												$title = "Файл";
											}

											$fileLinks[] = "<a href='view_file.php?path=$encoded' target='_blank' title='$title'>
																<i class='$icon'></i>
															</a>";
										}
									}
								}

								echo !empty($fileLinks)
									? implode(' ', $fileLinks)
									: "<span class='no-files'>—</span>";
								?>
							</td>

							<td class="actions">
								<a href="edit_warranty.php?id=<?= $row['id'] ?>"><i class='bx bx-edit-alt'></i></a>
								<a href="delete_warranty.php?id=<?= $row['id'] ?>" onclick="return confirm('Сигурен ли сте, че искате да изтриете тази гаранция?')"><i class='bx bx-trash'></i></a>
							</td>
						</tr>
					<?php } ?>
				</table>
			</div>
			<div class="card-view">
				<?php
				$status_labels = [
				  "ACTIVE" => "Активна",
				  "EXPIRES_SOON" => "Изтича скоро",
				  "EXPIRED" => "Изтекла"
				];

				$result->data_seek(0);

				while ($row = $result->fetch_assoc()) { ?>
				  <div class="warranty-card <?= $row['status'] ?>">

					<div class="card-head">
					  <div class="card-title"><?= htmlspecialchars($row['product_name']) ?></div>
					  <div class="card-status status <?= $row['status'] ?>">
						<?= $status_labels[$row['status']] ?? $row['status'] ?>
					  </div>
					</div>

					<div class="card-grid">
					  <div class="kv"><span>Покупка</span><b><?= htmlspecialchars($row['purchase_date']) ?></b></div>
					  <div class="kv"><span>Край</span><b><?= htmlspecialchars($row['warranty_end']) ?></b></div>
					  <div class="kv"><span>Гаранция</span><b><?= (int)$row['warranty_months'] ?> мес.</b></div>

					  <div class="kv">
						<span>Цена</span>
						<b class="price-cell" data-raw="<?= $row['price'] ?>">
						  <?= $row['price'] ?> лв.
						</b>
					  </div>

					  <div class="kv"><span>Доставчик</span><b><?= htmlspecialchars($row['supplier']) ?></b></div>
					</div>

					<?php if (!empty(trim($row['note'] ?? ""))): ?>
					  <div class="card-note"><?= htmlspecialchars($row['note']) ?></div>
					<?php endif; ?>

					<div class="card-foot">
					  <div class="card-files">
						<?php
						$fileLinks = [];
						if (!empty($row['files'])) {
						  $decoded = json_decode($row['files'], true);
						  if (is_array($decoded) && count($decoded) > 0) {
							foreach ($decoded as $file) {
							  $encoded = urlencode($file);
							  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
							  if ($ext === 'pdf') $icon = "bx bx-file";
							  elseif (in_array($ext, ['jpg','jpeg','png','webp','gif','heic'])) $icon = "bx bx-image";
							  else $icon = "bx bx-paperclip";

							  $fileLinks[] = "<a href='view_file.php?path=$encoded' target='_blank'><i class='$icon'></i></a>";
							}
						  }
						}
						echo !empty($fileLinks) ? implode(' ', $fileLinks) : "<span class='no-files'>—</span>";
						?>
					  </div>

					  <div class="card-actions">
						<a href="edit_warranty.php?id=<?= $row['id'] ?>" title="Редакция"><i class='bx bx-edit-alt'></i></a>
						<a href="delete_warranty.php?id=<?= $row['id'] ?>" onclick="return confirm('Сигурен ли сте, че искате да изтриете тази гаранция?')" title="Изтрий"><i class='bx bx-trash'></i></a>
					  </div>
					</div>

				  </div>
				<?php } ?>
			</div>

		</div>
	</div>

	<script src="Scripts/my_warranties.js" defer></script>

</body>
</html>


<?php
$stmt->close();
$conn->close();
?>
