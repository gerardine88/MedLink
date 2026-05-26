# 🚀 Registration System - Quick Start Guide

## Installation (5 minutes)

### Step 1: Database Setup
```bash
# Navigate to your MySQL directory and import the users table
mysql -u root -p
> CREATE DATABASE medlink;
> USE medlink;
> source C:\xampp\htdocs\MedLink\database\users.sql;
> EXIT;
```

Or use phpMyAdmin:
1. Go to `http://localhost/phpmyadmin`
2. Create database `medlink`
3. Import `database/users.sql`

### Step 2: Verify Configuration
Check `backend/config/database.php`:
```php
define('DB_HOST', 'localhost');  // ✓ Should be localhost
define('DB_USER', 'root');       // ✓ Should match your MySQL user
define('DB_PASS', '');           // ✓ Update if you have password
define('DB_NAME', 'medlink');    // ✓ Must be 'medlink'
```

### Step 3: Check Permissions
```bash
# Make directories writable (Windows)
cd C:\xampp\htdocs\MedLink
mkdir logs
mkdir uploads
```

---

## Testing (2 minutes)

### Option A: Use Test Interface
1. Open browser: `http://localhost/MedLink/backend/auth/TEST_REGISTRATION.html`
2. Fill out registration form
3. Click "Test Registration"
4. See JSON response

### Option B: Use cURL
```bash
curl -X POST http://localhost/MedLink/backend/auth/register.php ^
  -d "first_name=John" ^
  -d "last_name=Doe" ^
  -d "email=john@example.com" ^
  -d "password=SecurePass123" ^
  -d "confirm_password=SecurePass123"
```

### Option C: Use Frontend
1. Navigate to: `http://localhost/MedLink/frontend/pages/public/register.html`
2. Fill out form (ensure scripts are included)
3. Submit form
4. Check response

---

## Integration Steps

### 1. Update register.html
Ensure your register.html has these scripts:
```html
<!-- Inside <head> -->
<link rel="stylesheet" href="../../assets/css/registration.css">

<!-- Before closing </body> -->
<script src="../../assets/js/registration.js"></script>
```

### 2. Verify Form Structure
Ensure form has these elements:
```html
<form id="registerForm" method="post" novalidate>
    <!-- Form fields with correct names -->
</form>
```

### 3. Form Field Names
Match these exact names:
```html
<input name="first_name" />
<input name="last_name" />
<input name="email" />
<input name="password" />
<input name="confirm_password" />
<input name="phone" /> <!-- optional -->
<select name="gender" /> <!-- optional -->
```

---

## File Reference

| File | Purpose | Location |
|------|---------|----------|
| register.php | Main registration handler | backend/auth/ |
| check-email.php | Email availability check | backend/auth/ |
| validate-password.php | Password strength check | backend/auth/ |
| registration-utils.php | Helper functions | backend/auth/ |
| registration.js | Frontend form handler | frontend/assets/js/ |
| registration.css | Form styling | frontend/assets/css/ |

---

## Common Issues & Solutions

### "Connection refused" or "Database error"
**Problem:** Database connection failed  
**Solution:** 
- Verify MySQL is running: `http://localhost/phpmyadmin`
- Check credentials in `backend/config/database.php`
- Verify database `medlink` exists

### "Email already registered" on first use
**Problem:** Test data from users.sql  
**Solution:** 
- Use different email addresses
- Or delete test users from database
- See `database/users.sql` for test emails

### JavaScript not working / Form not submitting
**Problem:** Scripts not loading  
**Solution:**
- Check browser console (F12) for errors
- Verify `registration.js` is included in HTML
- Check file paths are correct

### Password validation too strict
**Problem:** Common passwords rejected  
**Solution:**
- This is intentional for security
- Use password like: `MySecurePass123`

### Logs not working
**Problem:** Events not logged  
**Solution:**
- Create `logs/` directory in root
- Ensure directory is writable

---

## Security Features ✓

- ✓ Bcrypt password hashing
- ✓ SQL injection prevention
- ✓ Input validation & sanitization
- ✓ Email uniqueness enforcement
- ✓ Password strength requirements
- ✓ Event logging
- ✓ XSS prevention ready

---

## API Endpoints

All endpoints are POST and return JSON

| Endpoint | Purpose |
|----------|---------|
| backend/auth/register.php | Register new user |
| backend/auth/check-email.php | Check email availability |
| backend/auth/validate-password.php | Validate password strength |

---

## Testing Checklist

- [ ] Database is created and accessible
- [ ] Users table exists
- [ ] Registration form displays correctly
- [ ] Can submit registration with valid data
- [ ] Get success message with user_id
- [ ] Invalid data shows appropriate errors
- [ ] Email check works in real-time
- [ ] Password strength indicator displays
- [ ] Can't register duplicate email
- [ ] Logs are created in logs/ directory

---

## Next Steps

1. **Email Verification** - Implement email confirmation
2. **Password Reset** - Add forgot password flow
3. **Profile Completion** - Additional user information
4. **Two-Factor Auth** - Enhanced security
5. **OAuth Integration** - Social login (optional)

---

## Documentation Files

| File | Contains |
|------|----------|
| REGISTRATION_DOCS.md | Complete API reference |
| IMPLEMENTATION_SUMMARY.md | What was built |
| TEST_REGISTRATION.html | Testing interface |
| This file | Quick start guide |

---

## Getting Help

1. **Check Documentation:** REGISTRATION_DOCS.md
2. **Use Test Interface:** TEST_REGISTRATION.html
3. **Review Examples:** REGISTRATION_DOCS.md has cURL examples
4. **Check Logs:** logs/app-YYYY-MM-DD.log
5. **Browser Console:** F12 to see JavaScript errors

---

## Success Indicators

✓ Form displays properly  
✓ Can register with valid data  
✓ Gets success message  
✓ User appears in database  
✓ Can't register duplicate email  
✓ Real-time validation works  
✓ Logs are being created  

---

**System Status: Ready for Production**

Created: May 21, 2026  
Version: 1.0  
Pure PHP - No frameworks required
