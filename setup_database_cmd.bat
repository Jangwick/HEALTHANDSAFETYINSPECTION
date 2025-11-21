@echo off
echo ========================================================================
echo   Database Creation via Command Line
echo ========================================================================
echo.
echo This will create the database using XAMPP's MariaDB.
echo.
echo Attempting to connect to MariaDB (no password)...
echo.

C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if errorlevel 1 (
    echo.
    echo Failed with no password. Trying with password...
    echo.
    set /p MYSQL_PASS="Enter your MySQL/MariaDB root password: "
    
    C:\xampp\mysql\bin\mysql.exe -u root -p%MYSQL_PASS% -e "CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if errorlevel 1 (
        echo.
        echo Failed to create database. Please check your password.
        pause
        exit /b 1
    )
    
    echo.
    echo Database created! Updating .env file...
    powershell -Command "(Get-Content .env) -replace '^DB_PASS=.*', 'DB_PASS=%MYSQL_PASS%' | Set-Content .env"
)

echo.
echo Database created successfully!
echo.
echo Now importing schema...
C:\xampp\mysql\bin\mysql.exe -u root healthinspection < database\migrations\001_create_tables.sql

if errorlevel 1 (
    echo.
    echo Schema import failed. Trying with password...
    C:\xampp\mysql\bin\mysql.exe -u root -p%MYSQL_PASS% healthinspection < database\migrations\001_create_tables.sql
)

echo.
echo Importing seed data...
C:\xampp\mysql\bin\mysql.exe -u root healthinspection < database\seeds\001_initial_data.sql

if errorlevel 1 (
    C:\xampp\mysql\bin\mysql.exe -u root -p%MYSQL_PASS% healthinspection < database\seeds\001_initial_data.sql
)

echo.
echo ========================================================================
echo   DATABASE SETUP COMPLETE!
echo ========================================================================
echo.
echo You can now login at: http://localhost:8000/views/auth/login.php
echo   Email: admin@lgu.gov.ph
echo   Password: Admin@123
echo.
pause
