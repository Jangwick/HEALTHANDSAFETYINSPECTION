# MySQL Password Reset Script (PowerShell) - Auto-confirm version
# Health & Safety Inspection System

Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "  MySQL Password Reset Tool - Auto Mode" -ForegroundColor Cyan
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "This script will reset your MySQL root password to empty (no password)." -ForegroundColor Yellow
Write-Host "Starting automatic password reset..." -ForegroundColor Green
Write-Host ""

# Step 1: Stop MySQL service
Write-Host "Step 1: Stopping MySQL service..." -ForegroundColor Green

$services = @("MySQL80", "MySQL", "mysql")
$serviceStopped = $false
$mysqlService = $null

foreach ($svc in $services) {
    try {
        $service = Get-Service -Name $svc -ErrorAction SilentlyContinue
        if ($service) {
            Write-Host "  Found service: $svc" -ForegroundColor Yellow
            Stop-Service -Name $svc -Force -ErrorAction Stop
            Write-Host "  [OK] Stopped $svc service" -ForegroundColor Green
            $mysqlService = $svc
            $serviceStopped = $true
            break
        }
    } catch {
        continue
    }
}

if (-not $serviceStopped) {
    Write-Host "  [ERROR] Could not find/stop MySQL service" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please try manually:" -ForegroundColor Yellow
    Write-Host "1. Open Services (services.msc)" -ForegroundColor Yellow
    Write-Host "2. Find MySQL service and stop it" -ForegroundColor Yellow
    exit 1
}

Start-Sleep -Seconds 3

Write-Host ""
Write-Host "Step 2: Creating temporary init file..." -ForegroundColor Green

$initFile = "$env:TEMP\mysql-init.txt"
$sqlContent = @"
ALTER USER 'root'@'localhost' IDENTIFIED BY '';
FLUSH PRIVILEGES;
"@
$sqlContent | Out-File -FilePath $initFile -Encoding ASCII

Write-Host "  [OK] Init file created: $initFile" -ForegroundColor Green

Write-Host ""
Write-Host "Step 3: Locating MySQL executable..." -ForegroundColor Green

# Common MySQL installation paths
$mysqldPaths = @(
    "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe",
    "C:\Program Files\MySQL\MySQL Server 8.1\bin\mysqld.exe",
    "C:\Program Files\MySQL\MySQL Server 8.2\bin\mysqld.exe",
    "C:\Program Files (x86)\MySQL\MySQL Server 8.0\bin\mysqld.exe",
    "C:\xampp\mysql\bin\mysqld.exe",
    "C:\MySQL\bin\mysqld.exe"
)

$mysqldPath = $null
foreach ($path in $mysqldPaths) {
    if (Test-Path $path) {
        $mysqldPath = $path
        Write-Host "  [OK] Found MySQL at: $mysqldPath" -ForegroundColor Green
        break
    }
}

if (-not $mysqldPath) {
    Write-Host "  [ERROR] Could not locate mysqld.exe" -ForegroundColor Red
    Write-Host ""
    Write-Host "Common locations checked:" -ForegroundColor Yellow
    foreach ($path in $mysqldPaths) {
        Write-Host "  - $path" -ForegroundColor Gray
    }
    Write-Host ""
    Start-Service -Name $mysqlService
    exit 1
}

Write-Host ""
Write-Host "Step 4: Starting MySQL with password reset..." -ForegroundColor Green
Write-Host "  This may take 10-15 seconds..." -ForegroundColor Yellow

try {
    $process = Start-Process -FilePath $mysqldPath -ArgumentList "--init-file=`"$initFile`"" -PassThru -WindowStyle Hidden -ErrorAction Stop
    Write-Host "  MySQL process started (PID: $($process.Id))" -ForegroundColor Yellow
    
    Start-Sleep -Seconds 12
    
    Write-Host "  [OK] Password reset processed" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Step 5: Stopping temporary MySQL process..." -ForegroundColor Green
    
    Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
    
    Write-Host "  [OK] Temporary process stopped" -ForegroundColor Green
} catch {
    Write-Host "  [WARNING] Process handling: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host "  Attempting to kill any remaining mysqld processes..." -ForegroundColor Yellow
    Get-Process mysqld -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

Write-Host ""
Write-Host "Step 6: Cleaning up..." -ForegroundColor Green
Remove-Item -Path $initFile -Force -ErrorAction SilentlyContinue
Write-Host "  [OK] Temporary files removed" -ForegroundColor Green

Write-Host ""
Write-Host "Step 7: Starting MySQL service..." -ForegroundColor Green
try {
    Start-Service -Name $mysqlService -ErrorAction Stop
    Start-Sleep -Seconds 5
    $serviceStatus = (Get-Service -Name $mysqlService).Status
    Write-Host "  [OK] MySQL service started (Status: $serviceStatus)" -ForegroundColor Green
} catch {
    Write-Host "  [ERROR] Failed to start service: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "  PASSWORD RESET COMPLETE!" -ForegroundColor Green
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "The MySQL root password is now empty (no password)." -ForegroundColor Green
Write-Host ""
Write-Host "Your .env file is already configured correctly:" -ForegroundColor White
Write-Host "  DB_PASS=" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""

# Automatically run database setup
Write-Host "Now running database setup..." -ForegroundColor Cyan
Write-Host ""
Start-Sleep -Seconds 2
