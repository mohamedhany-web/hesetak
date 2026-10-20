<?php

$dir = __DIR__ . '/../public/images';
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$w = 1200;
$h = 630;
$im = imagecreatetruecolor($w, $h);

$navy = imagecolorallocate($im, 11, 20, 36);
$gold = imagecolorallocate($im, 230, 176, 9);
$white = imagecolorallocate($im, 232, 238, 248);
$muted = imagecolorallocate($im, 143, 163, 192);

imagefilledrectangle($im, 0, 0, $w, $h, $navy);

for ($i = 0; $i < $h; $i++) {
    $t = $i / $h;
    $r = (int) (11 + (26 - 11) * $t);
    $g = (int) (20 + (63 - 20) * $t);
    $b = (int) (36 + (115 - 36) * $t);
    $c = imagecolorallocate($im, $r, $g, $b);
    imageline($im, 0, $i, (int) ($w * 0.58), $i, $c);
}

imagefilledrectangle($im, 0, $h - 10, $w, $h, $gold);

// Built-in font labels (reliable without TTF)
imagestring($im, 5, 72, 200, 'HESETAK', $gold);
imagestring($im, 5, 72, 250, 'Online 1:1 tutoring', $white);
imagestring($im, 5, 72, 290, 'Students. Parents. Teachers.', $muted);

$path = $dir . '/og-image.jpg';
imagejpeg($im, $path, 88);
imagedestroy($im);

echo 'wrote '.$path.' size='.filesize($path).PHP_EOL;
