# MedLink User Registration System Documentation

## Overview
Complete user registration backend built with pure PHP, including validation, security, and AJAX endpoints for real-time feedback.

## Files & Structure

### Backend Files
```
backend/auth/
├── register.php              # Main registration handler
├── check-email.php           # Email availability checker
├── validate-password.php     # Password strength validator
└── registration-utils.php    # Utility functions
```

### Frontend Files
```
frontend/assets/
├── js/registration.js        # Form handler and validation
├── css/registration.css      # Form styling
└── pages/public/register.html # Registration page
```

## API Endpoints

### 1. Main Registration Endpoint
**URL:** `backend/auth/register.php`  
**Method:** `POST`  
**Content-Type:** `application/x-www-form-urlencoded`

#### Request Parameters
```
first_name       (string, required)   - User first name (2-50 chars)
last_name        (string, required)   - User last name (2-50 chars)
email            (string, required)   - Valid email address
password         (string, required)   - Min 8 chars, with uppercase, lowercase, number
confirm_password (string, required)   - Must match password
phone            (string, optional)   - Phone number (7-15 digits/symbols)
gender           (string, optional)   - 'Male', 'Female', or 'Other'
```

#### Success Response (200)
```json
{
    "success": true,
    "message": "Registration successful! Please check your email to verify your account.",
    "errors": [],
    "data": {
        "user_id": 1,
        "email": "user@example.com",
        "name": "John Doe"
    }
}
```

#### Error Response (400)
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

### 2. Email Availability Checker
**URL:** `backend/auth/check-email.php`  
**Method:** `POST`  
**Parameters:** `email`

#### Response
```json
{
    "success": true,
    "message": "Email is available",
    "data": {
        "available": true
    }
}
```

### 3. Password Strength Validator
**URL:** `backend/auth/validate-password.php`  
**Method:** `POST`  
**Parameters:** `password`

#### Response
```json
{
    "success": true,
    "strength": "good",
    "score": 4,
    "requirements": {
        "length": {
            "required": 8,
            "met": true
        },
        "uppercase": {
            "pattern": "At least one uppercase letter",
            "met": true
        },
        "lowercase": {
            "pattern": "At least one lowercase letter",
            "met": true
        },
        "number": {
            "pattern": "At least one number",
            "met": true
        },
        "special": {
            "pattern": "At least one special character (!@#$%^&*)",
            "met": false
        }
    }
}
```

## Validation Rules

### First Name / Last Name
- ✓ Required
- ✓ 2-50 characters
- ✓ Only letters, spaces, hyphens, apostrophes
- ✗ Numbers or special characters not allowed

### Email
- ✓ Required
- ✓ Valid email format (RFC 5322)
- ✓ Maximum 100 characters
- ✗ Duplicate emails rejected

### Password
- ✓ Required
- ✓ Minimum 8 characters
- ✓ Maximum 255 characters
- ✓ Must contain uppercase letter (A-Z)
- ✓ Must contain lowercase letter (a-z)
- ✓ Must contain number (0-9)
- ✓ Must NOT be in common weak passwords list

### Phone (Optional)
- ✓ 7-15 characters
- ✓ Can include: +, -, spaces, parentheses, numbers

### Gender (Optional)
- ✓ Valid values: 'Male', 'Female', 'Other'

## Usage Examples

### HTML Form
```html
<form id="registerForm" action="../../backend/auth/register.php" method="post">
    <div class="form-row">
        <label for="fullName">Full name</label>
        <input id="fullName" name="first_name" type="text" required>
    </div>
    
    <div class="form-row">
        <label for="lastName">Last name</label>
        <input id="lastName" name="last_name" type="text" required>
    </div>
    
    <div class="form-row">
        <label for="registerIdentifier">Email</label>
        <input id="registerIdentifier" name="email" type="email" required>
    </div>
    
    <div class="form-row">
        <label for="registerPassword">Password</label>
        <input id="registerPassword" name="password" type="password" required>
    </div>
    
    <div class="form-row">
        <label for="registerConfirm">Confirm password</label>
        <input id="registerConfirm" name="confirm_password" type="password" required>
    </div>
    
    <button type="submit">Register</button>
</form>
```

### JavaScript Integration
```javascript
// Include the registration script
<script src="../../assets/js/registration.js"></script>

// Form validation will be automatic
// Real-time email checking and password strength indicator included
```

### cURL Example
```bash
curl -X POST http://localhost/MedLink/backend/auth/register.php \
  -d "first_name=John" \
  -d "last_name=Doe" \
  -d "email=john@example.com" \
  -d "password=SecurePass123" \
  -d "confirm_password=SecurePass123" \
  -d "phone=+1234567890" \
  -d "gender=Male"
```

### PHP Example
```php
$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'password' => 'SecurePass123',
    'confirm_password' => 'SecurePass123',
    'phone' => '+1234567890',
    'gender' => 'Male'
];

$ch = curl_init('http://localhost/MedLink/backend/auth/register.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if ($result['success']) {
    echo "Registration successful!";
} else {
    echo "Registration failed: " . $result['message'];
}
```

## Security Features

### Password Security
- ✓ Bcrypt hashing with cost factor 12
- ✓ Password strength validation
- ✓ Common weak password detection
- ✓ Passwords never logged or stored in plain text

### Input Validation
- ✓ Required field validation
- ✓ Type checking and format validation
- ✓ Length restrictions enforced
- ✓ Pattern matching for special characters

### Database Security
- ✓ Prepared statements prevent SQL injection
- ✓ Parameter binding with proper type hints
- ✓ Email uniqueness constraint at database level

### Data Protection
- ✓ Input sanitization
- ✓ XSS prevention through output encoding
- ✓ CSRF protection ready
- ✓ Rate limiting compatible

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| Email already registered | User exists | Suggest password reset link |
| Password mismatch | Confirm password differs | Verify both password fields |
| Invalid email format | Wrong email syntax | Show email format example |
| Password too weak | Missing requirements | Show strength indicator |
| Database connection error | Database down | Check database status |

## Database Schema

The system uses the existing `users` table:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    gender ENUM('Male', 'Female', 'Other'),
    user_role ENUM('patient', 'doctor', 'receptionist', 'admin') DEFAULT 'patient',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email)
);
```

## Logging

All registration activities are logged to:  
`logs/app-YYYY-MM-DD.log`

### Log Entries
```
[2026-05-21 10:30:45] [INFO] [User: GUEST] User registered successfully {"user_id":1,"email":"john@example.com","name":"John Doe"}
[2026-05-21 10:31:20] [ERROR] [User: GUEST] User registration failed {"email":"jane@example.com","error":"Duplicate entry"}
```

## Testing

### Test Cases

1. **Valid Registration**
   - All required fields filled correctly
   - Password meets all requirements
   - Email not registered
   - Expected: Success, user created

2. **Missing Required Fields**
   - One or more fields empty
   - Expected: Error message for each field

3. **Invalid Email**
   - Invalid format or duplicate
   - Expected: Email-specific error message

4. **Weak Password**
   - Missing uppercase/lowercase/number
   - Expected: Specific password requirement message

5. **Password Mismatch**
   - Confirm password differs
   - Expected: Mismatch error

6. **SQL Injection Attempt**
   - Email: `test@example.com' OR '1'='1`
   - Expected: Safely handled, no SQL injection

## Configuration

Settings in `backend/config/settings.php`:

```php
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_TIMEOUT', 15 * 60);
define('EMAIL_FROM', 'noreply@medlink.com');
define('DEBUG_MODE', true);
```

## Troubleshooting

### Issue: Database Connection Failed
**Solution:** Check `backend/config/database.php` credentials

### Issue: Form Not Submitting
**Solution:** Verify form has correct `id` and `name` attributes match POST parameters

### Issue: Email Check Not Working
**Solution:** Ensure `check-email.php` is accessible and database has email unique constraint

### Issue: JavaScript Not Loading
**Solution:** Verify path: `../../assets/js/registration.js`

## Next Steps

1. Set up email verification system
2. Implement password reset functionality
3. Add user profile completion flow
4. Set up OAuth integration (optional)
5. Implement rate limiting on registration

## Support

For issues or questions, refer to the main README.md or check application logs.
