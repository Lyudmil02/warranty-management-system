<?php
include 'db_connection.php';
session_start();
include 'update_statuses.php';

if (!isset($_SESSION['user_id'])) {
	header("Location: auth.php");
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'Потребител';

$sqlStats = "
    SELECT 
        COUNT(*) AS total_cnt,
        SUM(CASE WHEN status = 'ACTIVE'       THEN 1 ELSE 0 END) AS active_cnt,
        SUM(CASE WHEN status = 'EXPIRES_SOON' THEN 1 ELSE 0 END) AS soon_cnt,
        SUM(CASE WHEN status = 'EXPIRED'      THEN 1 ELSE 0 END) AS expired_cnt
    FROM warranties
    WHERE user_id = ?
";
$stmt = $conn->prepare($sqlStats);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$statsRes = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total    = (int)($statsRes['total_cnt']   ?? 0);
$active   = (int)($statsRes['active_cnt']  ?? 0);
$soon     = (int)($statsRes['soon_cnt']    ?? 0);
$expired  = (int)($statsRes['expired_cnt'] ?? 0);

function percent($value, $total) {
    return ($total > 0) ? round($value * 100 / $total) : 0;
}
$p_active  = percent($active,  $total);
$p_soon    = percent($soon,    $total);
$p_expired = 100 - ($p_active + $p_soon);
if ($p_expired < 0) $p_expired = 0;

$sqlNext = "
    SELECT product_name, supplier, warranty_end, status
    FROM warranties
    WHERE user_id = ?
      AND status IN ('ACTIVE','EXPIRES_SOON')
    ORDER BY warranty_end ASC
    LIMIT 1
";
$stmt = $conn->prepare($sqlNext);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$nextRes = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sqlCal = "
    SELECT product_name, supplier, warranty_end, status
    FROM warranties
    WHERE user_id = ?
";
$stmt = $conn->prepare($sqlCal);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$calRes = $stmt->get_result();

$calendarData = [];
while ($row = $calRes->fetch_assoc()) {
    $calendarData[] = [
        'date'    => $row['warranty_end'],
        'product' => $row['product_name'],
        'supplier'=> $row['supplier'],
        'status'  => $row['status']
    ];
}
$stmt->close();

$sqlRecent = "
    SELECT product_name, supplier, warranty_end, created_at, status
    FROM warranties
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($sqlRecent);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentRes = $stmt->get_result();
$stmt->close();


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

$stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unreadCount = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT id, title, message, created_at, is_read
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$status_labels = [
    "ACTIVE"        => "Активна",
    "EXPIRES_SOON"  => "Изтича скоро",
    "EXPIRED"       => "Изтекла"
];

?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Гаранционна система</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Styles/dashboard.css">
</head>
<body>
	<nav class="site-nav">
      <button class="sidebar-toggle">
        <span class="material-symbols-rounded">menu</span>
      </button>
    </nav>
	<div class="container">
		  <aside class="sidebar collapsed">
			<div class="sidebar-header">
				<div class="logo-group">
					<img src="images/WS1.png" alt="Logo" class="header-logo">
					<span class="header-title">Warranty System</span>
				</div>

				<button class="sidebar-toggle">
					<span class="material-symbols-rounded">chevron_left</span>
				</button>
			</div>
			<div class="sidebar-content">
			
		<form action="my_warranties.php" class="search-form" method="get">
    <span class="material-symbols-rounded">search</span>

    <?php if (!empty($_GET['status'])): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status']) ?>">
    <?php endif; ?>

    <?php if (!empty($_GET['sort'])): ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort']) ?>">
    <?php endif; ?>

    <?php if (!empty($_GET['order'])): ?>
        <input type="hidden" name="order" value="<?= htmlspecialchars($_GET['order']) ?>">
    <?php endif; ?>

    <input type="search" 
           name="search" 
           placeholder="Search..." 
		   autocomplete="off"
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
</form>


			  <ul class="menu-list">
				<li class="menu-item">
					<a href="my_warranties.php" class="menu-link">
						<span class="material-symbols-rounded">inventory_2</span>
						<span class="menu-label">Моите гаранции</span>
					</a>
				</li>
				<li class="menu-item">
					<a href="add_warranty.php" class="menu-link">
						<span class="material-symbols-rounded">add_circle</span>
						<span class="menu-label">Добави гаранция</span>
					</a>
				</li>
				<li class="menu-item">
					<a href="#" class="menu-link" onclick="confirmDelete(event)">
						<span class="material-symbols-rounded">person_remove</span>
						<span class="menu-label">Изтрий профила</span>
					</a>
					<form id="deleteForm" method="POST" style="display:none;">
					  <input type="hidden" name="delete_account" value="1">
					</form>
				</li>
			  
			  <ul class="menu-list" style="margin-top: 40px; border-top: 1px solid var(--color-border-hr); padding-top: 20px;">
				<li class="menu-item">
					<a href="logout.php" class="menu-link">
						<span class="material-symbols-rounded">logout</span>
						<span class="menu-label">Изход</span>
					</a>
				</li>
			</ul>
			  
			</div>
			<div class="sidebar-footer">
			  <button class="theme-toggle">
				<div class="theme-label">
				  <span class="theme-icon material-symbols-rounded">dark_mode</span>
				  <span class="theme-text">Dark Mode</span>
				</div>
				<div class="theme-toggle-track">
				  <div class="theme-toggle-indicator"></div>
				</div>
			  </button>
			</div>
		  </aside>
		  
		  <div class="main">
			<div class="notif-dropdown" id="notifDropdown">
				<div class="notif-inner">
					<h4>Известия</h4>

					<?php if (empty($notifications)): ?>
						<p class="no-notifs">Нямате известия.</p>
					<?php else: ?>
						<?php foreach ($notifications as $n): ?>
							<div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $n['id'] ?>">
								<span class="notif-delete">&times;</span>
								<div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
								<div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
								<small><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></small>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="top-block">
				<div class="top-left">
					<div>
						<div class="hello-line">Здравей, <?= htmlspecialchars($username) ?>!</div>
						<div class="notif-wrapper">
							<button class="notif-bell" id="notifBell">
								<i class="bx bx-bell"></i>
								<?php if ($unreadCount > 0): ?>
									<span class="notif-badge"><?= $unreadCount ?></span>
								<?php endif; ?>
							</button>
						</div>
						<div>
							<div class="notif-dropdown" id="notifDropdown">
								<h4>Известия</h4>

								<?php if (empty($notifications)): ?>
									<p class="no-notifs">Нямате известия.</p>
								<?php else: ?>
									<?php foreach ($notifications as $n): ?>
										<div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $n['id'] ?>">
											<div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
											<div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
											<small><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></small>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>

					</div>

					<div class="distribution-block">
						<div class="distribution-title">
							
						</div>
						<div class="status-bar">
							<?php if ($total == 0): ?>
								<div class="seg-empty"></div>
							<?php else: ?>
								<?php if ($p_active > 0): ?>
									<div class="seg-active" style="width: <?= $p_active ?>%;"></div>
								<?php endif; ?>
								<?php if ($p_soon > 0): ?>
									<div class="seg-soon" style="width: <?= $p_soon ?>%;"></div>
								<?php endif; ?>
								<?php if ($p_expired > 0): ?>
									<div class="seg-expired" style="width: <?= $p_expired ?>%;"></div>
								<?php endif; ?>
							<?php endif; ?>
						</div>

					</div>

					<div class="kpi-row">

						<a href="my_warranties.php?status=ACTIVE" class="kpi-mini kpi-active" style="text-decoration:none; color:inherit;">
							<div class="kpi-header">
								<div class="kpi-icon"><i class='bx bx-check'></i></div>
								<span>Активни</span>
							</div>
							<div class="kpi-value"><?= $active ?></div>
						</a>

						<a href="my_warranties.php?status=EXPIRES_SOON" class="kpi-mini kpi-soon" style="text-decoration:none; color:inherit;">
							<div class="kpi-header">
								<div class="kpi-icon"><i class='bx bx-time-five'></i></div>
								<span>Изтичащи скоро</span>
							</div>
							<div class="kpi-value"><?= $soon ?></div>
						</a>

						<a href="my_warranties.php?status=EXPIRED" class="kpi-mini kpi-expired" style="text-decoration:none; color:inherit;">
							<div class="kpi-header">
								<div class="kpi-icon"><i class='bx bx-x'></i></div>
								<span>Изтекли</span>
							</div>
							<div class="kpi-value"><?= $expired ?></div>
						</a>

					</div>


					
				</div>

				<div class="top-right">
					<img src="images/WS1.png" class="top-illustration">
				</div>
			</div>

			<div class="bottom-row">

				<div class="card">
					<div class="card-title-row">
						<div class="calendar-header">
							<button class="calendar-nav-btn" id="cal-prev">&#8249;</button>
							<span id="cal-month-label"></span>
							<button class="calendar-nav-btn" id="cal-next">&#8250;</button>
						</div>
					</div>

					<div class="calendar-wrapper">
						<div class="calendar-weekdays">
							<div>П</div><div>В</div><div>С</div><div>Ч</div><div>П</div><div>С</div><div>Н</div>
						</div>
						<div class="calendar-days" id="calendar-days"></div>
					</div>
					<div class="calendar-tooltip" id="calendar-tooltip"></div>
				</div>

				<div class="card">
					<div class="card-title-row">
						<div class="card-title">
							<i class='bx bx-history'></i>
							Последно добавени гаранции
						</div>
					</div>
					
					<ul class="recent-list">
						<?php if ($recentRes->num_rows === 0): ?>
							<li class="recent-item"><span>Нямаш все още добавени гаранции.</span></li>
						<?php else: ?>
							<?php while ($r = $recentRes->fetch_assoc()): ?>
								<li class="recent-item">
									<div class="recent-main">
										<span class="recent-product"><?= htmlspecialchars($r['product_name']) ?></span>
										<span class="recent-meta">
											<br><?= htmlspecialchars($r['supplier']) ?> • Изтича: <?= htmlspecialchars($r['warranty_end']) ?>
										</span>
									</div>
									<span class="badge <?= $r['status'] ?>">
										<?= $status_labels[$r['status']] ?? $r['status'] ?>
									</span>

								</li>
							<?php endwhile; ?>
						<?php endif; ?>
					</ul>
				</div>

				<div class="card">
					<div class="card-title-row">
						<div class="card-title">
							<i class='bx bx-bulb'></i>
							Бързи действия
						</div>
					</div>
					<ul class="quick-list">
						<li class="quick-item" onclick="window.location.href='add_warranty.php'">
							<div class="quick-main">
								<span class="quick-title">Добави нова гаранция</span>
								<span class="quick-sub"><br>Добави продукт и следи неговата гаранция.</span>
							</div>
							<i class='bx bx-plus-circle quick-icon'></i>
						</li>
						<li class="quick-item" onclick="window.location.href='my_warranties.php?status=EXPIRES_SOON'">
							<div class="quick-main">
								<span class="quick-title">Виж изтичащите скоро</span>
								<span class="quick-sub"><br>Провери кои гаранции са на финалната права.</span>
							</div>
							<i class='bx bx-time-five quick-icon'></i>
						</li>
						<li class="quick-item" onclick="window.location.href='my_warranties.php'">
							<div class="quick-main">
								<span class="quick-title">Всички гаранции</span>
								<span class="quick-sub"><br>Всички гаранции на едно място.</span>
							</div>
							<i class='bx bx-list-ul quick-icon'></i>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

<script>
  const warrantiesData = <?php
    echo json_encode($calendarData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>;
</script>
<script src="Scripts/dashboard.js" defer></script>

</body>
</html>
<?php
$conn->close();
?>