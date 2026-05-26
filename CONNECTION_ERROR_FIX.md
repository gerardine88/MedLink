# Registration Connection Error - Troubleshooting Guide

## 🔴 Common Issue: "Connection error. Please try again."

This error happens when the registration form cannot communicate with the backend PHP files.

---

## ✅ Step 1: Start MySQL Database

### Option A: Using XAMPP Control Panel
1. Open **XAMPP Control Panel** (C:\xampp\xampp-control.exe)
2. Look for "MySQL" row
3. Click "Start" button next to MySQL
4. Wait until it shows "Running" in green
5. Also ensure "Apache" is running

### Option B: Start from Command Line
```cmd
cd C:\xampp\mysql\bin
mysqld --console
```

### Option C: Start as Windows Service
```cmd
net start MySQL80
```
(Replace MySQL80 with your MySQL version if different)

---

## ✅ Step 2: Verify Database Exists

Once MySQL is running, open a command prompt:

```cmd
cd C:\xampp\mysql\bin
mysql -u root

# Then run:
SHOW DATABASES;
USE medlink;
SHOW TABLES;
DESC users;
EXIT;
```

**Expected Output:**
- Should see `medlink` database listed
- Should see `users` table in medlink database
- Should see columns: id, first_name, last_name, email, password, etc.

### If Database Doesn't Exist

Create it:
```cmd
mysql -u root < C:\xampp\htdocs\MedLink\database\users.sql
```

---

## ✅ Step 3: Test Backend Connection

1. Open browser
2. Go to: `http://localhost/MedLink/backend/test-connection.php`
3. You should see a JSON response showing:
   - ✅ `"database_ok": true`
   - ✅ `"files_ok": true`
   - ✅ `"config_ok": true`
   - ✅ `"functions_ok": true`

**If you see `false` values:**
- See troubleshooting section below

---

## 🔍 Troubleshooting

### Problem: "database_ok": false

**Solution 1: Restart MySQL**
```cmd
net stop MySQL80
net start MySQL80
```

**Solution 2: Check MySQL credentials**
Edit: `backend/config/database.php`
```php
define('DB_HOST', 'localhost');  // Change localhost if needed
define('DB_USER', 'root');        // Change if your username is different
define('DB_PASS', '');            // Add password if MySQL has one
define('DB_NAME', 'medlink');     // Database name
```

**Solution 3: Check MySQL Port**
Default port is 3306. If different:
```php
define('DB_PORT', 3307);  // Change to your port
```

### Problem: "files_ok": false

**Solution: Missing backend files**
Check that these exist:
- `backend/config/database.php` ✓
- `backend/config/settings.php` ✓
- `backend/auth/register.php` ✓
- `backend/includes/helpers.php` ✓
- `backend/includes/auth-check.php` ✓

### Problem: Still getting "Connection error"

1. Open **Browser Developer Tools** (Press F12)
2. Go to **Network** tab
3. Click "Register" button in the form
4. Look at the POST request to `register.php`
5. Click on it and check the **Response** tab
6. Copy the response and check what error message appears

---

## 🚀 Quick Registration Test

Once MySQL is running:

1. Go to: `http://localhost/MedLink/`
2. Click "Register"
3. Fill form:
   ```
   First Name: Test
   Last Name: User
   Email: test@example.com
   Password: TestPass123
   Confirm: TestPass123
   Phone: +1-555-1234 (optional)
   Gender: Male (optional)
   ```
4. Click "Create Free Account"

**Expected Result:**
- ✅ Success message
- ✅ Redirects to login page
- ✅ User appears in database

**Verify in Database:**
```cmd
mysql -u root
USE medlink;
SELECT * FROM users WHERE email='test@example.com';
EXIT;
```

---

## 📋 Checklist Before Testing

- [ ] MySQL is running (check XAMPP Control Panel)
- [ ] Apache is running
- [ ] Database `medlink` exists
- [ ] Table `users` exists in medlink database
- [ ] Backend connection test passes: `http://localhost/MedLink/backend/test-connection.php`
- [ ] Firewall allows localhost connections
- [ ] No other application using port 3306

---

## 🔧 Reset Everything

If nothing works, start fresh:

```cmd
# 1. Stop MySQL
net stop MySQL80

# 2. Start XAMPP Control Panel and click Start for MySQL and Apache

# 3. Create database fresh
cd C:\xampp\mysql\bin
mysql -u root
DROP DATABASE IF EXISTS medlink;
CREATE DATABASE medlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# 4. Import schema
mysql -u root medlink < C:\xampp\htdocs\MedLink\database\users.sql

# 5. Test
http://localhost/MedLink/backend/test-connection.php
```

---

## 📞 Need Help?

Check these logs:
- Browser Console: Press F12 in browser, check Console tab
- Network Tab: Press F12, see actual response from server
- XAMPP Logs: `C:\xampp\logs\mysql_error.log`
- PHP Logs: `C:\xampp\logs\error.log`

---

**Most Common Fix:** 
⚠️ **Start MySQL in XAMPP Control Panel** ⚠️

If MySQL is not running, **nothing will work**. Always ensure:
1. ✅ MySQL is Running (green status in XAMPP)
2. ✅ Apache is Running (green status in XAMPP)
3. ✅ Try registration again

