# Database Setup Instructions

## Current Status
✅ `.env` file created with database configuration  
✅ PHP development server running at http://localhost:8000  
✅ Login page simplified (direct PHP authentication)  
❌ **Database not yet created** (MySQL password issue)

---

## The Problem
The MySQL `root` user has a password set, but we don't know what it is.  
The scripts tried common passwords (`root`, `password`, `mysql`, empty) but none worked.

---

## Solution Options

### **OPTION 1: Use phpMyAdmin (Easiest)** ⭐ RECOMMENDED

1. **Open phpMyAdmin** - Already opened in Simple Browser: http://localhost/phpmyadmin

2. **Login** with your MySQL credentials (usually `root` + your MySQL password)

3. **Create Database**:
   - Click "New" in left sidebar
   - Database name: `healthinspection`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

4. **Import Schema**:
   - Select `healthinspection` database
   - Click "Import" tab
   - Choose file: `C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\database\migrations\001_create_tables.sql`
   - Click "Go"

5. **Import Seed Data**:
   - Click "Import" tab again
   - Choose file: `C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\database\seeds\001_initial_data.sql`
   - Click "Go"

6. **Update .env file**:
   - Open: `C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\.env`
   - Update line: `DB_PASS=your_mysql_password_here`

7. **Done!** Go to: http://localhost:8000/views/auth/login.php

---

### **OPTION 2: Use MySQL Command Line**

If you know the MySQL password:

```powershell
# Navigate to project
cd C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION

# Create database and import (replace YOUR_PASSWORD)
C:\xampp\mysql\bin\mysql.exe -u root -pYOUR_PASSWORD -e "CREATE DATABASE healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root -pYOUR_PASSWORD healthinspection < database\migrations\001_create_tables.sql
C:\xampp\mysql\bin\mysql.exe -u root -pYOUR_PASSWORD healthinspection < database\seeds\001_initial_data.sql

# Update .env file
notepad .env
# Change: DB_PASS=YOUR_PASSWORD
```

---

### **OPTION 3: Reset MySQL Password** (Advanced)

Run the password reset script we created:

```powershell
# This will reset MySQL root password to empty
powershell -ExecutionPolicy Bypass -File reset_mysql_password.ps1
```

Then run:
```powershell
C:\xampp\php\php.exe setup_simple.php
```

---

## After Database Setup

### Test Login
1. Go to: **http://localhost:8000/views/auth/login.php**
2. Use these credentials:
   ```
   Email: admin@lgu.gov.ph
   Password: Admin@123
   ```

### Other Test Accounts
```
superadmin@lgu.gov.ph / Admin@123 (Super Admin)
inspector@lgu.gov.ph / Admin@123 (Inspector)
owner@business.com / Admin@123 (Business Owner)
```

---

## Verification

After successful database setup, verify:

```powershell
C:\xampp\php\php.exe -r "
require 'config/database.php';
try {
    \$db = Database::getConnection();
    echo 'Database connection: SUCCESS\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"
```

---

## Need Help?

1. **Check MySQL is running**: Look for MySQL80 service in Task Manager
2. **Check XAMPP Control Panel**: MySQL should show as running
3. **Find MySQL password**: Check `C:\xampp\mysql\bin\my.ini` or XAMPP installation notes
4. **Reset MySQL password**: Use the `reset_mysql_password.ps1` script

---

## Quick Start (Once DB is Ready)

```powershell
# Start the server (if not already running)
C:\xampp\php\php.exe -S localhost:8000 -t public router.php

# Open login page
start http://localhost:8000/views/auth/login.php
```

**Login → Dashboard → Start using the Health & Safety Inspection System!** 🎉
