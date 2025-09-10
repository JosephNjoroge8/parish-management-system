# Production Build Script for Laravel Parish System
# This script performs all necessary steps to build the application for production

# Navigate to project directory
Set-Location -Path "C:\Users\Joseph Njoroge\parish-system"

# 1. Clear cache before starting
Write-Host "Clearing cache..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Run database migrations with force flag
Write-Host "Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force

# 3. Install npm dependencies if needed
Write-Host "Installing npm dependencies..." -ForegroundColor Yellow
npm install

# 4. Build frontend assets for production
Write-Host "Building frontend assets for production..." -ForegroundColor Yellow
npm run build

# 5. Optimize Laravel for production
Write-Host "Optimizing Laravel for production..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Create symbolic link for storage
Write-Host "Creating storage symbolic link..." -ForegroundColor Yellow
php artisan storage:link

# 7. Final cleanup
Write-Host "Performing final cleanup..." -ForegroundColor Yellow
php artisan optimize

Write-Host "Production build complete!" -ForegroundColor Green
Write-Host "The application is now ready for deployment." -ForegroundColor Green
