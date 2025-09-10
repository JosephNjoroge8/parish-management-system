# Git Branch Fix Script for Parish System
# This script helps fix the "main cannot be resolved to branch" error

Write-Host "🛠️ Git Branch Fix Script" -ForegroundColor Cyan
Write-Host "This script will help resolve the 'main cannot be resolved to branch' error" -ForegroundColor Yellow

# Check current branch
Write-Host "`nChecking current branch..." -ForegroundColor Yellow
$currentBranch = git rev-parse --abbrev-ref HEAD 2>$null

if (-not $currentBranch) {
    Write-Host "No branches found. This might be a new repository." -ForegroundColor Red
    Write-Host "Creating an initial commit..." -ForegroundColor Yellow
    
    # Check if there are files to commit
    $status = git status --porcelain
    if (-not $status) {
        Write-Host "No files found to commit. Adding a placeholder file..." -ForegroundColor Yellow
        "# Parish System" | Out-File -FilePath "README.md" -Encoding utf8 -Force
        git add README.md
    } else {
        git add .
    }
    
    git commit -m "Initial commit"
    $currentBranch = "master"  # Default branch for new repositories
}

Write-Host "Current branch: $currentBranch" -ForegroundColor Green

# Option 1: Push current branch
Write-Host "`nOption 1: Push the current '$currentBranch' branch to remote" -ForegroundColor Cyan
Write-Host "This will push your current branch to GitHub." -ForegroundColor Yellow

# Option 2: Rename to main
Write-Host "`nOption 2: Rename the current branch to 'main' and push" -ForegroundColor Cyan
Write-Host "This will rename your current branch to 'main' and push to GitHub." -ForegroundColor Yellow

# Option 3: Create main from current
Write-Host "`nOption 3: Create a new 'main' branch from current branch and push both" -ForegroundColor Cyan
Write-Host "This keeps your current branch and also creates a 'main' branch." -ForegroundColor Yellow

Write-Host "`nWhich option would you like to choose? (1/2/3)" -ForegroundColor Cyan
$option = Read-Host

switch ($option) {
    "1" {
        Write-Host "Pushing current '$currentBranch' branch to remote..." -ForegroundColor Yellow
        git push -u origin $currentBranch
        Write-Host "Done! Your '$currentBranch' branch has been pushed." -ForegroundColor Green
        Write-Host "On GitHub, you may want to set this branch as the default branch." -ForegroundColor Yellow
    }
    "2" {
        Write-Host "Renaming current branch to 'main'..." -ForegroundColor Yellow
        git branch -m main
        Write-Host "Pushing to remote..." -ForegroundColor Yellow
        git push -u origin main
        Write-Host "Done! Your branch has been renamed to 'main' and pushed." -ForegroundColor Green
    }
    "3" {
        Write-Host "Creating a new 'main' branch..." -ForegroundColor Yellow
        git checkout -b main
        Write-Host "Pushing 'main' branch to remote..." -ForegroundColor Yellow
        git push -u origin main
        Write-Host "Switching back to '$currentBranch'..." -ForegroundColor Yellow
        git checkout $currentBranch
        Write-Host "Pushing '$currentBranch' branch to remote..." -ForegroundColor Yellow
        git push -u origin $currentBranch
        Write-Host "Done! Both 'main' and '$currentBranch' branches have been pushed." -ForegroundColor Green
    }
    default {
        Write-Host "Invalid option. Please run the script again and choose 1, 2, or 3." -ForegroundColor Red
    }
}

Write-Host "`nRemember to:" -ForegroundColor Yellow
Write-Host "1. Set your preferred branch as the default on GitHub" -ForegroundColor Yellow
Write-Host "2. Make sure your local work matches the remote repository" -ForegroundColor Yellow
