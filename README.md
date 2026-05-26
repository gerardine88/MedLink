# MedLink Healthcare Management System

## Quick Start Guide

### Project Overview
MedLink is a comprehensive healthcare management system built with:
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache with mod_rewrite

### Directory Structure

```
frontend/           # Public-facing UI
├── pages/          # Role-based dashboards (patient, doctor, receptionist, admin)
├── components/     # Reusable HTML components
└── assets/         # CSS, JavaScript, Images

backend/            # Server-side logic
├── config/         # Database & application settings
├── auth/           # Login, registration, authentication
├── api/            # REST API endpoints
├── patient/        # Patient operations
├── doctor/         # Doctor operations
├── receptionist/   # Reception operations
├── admin/          # Admin operations
├── services/       # Service management
├── appointments/   # Appointment handling
├── queue/          # Queue management
├── billing/        # Billing & payments
├── records/        # Medical records
└── reports/        # Analytics & reports

database/           # SQL scripts
logs/              # Application logs
uploads/           # User-uploaded files
```

### Installation

1. **Database Setup**
   ```sql
   CREATE DATABASE medlink;
   USE medlink;
   SOURCE database/users.sql;
   ```

2. **Update Configuration**
   - Edit `backend/config/database.php` with your credentials
   - Update `backend/config/settings.php` if needed

3. **Set Permissions**
   ```
   chmod 755 logs/
   chmod 755 uploads/
   ```

4. **Access Application**
   - Navigate to: `http://localhost/MedLink`
   - Default credentials: Check `database/users.sql` for test accounts

### User Roles

- **Admin**: Full system access, user management, reports
- **Doctor**: Consultations, patient queue, prescriptions
- **Receptionist**: Patient check-in, appointments, walk-ins
- **Patient**: Appointments, medical history, profile

### File Structure

| Path | Purpose |
|------|---------|
| `backend/config/database.php` | MySQL connection settings |
| `backend/config/settings.php` | Application constants |
| `backend/includes/helpers.php` | Utility functions |
| `backend/auth/login.php` | User authentication |
| `backend/api/index.php` | API router |
| `database/users.sql` | Database schema |

### Security Features

✓ Password hashing with bcrypt  
✓ SQL injection prevention with prepared statements  
✓ Session timeout management  
✓ Input sanitization  
✓ CSRF protection  
✓ Role-based access control  

### API Endpoints

- `/backend/api/users` - User operations
- `/backend/api/appointments` - Appointment management
- `/backend/api/queue` - Queue operations
- `/backend/api/services` - Service management

### Development Tips

1. **Logging**: Events are logged to `logs/app-YYYY-MM-DD.log`
2. **Debug Mode**: Edit `backend/config/settings.php` - set `DEBUG_MODE` to `true`
3. **Error Handling**: All errors return JSON response with status and message
4. **Database**: Use prepared statements to prevent SQL injection

### Testing

- Test with sample data from `database/users.sql`
- Admin: admin@medlink.com
- Patient: john@example.com, jane@example.com

### Troubleshooting

**Database Connection Error**
- Check MySQL is running
- Verify credentials in `backend/config/database.php`
- Ensure database `medlink` exists

**Page Not Found**
- Check `.htaccess` is configured correctly
- Enable `mod_rewrite` in Apache

**Session Issues**
- Check PHP session directory is writable
- Verify `session.save_path` in php.ini

### Support & Documentation

For detailed documentation, see `PROJECT_STRUCTURE.md`

### License

© 2026 MedLink Inc. All rights reserved.
