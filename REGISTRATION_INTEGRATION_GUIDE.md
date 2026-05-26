# 🔗 Registration & Database Integration Guide

## ✅ What's Been Connected

### Frontend (HTML)
- ✓ Updated `frontend/pages/public/register.html` with proper form fields
- ✓ Correct form field names matching database: `first_name`, `last_name`, `email`, `password`, `confirm_password`, `phone`, `gender`
- ✓ Included `registration.css` for styling
- ✓ Included `registration.js` for form handling
- ✓ Updated all navigation links to correct paths
- ✓ Index.html moved to root with updated links

### Backend (PHP + Database)
- ✓ `backend/auth/register.php` - Main registration handler
- ✓ Database connection configured in `backend/config/database.php`
- ✓ Input validation in place
- ✓ Password hashing (bcrypt)
- ✓ Email uniqueness check
- ✓ Users table in database

### Assets
- ✓ CSS links updated to `../../assets/css/`
- ✓ JavaScript links updated to `../../assets/js/`
- ✓ All paths relative and correct

---

## 🚀 Quick Setup Steps

### Step 1: Verify Database
```bash
# Check MySQL is running
mysql -u root -p

# Import users table if not done
mysql -u root medlink < C:\xampp\htdocs\MedLink\database\users.sql

# Verify table exists
USE medlink;
DESCRIBE users;
```

### Step 2: Verify Configuration
Check `backend/config/database.php`:
```php
define('DB_HOST', 'localhost');  // ✓
define('DB_USER', 'root');       // ✓
define('DB_PASS', '');           // ✓ (or your password)
define('DB_NAME', 'medlink');    // ✓
```

### Step 3: Test Registration
Navigate to: `http://localhost/MedLink/frontend/pages/public/register.html`

Or access via root: `http://localhost/MedLink/index.html` → Click "Register"

---

## 📝 How the Integration Works

### 1. **Form Submission Flow**
```
HTML Form (register.html)
    ↓
registration.js (captures form data)
    ↓
Sends POST to backend/auth/register.php
    ↓
PHP validates and sanitizes
    ↓
Checks email uniqueness
    ↓
Hashes password with bcrypt
    ↓
Inserts into database
    ↓
Returns JSON response
    ↓
JavaScript displays result to user
```

### 2. **Form Field Mapping**
| HTML Input | Form Name | Database Field | Type |
|------------|-----------|----------------|------|
| First Name input | `first_name` | first_name | VARCHAR(50) |
| Last Name input | `last_name` | last_name | VARCHAR(50) |
| Email input | `email` | email | VARCHAR(100) |
| Password input | `password` | password | VARCHAR(255) |
| Confirm Password | `confirm_password` | (validated only) | - |
| Phone input | `phone` | phone | VARCHAR(15) |
| Gender select | `gender` | gender | ENUM |

### 3. **Database Fields**
```
users table columns:
- id (auto-increment primary key)
- first_name (required)
- last_name (required)
- email (required, unique)
- password (required, hashed)
- phone (optional)
- gender (optional)
- user_role (default: 'patient')
- status (default: 'active')
- created_at (timestamp)
- updated_at (timestamp)
- last_login (timestamp)
```

---

## 🧪 Testing Registration

### Test Case 1: Valid Registration
**URL:** `http://localhost/MedLink/frontend/pages/public/register.html`

**Fill Form:**
```
First Name: John
Last Name: Doe
Email: john@example.com
Password: SecurePass123
Confirm Password: SecurePass123
Phone: +1234567890
Gender: Male
```

**Expected Result:**
- ✓ Success message appears
- ✓ User ID returned
- ✓ Redirects to login after 2 seconds
- ✓ User appears in database

**Verify in Database:**
```sql
SELECT * FROM users WHERE email = 'john@example.com';
```

### Test Case 2: Duplicate Email
**Same email as Test Case 1**

**Expected Result:**
- ✗ Error: "Email is already registered"

### Test Case 3: Weak Password
**Password:** `password123`

**Expected Result:**
- ✗ Error: "Password must contain at least one uppercase letter"

### Test Case 4: Password Mismatch
**Password:** `SecurePass123`
**Confirm:** `SecurePass456`

**Expected Result:**
- ✗ Error: "Passwords do not match"

### Test Case 5: Invalid Email
**Email:** `notanemail`

**Expected Result:**
- ✗ Error: "Email format is invalid"

---

## 🔍 Debugging

### Check Logs
```bash
# View registration errors
tail -f C:\xampp\htdocs\MedLink\logs\app-2026-05-21.log
```

### Browser Console
Press F12 in browser and check:
- Network tab: See POST request to register.php
- Console tab: Any JavaScript errors
- Response: JSON from backend

### PHP Errors
Check `C:\xampp\htdocs\MedLink\logs\app-*.log`

### Common Issues

**Issue:** "Connection refused"
- **Solution:** Check MySQL is running

**Issue:** "Table doesn't exist"
- **Solution:** Import users.sql: `mysql -u root medlink < database/users.sql`

**Issue:** Form not submitting
- **Solution:** Check browser console (F12) for errors

**Issue:** Scripts not loading
- **Solution:** Check browser Network tab, verify CSS/JS paths

---

## 📁 File Structure Reference

```
MedLink/
├── index.html                                    (Root - landing page)
├── frontend/
│   ├── pages/
│   │   └── public/
│   │       ├── register.html                    ✓ Updated with DB integration
│   │       ├── login.html                       ✓ Updated paths
│   │       └── [other pages]                    ✓ CSS paths updated
│   └── assets/
│       ├── css/
│       │   ├── style.css                        (Main styles)
│       │   └── registration.css                 (Form styles)
│       └── js/
│           ├── registration.js                  ✓ Form handler
│           └── main.js                          (Global scripts)
│
├── backend/
│   ├── auth/
│   │   ├── register.php                         ✓ Main handler
│   │   ├── check-email.php                      ✓ AJAX endpoint
│   │   └── validate-password.php                ✓ AJAX endpoint
│   └── config/
│       ├── database.php                         ✓ DB connection
│       └── settings.php                         (Constants)
│
└── database/
    └── users.sql                                ✓ Schema + sample data
```

---

## 🎯 Real-Time Features

### Email Availability Check
- Automatically checks email as user leaves field
- Shows "Email already registered" error if taken
- Green checkmark if available

### Password Strength Indicator
- Real-time password validation
- Shows requirements (uppercase, lowercase, number)
- Color-coded strength meter (weak/fair/good/strong)

### Form Validation
- Field-specific error messages
- Errors disappear when user corrects field
- Submit button disabled if validation fails

---

## 📊 API Response Examples

### Successful Registration (200)
```json
{
    "success": true,
    "message": "Registration successful! Please check your email to verify your account.",
    "errors": [],
    "data": {
        "user_id": 42,
        "email": "john@example.com",
        "name": "John Doe"
    }
}
```

### Validation Error (400)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": "Email is already registered",
        "password": "Password must contain at least one uppercase letter"
    },
    "data": []
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Database error: [error details]",
    "errors": [],
    "data": []
}
```

---

## 🔐 Security Features Active

✓ **Password Security**
- Bcrypt hashing (cost 12)
- Complexity requirements enforced
- Min 8 characters with uppercase/lowercase/number

✓ **Data Protection**
- SQL injection prevention (prepared statements)
- Input sanitization
- XSS prevention ready

✓ **Database Security**
- Email unique constraint
- Proper indexes on email field
- Transaction support ready

✓ **Session Security**
- Session timeout configured
- Secure cookies ready
- CSRF protection ready

---

## 📞 Next Steps

1. **Test Registration** via `http://localhost/MedLink/index.html`
2. **Check Database** to verify user created
3. **Review Logs** in `logs/app-*.log`
4. **Implement Login** using similar pattern
5. **Add Email Verification** (optional)
6. **Set Up Password Reset** (optional)

---

## ✨ Features Included

✓ Real-time email availability checking  
✓ Live password strength indicator  
✓ Field-specific error messages  
✓ Password visibility toggle  
✓ Comprehensive input validation  
✓ Event logging  
✓ Responsive design  
✓ Professional error handling  

---

## 📚 Documentation Files

| File | Location | Contains |
|------|----------|----------|
| QUICK_START.md | backend/auth/ | 5-min setup guide |
| REGISTRATION_DOCS.md | backend/auth/ | Complete API docs |
| IMPLEMENTATION_SUMMARY.md | backend/auth/ | System overview |
| TEST_REGISTRATION.html | backend/auth/ | Interactive test UI |

---

## ✅ Integration Checklist

- [x] Database created and populated
- [x] PHP backend configured
- [x] HTML form fields match database
- [x] CSS and JS files linked correctly
- [x] Navigation links updated
- [x] Index.html moved to root
- [x] Form sends data to backend
- [x] Backend validates and sanitizes
- [x] Passwords hashed
- [x] Email uniqueness enforced
- [x] Responses return JSON
- [x] Error handling implemented
- [x] Logging configured
- [x] Ready for testing

---

**Status:** ✅ **FULLY INTEGRATED & READY TO USE**

Frontend and backend are now completely connected to the database!

Test it now: `http://localhost/MedLink/`
