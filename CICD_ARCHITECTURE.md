# CI/CD Pipeline Architecture - Visual Overview

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           DEVELOPER WORKFLOW                                 │
│                                                                               │
│  Local Development                                                            │
│  ┌──────────────┐                                                             │
│  │  Developer   │──► Edit Code                                               │
│  │  Machine     │──► Run Tests Locally                                       │
│  │              │──► Commit Changes                                          │
│  └──────┬───────┘                                                             │
│         │                                                                     │
│         │ git push origin Main                                                │
│         ▼                                                                     │
└─────────┼─────────────────────────────────────────────────────────────────────┘
          │
┌─────────┴─────────────────────────────────────────────────────────────────────┐
│                           GITHUB REPOSITORY                                   │
│                                                                               │
│  ┌────────────────────────────────────────────────────────────────┐          │
│  │                     GitHub Actions Workflow                     │          │
│  │                                                                 │          │
│  │  Job 1: Run Tests                        [2-3 minutes]         │          │
│  │  ├── Setup PHP 8.2                                             │          │
│  │  ├── Setup MySQL                                               │          │
│  │  ├── Install Composer dependencies                             │          │
│  │  ├── Run migrations                                            │          │
│  │  └── Execute PHPUnit tests                                     │          │
│  │                                                                 │          │
│  │  Job 2: Build Assets                     [3-5 minutes]         │          │
│  │  ├── Setup Node.js 20                                          │          │
│  │  ├── Install NPM dependencies                                  │          │
│  │  ├── Build production assets (Vite)                            │          │
│  │  └── Upload artifacts                                          │          │
│  │                                                                 │          │
│  │  Job 3: Code Quality                     [1-2 minutes]         │          │
│  │  ├── Run Laravel Pint                                          │          │
│  │  └── Check code style                                          │          │
│  │                                                                 │          │
│  │  Job 4: Notify                           [< 10 seconds]        │          │
│  │  └── Summary of all jobs                                       │          │
│  │                                                                 │          │
│  │  ✅ All Jobs Passed → Trigger Webhook                          │          │
│  └─────────────────────────────┬───────────────────────────────────┘          │
│                                │                                              │
└────────────────────────────────┼──────────────────────────────────────────────┘
                                 │
                                 │ HTTP POST Request
                                 │ (Signed with HMAC SHA-256)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      PRODUCTION SERVER (cPanel)                              │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────┐            │
│  │                   webhook.php [< 5 seconds]                 │            │
│  │                                                              │            │
│  │  1. Receive POST request from GitHub                        │            │
│  │  2. Verify request method (POST only)                       │            │
│  │  3. Check IP address (GitHub IPs)                           │            │
│  │  4. Validate signature (HMAC SHA-256)                       │            │
│  │  5. Parse JSON payload                                      │            │
│  │  6. Check event type (push only)                            │            │
│  │  7. Check branch (Main only)                                │            │
│  │  8. Log request details                                     │            │
│  │  9. Trigger deploy.php via curl                             │            │
│  │  10. Return success response                                │            │
│  │                                                              │            │
│  │  ✅ Signature Verified → Call Deployment                    │            │
│  └──────────────────────────┬──────────────────────────────────┘            │
│                             │                                                │
│                             ▼                                                │
│  ┌─────────────────────────────────────────────────────────────┐            │
│  │                   deploy.php [3-7 minutes]                  │            │
│  │                                                              │            │
│  │  Phase 1: Preparation                    [30 seconds]       │            │
│  │  ├── Verify deployment secret                               │            │
│  │  ├── Create backup of current code                          │            │
│  │  ├── Enable maintenance mode                                │            │
│  │  └── Log deployment start                                   │            │
│  │                                                              │            │
│  │  Phase 2: Code Update                    [30 seconds]       │            │
│  │  ├── Git fetch origin Main                                  │            │
│  │  ├── Git reset --hard origin/Main                           │            │
│  │  ├── Git clean -fd                                          │            │
│  │  └── Verify code updated                                    │            │
│  │                                                              │            │
│  │  Phase 3: Dependencies                   [1-2 minutes]      │            │
│  │  ├── Composer install --no-dev                              │            │
│  │  └── Verify vendor directory                                │            │
│  │                                                              │            │
│  │  Phase 4: Assets                         [2-4 minutes]      │            │
│  │  ├── NPM install                                            │            │
│  │  ├── NPM run build                                          │            │
│  │  └── Verify build/manifest.json                             │            │
│  │                                                              │            │
│  │  Phase 5: Database                       [10-30 seconds]    │            │
│  │  ├── Run migrations --force                                 │            │
│  │  └── Verify migrations completed                            │            │
│  │                                                              │            │
│  │  Phase 6: Optimization                   [20-30 seconds]    │            │
│  │  ├── Clear all caches                                       │            │
│  │  ├── Config cache                                           │            │
│  │  ├── Route cache                                            │            │
│  │  ├── View cache                                             │            │
│  │  ├── Event cache                                            │            │
│  │  └── Set file permissions                                   │            │
│  │                                                              │            │
│  │  Phase 7: Finalization                   [5 seconds]        │            │
│  │  ├── Create storage link                                    │            │
│  │  ├── Disable maintenance mode                               │            │
│  │  ├── Clean old backups                                      │            │
│  │  └── Log deployment success                                 │            │
│  │                                                              │            │
│  │  ✅ Deployment Complete!                                    │            │
│  └─────────────────────────────────────────────────────────────┘            │
│                                                                               │
│  On Failure: Automatic Rollback                                              │
│  ├── Restore from latest backup                                              │
│  ├── Clear caches                                                            │
│  ├── Disable maintenance mode                                                │
│  └── Log error details                                                       │
│                                                                               │
└─────────────────────────────────────────────────────────────────────────────┘
          │
          │ Production Site Updated
          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        END USERS (Website Visitors)                          │
│                                                                               │
│  ┌──────────────┐                                                             │
│  │   Browser    │──► Access updated site                                     │
│  │              │──► See latest features                                     │
│  │              │──► No downtime noticed (maintenance mode)                  │
│  └──────────────┘                                                             │
│                                                                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
GitHub → Webhook → Deployment → Production
  │         │          │            │
  │         │          │            └─► Users see updates
  │         │          └─► Install, build, migrate
  │         └─► Verify, authenticate, trigger
  └─► Test, build, trigger webhook
```

## Security Layers

```
┌─────────────────────────────────────────────┐
│         Layer 1: GitHub Actions              │
│  • Repository permissions                    │
│  • Branch protection rules                   │
│  • Required status checks                    │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────┴──────────────────────────┐
│         Layer 2: Webhook Security            │
│  • HMAC SHA-256 signature                    │
│  • IP whitelisting (GitHub IPs)              │
│  • HTTPS only                                │
│  • Rate limiting                             │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────┴──────────────────────────┐
│         Layer 3: Deployment Security         │
│  • Secret key verification                   │
│  • Internal server calls only                │
│  • Protected via .htaccess                   │
│  • Localhost-only access                     │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────┴──────────────────────────┐
│         Layer 4: Application Security        │
│  • .env not in repository                    │
│  • Debug mode off in production              │
│  • Database credentials secured              │
│  • File permissions (755/644)                │
└─────────────────────────────────────────────┘
```

## Timeline Breakdown

```
Time    Stage                    Activity
─────────────────────────────────────────────────────────────
00:00   Push to Main             Developer pushes code
00:05   GitHub Actions Start     Checkout code
00:30   Tests Begin              PHPUnit tests running
02:30   Tests Complete           ✅ All tests pass
02:35   Build Begin              Install NPM dependencies
05:30   Build Complete           ✅ Assets built
05:35   Pint Check              Code quality verification
06:00   All Jobs Complete        ✅ All checks passed
06:05   Webhook Triggered        POST to webhook.php
06:10   Webhook Verified         Signature validated
06:15   Deployment Start         deploy.php called
06:45   Backup Created           Current code backed up
07:00   Maintenance Mode         Site in maintenance
07:15   Code Pulled              Latest code from Git
07:45   Composer Install         Dependencies updated
09:45   NPM Build               Assets built on server
11:00   Migrations Run           Database updated
11:30   Caches Built            Optimized for production
12:00   Maintenance Off          Site back online
12:05   Deployment Complete      ✅ Production updated!
─────────────────────────────────────────────────────────────
Total Time: ~12 minutes (8-15 min typical range)
```

## File Structure

```
parish-management-system/
│
├── public/
│   ├── webhook.php              ◄── Receives GitHub webhooks
│   ├── deploy.php               ◄── Executes deployment
│   ├── .htaccess                ◄── Security rules
│   └── build/                   ◄── Production assets
│       ├── manifest.json
│       └── assets/
│
├── .github/
│   └── workflows/
│       └── laravel.yml          ◄── GitHub Actions workflow
│
├── storage/
│   └── logs/
│       ├── webhook.log          ◄── Webhook requests
│       ├── deployment.log       ◄── Deployment process
│       └── laravel.log          ◄── Application errors
│
├── backups/                     ◄── Automatic backups
│   ├── backup_20241202120000.tar.gz
│   ├── backup_20241202140000.tar.gz
│   └── ...
│
├── .cpanel.yml                  ◄── cPanel Git config (optional)
├── setup-webhook.sh             ◄── Configuration helper
├── install-cicd.sh              ◄── Installation script
│
└── Documentation/
    ├── README_CICD.md           ◄── Overview
    ├── CICD_QUICK_START.md      ◄── Quick setup
    ├── CICD_SETUP_GUIDE.md      ◄── Complete guide
    └── CICD_TESTING_GUIDE.md    ◄── Testing guide
```

## Logging & Monitoring

```
┌─────────────────────────────────────────────────────────────┐
│                    Logging System                            │
│                                                               │
│  webhook.log                                                 │
│  ├── Timestamp                                               │
│  ├── Request method                                          │
│  ├── Remote IP                                               │
│  ├── Event type                                              │
│  ├── Branch                                                  │
│  ├── Commit info                                             │
│  └── Deployment trigger result                               │
│                                                               │
│  deployment.log                                              │
│  ├── Deployment start                                        │
│  ├── Each phase execution                                    │
│  ├── Command outputs                                         │
│  ├── Success/failure status                                  │
│  └── Duration                                                │
│                                                               │
│  webhook-errors.log                                          │
│  ├── PHP errors in webhook.php                               │
│  └── Exception stack traces                                  │
│                                                               │
│  laravel.log                                                 │
│  ├── Application errors                                      │
│  ├── Database queries (if debug)                             │
│  └── Custom log messages                                     │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## Error Handling & Recovery

```
┌───────────────────────────────────────────────────────────┐
│                  Error Detection                           │
│                                                             │
│  Tests Fail ──────────────► Block Deployment               │
│  Build Fails ─────────────► Block Deployment               │
│  Webhook Invalid ─────────► Reject Request                 │
│  Git Pull Fails ──────────► Rollback                       │
│  Composer Fails ──────────► Rollback                       │
│  Build Fails ─────────────► Rollback                       │
│  Migration Fails ─────────► Continue (log warning)         │
│                                                             │
└───────────────────┬───────────────────────────────────────┘
                    │
                    ▼
┌───────────────────────────────────────────────────────────┐
│                  Automatic Recovery                        │
│                                                             │
│  1. Detect failure                                         │
│  2. Log error details                                      │
│  3. Find latest backup                                     │
│  4. Extract backup to directory                            │
│  5. Clear all caches                                       │
│  6. Rebuild optimizations                                  │
│  7. Disable maintenance mode                               │
│  8. Send notification (if configured)                      │
│  9. Log rollback complete                                  │
│                                                             │
│  ✅ Site restored to working state                         │
│                                                             │
└───────────────────────────────────────────────────────────┘
```

## Backup Strategy

```
Backup Created Before Each Deployment
│
├── Naming: backup_YYYYMMDDHHmmss.tar.gz
├── Location: /backups/
├── Contents: All files except node_modules, vendor, logs
├── Retention: Keep last 5 backups
└── Automatic cleanup: Delete oldest when > 5

Backup Process:
1. Create timestamp
2. Compress current directory
3. Exclude: node_modules, vendor, logs, cache
4. Save to backups/
5. Delete backups beyond retention limit

Restore Process:
1. Identify latest backup
2. Extract to deployment directory
3. Clear all caches
4. Rebuild optimizations
5. Verify site functionality
```

## Performance Metrics

```
┌─────────────────────────────────────────────────────────────┐
│                   Typical Performance                        │
│                                                               │
│  GitHub Actions                                              │
│  ├── Test Job:           2-3 minutes                         │
│  ├── Build Job:          3-5 minutes                         │
│  ├── Quality Job:        1-2 minutes                         │
│  └── Notify Job:         < 10 seconds                        │
│  Total:                  6-8 minutes                          │
│                                                               │
│  Deployment Process                                          │
│  ├── Webhook:            < 5 seconds                         │
│  ├── Backup:             10-30 seconds                       │
│  ├── Git Pull:           10-30 seconds                       │
│  ├── Composer:           1-2 minutes                         │
│  ├── NPM Build:          2-4 minutes                         │
│  ├── Migrations:         5-30 seconds                        │
│  └── Optimization:       20-30 seconds                       │
│  Total:                  4-7 minutes                          │
│                                                               │
│  Overall Pipeline:       10-15 minutes                        │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

**Legend:**
- ◄── = File or directory
- ──► = Data flow
- ✅ = Success checkpoint
- ┌─┐ = Process boundary
- │ = Connection
- └─┘ = Process end
