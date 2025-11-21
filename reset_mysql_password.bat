@echo off
echo ========================================================================
echo   MySQL Password Reset Tool - Health & Safety Inspection System
echo ========================================================================
echo.
echo This script will help you reset your MySQL root password.
echo.
echo WARNING: This will stop MySQL service temporarily.
echo.
pause

echo.
echo Step 1: Stopping MySQL service...
net stop MySQL80
if errorlevel 1 (
    echo Failed to stop MySQL80. Trying alternate service names...
    net stop mysql
)

echo.
echo Step 2: Creating temporary init file...
echo ALTER USER 'root'@'localhost' IDENTIFIED BY ''; > %TEMP%\mysql-init.txt
echo FLUSH PRIVILEGES; >> %TEMP%\mysql-init.txt

echo.
echo Step 3: Starting MySQL with init file to reset password...
echo This will set the root password to empty (blank).
echo.

start /B "MySQL Reset" "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe" --init-file=%TEMP%\mysql-init.txt --console

echo Waiting 10 seconds for MySQL to process reset...
timeout /t 10 /nobreak

echo.
echo Step 4: Stopping temporary MySQL process...
taskkill /F /FI "WINDOWTITLE eq MySQL Reset*"

echo.
echo Step 5: Cleaning up temporary file...
del %TEMP%\mysql-init.txt

echo.
echo Step 6: Starting MySQL service normally...
net start MySQL80
if errorlevel 1 (
    net start mysql
)

echo.
echo ========================================================================
echo   Password Reset Complete!
echo ========================================================================
echo.
echo The MySQL root password has been reset to empty (no password).
echo.
echo Your .env file is already configured for no password:
echo   DB_PASS=
echo.
echo You can now run the database setup:
echo   C:\xampp\php\php.exe setup_database_interactive.php
echo.
echo ========================================================================
pause
