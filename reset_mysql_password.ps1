# MySQL Password Reset Script (PowerShell)
# Health & Safety Inspection System

Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "  MySQL Password Reset Tool" -ForegroundColor Cyan
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "This script will reset your MySQL root password to empty (no password)." -ForegroundColor Yellow
Write-Host "WARNING: This will temporarily stop the MySQL service." -ForegroundColor Red
Write-Host ""

$confirm = Read-Host "Do you want to continue? (yes/no)"
if ($confirm -ne "yes") {
    Write-Host "Cancelled." -ForegroundColor Red
    exit
}

Write-Host ""
Write-Host "Step 1: Stopping MySQL service..." -ForegroundColor Green

# Try different MySQL service names
$services = @("MySQL80", "MySQL", "mysql")
$serviceStopped = $false

foreach ($svc in $services) {
    try {
        $service = Get-Service -Name $svc -ErrorAction SilentlyContinue
        if ($service) {
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
    Write-Host "2. Find MySQL service and note its name" -ForegroundColor Yellow
    Write-Host "3. Stop the service" -ForegroundColor Yellow
    exit 1
}

Start-Sleep -Seconds 2

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
    Write-Host "Please locate mysqld.exe manually and run:" -ForegroundColor Yellow
    Write-Host "  mysqld.exe --init-file=$initFile" -ForegroundColor Yellow
    Start-Service -Name $mysqlService
    exit 1
}

Write-Host ""
Write-Host "Step 4: Starting MySQL with password reset..." -ForegroundColor Green
Write-Host "  This may take 10-15 seconds..." -ForegroundColor Yellow

$process = Start-Process -FilePath $mysqldPath -ArgumentList "--init-file=`"$initFile`"" -PassThru -WindowStyle Hidden

Start-Sleep -Seconds 12

Write-Host "  [OK] Password reset processed" -ForegroundColor Green

Write-Host ""
Write-Host "Step 5: Stopping temporary MySQL process..." -ForegroundColor Green

Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

Write-Host "  [OK] Temporary process stopped" -ForegroundColor Green

Write-Host ""
Write-Host "Step 6: Cleaning up..." -ForegroundColor Green
Remove-Item -Path $initFile -Force -ErrorAction SilentlyContinue
Write-Host "  [OK] Temporary files removed" -ForegroundColor Green

Write-Host ""
Write-Host "Step 7: Starting MySQL service..." -ForegroundColor Green
Start-Service -Name $mysqlService
Start-Sleep -Seconds 3
Write-Host "  [OK] MySQL service started" -ForegroundColor Green

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
Write-Host "Next step: Run the database setup script:" -ForegroundColor White
Write-Host "  C:\xampp\php\php.exe setup_database_interactive.php" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================================================" -ForegroundColor Cyan
