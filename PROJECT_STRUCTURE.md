/**
 * Project Structure Documentation
 * MedLink Healthcare Management System
 */

PROJECT_ROOT: c:\xampp\htdocs\MedLink

=== FRONTEND STRUCTURE ===
frontend/
├── index.html                    # Main landing page
├── assets/
│   ├── css/
│   │   └── style.css            # Main stylesheet
│   ├── js/
│   │   └── main.js              # Main JavaScript
│   └── images/                  # Image assets
│
├── components/
│   ├── header.html              # Reusable header component
│   ├── sidebar.html             # Reusable sidebar component
│   └── footer.html              # Reusable footer component
│
└── pages/
    ├── public/
    │   ├── index.html           # Public home page
    │   ├── login.html           # Login page
    │   ├── register.html        # Registration page
    │   ├── services.html        # Services listing
    │   └── about.html           # About page
    │
    ├── patient/
    │   ├── dashboard.html       # Patient dashboard
    │   ├── appointments.html    # View appointments
    │   ├── medical-history.html # Medical records
    │   ├── prescriptions.html   # View prescriptions
    │   └── profile.html         # Patient profile
    │
    ├── receptionist/
    │   ├── dashboard.html       # Receptionist dashboard
    │   ├── check-in.html        # Patient check-in
    │   ├── queue-management.html# Manage queue
    │   ├── appointments.html    # Manage appointments
    │   └── walk-ins.html        # Walk-in registration
    │
    ├── doctor/
    │   ├── dashboard.html       # Doctor dashboard
    │   ├── queue-status.html    # View patient queue
    │   ├── consultations.html   # Consultations
    │   ├── prescriptions.html   # Issue prescriptions
    │   └── patient-notes.html   # Patient clinical notes
    │
    └── admin/
        ├── dashboard.html       # Admin dashboard
        ├── users-management.html# User management
        ├── doctors.html         # Doctor management
        ├── departments.html     # Department management
        ├── reports.html         # System reports
        ├── billing.html         # Billing management
        └── settings.html        # System settings

=== BACKEND STRUCTURE ===
backend/
├── config/
│   ├── database.php             # Database connection
│   └── settings.php             # Application settings
│
├── includes/
│   ├── auth-check.php           # Authentication verification
│   └── helpers.php              # Utility functions
│
├── auth/
│   ├── register.php             # User registration handler
│   ├── login.php                # User login handler
│   ├── logout.php               # User logout handler
│   └── password-reset.php       # Password reset handler
│
├── patient/
│   ├── profile.php              # Patient profile operations
│   ├── medical-history.php      # Medical records management
│   └── appointments.php         # Patient appointment management
│
├── receptionist/
│   ├── check-in.php             # Patient check-in operations
│   ├── walk-in-registration.php # Walk-in patient registration
│   └── queue-management.php     # Queue operations
│
├── doctor/
│   ├── consultations.php        # Consultation management
│   ├── prescriptions.php        # Prescription management
│   └── patient-notes.php        # Clinical notes
│
├── admin/
│   ├── users.php                # User management
│   ├── doctors.php              # Doctor management
│   ├── departments.php          # Department operations
│   └── system-settings.php      # System configuration
│
├── services/
│   ├── create.php               # Create service
│   ├── read.php                 # Get services
│   ├── update.php               # Update service
│   └── delete.php               # Delete service
│
├── appointments/
│   ├── book.php                 # Book appointment
│   ├── list.php                 # List appointments
│   ├── update.php               # Update appointment
│   ├── cancel.php               # Cancel appointment
│   └── reschedule.php           # Reschedule appointment
│
├── queue/
│   ├── add-patient.php          # Add patient to queue
│   ├── get-queue.php            # Get current queue
│   ├── remove-patient.php       # Remove from queue
│   └── update-status.php        # Update patient status
│
├── billing/
│   ├── create-invoice.php       # Create invoice
│   ├── process-payment.php      # Process payment
│   ├── payment-history.php      # Payment records
│   └── invoice-list.php         # List invoices
│
├── records/
│   ├── medical-history.php      # Medical history
│   ├── upload-document.php      # Upload medical documents
│   ├── get-documents.php        # Retrieve documents
│   └── delete-document.php      # Remove documents
│
├── reports/
│   ├── appointment-analytics.php# Appointment reports
│   ├── patient-analytics.php    # Patient statistics
│   ├── billing-reports.php      # Billing analytics
│   └── system-reports.php       # System statistics
│
└── api/
    ├── index.php                # API router
    └── endpoints/
        ├── users.php            # User API endpoints
        ├── appointments.php     # Appointment API endpoints
        ├── queue.php            # Queue API endpoints
        └── services.php         # Service API endpoints

=== DATABASE STRUCTURE ===
database/
└── users.sql                    # User table and procedures

=== LOGS & UPLOADS ===
logs/                           # Application logs
uploads/                        # User uploaded files

=== KEY FEATURES ===

✓ Separation of Concerns: Frontend/Backend clearly separated
✓ Role-Based Access: Different dashboards for each user role
✓ Modular Architecture: Each module handles specific functionality
✓ Database Layer: Centralized database configuration
✓ Security: Authentication checks and input sanitization
✓ API-Driven: AJAX endpoints for dynamic functionality
✓ Reusable Components: Header, sidebar, footer components
✓ Scalable Design: Easy to add new features and modules

=== BEST PRACTICES IMPLEMENTED ===

1. Configuration Management
   - Database config in one place
   - Settings centralized for easy updates
   
2. Security
   - Input sanitization functions
   - Password hashing with bcrypt
   - Session timeout management
   - SQL injection prevention with prepared statements
   
3. Error Handling
   - Centralized error logging
   - Consistent error responses
   
4. Code Organization
   - Logical grouping by functionality
   - Reusable helper functions
   - Clear separation of concerns
   
5. Scalability
   - Module-based architecture
   - Easy to add new endpoints
   - Database-driven role management

=== NEXT STEPS ===

1. Set up database tables using database/users.sql
2. Create remaining pages in frontend/pages/
3. Implement API endpoints in backend/api/endpoints/
4. Add CSS styling to frontend/assets/css/
5. Implement JavaScript functionality in frontend/assets/js/
6. Configure web server routing

