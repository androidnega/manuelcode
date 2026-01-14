<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/db.php';
include '../includes/google_config.php';

// Ensure we're using the correct redirect URI from config

if (isset($_GET['code'])) {
    // Get access token
    $token_url = "https://oauth2.googleapis.com/token";
    $token_data = [
        "code" => $_GET['code'],
        "client_id" => GOOGLE_CLIENT_ID,
        "client_secret" => GOOGLE_CLIENT_SECRET,
        "redirect_uri" => GOOGLE_REDIRECT_URI,
        "grant_type" => "authorization_code"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    curl_close($ch);

    $token = json_decode($token_response, true);

    if (isset($token['access_token'])) {
        // Get user info from Google
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v2/userinfo");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $token['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_info = curl_exec($ch);
        curl_close($ch);

        $google_user = json_decode($user_info, true);

        $google_id = $google_user['id'];
        $email = $google_user['email'];
        $name = $google_user['name'];

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
        $stmt->execute([$google_id, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Generate unique user ID
            include '../includes/user_id_generator.php';
            $generator = new UserIDGenerator($pdo);
            $unique_user_id = $generator->generateUserID($name);
            
            // Insert new Google user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, google_id, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $google_id, $unique_user_id]);
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Update last_login timestamp
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $_SESSION['user'] = $user;
        
        // Redirect based on user role
        if ($user['role'] === 'admin') {
            header("Location: ../dashboard/admin-dashboard");
        } else {
            header("Location: ../dashboard/");
        }
        exit;
    } else {
        echo "Google login failed.";
    }
} else {
    echo "Invalid request.";
}
?>
