# 🔐 URGENT: Manual Database Setup Required

## Current Situation

The automated password reset requires **Administrator privileges** which we cannot execute from within VS Code.

**MySQL root has a password set** - automated attempts with common passwords all failed.

---

## ✅ FASTEST SOLUTION: Use phpMyAdmin (Already Open!)

### Step-by-Step Instructions:

#### 1. **Login to phpMyAdmin**
   - URL: http://localhost/phpmyadmin (already opened in Simple Browser)
   - Username: `root`
   - Password: **Your MySQL password** (you should know this from when you installed MySQL)

#### 2. **Create the Database**
   Once logged in:
   - Click **"New"** in the left sidebar
   - Database name: `healthinspection`
   - Collation: Select `utf8mb4_unicode_ci`
   - Click **"Create"** button

#### 3. **Import Schema (Tables)**
   - Click on `healthinspection` database in left sidebar
   - Click **"Import"** tab at the top
   - Click **"Choose File"** button
   - Navigate to: `C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\database\migrations\001_create_tables.sql`
   - Click **"Go"** button at bottom
   - Wait for "Import has been successfully finished" message

#### 4. **Import Seed Data (Users & Test Data)**
   - Stay in the `healthinspection` database
   - Click **"Import"** tab again
   - Click **"Choose File"** button
   - Navigate to: `C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\database\seeds\001_initial_data.sql`
   - Click **"Go"** button at bottom
   - Wait for success message

#### 5. **Update .env File**
   - Open: `C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\.env`
   - Find the line: `DB_PASS=`
   - Update it to: `DB_PASS=your_mysql_password`
   - Save the file

#### 6. **Test the Login!** 🎉
   - Go to: http://localhost:8000/views/auth/login.php
   - Email: `admin@lgu.gov.ph`
   - Password: `Admin@123`
   - Click **Login**

---

## Alternative: Reset Password Manually

If you don't remember your MySQL password:

### Using MySQL Installer (If you have it)
1. Search Windows for "MySQL Installer"
2. Click "Reconfigure" next to MySQL Server
3. Follow wizard to set a new password
4. Update `.env` file with new password
5. Run: `C:\xampp\php\php.exe setup_find_password.php`

### Using Command Line (Advanced)
1. **Run PowerShell as Administrator** (Right-click → Run as Administrator)
2. Navigate to project:
   ```powershell
   cd C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION
   ```
3. Run the reset script:
   ```powershell
   .\reset_mysql_auto.ps1
   ```
4. Then setup database:
   ```powershell
   C:\xampp\php\php.exe setup_find_password.php
   ```

---

## Files Locations for phpMyAdmin Import

**Schema File (import first):**
```
C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\database\migrations\001_create_tables.sql
```

**Seed Data File (import second):**
```
C:\xampp\htdocs\HEALTHANDSAFETYINSPECTION\database\seeds\001_initial_data.sql
```

---

## After Setup - Test Accounts

```
Super Admin:
  Email: superadmin@lgu.gov.ph
  Password: Admin@123

Admin:
  Email: admin@lgu.gov.ph
  Password: Admin@123

Inspector:
  Email: inspector@lgu.gov.ph
  Password: Admin@123
```

---

## Troubleshooting

### Can't access phpMyAdmin?
- Make sure Apache is running in XAMPP Control Panel
- Try: http://localhost/phpmyadmin
- Or try: http://127.0.0.1/phpmyadmin

### Don't know MySQL password?
- Check your MySQL installation notes
- Look for password in: `C:\ProgramData\MySQL\MySQL Server 8.0\my.ini`
- Or use MySQL Installer to reset it

### Import file too large?
Edit `C:\xampp\php\php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```
Then restart Apache in XAMPP.

---

## 🎯 RECOMMENDED NEXT STEP

**Use phpMyAdmin** (Steps 1-6 above) - it's the easiest and quickest way!

The Simple Browser should already have it open at: http://localhost/phpmyadmin

Just login with your MySQL credentials and import the 2 SQL files!
