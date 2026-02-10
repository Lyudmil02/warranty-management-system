<?php
include 'db_connection.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $stmt = $conn->prepare("SELECT id, username, email, first_name FROM users WHERE activation_code = ? AND status = 'PENDING'");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->bind_result($user_id, $username, $email, $first_name);
        $stmt->fetch();

        $update = $conn->prepare("UPDATE users SET status = 'ACTIVE', activation_code = NULL WHERE activation_code = ?");
        $update->bind_param("s", $code);
        $update->execute();
        $update->close();


        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ilianka08102000@gmail.com';
            $mail->Password = 'pihtlufxxcpipvgs';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

			$admins = $conn->query("SELECT email, first_name FROM users WHERE is_admin = 1");

			if ($admins->num_rows > 0) {
				while ($admin = $admins->fetch_assoc()) {
					$mail->addAddress($admin['email'], $admin['first_name']);
				}
			} else {
				$mail->addAddress("ilianka08102000@gmail.com", "Admin");
			}
            $mail->setFrom('ilianka08102000@gmail.com', 'Warranty System');

            $mail->isHTML(true);
            $mail->Subject = 'Нов потребител изчаква одобрение';
            $mail->Body = "
                Здравей!<br><br>
                Потребител <b>$username</b> с имейл $email току-що активира акаунта си.<br>
                Моля, влез в админ панела, за да го <b>одобриш</b> или <b>отхвърлиш</b>.<br><br>
                <a href='http://localhost/WarrantyCardsManagmentSystem/admin_panel.php'>
                    Отвори админ панела
                </a>
                <br><br>Поздрави,<br>Екипът на <b>Warranty System</b>";
            
            $mail->send();

        } catch (Exception $e) {
        }

        header("Location: auth.php");
        exit();

    } else {
        echo "Невалиден или вече използван активационен код.";
    }

    $stmt->close();
}

$conn->close();
?>
