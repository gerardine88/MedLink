# Registration System - Complete Connection Map

## 🔗 Connection Flow

```
User Browser
    ↓
[register.html] (Frontend Form)
    ↓
[registration.js] (Form Handler)
    ↓ (POST request with form data)
    ↓
[register.php] (Backend Handler)
    ↓
[database.php] (Connection)
    ↓
[helpers.php] (Validation & Hashing)
    ↓
MySQL Database
    ↓
[users] Table
```

---

## 📋 File Connections

### Frontend → Backend

**File:** `frontend/pages/public/register.html`
```html
<!-- Includes styling -->
<link rel="stylesheet" href="../../assets/css/registration.css">

<!-- Form with correct field names -->
<input name="first_name" ... />
<input name="last_name" ... />
<input name="email" ... />
<input name="password" ... />
<input name="confirm_password" ... />
<input name="phone" ... />
<input name="gender" ... />

<!-- Includes JavaScript handler -->
<script src="../../assets/js/registration.js"></script>
```

### JavaScript → Backend

**File:** `frontend/assets/js/registration.js`
```javascript
// Listens for form submission
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    registerForm.addEventListener('submit', handleRegistration);
});

// Sends POST to backend
async function handleRegistration(event) {
    const response = await fetch('../../backend/auth/register.php', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();
    // Handle response...
}
```

### Backend → Database

**File:** `backend/auth/register.php`
```php
// Includes database connection
require_once '../../config/database.php';
require_once '../../includes/helpers.php';

// Validates form data
$validation_errors = validate_registration_input(...);

// Checks if email exists
if (email_exists($conn, $email)) { ... }

// Hashes password
$password_hash = hash_password($password);

// Inserts into database
$stmt = $conn->prepare(
    "INSERT INTO users (first_name, last_name, email, password, phone, gender, ...) 
     VALUES (?, ?, ?, ?, ?, ?, ...)"
);
$stmt->bind_param("ssssssss", ...);
$stmt->execute();
```

### Database Connection

**File:** `backend/config/database.php`
```php
$conn = new mysqli(
    'localhost',  // DB_HOST
    'root',       // DB_USER
    '',           // DB_PASS
    'medlink',    // DB_NAME
    3306          // DB_PORT
);
```

---

## ✅ Complete Connection Checklist

| Component | File | Status |
|-----------|------|--------|
| **Frontend Form** | `frontend/pages/public/register.html` | ✅ Connected |
| **Form Fields** | name="first_name", "last_name", "email", etc. | ✅ Correct |
| **CSS Styling** | `frontend/assets/css/registration.css` | ✅ Linked |
| **JavaScript Handler** | `frontend/assets/js/registration.js` | ✅ Linked |
| **Backend Endpoint** | `backend/auth/register.php` | ✅ Connected |
| **Form Submission URL** | `../../backend/auth/register.php` | ✅ Correct |
| **Database Connection** | `backend/config/database.php` | ✅ Configured |
| **Helper Functions** | `backend/includes/helpers.php` | ✅ Included |
| **Database Table** | `medlink.users` | ✅ Created |

---

## 🧪 How to Test

### Method 1: Use Test Page
```
URL: http://localhost/MedLink/frontend/pages/public/test-registration.html
```
This page will:
- ✅ Verify all files exist
- ✅ Test database connection
- ✅ Test form submission with real data
- ✅ Show any errors with solutions

### Method 2: Manual Test
1. Go to: `http://localhost/MedLink/`
2. Click "Register" button
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
5. Should see success message

### Method 3: Verify in Database
```sql
mysql -u root
USE medlink;
SELECT * FROM users WHERE email = 'test@example.com';
```

---

## 📊 Data Flow Example

### Frontend Form Data
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "confirm_password": "SecurePass123",
  "phone": "+1-555-1234",
  "gender": "Male"
}
```

### Backend Processing
```php
1. Validate all fields
2. Check email uniqueness
3. Sanitize inputs
4. Hash password with bcrypt
5. Insert into database
6. Return JSON response
```

### Database Storage
```sql
INSERT INTO users (first_name, last_name, email, password, phone, gender, user_role, status, created_at)
VALUES ('John', 'Doe', 'john@example.com', '$2y$12$...hashed...', '+1-555-1234', 'Male', 'patient', 'active', NOW());
```

### JSON Response
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

---

## 🔍 If Connection Fails

### Error: "Connection error. Please try again."
**Fix:** Start MySQL in XAMPP Control Panel

### Error: "Email is already registered"
**Fix:** Use different email

### Error: "Password must contain..."
**Fix:** Password needs: 8+ chars, uppercase, lowercase, number

### Error: Database connection failed
**Fix:** Check credentials in `backend/config/database.php`

---

## 📁 Directory Structure

```
MedLink/
├── frontend/
│   ├── pages/public/
│   │   ├── register.html          ← Form page
│   │   └── test-registration.html ← Test page
│   └── assets/
│       ├── css/
│       │   └── registration.css   ← Form styling
│       └── js/
│           └── registration.js    ← Form handler
│
├── backend/
│   ├── auth/
│   │   └── register.php           ← Form processor
│   ├── config/
│   │   └── database.php           ← DB connection
│   └── includes/
│       └── helpers.php            ← Utilities
│
└── database/
    └── users.sql                  ← Schema
```

---

## ✨ Complete System Ready

The registration system has **full connection**:
- ✅ Frontend collects user data
- ✅ JavaScript validates and sends to backend
- ✅ Backend validates, hashes, and saves to database
- ✅ Database stores user securely
- ✅ Response sent back to frontend
- ✅ User sees success/error message

**To use:** Just go to `http://localhost/MedLink/` and click Register!
