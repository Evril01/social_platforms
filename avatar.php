<?php
// Create a default avatar image
function create_default_avatar() {
    $width = 200;
    $height = 200;
    
    // Create image
    $image = imagecreate($width, $height);
    
    // Colors
    $background = imagecolorallocate($image, 240, 240, 240); // Light gray
    $text_color = imagecolorallocate($image, 150, 150, 150); // Dark gray
    
    // Fill background
    imagefill($image, 0, 0, $background);
    
    // Add text
    $text = "Avatar";
    $font = 5; // Built-in font
    $text_width = imagefontwidth($font) * strlen($text);
    $text_height = imagefontheight($font);
    
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font, $x, $y, $text, $text_color);
    
    // Save image
    if (!file_exists('uploads/profile_pics')) {
        mkdir('uploads/profile_pics', 0777, true);
    }
    
    imagepng($image, 'uploads/profile_pics/default.png');
    imagedestroy($image);
}

// Create default avatar
create_default_avatar();
echo "Default avatar created successfully!";
?>