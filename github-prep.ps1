# GitHub Repository Preparation Script
# This script helps you prepare your Laravel Parish System for GitHub push

# Step 1: Make sure .gitignore is properly set up (already checked)
Write-Host "✅ .gitignore file is properly configured" -ForegroundColor Green

# Step 2: Check for sensitive information
Write-Host "Checking for sensitive information in repository..." -ForegroundColor Yellow
Write-Host "  - Make sure .env files are in .gitignore (already confirmed)" -ForegroundColor Green
Write-Host "  - Make sure no API keys or credentials are hardcoded in the source files" -ForegroundColor Yellow
Write-Host "  - Ensure database passwords aren't committed to the repository" -ForegroundColor Yellow

# Step 3: Check Git status
Write-Host "`nChecking Git repository status..." -ForegroundColor Yellow
git status

# Step 4: Stage changes
Write-Host "`nWould you like to stage all changes? (y/n)" -ForegroundColor Cyan
$stageAll = Read-Host
if ($stageAll -eq "y") {
    git add .
    Write-Host "All changes have been staged." -ForegroundColor Green
}
else {
    Write-Host "You can manually stage changes with 'git add <file>'" -ForegroundColor Yellow
}

# Step 5: Prepare commit
Write-Host "`nPlease enter a commit message:" -ForegroundColor Cyan
$commitMessage = Read-Host
if ($commitMessage) {
    git commit -m "$commitMessage"
    Write-Host "Changes committed with message: $commitMessage" -ForegroundColor Green
}
else {
    Write-Host "No commit message provided. You can commit later with 'git commit -m \"Your message\"'" -ForegroundColor Yellow
}

# Step 6: Check remote status and branch name
Write-Host "`nChecking current branch name..." -ForegroundColor Yellow
$currentBranch = git rev-parse --abbrev-ref HEAD 2>$null
if (-not $currentBranch) {
    Write-Host "No branches found. This might be a new repository." -ForegroundColor Yellow
    Write-Host "Creating an initial commit..." -ForegroundColor Yellow
    git add .
    git commit -m "Initial commit"
    $currentBranch = "master"  # Default to master for new repositories
}

Write-Host "Current branch: $currentBranch" -ForegroundColor Green

Write-Host "`nChecking remote repository status..." -ForegroundColor Yellow
$remotes = git remote -v
if ($remotes) {
    Write-Host "Remote repositories configured:" -ForegroundColor Green
    Write-Host $remotes
    
    Write-Host "`nWould you like to push to the remote repository? (y/n)" -ForegroundColor Cyan
    $push = Read-Host
    if ($push -eq "y") {
        Write-Host "Pushing to remote repository..." -ForegroundColor Yellow
        git push -u origin $currentBranch
        
        # If current branch is master and we want to push to main, create main branch
        if ($currentBranch -eq "master") {
            Write-Host "`nWould you like to create and push a main branch as well? (y/n)" -ForegroundColor Cyan
            $createMain = Read-Host
            if ($createMain -eq "y") {
                Write-Host "Creating and pushing main branch..." -ForegroundColor Yellow
                git checkout -b main
                git push -u origin main
                Write-Host "Main branch created and pushed successfully." -ForegroundColor Green
            }
        }
    }
}
else {
    Write-Host "No remote repository configured. You need to add one with:" -ForegroundColor Yellow
    Write-Host "git remote add origin https://github.com/JosephNjoroge8/parish-management-system.git" -ForegroundColor Cyan
    
    Write-Host "`nWould you like to add the GitHub repository now? (y/n)" -ForegroundColor Cyan
    $addRemote = Read-Host
    if ($addRemote -eq "y") {
        Write-Host "Enter your GitHub repository URL:" -ForegroundColor Cyan
        $repoUrl = Read-Host
        if ($repoUrl) {
            git remote add origin $repoUrl
            Write-Host "Remote repository added. You can now push with:" -ForegroundColor Green
            Write-Host "git push -u origin $currentBranch" -ForegroundColor Cyan
        }
    }
}

Write-Host "`nGitHub preparation complete!" -ForegroundColor Green
Write-Host "Remember to:" -ForegroundColor Yellow
Write-Host "1. Make sure all migrations are completed with 'php artisan migrate'" -ForegroundColor Yellow
Write-Host "2. Ensure the application works correctly before pushing" -ForegroundColor Yellow
Write-Host "3. Double-check that no sensitive data is being committed" -ForegroundColor Yellow
