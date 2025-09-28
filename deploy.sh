#!/bin/bash

# Parish Management System - cPanel Deployment Script
# Alternative bash script for deployment

# Configuration
REPO_PATH="/home/yourusername/public_html/parish-management-system"  # Adjust this path
LOG_FILE="/home/yourusername/public_html/deployment.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Function to log messages
log_message() {
    echo "[$TIMESTAMP] $1" >> "$LOG_FILE"
    echo "$1"
}

log_message "=== DEPLOYMENT STARTED ==="

# Change to repository directory
cd "$REPO_PATH" || {
    log_message "ERROR: Could not change to repository directory: $REPO_PATH"
    exit 1
}

log_message "Changed to repository directory: $REPO_PATH"

# Pull latest changes
log_message "Pulling latest changes from GitHub..."
git pull origin main >> "$LOG_FILE" 2>&1

# Check and preserve existing .env file
if [ ! -f ".env" ]; then
    if [ -f ".env.cpanel" ]; then
        log_message "No .env found, copying from .env.cpanel template"
        cp .env.cpanel .env
    else
        log_message "WARNING: No .env file found and no template available"
    fi
else
    log_message "Existing .env file preserved - not overwriting"
fi

# Check if composer is available and install dependencies
if [ -f "composer.json" ]; then
    log_message "Running composer install..."
    composer install --no-dev --optimize-autoloader >> "$LOG_FILE" 2>&1
else
    log_message "No composer.json found, skipping composer install"
fi

# Check if package.json exists and build assets
if [ -f "package.json" ]; then
    log_message "Installing npm dependencies..."
    npm ci >> "$LOG_FILE" 2>&1
    
    log_message "Building production assets..."
    npm run build >> "$LOG_FILE" 2>&1
else
    log_message "No package.json found, skipping npm build"
fi

# Laravel specific commands
if [ -f "artisan" ]; then
    log_message "Running Laravel optimization commands..."
    
    # Clear caches
    php artisan cache:clear >> "$LOG_FILE" 2>&1
    php artisan config:clear >> "$LOG_FILE" 2>&1
    php artisan route:clear >> "$LOG_FILE" 2>&1
    php artisan view:clear >> "$LOG_FILE" 2>&1
    
    # Optimize for production
    php artisan config:cache >> "$LOG_FILE" 2>&1
    php artisan route:cache >> "$LOG_FILE" 2>&1
    php artisan view:cache >> "$LOG_FILE" 2>&1
    
    # Run migrations (be careful with this in production)
    php artisan migrate --force >> "$LOG_FILE" 2>&1
    
    # Ensure roles and permissions are set up
    php artisan db:seed --class=RolePermissionSeeder --force >> "$LOG_FILE" 2>&1
    
    # Create storage link if needed
    if [ ! -L "public/storage" ]; then
        php artisan storage:link >> "$LOG_FILE" 2>&1
    fi
    
    log_message "Laravel optimization completed"
fi

# Set proper permissions
log_message "Setting file permissions..."
if [ -d "storage" ]; then
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
fi

# Fix ownership if needed (adjust username)
# chown -R yourusername:yourusername .

log_message "=== DEPLOYMENT COMPLETED SUCCESSFULLY ==="

exit 0