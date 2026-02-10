<?php
session_start();

$width = 130;
$height = 45;

$image = imagecreatetruecolor($width, $height);

$bg_color = imagecolorallocate($image, 250, 240, 255);
$text_color = imagecolorallocate($image, 70, 0, 120);
$noise_color = imagecolorallocate($image, 190, 160, 230);

imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

for ($i = 0; $i < 150; $i++) {
    imagefilledellipse($image, rand(0, $width), rand(0, $height), 2, 2, $noise_color);
}

$characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
$captcha_text = substr(str_shuffle($characters), 0, 5);

$_SESSION['captcha_code'] = $captcha_text;

$font = __DIR__ . '/arial.ttf';

if (file_exists($font)) {
    $x = 10;
    for ($i = 0; $i < strlen($captcha_text); $i++) {
        $angle = rand(-20, 20);
        $x += 20;
        $y = rand(28, 38);
        imagettftext($image, 22, $angle, $x, $y, $text_color, $font, $captcha_text[$i]);
    }
} else {
    imagestring($image, 5, 30, 10, $captcha_text, $text_color);
}

for ($i = 0; $i < 4; $i++) {
    $x1 = rand(0, $width);
    $y1 = rand(0, $height);
    $x2 = rand(0, $width);
    $y2 = rand(0, $height);
    imageline($image, $x1, $y1, $x2, $y2, $noise_color);
}

header("Content-Type: image/png");
imagepng($image);
imagedestroy($image);
?>
