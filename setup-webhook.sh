#!/bin/bash

# ============================================================================
# WEBHOOK SETUP HELPER SCRIPT
# ============================================================================
# This script helps you configure the webhook files with your actual values
# Run this script once during initial setup
#
# Usage: bash setup-webhook.sh
# ============================================================================

echo "=========================================="
echo "GitHub Webhook Setup Helper"
echo "=========================================="
echo ""

# Function to update file with sed
update_config() {
    local file=$1
    local search=$2
    local replace=$3
    
    if [ -f "$file" ]; then
        sed -i "s|${search}|${replace}|g" "$file"
        echo "✅ Updated: $file"
    else
        echo "❌ File not found: $file"
    fi
}

# Get configuration values
echo "Please provide the following information:"
echo ""

read -p "Enter your webhook secret key (min 32 characters): " WEBHOOK_SECRET
read -p "Enter your deployment path (e.g., /home2/username/parish_system): " DEPLOY_PATH
read -p "Enter your PHP version (82, 83, 84): " PHP_VER
read -p "Enter your notification email (optional): " NOTIFY_EMAIL
read -p "Enter your domain (e.g., parish.quovadisyouthhub.org): " DOMAIN

echo ""
echo "Updating configuration files..."
echo ""

# Update webhook.php
update_config "public/webhook.php" "your-super-secret-webhook-key-change-this" "$WEBHOOK_SECRET"
update_config "public/webhook.php" "/home2/shemidig/parish_system" "$DEPLOY_PATH"
update_config "public/webhook.php" "define('PHP_VERSION', '82');" "define('PHP_VERSION', '$PHP_VER');"
update_config "public/webhook.php" "admin@quovadisyouthhub.org" "$NOTIFY_EMAIL"

# Update deploy.php
update_config "public/deploy.php" "your-super-secret-webhook-key-change-this" "$WEBHOOK_SECRET"
update_config "public/deploy.php" "/home2/shemidig/parish_system" "$DEPLOY_PATH"
update_config "public/deploy.php" "define('PHP_VERSION', '82');" "define('PHP_VERSION', '$PHP_VER');"

# Update .cpanel.yml
update_config ".cpanel.yml" "/home2/shemidig/parish_system" "$DEPLOY_PATH"
update_config ".cpanel.yml" "PHPVER=82" "PHPVER=$PHP_VER"

echo ""
echo "=========================================="
echo "✅ Configuration Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Upload webhook.php and deploy.php to your server's public/ directory"
echo "2. Create the webhook in GitHub:"
echo "   URL: https://${DOMAIN}/webhook.php"
echo "   Secret: ${WEBHOOK_SECRET}"
echo "   Content type: application/json"
echo "   Events: Just the push event"
echo "3. Test the webhook by pushing to your Main branch"
echo ""
echo "For detailed instructions, see CICD_SETUP_GUIDE.md"
echo ""
