<?php
/**
 * Upload team.png to Cloudinary
 * Run this script once to upload the team image
 */
include_once 'includes/db.php';
include_once 'includes/cloudinary_helper.php';

$team_image_path = 'assets/images/team.png';

if (!file_exists($team_image_path)) {
    die("Error: Team image not found at $team_image_path\n");
}

echo "Uploading team image to Cloudinary...\n";

$cloudinaryHelper = new CloudinaryHelper($pdo);

if (!$cloudinaryHelper->isEnabled()) {
    die("Error: Cloudinary is not enabled. Please configure it in System Settings.\n");
}

// Upload image to Cloudinary
$uploadResult = $cloudinaryHelper->uploadImage($team_image_path, 'homepage', [
    'public_id' => 'team',
    'overwrite' => true
]);

if ($uploadResult && isset($uploadResult['url'])) {
    // Save the URL to database settings
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, value) 
        VALUES ('homepage_team_image_url', ?)
        ON DUPLICATE KEY UPDATE value = ?
    ");
    $stmt->execute([$uploadResult['url'], $uploadResult['url']]);
    
    echo "✓ Successfully uploaded team image to Cloudinary!\n";
    echo "URL: " . $uploadResult['url'] . "\n";
    echo "Public ID: " . $uploadResult['public_id'] . "\n";
    echo "\nThe image URL has been saved to the database.\n";
} else {
    die("Error: Failed to upload image to Cloudinary.\n");
}
?>

