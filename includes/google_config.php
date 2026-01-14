<?php
// Google OAuth Configuration
// Reading credentials from JSON file for better security

function getGoogleCredentials() {
    $json_file = __DIR__ . '/../client_secret_878393599939-k2hi9qqvrtbhr8hgcavvg4vosmuvc7gj.apps.googleusercontent.com.json';
    
    if (file_exists($json_file)) {
        $json_content = file_get_contents($json_file);
        $credentials = json_decode($json_content, true);
        
        if (isset($credentials['web'])) {
            return $credentials['web'];
        }
    }
    
    // Fallback to hardcoded values if JSON file is not available
    return [
        'client_id' => '878393599939-k2hi9qqvrtbhr8hgcavvg4vosmuvc7gj.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uris' => [
            'https://www.manuelcode.info/manuelcode/auth/google_callback.php',
            'http://localhost/ManuelCode.info/manuelcode/auth/google_callback.php'
        ]
    ];
}

$google_creds = getGoogleCredentials();

// Define constants for easy access
define('GOOGLE_CLIENT_ID', $google_creds['client_id']);
define('GOOGLE_CLIENT_SECRET', $google_creds['client_secret']);

// Use the first redirect URI from the JSON file, or fallback to production URL
$redirect_uri = isset($google_creds['redirect_uris'][0]) 
    ? $google_creds['redirect_uris'][0] 
    : 'https://www.manuelcode.info/manuelcode/auth/google_callback.php';

define('GOOGLE_REDIRECT_URI', $redirect_uri);

// For local development, you can uncomment this line:
// define('GOOGLE_REDIRECT_URI', 'http://localhost/ManuelCode.info/manuelcode/auth/google_callback.php');
?>
