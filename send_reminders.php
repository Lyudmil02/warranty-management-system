<?php
include __DIR__ . '/db_connection.php';
include 'update_statuses.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set("Europe/Sofia");

$today = new DateTime('today');

echo "<h2>Стартиране на напомнянията...</h2>";

$sql = "
  SELECT
    w.id,
    w.user_id,
    w.product_name,
    w.warranty_end,
    w.reminder_stage,
    w.notify_days_before,
    u.email,
    u.first_name
  FROM warranties w
  JOIN users u ON u.id = w.user_id
  WHERE
    w.status = 'EXPIRES_SOON'
    AND w.reminder_stage < 3
    AND DATEDIFF(w.warranty_end, CURDATE()) >= 0  -- да не пращаме за минали гаранции
";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $endDate  = new DateTime($row['warranty_end']);
    $daysLeft = (int)$today->diff($endDate)->days;
    $n        = (int)$row['notify_days_before'];
    $stage    = (int)$row['reminder_stage'];
    $shouldSend = false;

    $halfN = (int) floor($n / 2);

    if ($stage === 0 && $daysLeft === $n) {
        $shouldSend = true;
    } elseif ($stage === 1 && $daysLeft === $halfN) {
        $shouldSend = true;
    } elseif ($stage === 2 && $daysLeft === 0) {
        $shouldSend = true; 
    }

    if (!$shouldSend) continue;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ilianka08102000@gmail.com';
        $mail->Password   = 'pihtlufxxcpipvgs';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        $mail->setFrom('ilianka08102000@gmail.com', 'Warranty System');
        $mail->addAddress($row['email'], $row['first_name']);
        $mail->isHTML(true);

        if ($daysLeft == 0) {
            $mail->Subject = "Гаранцията за „{$row['product_name']}“ изтича днес";
            $mail->Body = "
                Здравейте, {$row['first_name']}!<br><br>
                С този имейл Ви уведомяваме, че вашата гаранция на
                <b>{$row['product_name']}</b> изтича днес.<br>
                Благодарим Ви за доверието и ползването на нашата система.<br><br>
                С най-добри пожелания,<br>Екипът на <b>Warranty System</b>.
            ";
        } else {
            $mail->Subject = "Напомняне: Гаранцията за „{$row['product_name']}“ изтича";
            $mail->Body = "
                Здравейте, {$row['first_name']}!<br><br>
                С този имейл Ви уведомяваме, че гаранцията на
                <b>{$row['product_name']}</b> изтича на <b>{$row['warranty_end']}</b>.<br>
                Остават <b>$daysLeft дни</b> до крайния срок на валидност.<br><br>
                Благодарим Ви за доверието и ползването на нашата система.<br>
                С най-добри пожелания,<br>Екипът на <b>Warranty System</b>.
            ";
        }

        $mail->send();

        $nextStage = $stage + 1;
        $upd = $conn->prepare("UPDATE warranties SET reminder_stage = ? WHERE id = ?");
        $upd->bind_param("ii", $nextStage, $row['id']);
        $upd->execute();
        $upd->close();

        if ($daysLeft == 0) {
            $notifTitle = "Гаранцията изтича днес";
            $notifMsg   = "Гаранцията за „{$row['product_name']}“ изтича днес.";
        } else {
            $notifTitle = "Напомняне за изтичане";
            $notifMsg   = "Остават {$daysLeft} дни до изтичане на гаранцията за „{$row['product_name']}“ (краен срок: {$row['warranty_end']}).";
        }

        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, warranty_id, title, message)
            VALUES (?, ?, ?, ?)
        ");
        $notifStmt->bind_param(
            "iiss",
            $row['user_id'],
            $row['id'],
            $notifTitle,
            $notifMsg
        );
        $notifStmt->execute();
        $notifStmt->close();

        echo "Изпратено до {$row['email']} (етап $stage → $nextStage) | Продукт: {$row['product_name']}<br>";
    } catch (Exception $e) {
        echo "Грешка към {$row['email']}: {$mail->ErrorInfo}<br>";
    }
}

$conn->close();
echo "<h3>Готово.</h3>";
