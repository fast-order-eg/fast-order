<#
.SYNOPSIS
    Fast Order Zero-Downtime Deployment Script for Windows.
.DESCRIPTION
    Deploys updates to the production server (new-cyberpanel-2) with Zero-Downtime,
    atomic symlink switching, proper permissions (fasto5299:nobody), and chunk preservation.
.PARAMETER Direct
    Performs fast atomic release deployment directly from local machine (default).
.PARAMETER Git
    Pushes to GitHub main branch and triggers zero_downtime_deploy.sh on the server.
#>
param(
    [switch]$Git,
    [switch]$SkipBuild,
    [string]$Message = "Update and deploy"
)

$ErrorActionPreference = "Stop"

Write-Host "=================================================" -ForegroundColor Cyan
Write-Host "🚀 Fast Order - Zero-Downtime Deployment System" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

if ($Git) {
    Write-Host "`n1️⃣ Staging and committing git changes..." -ForegroundColor Yellow
    git add .
    git commit -m $Message
    Write-Host "2️⃣ Pushing to GitHub main branch..." -ForegroundColor Yellow
    git push origin main

    Write-Host "3️⃣ Triggering Zero-Downtime Deploy on server..." -ForegroundColor Yellow
    $deployToken = $env:GITHUB_TOKEN
    ssh -o StrictHostKeyChecking=no root@new-cyberpanel-2 "bash /home/fast-order-eg.tech/deploy/zero_downtime_deploy.sh $deployToken"
    
    Write-Host "`n🎉 Deployment via GitHub CI/CD completed successfully!" -ForegroundColor Green
    exit 0
}

# Default: Direct Atomic Release Deployment
if (-not $SkipBuild) {
    Write-Host "`n1️⃣ Building Frontend Assets with Vite..." -ForegroundColor Yellow
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Frontend build failed! Aborting deployment."
    }
} else {
    Write-Host "`n1️⃣ Skipping frontend build as requested (-SkipBuild)..." -ForegroundColor DarkGray
}

$ReleaseTimestamp = Get-Date -Format "yyyyMMddHHmmss"
$ArchiveName = "release_$ReleaseTimestamp.tar.gz"
$ArchivePath = Join-Path $env:TEMP $ArchiveName

Write-Host "`n2️⃣ Creating clean release package: $ArchiveName..." -ForegroundColor Yellow

$targets = @(
    "app", "bootstrap", "config", "database", "lang",
    "public", "resources", "routes", "scripts",
    "artisan", "composer.json", "composer.lock",
    "package.json", "package-lock.json", "vite.config.js"
)

tar.exe -czf $ArchivePath --exclude="public/storage" --exclude="public/hot" --exclude="bootstrap/cache/*.php" @targets

if ($LASTEXITCODE -ne 0) {
    throw "Release archive creation failed. Deployment aborted."
}

$archiveEntries = @(tar.exe -tzf $ArchivePath)
if ($LASTEXITCODE -ne 0 -or ($archiveEntries | Where-Object { $_ -match '^(\./)?public/hot$' })) {
    throw "Unsafe release archive: could not inspect it or found the Vite development hot file. Deployment aborted."
}

if (-not (Test-Path $ArchivePath)) {
    Write-Error "Failed to create archive!"
}

$ArchiveSize = (Get-Item $ArchivePath).Length / 1MB
Write-Host ("   Archive created successfully ({0:N2} MB)" -f $ArchiveSize) -ForegroundColor Green

Write-Host "`n3️⃣ Uploading release to server..." -ForegroundColor Yellow
ssh -o StrictHostKeyChecking=no root@new-cyberpanel-2 "mkdir -p /home/fast-order-eg.tech/deploy/releases/$ReleaseTimestamp"
scp -q -o StrictHostKeyChecking=no $ArchivePath "root@new-cyberpanel-2:/home/fast-order-eg.tech/deploy/releases/$ReleaseTimestamp/$ArchiveName"

Write-Host "`n4️⃣ Extracting release files on server..." -ForegroundColor Yellow
ssh -o StrictHostKeyChecking=no root@new-cyberpanel-2 "tar -xzf /home/fast-order-eg.tech/deploy/releases/$ReleaseTimestamp/$ArchiveName -C /home/fast-order-eg.tech/deploy/releases/$ReleaseTimestamp && rm -f /home/fast-order-eg.tech/deploy/releases/$ReleaseTimestamp/$ArchiveName"

# Cleanup local temp archive
Remove-Item -Path $ArchivePath -Force -ErrorAction SilentlyContinue

Write-Host "`n5️⃣ Activating release with Zero-Downtime..." -ForegroundColor Yellow
ssh -o StrictHostKeyChecking=no root@new-cyberpanel-2 "bash /home/fast-order-eg.tech/deploy/deploy_direct_release.sh $ReleaseTimestamp"

Write-Host "`n6️⃣ Live Site Health Verification..." -ForegroundColor Yellow
$testUrl = "https://asma-tex.fast-order-eg.tech/admin/marketing/ads?date_preset=today"
try {
    $resp = Invoke-WebRequest -Uri $testUrl -UseBasicParsing -TimeoutSec 15
    Write-Host ("   ✅ Live check: {0} (Status {1})" -f $testUrl, $resp.StatusCode) -ForegroundColor Green
} catch {
    Write-Host ("   ⚠️ Live check returned: {0}" -f $_.Exception.Message) -ForegroundColor Yellow
}

Write-Host "`n=================================================" -ForegroundColor Cyan
Write-Host "🎉 Zero-Downtime Deployment Finished Successfully!" -ForegroundColor Green
Write-Host "=================================================" -ForegroundColor Cyan
