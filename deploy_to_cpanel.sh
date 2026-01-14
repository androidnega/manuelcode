#!/bin/bash
# Deployment script for cPanel server
# Usage: ./deploy_to_cpanel.sh

SERVER="manuelc8@manuelcode.info"
PORT="7522"
REMOTE_PATH="/home3/manuelc8/public_html"
LOCAL_PATH="."

echo "Deploying to cPanel server..."

# Exclude sensitive files and git files
rsync -avz --delete \
  -e "ssh -p $PORT" \
  --exclude '.git' \
  --exclude '.gitignore' \
  --exclude 'includes/db.php' \
  --exclude 'config/payment_config.php' \
  --exclude 'config/sms_config.php' \
  --exclude '*.log' \
  --exclude 'error_log' \
  --exclude '*.sql' \
  --exclude 'node_modules' \
  --exclude '.env' \
  "$LOCAL_PATH/" "$SERVER:$REMOTE_PATH/"

echo "Deployment complete!"

