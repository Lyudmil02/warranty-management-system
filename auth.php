<?php
include 'db_connection.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	if (isset($_POST['login'])) {

		if (empty($_POST['captcha']) || !isset($_SESSION['captcha_code']) || strcasecmp($_POST['captcha'], $_SESSION['captcha_code']) !== 0) {
			$error = "Грешен CAPTCHA код. Опитайте отново.";
		} else {
			$username = trim($_POST['username']);
			$password = trim($_POST['password']);

			if (!preg_match("/^[a-zA-Z0-9]{5,20}$/", $username)) {
				$error = "Невалиден формат за потребителско име.";
			} else {
				$stmt = $conn->prepare("SELECT id, password, status, is_admin FROM users WHERE username = ?");
				$stmt->bind_param("s", $username);
				$stmt->execute();
				$stmt->store_result();

				if ($stmt->num_rows > 0) {
					$stmt->bind_result($userId, $hashed_password, $status, $is_admin);
					$stmt->fetch();

					if (!password_verify($password, $hashed_password)) {
						$error = "Грешна парола. Опитайте отново.";
					} else {
						switch ($status) {
							case 'PENDING':
								$error = "Моля, активирайте акаунта си чрез линка в имейла.";
								break;
							case 'ACTIVE':
								$error = "Акаунтът ви още не е одобрен от администратор.";
								break;
							case 'DISABLED':
								$error = "Акаунтът ви е деактивиран.";
								break;
							case 'APPROVED':
								$_SESSION['user_id'] = $userId;
								$_SESSION['username'] = $username;
								$_SESSION['is_admin'] = $is_admin;

								if ($is_admin == 1) {
									header("Location: admin_panel.php");
								} else {
									header("Location: dashboard.php");
								}
								exit();

							default:
								$error = "Невалиден статус.";
						}
					}
				} else {
					$error = "Потребителят не е намерен.";
				}
				$stmt->close();
			}
		}
	}

	elseif (isset($_POST['register'])) {
		$username         = trim($_POST['username']);
		$password_raw     = trim($_POST['password']);
		$password_confirm = trim($_POST['password_confirm']);
		$email            = trim($_POST['email']);
		$first_name       = trim($_POST['first_name']);
		$last_name        = trim($_POST['last_name']);

		$errors = [];

		if (!preg_match("/^[a-zA-Z0-9]{5,20}$/", $username)) {
			$errors[] = "Невалидно потребителско име (5–20 символа, само латински букви и цифри).";
		}

		if (strlen($password_raw) < 8) {
			$errors[] = "Паролата трябва да е поне 8 символа.";
		}
		if (!preg_match("/[A-Z]/", $password_raw)) {
			$errors[] = "Паролата трябва да съдържа поне 1 главна буква.";
		}
		if (!preg_match("/[a-z]/", $password_raw)) {
			$errors[] = "Паролата трябва да съдържа поне 1 малка буква.";
		}
		if (!preg_match("/\d/", $password_raw)) {
			$errors[] = "Паролата трябва да съдържа поне 1 цифра.";
		}
		if (!preg_match("/[@$!%*?&\.]/", $password_raw)) {
			$errors[] = "Паролата трябва да съдържа поне 1 специален символ (@ $ ! % * ? & .).";
		}

		if ($password_raw !== $password_confirm) {
			$errors[] = "Паролите не съвпадат.";
		}

		if (!empty($errors)) {
			$error = implode("<br>", $errors);

		} else {
			$password = password_hash($password_raw, PASSWORD_BCRYPT);

			$checkStmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
			$checkStmt->bind_param("ss", $username, $email);
			$checkStmt->execute();
			$result = $checkStmt->get_result();

			if ($row = $result->fetch_assoc()) {
				if ($row['username'] === $username) {
					$error = "Потребителското име вече съществува.";
				} elseif ($row['email'] === $email) {
					$error = "Този имейл вече е регистриран.";
				}
			} else {
				$is_admin = (isset($_POST['is_admin']) && $_POST['is_admin'] === '1') ? 1 : 0;
				$activation_code = bin2hex(random_bytes(32));
				$status = $is_admin ? 'APPROVED' : 'PENDING';

				$stmt = $conn->prepare("
					INSERT INTO users (username, password, first_name, last_name, email, activation_code, is_admin, status)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)
				");
				$stmt->bind_param("ssssssis", $username, $password, $first_name, $last_name, $email, $activation_code, $is_admin, $status);

				if ($stmt->execute()) {

					if ($is_admin) {
						$success = "Администраторският акаунт е създаден и активиран успешно!";
					} else {
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
							$mail->Encoding = 'base64';

							$mail->setFrom('ilianka08102000@gmail.com', 'Warranty System');
							$mail->addAddress($email, $first_name);

							$activation_link = "http://localhost/WarrantyCardsManagmentSystem/activate.php?code=$activation_code";

							$mail->isHTML(true);
							$mail->Subject = 'Активирайте акаунта си';
							$mail->Body = "
								Здравей, $first_name!<br><br>
								За да активираш акаунта си, натисни тук:<br>
								<a href='$activation_link'>$activation_link</a><br>
								След активиране, акаунта Ви ще трябва да се одобри от администратор.<br>
								След одобрението от администратора, ще Ви уведомим чрез допълнителен имейл!<br><br>
								Поздрави,<br>Екипът на Warranty System.
							";

							$mail->send();
							$success = "Регистрацията е успешна! Провери имейла си за линк за активация.";
						} catch (Exception $e) {
							$error = "Грешка при изпращане на имейл: {$mail->ErrorInfo}";
						}
					}

				} else {
					$error = "Грешка при регистрация: " . $stmt->error;
				}

				$stmt->close();
			}
			$checkStmt->close();
		}
	}

}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title>Login / Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="Styles/auth.css">
</head>
<body>
  <div class="container">
	<div class="mobile-switch">
	  <button type="button" class="mobile-tab login-tab">Вход</button>
	  <button type="button" class="mobile-tab register-tab">Регистрация</button>
	</div>
    <div class="form-box login">
      <form method="POST" action="">
        <h1>Вход</h1>
        <div class="input-box">
          <i class='bx bxs-user'></i>
          <input type="text" name="username" placeholder="Потребителско име" required>
        </div>
        <div class="input-box">
          <i class="bx bxs-lock-alt toggle-password" data-icon="bxs-lock-alt"></i>
          <input type="password" name="password" placeholder="Парола" required>
        </div>

        <div class="captcha-container">
		  <img id="captchaImage" src="capcha.php" alt="CAPTCHA">
		  <i class='bx bx-refresh' id="reloadCaptcha" title="Нов Код"></i>
		</div>
		<div class="input-box">
		  <input type="text" name="captcha" placeholder="Въведи кода от изображението" required>
		</div>


        <button type="submit" class="btn" name="login">Вход</button>

        <?php if(isset($error) && isset($_POST['login'])): ?>
          <div class="message-box">
            <p class="message"><?= $error ?></p>
          </div>
        <?php endif; ?>
      </form>
    </div>

    <div class="form-box register">
      <form method="POST" action="">
        <h2>Регистрация</h2>
        <div class="input-box username-box">
			<i class='bx bxs-user'></i>

			<input type="text" name="username" id="reg-username" placeholder="Потребителско име" required>

			<i class='bx bx-info-circle username-info'></i>

			<div class="username-tooltip">
				Потребителското име трябва да съдържа:<br>
				<span id="u-len">✖ между 5 и 20 символа</span><br>
				<span id="u-chars">✖ само латински букви и цифри</span>
			</div>
		</div>

        <div class="input-box"><i class='bx bxs-user-detail'></i>
          <input type="text" name="first_name" placeholder="Име" required>
        </div>
        <div class="input-box"><i class='bx bxs-user-detail'></i>
          <input type="text" name="last_name" placeholder="Фамилия" required>
        </div>
        <div class="input-box"><i class='bx bxs-envelope'></i>
          <input type="email" name="email" placeholder="Имейл" required>
        </div>
        <div class="input-box password-box">
			<i class="bx bxs-lock-alt toggle-password" data-icon="bxs-lock-alt"></i>

			<input type="password" name="password" placeholder="Парола" required>

			<i class='bx bx-info-circle password-info'></i>

			<div class="password-tooltip">
				Паролата трябва да съдържа:<br>
				<span id="t-len">✖ поне 8 символа</span><br>
				<span id="t-upper">✖ поне 1 главна буква</span><br>
				<span id="t-lower">✖ поне 1 малка буква</span><br>
				<span id="t-digit">✖ поне 1 цифра</span><br>
				<span id="t-special">✖ поне 1 специален символ</span>
			</div>

			
		</div>

        <div class="input-box"><i class="bx bxs-lock toggle-password" data-icon="bxs-lock"></i>
          <input type="password" name="password_confirm" placeholder="Повтори паролата" required>
        </div>

		
        <button type="submit" class="btn" name="register">Регистрация</button>
        <?php if(isset($error) && isset($_POST['register'])) echo "<p class='message'>$error</p>"; ?>
        <?php if(isset($success)) echo "<p class='message success'>$success</p>"; ?>
      </form>
    </div>

    <div class="toggle-box">
      <div class="toggle-panel toggle-left">
        <h1>Влез в акаунта си</h1>
        <p>Нямаш профил? Регистрирай се и започни сега</p>
        <button class="btn register-btn">Регистрация</button>
      </div>
      <div class="toggle-panel toggle-right">
        <h1>Създай нов акаунт</h1>
        <p>Вече имаш акаунт?</p>
        <button class="btn login-btn">Вход</button>
      </div>
    </div>
	
  </div>
  
<div class="auth-footer">
    <span class="footer-box">
         2025 © Warranty System
        <a href="about_system.php" class="info-btn" title="Информация за проекта">
            <i class='bx bx-info-circle'></i>
        </a>
    </span>
</div>

	<script>
		const isRegisterPost = <?= isset($_POST['register']) ? 'true' : 'false' ?>;
	</script>
	
    <script src="Scripts/auth.js" defer></script>
</body>
</html>
