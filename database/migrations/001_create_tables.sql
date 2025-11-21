-- ============================================================================
-- Health & Safety Inspections System - Complete Database Schema
-- Version: 1.0.0
-- Date: 2025-11-21
-- ============================================================================

-- Set character set and collation
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- SUBSYSTEM 0: AUTHENTICATION & USER MANAGEMENT
-- ============================================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_photo_url VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended', 'pending_verification') DEFAULT 'pending_verification',
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    failed_login_attempts TINYINT UNSIGNED DEFAULT 0,
    account_locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    role_description TEXT,
    hierarchy_level TINYINT UNSIGNED NOT NULL,
    parent_role_id INT UNSIGNED,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_hierarchy_level (hierarchy_level),
    FOREIGN KEY (parent_role_id) REFERENCES roles(role_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User roles (many-to-many)
CREATE TABLE IF NOT EXISTS user_roles (
    user_role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permission_key (permission_key),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role permissions (many-to-many)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_permission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    jwt_token TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password resets
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    reset_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_token (reset_token),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username_or_email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username_or_email (username_or_email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs
CREATE TABLE IF NOT EXISTS audit_logs (
    audit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_type VARCHAR(50),
    record_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 2: ESTABLISHMENT REGISTRY
-- ============================================================================

-- Establishments
CREATE TABLE IF NOT EXISTS establishments (
    establishment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('food_establishment', 'building', 'workplace', 'public_space', 'healthcare_facility') NOT NULL,
    subtype VARCHAR(100),
    owner_name VARCHAR(255) NOT NULL,
    owner_contact VARCHAR(20),
    owner_email VARCHAR(255),
    owner_user_id INT UNSIGNED,
    manager_name VARCHAR(255),
    manager_contact VARCHAR(20),
    business_permit_number VARCHAR(100),
    permit_issue_date DATE,
    permit_expiry_date DATE,
    address_street TEXT NOT NULL,
    address_barangay VARCHAR(100) NOT NULL,
    address_city VARCHAR(100) NOT NULL,
    address_postal_code VARCHAR(20),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    employee_count INT UNSIGNED,
    floor_area_sqm DECIMAL(10, 2),
    operating_hours JSON,
    risk_category ENUM('low', 'medium', 'high') DEFAULT 'medium',
    compliance_status ENUM('compliant', 'non_compliant', 'suspended', 'revoked') DEFAULT 'non_compliant',
    last_inspection_date DATE,
    next_inspection_due DATE,
    status ENUM('active', 'inactive', 'suspended', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reference_number (reference_number),
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_risk_category (risk_category),
    INDEX idx_compliance_status (compliance_status),
    INDEX idx_status (status),
    INDEX idx_barangay (address_barangay),
    INDEX idx_gps (gps_latitude, gps_longitude),
    FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Establishment contacts
CREATE TABLE IF NOT EXISTS establishment_contacts (
    contact_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT UNSIGNED NOT NULL,
    contact_type ENUM('owner', 'manager', 'safety_officer', 'emergency') NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    INDEX idx_establishment_id (establishment_id),
    FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Establishment permits
CREATE TABLE IF NOT EXISTS establishment_permits (
    permit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    establishment_id INT UNSIGNED NOT NULL,
    permit_type ENUM('business', 'health', 'fire_safety', 'building', 'sanitary') NOT NULL,
    permit_number VARCHAR(100) NOT NULL,
    issuing_authority VARCHAR(255),
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('valid', 'expired', 'suspended', 'revoked') DEFAULT 'valid',
    document_url VARCHAR(255),
    INDEX idx_establishment_id (establishment_id),
    INDEX idx_permit_type (permit_type),
    INDEX idx_status (status),
    INDEX idx_expiry_date (expiry_date),
    FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 5: CHECKLIST & TEMPLATE
-- ============================================================================

-- Checklist templates
CREATE TABLE IF NOT EXISTS checklist_templates (
    template_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    establishment_type VARCHAR(100),
    inspection_type VARCHAR(100),
    version VARCHAR(20) NOT NULL,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_establishment_type (establishment_type),
    INDEX idx_inspection_type (inspection_type),
    INDEX idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Checklist items
CREATE TABLE IF NOT EXISTS checklist_items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100),
    item_number VARCHAR(20),
    requirement_text TEXT NOT NULL,
    mandatory BOOLEAN DEFAULT TRUE,
    scoring_type ENUM('pass_fail', 'rating_scale', 'numeric', 'text') DEFAULT 'pass_fail',
    points_possible INT UNSIGNED,
    legal_reference TEXT,
    guidance_notes TEXT,
    order_sequence INT UNSIGNED DEFAULT 0,
    INDEX idx_template_id (template_id),
    INDEX idx_category (category),
    INDEX idx_order_sequence (order_sequence),
    FOREIGN KEY (template_id) REFERENCES checklist_templates(template_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Checklist categories
CREATE TABLE IF NOT EXISTS checklist_categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color_code VARCHAR(20),
    order_sequence INT UNSIGNED DEFAULT 0,
    INDEX idx_order_sequence (order_sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 6: INSPECTOR MANAGEMENT
-- ============================================================================

-- Inspectors
CREATE TABLE IF NOT EXISTS inspectors (
    inspector_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    badge_number VARCHAR(50) UNIQUE NOT NULL,
    specializations JSON,
    certification_number VARCHAR(100),
    certification_expiry DATE,
    employment_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    years_of_experience INT UNSIGNED,
    hired_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_badge_number (badge_number),
    INDEX idx_employment_status (employment_status),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inspector certifications
CREATE TABLE IF NOT EXISTS inspector_certifications (
    cert_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NOT NULL,
    certification_type VARCHAR(100) NOT NULL,
    issuing_body VARCHAR(255),
    certification_number VARCHAR(100),
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('valid', 'expired', 'suspended') DEFAULT 'valid',
    INDEX idx_inspector_id (inspector_id),
    INDEX idx_expiry_date (expiry_date),
    FOREIGN KEY (inspector_id) REFERENCES inspectors(inspector_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inspector schedule
CREATE TABLE IF NOT EXISTS inspector_schedule (
    schedule_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    status ENUM('available', 'assigned', 'leave', 'training') DEFAULT 'available',
    notes TEXT,
    INDEX idx_inspector_id (inspector_id),
    INDEX idx_date (date),
    FOREIGN KEY (inspector_id) REFERENCES inspectors(inspector_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inspector performance
CREATE TABLE IF NOT EXISTS inspector_performance (
    performance_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NOT NULL,
    period VARCHAR(7) NOT NULL, -- YYYY-MM
    inspections_completed INT UNSIGNED DEFAULT 0,
    avg_inspection_duration_mins INT UNSIGNED,
    violations_found INT UNSIGNED DEFAULT 0,
    certificates_issued INT UNSIGNED DEFAULT 0,
    rating_score DECIMAL(3, 2),
    INDEX idx_inspector_id (inspector_id),
    INDEX idx_period (period),
    UNIQUE KEY unique_inspector_period (inspector_id, period),
    FOREIGN KEY (inspector_id) REFERENCES inspectors(inspector_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 1: INSPECTION MANAGEMENT
-- ============================================================================

-- Inspections
CREATE TABLE IF NOT EXISTS inspections (
    inspection_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    establishment_id INT UNSIGNED NOT NULL,
    inspection_type ENUM('food_safety', 'building_safety', 'workplace_safety', 'fire_safety', 'sanitation') NOT NULL,
    inspector_id INT UNSIGNED,
    scheduled_date DATE NOT NULL,
    actual_start_datetime TIMESTAMP NULL,
    actual_end_datetime TIMESTAMP NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'failed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    checklist_template_id INT UNSIGNED,
    overall_rating ENUM('excellent', 'satisfactory', 'needs_improvement', 'failed'),
    inspector_notes TEXT,
    weather_conditions VARCHAR(100),
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reference_number (reference_number),
    INDEX idx_establishment_id (establishment_id),
    INDEX idx_inspector_id (inspector_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status),
    INDEX idx_inspection_type (inspection_type),
    FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES inspectors(inspector_id) ON DELETE SET NULL,
    FOREIGN KEY (checklist_template_id) REFERENCES checklist_templates(template_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inspection checklist responses
CREATE TABLE IF NOT EXISTS inspection_checklist_responses (
    response_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT UNSIGNED NOT NULL,
    checklist_item_id INT UNSIGNED NOT NULL,
    response ENUM('pass', 'fail', 'na', 'observed') NOT NULL,
    notes TEXT,
    evidence_photos JSON,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inspection_id (inspection_id),
    INDEX idx_checklist_item_id (checklist_item_id),
    FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE CASCADE,
    FOREIGN KEY (checklist_item_id) REFERENCES checklist_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inspection photos
CREATE TABLE IF NOT EXISTS inspection_photos (
    photo_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT UNSIGNED NOT NULL,
    photo_url VARCHAR(255) NOT NULL,
    photo_type ENUM('violation', 'evidence', 'general') DEFAULT 'general',
    caption TEXT,
    gps_coordinates VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inspection_id (inspection_id),
    FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inspector assignments
CREATE TABLE IF NOT EXISTS inspector_assignments (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NOT NULL,
    inspection_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('assigned', 'accepted', 'declined', 'completed') DEFAULT 'assigned',
    INDEX idx_inspector_id (inspector_id),
    INDEX idx_inspection_id (inspection_id),
    FOREIGN KEY (inspector_id) REFERENCES inspectors(inspector_id) ON DELETE CASCADE,
    FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 3: VIOLATIONS & FINDINGS
-- ============================================================================

-- Violation codes
CREATE TABLE IF NOT EXISTS violation_codes (
    code_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    default_severity ENUM('minor', 'major', 'critical') DEFAULT 'minor',
    corrective_action_template TEXT,
    legal_reference TEXT,
    fine_amount DECIMAL(10, 2),
    INDEX idx_code (code),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violations
CREATE TABLE IF NOT EXISTS violations (
    violation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT UNSIGNED NOT NULL,
    establishment_id INT UNSIGNED NOT NULL,
    violation_code VARCHAR(20),
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('minor', 'major', 'critical') NOT NULL,
    corrective_action_required TEXT,
    corrective_action_deadline DATE,
    estimated_cost_to_fix DECIMAL(10, 2),
    status ENUM('open', 'in_progress', 'resolved', 'waived') DEFAULT 'open',
    resolution_date DATE,
    resolution_notes TEXT,
    evidence_photos JSON,
    reported_by INT UNSIGNED,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_by INT UNSIGNED,
    resolved_at TIMESTAMP NULL,
    INDEX idx_inspection_id (inspection_id),
    INDEX idx_establishment_id (establishment_id),
    INDEX idx_violation_code (violation_code),
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE CASCADE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violation follow-ups
CREATE TABLE IF NOT EXISTS violation_follow_ups (
    follow_up_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    violation_id INT UNSIGNED NOT NULL,
    follow_up_date DATE NOT NULL,
    inspector_id INT UNSIGNED,
    status_update TEXT,
    photos JSON,
    notes TEXT,
    next_follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_violation_id (violation_id),
    INDEX idx_follow_up_date (follow_up_date),
    FOREIGN KEY (violation_id) REFERENCES violations(violation_id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES inspectors(inspector_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Corrective action plans
CREATE TABLE IF NOT EXISTS corrective_action_plans (
    plan_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    violation_id INT UNSIGNED NOT NULL,
    establishment_id INT UNSIGNED NOT NULL,
    action_items JSON NOT NULL,
    estimated_completion_date DATE,
    actual_completion_date DATE,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    submitted_by INT UNSIGNED,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT UNSIGNED,
    approved_at TIMESTAMP NULL,
    INDEX idx_violation_id (violation_id),
    INDEX idx_establishment_id (establishment_id),
    INDEX idx_status (status),
    FOREIGN KEY (violation_id) REFERENCES violations(violation_id) ON DELETE CASCADE,
    FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 4: CERTIFICATION & PERMITTING
-- ============================================================================

-- Certificates
CREATE TABLE IF NOT EXISTS certificates (
    certificate_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    establishment_id INT UNSIGNED NOT NULL,
    inspection_id INT UNSIGNED,
    certificate_type ENUM('food_safety', 'fire_safety', 'building_occupancy', 'sanitary_permit') NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('valid', 'expired', 'suspended', 'revoked') DEFAULT 'valid',
    conditions JSON,
    issued_by INT UNSIGNED,
    approved_by INT UNSIGNED,
    qr_code_data TEXT,
    pdf_url VARCHAR(255),
    revocation_reason TEXT,
    revoked_by INT UNSIGNED,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_establishment_id (establishment_id),
    INDEX idx_status (status),
    INDEX idx_expiry_date (expiry_date),
    FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
    FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (revoked_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificate renewals
CREATE TABLE IF NOT EXISTS certificate_renewals (
    renewal_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_id INT UNSIGNED NOT NULL,
    previous_certificate_id INT UNSIGNED,
    renewal_date DATE NOT NULL,
    inspection_required BOOLEAN DEFAULT TRUE,
    inspection_id INT UNSIGNED,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    processed_by INT UNSIGNED,
    processed_at TIMESTAMP NULL,
    INDEX idx_certificate_id (certificate_id),
    FOREIGN KEY (certificate_id) REFERENCES certificates(certificate_id) ON DELETE CASCADE,
    FOREIGN KEY (previous_certificate_id) REFERENCES certificates(certificate_id) ON DELETE SET NULL,
    FOREIGN KEY (inspection_id) REFERENCES inspections(inspection_id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificate verifications (public access log)
CREATE TABLE IF NOT EXISTS certificate_verifications (
    verification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_id INT UNSIGNED NOT NULL,
    verified_by ENUM('public', 'inspector', 'establishment') NOT NULL,
    verification_method ENUM('qr_code', 'certificate_number', 'web_portal') NOT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_certificate_id (certificate_id),
    INDEX idx_verified_at (verified_at),
    FOREIGN KEY (certificate_id) REFERENCES certificates(certificate_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 7: ANALYTICS & REPORTING
-- ============================================================================

-- Analytics snapshots
CREATE TABLE IF NOT EXISTS analytics_snapshots (
    snapshot_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_date DATE UNIQUE NOT NULL,
    total_establishments INT UNSIGNED DEFAULT 0,
    active_establishments INT UNSIGNED DEFAULT 0,
    total_inspections_ytd INT UNSIGNED DEFAULT 0,
    compliance_rate DECIMAL(5, 2),
    avg_violations_per_inspection DECIMAL(5, 2),
    certificates_issued_ytd INT UNSIGNED DEFAULT 0,
    critical_violations_count INT UNSIGNED DEFAULT 0,
    metrics_json JSON,
    INDEX idx_snapshot_date (snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report templates
CREATE TABLE IF NOT EXISTS report_templates (
    template_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    parameters_json JSON,
    schedule ENUM('daily', 'weekly', 'monthly', 'quarterly', 'on_demand') DEFAULT 'on_demand',
    recipients JSON,
    last_generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated reports
CREATE TABLE IF NOT EXISTS generated_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    period_start DATE,
    period_end DATE,
    file_url VARCHAR(255),
    file_format ENUM('pdf', 'excel', 'csv') DEFAULT 'pdf',
    generated_by INT UNSIGNED,
    INDEX idx_template_id (template_id),
    INDEX idx_generated_at (generated_at),
    FOREIGN KEY (template_id) REFERENCES report_templates(template_id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 8: INTEGRATION HUB
-- ============================================================================

-- Integration logs
CREATE TABLE IF NOT EXISTS integration_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    direction ENUM('inbound', 'outbound') NOT NULL,
    source_module VARCHAR(100),
    target_module VARCHAR(100),
    endpoint VARCHAR(255),
    payload JSON,
    response JSON,
    status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_direction (direction),
    INDEX idx_status (status),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks
CREATE TABLE IF NOT EXISTS webhooks (
    webhook_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_module VARCHAR(100) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    callback_url VARCHAR(255) NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_triggered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_source_module (source_module),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys
CREATE TABLE IF NOT EXISTS api_keys (
    key_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    permissions JSON,
    rate_limit INT UNSIGNED DEFAULT 100,
    status ENUM('active', 'revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 9: NOTIFICATION & ALERTS
-- ============================================================================

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id INT UNSIGNED,
    recipient_email VARCHAR(255),
    recipient_phone VARCHAR(20),
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'sent', 'failed', 'read') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient_user_id (recipient_user_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification templates
CREATE TABLE IF NOT EXISTS notification_templates (
    template_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_template TEXT NOT NULL,
    sms_template TEXT,
    variables JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification preferences
CREATE TABLE IF NOT EXISTS notification_preferences (
    preference_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    channel ENUM('email', 'sms', 'in_app') NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_user_channel_type (user_id, channel, notification_type),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUBSYSTEM 10: DOCUMENT MANAGEMENT
-- ============================================================================

-- Documents
CREATE TABLE IF NOT EXISTS documents (
    document_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_type VARCHAR(50) NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size_kb INT UNSIGNED,
    mime_type VARCHAR(100),
    uploaded_by INT UNSIGNED,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    version INT UNSIGNED DEFAULT 1,
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_document_type (document_type),
    INDEX idx_uploaded_at (uploaded_at),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document access logs
CREATE TABLE IF NOT EXISTS document_access_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    accessed_by INT UNSIGNED,
    action ENUM('view', 'download', 'delete') NOT NULL,
    ip_address VARCHAR(45),
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document_id (document_id),
    INDEX idx_accessed_at (accessed_at),
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE,
    FOREIGN KEY (accessed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
