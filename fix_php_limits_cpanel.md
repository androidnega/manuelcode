# How to Increase PHP Upload Limits on cPanel (Live Server)

Since you're on a live server (cPanel), you cannot directly edit `php.ini`. Here are the methods to increase PHP upload limits:

## Method 1: Using .htaccess (Recommended - Easiest)

1. **Access your cPanel File Manager** or use FTP
2. **Navigate to your website root** (usually `public_html` or `htdocs`)
3. **Edit or create `.htaccess` file** in the root directory
4. **Add these lines** at the top of the file:

```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M
```

5. **Save the file**
6. **Test** by visiting `https://manuelcode.info/check_php_limits.php`

**Note:** This only works if your hosting allows `.htaccess` overrides. Some hosts disable this for security.

## Method 2: Using cPanel MultiPHP INI Editor

1. **Login to cPanel**
2. **Find "MultiPHP INI Editor"** (usually under "Software" section)
3. **Select your domain** from the dropdown
4. **Click "Editor Mode"** tab
5. **Find and edit these values:**
   - `upload_max_filesize = 10M`
   - `post_max_size = 10M`
   - `max_execution_time = 300`
   - `memory_limit = 256M`
6. **Click "Save"**
7. **Wait a few minutes** for changes to take effect

## Method 3: Contact Your Hosting Provider

If the above methods don't work, contact your hosting provider and ask them to:
- Increase `upload_max_filesize` to 10M
- Increase `post_max_size` to 10M
- Increase `max_execution_time` to 300 seconds

## Verify Changes

After making changes, visit:
- `https://manuelcode.info/check_php_limits.php`

This will show your current PHP limits and confirm if the changes took effect.

## Current Status

Based on your diagnostic:
- **Current upload limit:** 2M (needs to be 10M)
- **Current POST limit:** 8M (needs to be 10M)
- **PHP Config File:** `/opt/alt/php81/etc/php.ini` (cPanel managed)

## Troubleshooting

If `.htaccess` method doesn't work:
1. Check if your hosting allows PHP overrides
2. Try Method 2 (MultiPHP INI Editor)
3. Contact support if neither works

