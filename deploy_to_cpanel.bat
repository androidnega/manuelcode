@echo off
REM Deployment script for cPanel server (Windows)
REM Usage: deploy_to_cpanel.bat

set SERVER=manuelc8@manuelcode.info
set PORT=7522
set REMOTE_PATH=/home3/manuelc8/public_html

echo Deploying to cPanel server...

REM Using rsync via WSL or Git Bash
REM If you have rsync installed, uncomment the line below:
REM rsync -avz --delete -e "ssh -p %PORT%" --exclude '.git' --exclude 'includes/db.php' --exclude '*.log' --exclude '*.sql' ./ %SERVER%:%REMOTE_PATH%/

echo.
echo ========================================
echo Manual Deployment Instructions:
echo ========================================
echo.
echo 1. SSH into your server:
echo    ssh -p %PORT% %SERVER%
echo.
echo 2. Navigate to public_html:
echo    cd %REMOTE_PATH%
echo.
echo 3. Initialize git (if not already done):
echo    git init
echo    git config receive.denyCurrentBranch updateInstead
echo.
echo 4. Then from your local machine, run:
echo    git push cpanel master
echo.
echo ========================================
pause

