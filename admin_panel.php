<?php
include 'db_connection.php';

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

function sendUserStatusMail($toEmail, $toName, $type) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ilianka08102000@gmail.com';
        $mail->Password = 'pihtlufxxcpipvgs';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('ilianka08102000@gmail.com', 'Warranty System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);

        if ($type === 'approved') {
            $mail->Subject = 'Акаунтът ви е одобрен';
            $mail->Body = "
                Здравей, <b>$toName</b>,<br><br>
                Вашият акаунт беше <b>одобрен от администратор</b>.<br>
                Вече можете да влезете в системата от следния адрес:<br><br>
                <a href='http://localhost/WarrantyCardsManagmentSystem/auth.php'>
                    Вход в Warranty System
                </a><br><br>
                Поздрави,<br>
                Екипът на Warranty System.
            ";
        }

        if ($type === 'declined') {
            $mail->Subject = 'Акаунтът ви е отхвърлен';
            $mail->Body = "
                Здравей, <b>$toName</b>,<br><br>
                За съжаление, вашият акаунт беше <b>отхвърлен от администратор</b>.<br>
                Ако имате въпроси можете да се свържете с поддръжката.<br><br>
                Поздрави,<br>
                Екипът на Warranty System.
            ";
        }

        $mail->send();
    } catch (Exception $e) {
    }
}


if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: auth.php");
    exit();
}

if (isset($_GET['approve'])) {
    $user_id = intval($_GET['approve']);

    $u = $conn->prepare("SELECT email, username FROM users WHERE id = ? AND status = 'ACTIVE'");
    $u->bind_param("i", $user_id);
    $u->execute();
    $user = $u->get_result()->fetch_assoc();
    $u->close();

    if ($user) {
        $stmt = $conn->prepare("UPDATE users SET status = 'APPROVED' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        sendUserStatusMail($user['email'], $user['username'], 'approved');
        $message = "Потребителят е успешно одобрен!";
    }
}


if (isset($_GET['decline'])) {
    $user_id = intval($_GET['decline']);

    $u = $conn->prepare("SELECT email, username FROM users WHERE id = ? AND status = 'ACTIVE'");
    $u->bind_param("i", $user_id);
    $u->execute();
    $user = $u->get_result()->fetch_assoc();
    $u->close();

    if ($user) {
        $stmt = $conn->prepare("UPDATE users SET status = 'DISABLED' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        sendUserStatusMail($user['email'], $user['username'], 'declined');
        $message = "Потребителят е отказан и деактивиран.";
    }
}


$result = $conn->query("SELECT id, username, email, registration_date FROM users WHERE status = 'ACTIVE'");
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панел</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Styles/admin_panel.css">
</head>
<body>

<div class="container">
    <h1><i class='bx bx-user-check'></i> Управление на потребители</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Потребител</th>
                <th>Имейл</th>
                <th>Дата на регистрация</th>
                <th>Действие</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
					<td data-label="Потребител"><?= htmlspecialchars($row['username']) ?></td>
					<td data-label="Имейл"><?= htmlspecialchars($row['email']) ?></td>
					<td data-label="Дата на регистрация">
						<?= htmlspecialchars(date("d.m.Y", strtotime($row['registration_date']))) ?>
					</td>
					<td data-label="Действие">
						<a href="?approve=<?= $row['id'] ?>" class="approve-btn">
							<i class='bx bx-check'></i> Одобри
						</a>
						<a href="?decline=<?= $row['id'] ?>" class="decline-btn">
							<i class='bx bx-x'></i> Откажи
						</a>
					</td>
				</tr>

            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p class="no-users">Няма чакащи потребители за одобрение.</p>
    <?php endif; ?>

    <a href="logout.php" class="back-link">← Изход</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
