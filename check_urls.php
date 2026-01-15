<?php
/**
 * URL Checker - Verify all system URLs work correctly
 */
session_start();

$logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

echo "<!DOCTYPE html><html><head><title>URL Checker</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#f5f5f5;} 
.container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
h1{color:#333;border-bottom:3px solid #4CAF50;padding-bottom:10px;}
h2{color:#555;margin-top:30px;border-bottom:2px solid #ddd;padding-bottom:8px;}
.status{display:inline-block;padding:4px 12px;border-radius:4px;font-size:12px;font-weight:bold;margin-right:10px;}
.success{background:#4CAF50;color:white;}
.warning{background:#FF9800;color:white;}
.error{background:#f44336;color:white;}
.info{background:#2196F3;color:white;}
.url-list{list-style:none;padding:0;}
.url-item{padding:12px;margin:8px 0;background:#f9f9f9;border-left:4px solid #4CAF50;border-radius:4px;display:flex;align-items:center;justify-content:space-between;}
.url-item.restricted{border-left-color:#FF9800;}
.url-link{color:#2196F3;text-decoration:none;flex-grow:1;}
.url-link:hover{text-decoration:underline;}
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 ManuelCode URL Checker</h1>";
echo "<p><strong>Login Status:</strong> ";
if ($logged_in) {
    echo "<span class='status success'>✓ Logged In</span>";
    echo " User ID: {$_SESSION['user_id']}";
} else {
    echo "<span class='status warning'>✗ Not Logged In</span>";
}
if ($is_admin) {
    echo " <span class='status info'>Admin</span>";
}
echo "</p>";

// Define all URLs to check
$urls = [
    'Public Pages' => [
        'Home' => '/',
        'Store' => '/store.php',
        'About' => '/about',
        'Services' => '/services',
        'Projects' => '/projects',
        'Contact' => '/contact',
        'Login' => '/login',
        'Register' => '/auth/register.php',
    ],
    
    'User Dashboard' => [
        'Dashboard Home' => '/dashboard',
        'Dashboard Index' => '/dashboard/index',
        'My Purchases' => '/dashboard/my-purchases',
        'Purchases (alias)' => '/dashboard/purchases',
        'Purchases.php (redirect)' => '/dashboard/purchases.php',
        'Downloads' => '/dashboard/downloads',
        'Receipts' => '/dashboard/receipts',
        'Refunds' => '/dashboard/refunds',
        'Settings' => '/dashboard/settings',
        'Support' => '/dashboard/support',
        'Notifications' => '/dashboard/notifications',
    ],
    
    'Admin Pages' => [
        'Admin Login' => '/admin',
        'Admin Dashboard' => '/dashboard/admin',
        'Products' => '/dashboard/products',
        'Orders' => '/dashboard/orders',
        'Users' => '/dashboard/users',
        'Reports' => '/dashboard/reports',
        'Coupons' => '/dashboard/coupons',
    ],
    
    'Payment Pages' => [
        'Payment Callback' => '/payment/callback.php',
        'Guest Callback' => '/payment/guest_callback.php',
    ],
    
    'Utility Pages' => [
        'Purchase Status Checker' => '/check_purchase_status.php',
        'URL Checker (this page)' => '/check_urls.php',
    ],
];

foreach ($urls as $category => $category_urls) {
    echo "<h2>$category</h2>";
    echo "<ul class='url-list'>";
    
    foreach ($category_urls as $name => $url) {
        $full_url = 'https://manuelcode.info' . $url;
        
        // Determine if URL requires auth
        $requires_login = (strpos($url, '/dashboard') === 0 && strpos($url, '/admin') === false);
        $requires_admin = (strpos($url, '/admin') !== false || strpos($url, '/dashboard/admin') === 0);
        
        // Determine status
        $status_class = 'success';
        $status_text = '✓ Available';
        $note = '';
        
        if ($requires_admin && !$is_admin) {
            $status_class = 'warning';
            $status_text = '🔒 Requires Admin';
            $note = ' (You need admin access)';
        } elseif ($requires_login && !$logged_in) {
            $status_class = 'warning';
            $status_text = '🔒 Requires Login';
            $note = ' (You need to log in)';
        }
        
        echo "<li class='url-item'>";
        echo "<a href='$full_url' class='url-link' target='_blank'>$name</a>";
        echo "<span class='status $status_class'>$status_text</span>";
        if ($note) echo "<small>$note</small>";
        echo "</li>";
    }
    
    echo "</ul>";
}

echo "<hr style='margin:40px 0;'>";
echo "<h2>Testing Instructions</h2>";
echo "<ol>";
echo "<li><strong>If you're not logged in:</strong> Click on any User Dashboard link - you should be redirected to login page</li>";
echo "<li><strong>After logging in:</strong> All User Dashboard links should work</li>";
echo "<li><strong>Admin links:</strong> Only work if you're logged in as admin</li>";
echo "<li><strong>Public pages:</strong> Should work for everyone</li>";
echo "</ol>";

if (!$logged_in) {
    echo "<div style='background:#fff3cd;padding:15px;border-left:4px solid #ffc107;margin:20px 0;'>";
    echo "<strong>⚠️ Note:</strong> You're not logged in. Please <a href='/login'>log in</a> to test dashboard URLs.";
    echo "</div>";
}

echo "</div></body></html>";
?>

