<?php
// Create a 1080x1080 transparent image
$width = 1080;
$height = 1080;
$image = imagecreatetruecolor($width, $height);

// Enable alpha blending and save alpha flag
imagealphablending($image, false);
imagesavealpha($image, true);

// Create transparent background
$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
imagefill($image, 0, 0, $transparent);

// Enable alpha blending for drawing
imagealphablending($image, true);

// Colors
$darkBlue = imagecolorallocatealpha($image, 15, 23, 42, 0); // Tailwind slate-900
$accentColor = imagecolorallocatealpha($image, 59, 130, 246, 0); // Tailwind blue-500
$white = imagecolorallocatealpha($image, 255, 255, 255, 0);
$shadow = imagecolorallocatealpha($image, 0, 0, 0, 80);

// 1. Top Gradient overlay for Logo and Date (Top 150px)
for ($y = 0; $y < 150; $y++) {
    $alpha = intval(($y / 150) * 127);
    $gradColor = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
    imageline($image, 0, $y, $width, $y, $gradColor);
}

// 2. Bottom Headline Box (Bottom 250px)
// We'll create a nice angled or rounded box at the bottom
// Shadow
imagefilledrectangle($image, 0, $height - 250, $width, $height, $shadow);
// Main Box
imagefilledrectangle($image, 0, $height - 240, $width, $height, $darkBlue);

// 3. Accent Line on top of the bottom box
imagefilledrectangle($image, 0, $height - 240, $width, $height - 230, $accentColor);

// 4. "LIVE NEWS" Badge at the top left of the headline box
imagefilledrectangle($image, 40, $height - 280, 220, $height - 230, $accentColor);

// Save the image
$outputPath = __DIR__ . '/news-frame-template.png';
imagepng($image, $outputPath);
imagedestroy($image);

echo "Frame generated at $outputPath\n";
?>
