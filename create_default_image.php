<?php
// create_default_image.php
$width = 300;
$height = 200;

// Basit bir GD resmi oluştur
$image = imagecreate($width, $height);

// Renkler
$background = imagecolorallocate($image, 52, 152, 219); // Mavi
$text_color = imagecolorallocate($image, 255, 255, 255); // Beyaz

// Arka plan
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Metin
$text = "GPU Image";
$font = 5; // Built-in font
$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font, $x, $y, $text, $text_color);

// Klasörü kontrol et ve oluştur
if (!file_exists('assets/images')) {
    mkdir('assets/images', 0777, true);
}

// Resmi kaydet
imagejpeg($image, 'assets/images/default-gpu.jpg', 90);
imagedestroy($image);

echo "Default GPU resmi oluşturuldu: assets/images/default-gpu.jpg";
?>