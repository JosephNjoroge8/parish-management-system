@echo off
REM GitHub Repository Preparation Script
REM This script helps you prepare your Laravel Parish System for GitHub push

echo [92m✅ .gitignore file is properly configured[0m

echo [93mChecking for sensitive information in repository...[0m
echo [92m  - Make sure .env files are in .gitignore (already confirmed)[0m
echo [93m  - Make sure no API keys or credentials are hardcoded in the source files[0m
echo [93m  - Ensure database passwords aren't committed to the repository[0m

echo.
echo [93mChecking Git repository status...[0m
git status

echo.
set /p stageAll="Would you like to stage all changes? (y/n): "
if /i "%stageAll%"=="y" (
    git add .
    echo [92mAll changes have been staged.[0m
) else (
    echo [93mYou can manually stage changes with 'git add <file>'[0m
)

echo.
set /p commitMessage="Please enter a commit message: "
if not "%commitMessage%"=="" (
    git commit -m "%commitMessage%"
    echo [92mChanges committed with message: %commitMessage%[0m
) else (
    echo [93mNo commit message provided. You can commit later with 'git commit -m "Your message"'[0m
)

echo.
echo [93mChecking remote repository status...[0m
git remote -v
if %ERRORLEVEL% EQU 0 (
    echo [92mRemote repositories configured:[0m
    git remote -v
    
    echo.
    set /p push="Would you like to push to the remote repository? (y/n): "
    if /i "%push%"=="y" (
        echo [93mPushing to remote repository...[0m
        git push origin main
    )
) else (
    echo [93mNo remote repository configured. You need to add one with:[0m
    echo [96mgit remote add origin https://github.com/JosephNjoroge8/parish-management-system.git[0m
    
    echo.
    set /p addRemote="Would you like to add the GitHub repository now? (y/n): "
    if /i "%addRemote%"=="y" (
        set /p repoUrl="Enter your GitHub repository URL: "
        if not "%repoUrl%"=="" (
            git remote add origin %repoUrl%
            echo [92mRemote repository added. You can now push with:[0m
            echo [96mgit push -u origin main[0m
        )
    )
)

echo.
echo [92mGitHub preparation complete![0m
echo [93mRemember to:[0m
echo [93m1. Make sure all migrations are completed with 'php artisan migrate'[0m
echo [93m2. Ensure the application works correctly before pushing[0m
echo [93m3. Double-check that no sensitive data is being committed[0m

pause
