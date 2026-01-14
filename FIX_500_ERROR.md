# Fix HTTP 500 Error on ManuelCode.info

## Quick Fix Steps:

### 1. Check Database Credentials
The most common cause is wrong database credentials in `includes/db.php` on your live server.

**On your live server, edit `includes/db.php`:**
```php
<?php
$host = "localhost";  // Usually "localhost" on cPanel
$dbname = "your_live_database_name";  // Check in cPanel
$username = "your_cpanel_db_user";  // Check in cPanel
$password = "your_cpanel_db_password";  // Check in cPanel

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
```

### 2. Upload Diagnostic Files
Upload these files to your server root:
- `test_index.php` - Simple PHP test
- `error_check.php` - Detailed error diagnostics

Access them via:
- https://manuelcode.info/test_index.php
- https://manuelcode.info/error_check.php

### 3. Check Error Logs
In cPanel, check:
- **Error Log** section
- Look for PHP errors or database connection errors

### 4. Verify File Permissions
Make sure files have correct permissions:
- PHP files: 644
- Directories: 755
- `.htaccess`: 644

### 5. Test Database Connection
Create a test file `test_db.php`:
```php
<?php
include 'includes/db.php';
if (isset($pdo)) {
    echo "✅ Database connected!";
} else {
    echo "❌ Database connection failed!";
}
?>
```

### 6. Temporarily Disable .htaccess
If still having issues, rename `.htaccess` to `.htaccess.bak` temporarily to see if that's the issue.

## Common Issues:

1. **Database credentials wrong** - Most common
2. **Missing includes/db.php** - File not uploaded
3. **PHP version mismatch** - Check PHP version in cPanel
4. **.htaccess syntax error** - Check Apache error logs
5. **File permissions** - Files not readable by web server

## After Fixing:

1. Delete diagnostic files (`test_index.php`, `error_check.php`)
2. Test the homepage: https://manuelcode.info
3. Test dashboard: https://manuelcode.info/dashboard/

