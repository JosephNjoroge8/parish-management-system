# Script to find PHP syntax errors in the project
# This helps identify syntax errors before pushing to GitHub

Write-Host "🔍 PHP Syntax Error Finder" -ForegroundColor Cyan
Write-Host "Scanning all PHP files for syntax errors..." -ForegroundColor Yellow

$directories = @("app", "routes", "config", "resources", "database")
$hasErrors = $false
$errorFiles = @()

foreach ($dir in $directories) {
    if (Test-Path $dir) {
        Write-Host "`nChecking files in $dir directory..." -ForegroundColor Blue
        
        $files = Get-ChildItem -Path $dir -Recurse -Filter "*.php"
        
        foreach ($file in $files) {
            $output = & php -l $file.FullName 2>&1
            
            if ($output -match "Errors parsing") {
                Write-Host "❌ Error in $($file.FullName)" -ForegroundColor Red
                Write-Host $output -ForegroundColor Red
                $hasErrors = $true
                $errorFiles += $file.FullName
            } else {
                Write-Host "✓ $($file.FullName)" -ForegroundColor Green
            }
        }
    } else {
        Write-Host "Directory $dir not found, skipping..." -ForegroundColor Yellow
    }
}

if ($hasErrors) {
    Write-Host "`n❌ Found syntax errors in the following files:" -ForegroundColor Red
    foreach ($errorFile in $errorFiles) {
        Write-Host "- $errorFile" -ForegroundColor Red
    }
    Write-Host "`nFix these errors before pushing to GitHub." -ForegroundColor Yellow
} else {
    Write-Host "`n✅ No PHP syntax errors found! Your code looks good." -ForegroundColor Green
}
