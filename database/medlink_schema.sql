-- ============================================================
-- MedLink Hospital Management System — Full Database Schema
-- Database : medlink
-- Engine   : InnoDB  |  Charset: utf8mb4_unicode_ci
-- Version  : 2.0
-- ============================================================
-- Seed passwords are all: Admin123@
-- Hash generated with: password_hash('Admin123@', PASSWORD_BCRYPT, ['cost'=>12])
-- ============================================================

CREATE DATABASE IF NOT EXISTS medlink
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE medlink;

-- ============================================================
-- DROP TABLES (reverse dependency order)
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS consultations;
DROP TABLE IF EXISTS queues;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE roles (
    id          TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    role_name   VARCHAR(30)         NOT NULL,
    display_name VARCHAR(50)        NOT NULL,
    description VARCHAR(200)        NULL,
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. DEPARTMENTS
-- ============================================================
CREATE TABLE departments (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    department_name     VARCHAR(100)        NOT NULL,
    description         TEXT                NULL,
    head_doctor_id      INT UNSIGNED        NULL COMMENT 'References doctors.id — set after doctors are created',
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_department_name (department_name),
    KEY idx_dept_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. USERS
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    role_id         TINYINT UNSIGNED    NOT NULL,
    first_name      VARCHAR(50)         NOT NULL,
    last_name       VARCHAR(50)         NOT NULL,
    email           VARCHAR(100)        NOT NULL,
    password_hash   VARCHAR(255)        NOT NULL,
    phone           VARCHAR(20)         NULL,
    gender          ENUM('Male','Female','Other') NULL,
    status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login      TIMESTAMP           NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email),
    KEY idx_role_id   (role_id),
    KEY idx_status    (status),
    KEY idx_created_at (created_at),

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. DOCTORS
-- ============================================================
CREATE TABLE doctors (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED        NOT NULL,
    department_id       INT UNSIGNED        NULL,
    specialization      VARCHAR(100)        NULL,
    license_number      VARCHAR(50)         NULL,
    consultation_fee    DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
    bio                 TEXT                NULL,
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_doctor_user (user_id),
    UNIQUE KEY uq_license     (license_number),
    KEY idx_doctor_dept   (department_id),
    KEY idx_doctor_status (status),

    CONSTRAINT fk_doctors_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doctors_dept
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add head_doctor FK to departments now that doctors table exists
ALTER TABLE departments
    ADD CONSTRAINT fk_dept_head_doctor
        FOREIGN KEY (head_doctor_id) REFERENCES doctors(id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================
-- 5. PATIENTS
-- ============================================================
CREATE TABLE patients (
    id                          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id                     INT UNSIGNED    NOT NULL,
    patient_code                VARCHAR(20)     NOT NULL,
    date_of_birth               DATE            NULL,
    blood_group                 ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown')
                                                NOT NULL DEFAULT 'Unknown',
    allergies                   TEXT            NULL,
    chronic_conditions          TEXT            NULL,
    emergency_contact_name      VARCHAR(100)    NULL,
    emergency_contact_phone     VARCHAR(20)     NULL,
    emergency_contact_relation  VARCHAR(50)     NULL,
    address                     TEXT            NULL,
    created_at                  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_patient_user (user_id),
    UNIQUE KEY uq_patient_code (patient_code),
    KEY idx_dob           (date_of_birth),
    KEY idx_blood_group   (blood_group),

    CONSTRAINT fk_patients_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. SERVICES
-- ============================================================
CREATE TABLE services (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    department_id       INT UNSIGNED        NULL,
    service_name        VARCHAR(150)        NOT NULL,
    description         TEXT                NULL,
    price               DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
    duration_minutes    SMALLINT UNSIGNED   NOT NULL DEFAULT 30,
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_service_dept   (department_id),
    KEY idx_service_status (status),
    KEY idx_service_price  (price),

    CONSTRAINT fk_services_dept
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. APPOINTMENTS
-- ============================================================
CREATE TABLE appointments (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id          INT UNSIGNED    NOT NULL,
    doctor_id           INT UNSIGNED    NOT NULL,
    service_id          INT UNSIGNED    NULL,
    appointment_date    DATE            NOT NULL,
    appointment_time    TIME            NOT NULL,
    booking_type        ENUM('walk-in','online','phone') NOT NULL DEFAULT 'online',
    reason              TEXT            NULL,
    status              ENUM('scheduled','confirmed','in_progress','completed','cancelled','no_show')
                                        NOT NULL DEFAULT 'scheduled',
    notes               TEXT            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_appt_patient    (patient_id),
    KEY idx_appt_doctor     (doctor_id),
    KEY idx_appt_date       (appointment_date),
    KEY idx_appt_status     (status),

    CONSTRAINT fk_appt_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_appt_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_appt_service
        FOREIGN KEY (service_id) REFERENCES services(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. QUEUES
-- ============================================================
CREATE TABLE queues (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    appointment_id  INT UNSIGNED    NOT NULL,
    queue_number    SMALLINT UNSIGNED NOT NULL,
    queue_type      ENUM('walk-in','online') NOT NULL DEFAULT 'online',
    status          ENUM('waiting','serving','completed','absent','cancelled')
                                    NOT NULL DEFAULT 'waiting',
    called_at       TIMESTAMP       NULL,
    served_at       TIMESTAMP       NULL,
    completed_at    TIMESTAMP       NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_queue_appt (appointment_id),
    KEY idx_queue_status (status),
    KEY idx_queue_number (queue_number),

    CONSTRAINT fk_queue_appt
        FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. CONSULTATIONS
-- ============================================================
CREATE TABLE consultations (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    appointment_id          INT UNSIGNED    NOT NULL,
    doctor_id               INT UNSIGNED    NOT NULL,
    chief_complaint         TEXT            NULL,
    symptoms                TEXT            NULL,
    diagnosis               TEXT            NULL,
    treatment_plan          TEXT            NULL,
    vitals_bp               VARCHAR(20)     NULL COMMENT 'e.g. 120/80',
    vitals_temp             DECIMAL(4,1)    NULL COMMENT 'Celsius',
    vitals_pulse            SMALLINT UNSIGNED NULL COMMENT 'bpm',
    vitals_o2               TINYINT UNSIGNED  NULL COMMENT 'percent',
    vitals_weight           DECIMAL(5,1)    NULL COMMENT 'kg',
    vitals_height           SMALLINT UNSIGNED NULL COMMENT 'cm',
    vitals_resp_rate        TINYINT UNSIGNED  NULL COMMENT 'breaths/min',
    doctor_notes            TEXT            NULL,
    patient_instructions    TEXT            NULL,
    follow_up               ENUM('none','1_week','2_weeks','1_month','3_months','prn','referred')
                                            NOT NULL DEFAULT 'none',
    status                  ENUM('in_progress','completed','referred')
                                            NOT NULL DEFAULT 'in_progress',
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_consult_appt (appointment_id),
    KEY idx_consult_doctor (doctor_id),
    KEY idx_consult_status (status),

    CONSTRAINT fk_consult_appt
        FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_consult_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. PRESCRIPTIONS
-- ============================================================
CREATE TABLE prescriptions (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    consultation_id     INT UNSIGNED    NOT NULL,
    patient_id          INT UNSIGNED    NOT NULL,
    doctor_id           INT UNSIGNED    NOT NULL,
    medication_name     VARCHAR(150)    NOT NULL,
    dosage              VARCHAR(50)     NULL,
    frequency           VARCHAR(80)     NULL,
    duration            VARCHAR(50)     NULL,
    route               VARCHAR(50)     NULL COMMENT 'Oral, IV, Topical, etc.',
    instructions        TEXT            NULL,
    status              ENUM('active','dispensed','cancelled') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_presc_consult  (consultation_id),
    KEY idx_presc_patient  (patient_id),
    KEY idx_presc_status   (status),

    CONSTRAINT fk_presc_consult
        FOREIGN KEY (consultation_id) REFERENCES consultations(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_presc_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_presc_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. SERVICE REQUESTS
-- ============================================================
CREATE TABLE service_requests (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    consultation_id     INT UNSIGNED    NOT NULL,
    patient_id          INT UNSIGNED    NOT NULL,
    doctor_id           INT UNSIGNED    NOT NULL,
    request_type        ENUM('laboratory','imaging_xray','imaging_ultrasound',
                             'imaging_ct','imaging_mri','referral',
                             'physiotherapy','other')
                                        NOT NULL,
    description         TEXT            NOT NULL,
    urgency             ENUM('routine','urgent','emergency') NOT NULL DEFAULT 'routine',
    result_notes        TEXT            NULL,
    status              ENUM('pending','in_progress','completed','cancelled')
                                        NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_svcreq_consult  (consultation_id),
    KEY idx_svcreq_patient  (patient_id),
    KEY idx_svcreq_status   (status),
    KEY idx_svcreq_urgency  (urgency),

    CONSTRAINT fk_svcreq_consult
        FOREIGN KEY (consultation_id) REFERENCES consultations(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_svcreq_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_svcreq_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. BILLS
-- ============================================================
CREATE TABLE bills (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id      INT UNSIGNED    NOT NULL,
    appointment_id  INT UNSIGNED    NULL,
    invoice_number  VARCHAR(30)     NOT NULL,
    subtotal        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    tax             DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    discount        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total_amount    DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    amount_paid     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    payment_status  ENUM('unpaid','partial','paid','waived') NOT NULL DEFAULT 'unpaid',
    due_date        DATE            NULL,
    notes           TEXT            NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_invoice_number (invoice_number),
    KEY idx_bill_patient        (patient_id),
    KEY idx_bill_appt           (appointment_id),
    KEY idx_bill_status         (payment_status),
    KEY idx_bill_due            (due_date),

    CONSTRAINT fk_bill_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_bill_appt
        FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. PAYMENTS
-- ============================================================
CREATE TABLE payments (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    bill_id                 INT UNSIGNED    NOT NULL,
    amount_paid             DECIMAL(10,2)   NOT NULL,
    payment_method          ENUM('cash','mtn_mobile','airtel_mobile',
                                 'card','insurance','bank_transfer')
                                            NOT NULL,
    transaction_reference   VARCHAR(100)    NULL,
    notes                   TEXT            NULL,
    received_by             INT UNSIGNED    NULL COMMENT 'user_id of receptionist',
    paid_at                 TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_pay_bill        (bill_id),
    KEY idx_pay_method      (payment_method),
    KEY idx_pay_received_by (received_by),
    KEY idx_pay_date        (paid_at),

    CONSTRAINT fk_pay_bill
        FOREIGN KEY (bill_id) REFERENCES bills(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pay_received_by
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. MEDICAL RECORDS
-- ============================================================
CREATE TABLE medical_records (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    patient_id          INT UNSIGNED    NOT NULL,
    consultation_id     INT UNSIGNED    NULL,
    record_type         ENUM('lab_result','imaging','referral',
                             'discharge_summary','vaccination','other')
                                        NOT NULL,
    description         TEXT            NULL,
    attachment_path     VARCHAR(500)    NULL,
    attachment_type     VARCHAR(50)     NULL COMMENT 'pdf, jpg, png, etc.',
    is_confidential     TINYINT(1)      NOT NULL DEFAULT 0,
    created_by          INT UNSIGNED    NULL COMMENT 'user_id of uploader',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_mrec_patient     (patient_id),
    KEY idx_mrec_consult     (consultation_id),
    KEY idx_mrec_type        (record_type),
    KEY idx_mrec_created_at  (created_at),

    CONSTRAINT fk_mrec_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mrec_consult
        FOREIGN KEY (consultation_id) REFERENCES consultations(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mrec_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SEED DATA
-- ============================================================

-- ── 1. Roles ──────────────────────────────────────────────
INSERT INTO roles (role_name, display_name, description) VALUES
    ('patient',      'Patient',       'Registered patient with access to personal health data'),
    ('doctor',       'Doctor',        'Medical doctor who conducts consultations'),
    ('receptionist', 'Receptionist',  'Front-desk staff managing appointments and billing'),
    ('admin',        'Administrator', 'System administrator with full access'),
    ('staff',        'Staff',         'General hospital staff');

-- ── 2. Departments ────────────────────────────────────────
INSERT INTO departments (department_name, description, status) VALUES
    ('Outpatient / General Practice', 'General outpatient consultations and primary care', 'active'),
    ('Cardiology',                    'Heart and cardiovascular system specialist care',   'active'),
    ('Radiology & Imaging',           'X-ray, ultrasound, CT scan, and MRI services',     'active'),
    ('Laboratory Services',           'Blood tests, urinalysis, and diagnostic tests',    'active'),
    ('Emergency',                     'Emergency and urgent care unit',                   'active'),
    ('Pharmacy',                      'Dispensing medications and pharmaceutical services','active');

-- ── 3. Users ──────────────────────────────────────────────
-- Default password = Admin123@  hash: $2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq
-- Admin@2026 hash:               $2y$12$EcOyl1zMq8NTYGiB.9O43OwvMts432Ee3w7ZomxFvOxgv1.oa01Ii
INSERT INTO users (role_id, first_name, last_name, email, password_hash, phone, gender, status) VALUES
    (4, 'Gerardine', 'Mukantwari',  'gerardinemukantwari88@gmail.com', '$2y$12$EcOyl1zMq8NTYGiB.9O43OwvMts432Ee3w7ZomxFvOxgv1.oa01Ii', '+250788000000', 'Female', 'active'),
    (4, 'Gerardine', 'Mukantwari',  'admin@medlink.com',          '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000001', 'Female', 'active'),
    (2, 'James',     'Karanja',     'doctor.karanja@medlink.com', '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000002', 'Male',   'active'),
    (2, 'Sarah',     'Ochieng',     'doctor.ochieng@medlink.com', '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000003', 'Female', 'active'),
    (3, 'Alice',     'Uwera',       'reception@medlink.com',      '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000004', 'Female', 'active'),
    (1, 'John',      'Doe',         'john@example.com',           '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000005', 'Male',   'active'),
    (1, 'Jane',      'Smith',       'jane@example.com',           '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000006', 'Female', 'active'),
    (1, 'Mary',      'Wanjiru',     'mary@example.com',           '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000007', 'Female', 'active'),
    (1, 'Peter',     'Ouma',        'peter@example.com',          '$2y$12$STBsH7VDdwJ2X7qn2OcqOOvJgXI9w3cxxlrsBfgNpDXYAgv0DWxtq', '+250788000008', 'Male',   'active');

-- ── 4. Doctors ────────────────────────────────────────────
INSERT INTO doctors (user_id, department_id, specialization, license_number, consultation_fee) VALUES
    (2, 1, 'General Practitioner', 'MDL-2019-0041', 5000.00),
    (3, 2, 'Cardiologist',         'MDL-2017-0018', 12000.00);

-- Set department heads
UPDATE departments SET head_doctor_id = 1 WHERE id = 1;
UPDATE departments SET head_doctor_id = 2 WHERE id = 2;

-- ── 5. Patients ───────────────────────────────────────────
INSERT INTO patients
    (user_id, patient_code, date_of_birth, blood_group, allergies, chronic_conditions,
     emergency_contact_name, emergency_contact_phone, emergency_contact_relation) VALUES
    (5, 'PAT-00001', '1988-03-15', 'B+',      NULL,         NULL,             'Anna Doe',     '+250788111001', 'Spouse'),
    (6, 'PAT-00002', '1995-07-22', 'A-',      NULL,         NULL,             'Tom Smith',    '+250788111002', 'Brother'),
    (7, 'PAT-00003', '1990-03-12', 'O+',      'Penicillin', 'Hypertension',   'Robert Njeri', '+250788111003', 'Husband'),
    (8, 'PAT-00004', '1985-11-05', 'AB+',     NULL,         'Type 2 Diabetes','Grace Ouma',   '+250788111004', 'Wife');

-- ── 6. Services ───────────────────────────────────────────
INSERT INTO services (department_id, service_name, description, price, duration_minutes) VALUES
    (1, 'General Consultation',       'Standard outpatient consultation with a GP',          5000,  30),
    (1, 'Follow-up Consultation',     'Follow-up visit for an existing condition',            3000,  20),
    (2, 'Specialist Consultation',    'Consultation with a specialist doctor',               12000,  45),
    (2, 'ECG (12-lead)',              'Electrocardiogram heart tracing',                      8000,  20),
    (3, 'Chest X-Ray (PA)',           'Posteroanterior chest radiograph',                    15000,  30),
    (3, 'Abdominal Ultrasound',       'Ultrasound scan of abdominal organs',                 20000,  45),
    (3, 'Obstetric Ultrasound',       'Ultrasound for pregnancy monitoring',                 18000,  45),
    (4, 'Full Blood Count (FBC)',     'Complete blood count — WBC, RBC, platelets',           4500, 120),
    (4, 'Lipid Profile',              'Cholesterol and triglycerides panel',                  6000, 240),
    (4, 'HbA1c',                      'Glycated haemoglobin for diabetes monitoring',         7500, 240),
    (4, 'Malaria RDT',                'Rapid malaria antigen test',                           2000,  30),
    (4, 'Urinalysis',                 'Urine microscopy, culture and sensitivity',            2500,  60),
    (5, 'Emergency Consultation',     'Emergency walk-in assessment and triage',             10000,   0),
    (6, 'Pharmacy Dispensing Fee',    'Standard dispensing fee per prescription',              500,   0);

-- ── 7. Appointments ───────────────────────────────────────
INSERT INTO appointments
    (patient_id, doctor_id, service_id, appointment_date, appointment_time,
     booking_type, reason, status) VALUES
    (3, 1, 1, CURDATE(), '09:30:00', 'online',  'Persistent headache and dizziness',    'completed'),
    (4, 1, 2, CURDATE(), '10:00:00', 'online',  'Diabetes follow-up and HbA1c review',  'in_progress'),
    (1, 1, 1, CURDATE(), '10:30:00', 'walk-in', 'Routine check-up',                     'confirmed'),
    (2, 2, 3, CURDATE(), '11:00:00', 'online',  'Chest pain and palpitations',          'scheduled'),
    (3, 1, 1, DATE_SUB(CURDATE(),INTERVAL 7 DAY), '09:00:00', 'online', 'Annual check-up', 'completed'),
    (4, 1, 2, DATE_SUB(CURDATE(),INTERVAL 14 DAY),'10:00:00', 'online', 'Type 2 Diabetes management','completed');

-- ── 8. Queues ─────────────────────────────────────────────
INSERT INTO queues (appointment_id, queue_number, queue_type, status, served_at, completed_at) VALUES
    (1, 1, 'online',  'completed', DATE_SUB(NOW(), INTERVAL 90 MINUTE), DATE_SUB(NOW(), INTERVAL 60 MINUTE)),
    (2, 2, 'online',  'serving',   DATE_SUB(NOW(), INTERVAL 15 MINUTE), NULL),
    (3, 3, 'walk-in', 'waiting',   NULL, NULL),
    (4, 4, 'online',  'waiting',   NULL, NULL);

-- ── 9. Consultations ──────────────────────────────────────
INSERT INTO consultations
    (appointment_id, doctor_id,
     chief_complaint, symptoms, diagnosis, treatment_plan,
     vitals_bp, vitals_temp, vitals_pulse, vitals_o2, vitals_weight, vitals_height,
     doctor_notes, patient_instructions, follow_up, status) VALUES
    (
        1, 1,
        'Persistent headache for 3 days',
        'Recurring frontal headache, dizziness, mild neck stiffness',
        'Tension headache secondary to uncontrolled hypertension',
        'Initiate Amlodipine 5mg OD; Paracetamol 500mg PRN; lifestyle modification',
        '148/92', 36.7, 82, 98, 68.0, 162,
        'Patient is allergic to Penicillin. Monitor BP at follow-up.',
        'Take Amlodipine at the same time every day. Avoid grapefruit juice. Reduce salt intake.',
        '2_weeks', 'completed'
    ),
    (
        5, 1,
        'Annual check-up',
        'Fatigue, mild joint aches',
        'Vitamin D deficiency (serum level 18 ng/mL). Borderline lipids.',
        'Vitamin D3 2000 IU daily for 3 months. Repeat labs in 3 months.',
        '128/80', 36.5, 74, 99, 68.0, 162,
        'Borderline lipid profile — advise dietary changes.',
        'Increase sun exposure. Reduce saturated fats in diet.',
        '3_months', 'completed'
    ),
    (
        6, 1,
        'Diabetes follow-up',
        'Increased thirst, frequent urination, fatigue',
        'Type 2 Diabetes Mellitus — suboptimal glycaemic control (HbA1c 7.2%)',
        'Continue Metformin 500mg BD. Add Atorvastatin 20mg ON. Repeat HbA1c in 3 months.',
        '138/86', 36.8, 80, 97, 88.0, 175,
        'HbA1c 7.2% — borderline. Reinforce dietary advice.',
        'Follow diabetic diet. Exercise 30 min/day. Take medication after meals.',
        '3_months', 'completed'
    );

-- ── 10. Prescriptions ─────────────────────────────────────
INSERT INTO prescriptions
    (consultation_id, patient_id, doctor_id, medication_name, dosage, frequency, duration, route, instructions, status) VALUES
    (1, 3, 1, 'Amlodipine',   '5mg',    'Once daily',       '30 days',  'Oral', 'Take in the morning after breakfast', 'active'),
    (1, 3, 1, 'Paracetamol',  '500mg',  'PRN (max 3/day)',  'As needed','Oral', 'Take when headache occurs',           'active'),
    (2, 3, 1, 'Vitamin D3',   '2000 IU','Once daily',       '90 days',  'Oral', 'Take with a meal',                    'dispensed'),
    (3, 4, 1, 'Metformin',    '500mg',  'Twice daily',      '60 days',  'Oral', 'Take after meals',                    'active'),
    (3, 4, 1, 'Atorvastatin', '20mg',   'Once daily at bedtime','60 days','Oral','Take at bedtime',                    'active');

-- ── 11. Service Requests ──────────────────────────────────
INSERT INTO service_requests
    (consultation_id, patient_id, doctor_id, request_type, description, urgency, result_notes, status) VALUES
    (1, 3, 1, 'laboratory',        'Full Blood Count, Lipid Profile',       'routine',  NULL,                                         'pending'),
    (2, 3, 1, 'laboratory',        'Vitamin D serum level, Lipid Profile',  'routine',  'Vit D: 18 ng/mL (low). LDL borderline.',    'completed'),
    (3, 4, 1, 'laboratory',        'HbA1c, Fasting Blood Sugar, Lipids',    'routine',  'HbA1c: 7.2%. FBS: 9.1 mmol/L.',            'completed'),
    (3, 4, 1, 'imaging_ultrasound','Abdominal Ultrasound — liver assessment','routine',  'Mild hepatomegaly. No focal lesions.',       'completed');

-- ── 12. Bills ─────────────────────────────────────────────
INSERT INTO bills
    (patient_id, appointment_id, invoice_number, subtotal, tax, discount, total_amount, amount_paid, payment_status, due_date) VALUES
    (3, 1, 'INV-2026-0001', 5000.00,  0.00,    0.00,    5000.00,  5000.00, 'paid',    CURDATE()),
    (3, 5, 'INV-2026-0002', 5000.00,  0.00,    0.00,    5000.00,  5000.00, 'paid',    DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
    (4, 6, 'INV-2026-0003', 6000.00,  0.00,    500.00,  5500.00,  3000.00, 'partial', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
    (4, 2, 'INV-2026-0004', 3000.00,  0.00,    0.00,    3000.00,  0.00,    'unpaid',  DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
    (1, 3, 'INV-2026-0005', 5000.00,  0.00,    0.00,    5000.00,  0.00,    'unpaid',  DATE_ADD(CURDATE(), INTERVAL 14 DAY));

-- ── 13. Payments ──────────────────────────────────────────
INSERT INTO payments
    (bill_id, amount_paid, payment_method, transaction_reference, received_by) VALUES
    (1, 5000.00, 'cash',        NULL,           4),
    (2, 5000.00, 'mtn_mobile',  'MTN-20260520', 4),
    (3, 3000.00, 'cash',        NULL,           4);

-- ── 14. Medical Records ───────────────────────────────────
INSERT INTO medical_records
    (patient_id, consultation_id, record_type, description, is_confidential, created_by) VALUES
    (3, 1, 'lab_result', 'Full Blood Count — pending results',           0, 2),
    (3, 2, 'lab_result', 'Vitamin D serum level: 18 ng/mL. LDL: 3.9.',  0, 2),
    (4, 3, 'lab_result', 'HbA1c: 7.2%. FBS: 9.1 mmol/L.',              0, 2),
    (4, 3, 'imaging',    'Abdominal Ultrasound — mild hepatomegaly.',    0, 2);


-- ============================================================
-- VERIFY STRUCTURE
-- ============================================================
SELECT
    TABLE_NAME,
    TABLE_ROWS,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) AS size_kb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'medlink'
ORDER BY TABLE_NAME;
