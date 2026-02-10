<?php
if (!isset($error_message)) {
    $error_message = "Възникна непозната грешка.";
}

$redirect_back_js = "setTimeout(() => { window.history.back(); }, 7000);";
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>Грешка</title>
<link rel="stylesheet" href="Styles/error_view.css">
</head>
<body>

<div class="card">
    <div class="icon">❌</div>
    <h2>Възникна грешка</h2>

    <p><?= htmlspecialchars($error_message) ?></p>

    <div class="auto-msg">Ще бъдеш върнат автоматично след 10 секунди…</div>
</div>

<script>
<?= $redirect_back_js ?>
</script>

</body>
</html>
