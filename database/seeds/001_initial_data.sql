-- ============================================================================
-- Health & Safety Inspections System - Seed Data
-- Version: 1.0.0
-- Date: 2025-11-21
-- ============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- 1. ROLES (with hierarchy)
-- ============================================================================

INSERT INTO roles (role_name, role_description, hierarchy_level, parent_role_id, permissions) VALUES
('super_admin', 'System Administrator with full access', 1, NULL, '["*.*"]'),
('admin', 'Module Administrator', 2, NULL, '["inspections.*", "establishments.*", "violations.*", "certificates.*", "analytics.*"]'),
('senior_inspector', 'Lead Inspector', 3, NULL, '["inspections.*", "violations.*", "certificates.view", "certificates.download"]'),
('inspector', 'Field Inspector', 4, NULL, '["inspections.create", "inspections.read", "inspections.update", "violations.create", "violations.read"]'),
('establishment_owner', 'Business Owner', 5, NULL, '["establishments.read_own", "inspections.read_own", "certificates.read_own", "certificates.download_own"]'),
('public', 'Public User (Read-Only)', 6, NULL, '["certificates.verify"]');

-- ============================================================================
-- 2. PERMISSIONS
-- ============================================================================

INSERT INTO permissions (permission_key, module, action, description) VALUES
-- User management
('users.create', 'users', 'create', 'Create new users'),
('users.read', 'users', 'read', 'View user information'),
('users.update', 'users', 'update', 'Update user information'),
('users.delete', 'users', 'delete', 'Delete users'),
('users.assign_role', 'users', 'assign_role', 'Assign roles to users'),

-- Inspections
('inspections.create', 'inspections', 'create', 'Create new inspections'),
('inspections.read', 'inspections', 'read', 'View all inspections'),
('inspections.read_own', 'inspections', 'read', 'View assigned inspections'),
('inspections.update', 'inspections', 'update', 'Update inspection details'),
('inspections.delete', 'inspections', 'delete', 'Delete inspections'),
('inspections.assign', 'inspections', 'assign', 'Assign inspectors'),
('inspections.start', 'inspections', 'start', 'Start inspection'),
('inspections.complete', 'inspections', 'complete', 'Complete inspection'),

-- Establishments
('establishments.create', 'establishments', 'create', 'Register new establishments'),
('establishments.read', 'establishments', 'read', 'View all establishments'),
('establishments.read_own', 'establishments', 'read', 'View own establishment'),
('establishments.update', 'establishments', 'update', 'Update establishment information'),
('establishments.delete', 'establishments', 'delete', 'Delete establishments'),
('establishments.suspend', 'establishments', 'suspend', 'Suspend establishment operations'),

-- Violations
('violations.create', 'violations', 'create', 'Report violations'),
('violations.read', 'violations', 'read', 'View all violations'),
('violations.read_own', 'violations', 'read', 'View own establishment violations'),
('violations.update', 'violations', 'update', 'Update violation details'),
('violations.delete', 'violations', 'delete', 'Delete violations'),
('violations.resolve', 'violations', 'resolve', 'Mark violations as resolved'),

-- Certificates
('certificates.issue', 'certificates', 'issue', 'Issue new certificates'),
('certificates.read', 'certificates', 'read', 'View all certificates'),
('certificates.read_own', 'certificates', 'read', 'View own certificates'),
('certificates.download', 'certificates', 'download', 'Download certificates'),
('certificates.download_own', 'certificates', 'download', 'Download own certificates'),
('certificates.revoke', 'certificates', 'revoke', 'Revoke certificates'),
('certificates.verify', 'certificates', 'verify', 'Verify certificate authenticity'),

-- Checklists
('checklists.create', 'checklists', 'create', 'Create checklist templates'),
('checklists.read', 'checklists', 'read', 'View checklist templates'),
('checklists.update', 'checklists', 'update', 'Update checklist templates'),
('checklists.delete', 'checklists', 'delete', 'Delete checklist templates'),

-- Analytics
('analytics.view', 'analytics', 'read', 'View analytics dashboard'),
('analytics.export', 'analytics', 'export', 'Export analytics data'),
('reports.generate', 'reports', 'create', 'Generate reports'),

-- Documents
('documents.upload', 'documents', 'create', 'Upload documents'),
('documents.read', 'documents', 'read', 'View documents'),
('documents.delete', 'documents', 'delete', 'Delete documents');

-- ============================================================================
-- 3. ROLE PERMISSIONS (Assign permissions to roles)
-- ============================================================================

-- Super Admin: All permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions;

-- Admin: Most permissions except user management
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, permission_id FROM permissions 
WHERE permission_key NOT LIKE 'users.assign_role';

-- Senior Inspector: Inspection and violation management
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, permission_id FROM permissions 
WHERE permission_key IN (
    'inspections.create', 'inspections.read', 'inspections.update', 'inspections.assign', 
    'inspections.start', 'inspections.complete',
    'violations.create', 'violations.read', 'violations.update', 'violations.resolve',
    'establishments.read', 'establishments.update',
    'certificates.read', 'certificates.download', 'certificates.issue',
    'checklists.read', 'documents.upload', 'documents.read'
);

-- Inspector: Basic inspection operations
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, permission_id FROM permissions 
WHERE permission_key IN (
    'inspections.create', 'inspections.read_own', 'inspections.update', 
    'inspections.start', 'inspections.complete',
    'violations.create', 'violations.read',
    'establishments.read',
    'checklists.read', 'documents.upload', 'documents.read'
);

-- Establishment Owner: View own data only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, permission_id FROM permissions 
WHERE permission_key IN (
    'establishments.read_own', 'inspections.read_own', 
    'violations.read_own', 'certificates.read_own', 'certificates.download_own',
    'documents.upload', 'documents.read'
);

-- Public: Certificate verification only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, permission_id FROM permissions 
WHERE permission_key = 'certificates.verify';

-- ============================================================================
-- 4. SAMPLE USERS
-- ============================================================================

-- Password: Admin@123 (bcrypt hash)
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, status, email_verified_at) VALUES
('superadmin', 'superadmin@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', '09171234567', 'active', NOW()),
('admin', 'admin@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '09171234568', 'active', NOW()),
('juan.delacruz', 'juan.delacruz@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Dela Cruz', '09171234569', 'active', NOW()),
('maria.santos', 'maria.santos@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', '09171234570', 'active', NOW()),
('pedro.reyes', 'pedro.reyes@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pedro', 'Reyes', '09171234571', 'active', NOW());

-- ============================================================================
-- 5. ASSIGN ROLES TO USERS
-- ============================================================================

INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at) VALUES
(1, 1, NULL, NOW()), -- Super Admin
(2, 2, 1, NOW()),    -- Admin
(3, 3, 1, NOW()),    -- Senior Inspector (Juan)
(4, 4, 1, NOW()),    -- Inspector (Maria)
(5, 5, 2, NOW());    -- Establishment Owner (Pedro)

-- ============================================================================
-- 6. INSPECTORS
-- ============================================================================

INSERT INTO inspectors (user_id, badge_number, specializations, certification_number, certification_expiry, employment_status, years_of_experience, hired_date) VALUES
(3, 'INS-2023-001', '["food_safety", "sanitation", "building_safety"]', 'CERT-FS-2023-001', '2025-12-31', 'active', 5, '2020-01-15'),
(4, 'INS-2023-002', '["food_safety", "workplace_safety"]', 'CERT-FS-2023-002', '2025-12-31', 'active', 3, '2022-03-01');

-- ============================================================================
-- 7. CHECKLIST CATEGORIES
-- ============================================================================

INSERT INTO checklist_categories (name, description, icon, color_code, order_sequence) VALUES
('Food Safety', 'Food handling and storage requirements', 'utensils', '#10B981', 1),
('Sanitation', 'Cleanliness and hygiene standards', 'sparkles', '#3B82F6', 2),
('Structural Safety', 'Building structure and maintenance', 'building', '#F59E0B', 3),
('Fire Safety', 'Fire prevention and suppression systems', 'fire', '#EF4444', 4),
('Ventilation', 'Air quality and ventilation systems', 'wind', '#8B5CF6', 5),
('Waste Management', 'Proper waste disposal and segregation', 'trash', '#6B7280', 6);

-- ============================================================================
-- 8. CHECKLIST TEMPLATE (Food Safety)
-- ============================================================================

INSERT INTO checklist_templates (name, description, establishment_type, inspection_type, version, status, created_by) VALUES
('Food Establishment Standard Checklist', 'Standard checklist for food safety inspections of restaurants and food establishments', 'food_establishment', 'food_safety', '1.0', 'active', 1);

SET @template_id = LAST_INSERT_ID();

INSERT INTO checklist_items (template_id, category, subcategory, item_number, requirement_text, mandatory, scoring_type, points_possible, order_sequence) VALUES
(@template_id, 'Food Safety', 'Food Storage', '1.1', 'Food stored at proper temperatures (cold: <5°C, hot: >60°C)', TRUE, 'pass_fail', 10, 1),
(@template_id, 'Food Safety', 'Food Storage', '1.2', 'Raw and cooked foods properly separated', TRUE, 'pass_fail', 10, 2),
(@template_id, 'Food Safety', 'Food Storage', '1.3', 'Food containers properly labeled with dates', TRUE, 'pass_fail', 10, 3),
(@template_id, 'Food Safety', 'Food Handling', '1.4', 'Food handlers wearing proper attire (hairnets, gloves, aprons)', TRUE, 'pass_fail', 10, 4),
(@template_id, 'Food Safety', 'Food Handling', '1.5', 'No evidence of cross-contamination', TRUE, 'pass_fail', 10, 5),
(@template_id, 'Sanitation', 'Cleanliness', '2.1', 'Kitchen and food prep areas clean and sanitized', TRUE, 'pass_fail', 10, 6),
(@template_id, 'Sanitation', 'Cleanliness', '2.2', 'Floors, walls, and ceilings clean and in good repair', TRUE, 'pass_fail', 10, 7),
(@template_id, 'Sanitation', 'Pest Control', '2.3', 'No evidence of pests (rodents, insects, etc.)', TRUE, 'pass_fail', 10, 8),
(@template_id, 'Sanitation', 'Waste Management', '2.4', 'Garbage properly stored in covered bins', TRUE, 'pass_fail', 10, 9),
(@template_id, 'Sanitation', 'Handwashing', '2.5', 'Handwashing stations available with soap and paper towels', TRUE, 'pass_fail', 10, 10);

-- ============================================================================
-- 9. VIOLATION CODES
-- ============================================================================

INSERT INTO violation_codes (code, category, description, default_severity, corrective_action_template, legal_reference, fine_amount) VALUES
('FS-001', 'food_handling', 'Improper food storage temperature', 'major', 'Install or repair refrigeration units to maintain proper temperature', 'Food Safety Act Section 5.1', 5000.00),
('FS-002', 'food_handling', 'Cross-contamination of raw and cooked foods', 'critical', 'Implement separate storage and preparation areas for raw and cooked foods', 'Food Safety Act Section 5.2', 10000.00),
('FS-003', 'food_handling', 'Food handlers without proper protective equipment', 'major', 'Provide and enforce use of hairnets, gloves, and aprons for all food handlers', 'Food Safety Act Section 6.1', 3000.00),
('SAN-001', 'sanitation', 'Unsanitary kitchen conditions', 'major', 'Deep clean and sanitize all kitchen surfaces and equipment', 'Sanitation Code Section 3.2', 5000.00),
('SAN-002', 'sanitation', 'Pest infestation', 'critical', 'Engage licensed pest control services and seal entry points', 'Sanitation Code Section 4.5', 15000.00),
('SAN-003', 'sanitation', 'Inadequate handwashing facilities', 'major', 'Install additional handwashing stations with soap and paper towels', 'Sanitation Code Section 2.3', 4000.00),
('WM-001', 'waste_management', 'Improper waste disposal', 'minor', 'Provide covered waste bins and implement proper waste segregation', 'Waste Management Act Section 8', 2000.00),
('BS-001', 'structural', 'Structural damage or deterioration', 'major', 'Repair damaged floors, walls, or ceilings', 'Building Code Section 12', 10000.00),
('FS-004', 'fire_safety', 'Fire extinguisher missing or expired', 'major', 'Install or replace fire extinguishers and ensure regular maintenance', 'Fire Code Section 7.2', 5000.00),
('FS-005', 'fire_safety', 'Blocked fire exits', 'critical', 'Clear all fire exits and ensure they remain unobstructed', 'Fire Code Section 3.1', 20000.00);

-- ============================================================================
-- 10. SAMPLE ESTABLISHMENTS
-- ============================================================================

INSERT INTO establishments (reference_number, name, type, subtype, owner_name, owner_contact, owner_email, owner_user_id, business_permit_number, permit_issue_date, permit_expiry_date, address_street, address_barangay, address_city, gps_latitude, gps_longitude, employee_count, floor_area_sqm, risk_category, compliance_status, status) VALUES
('EST-2025-001', 'ABC Restaurant', 'food_establishment', 'restaurant', 'Pedro Reyes', '09171234571', 'pedro.reyes@restaurant.com', 5, 'BP-2025-1234', '2025-01-15', '2025-12-31', '123 Main Street', 'Poblacion', 'Quezon City', 14.6760, 121.0437, 15, 120.50, 'medium', 'compliant', 'active'),
('EST-2025-002', 'Golden Bakery', 'food_establishment', 'bakery', 'Maria Garcia', '09181234567', 'maria@goldenbakery.com', NULL, 'BP-2025-1235', '2025-01-20', '2025-12-31', '456 Rizal Avenue', 'San Antonio', 'Makati City', 14.5547, 121.0244, 8, 80.00, 'low', 'compliant', 'active'),
('EST-2025-003', 'Grand Hotel Manila', 'workplace', 'hotel', 'Carlos Tan', '09191234567', 'carlos@grandhotel.com', NULL, 'BP-2025-1236', '2025-02-01', '2025-12-31', '789 Roxas Boulevard', 'Malate', 'Manila', 14.5764, 120.9822, 50, 500.00, 'high', 'non_compliant', 'active'),
('EST-2025-004', 'Fresh Market', 'public_space', 'market', 'City Government', '09201234567', 'market@lgucity.gov.ph', NULL, 'BP-2025-1237', '2025-01-10', '2025-12-31', 'Public Market Complex', 'Centro', 'Pasig City', 14.5764, 121.0851, 100, 2000.00, 'high', 'non_compliant', 'active'),
('EST-2025-005', 'HealthCare Clinic', 'healthcare_facility', 'clinic', 'Dr. Anna Cruz', '09211234567', 'anna@healthcareclinic.com', NULL, 'BP-2025-1238', '2025-02-15', '2025-12-31', '321 Health Street', 'San Juan', 'San Juan City', 14.6019, 121.0355, 12, 150.00, 'medium', 'compliant', 'active');

-- ============================================================================
-- 11. SAMPLE INSPECTIONS
-- ============================================================================

INSERT INTO inspections (reference_number, establishment_id, inspection_type, inspector_id, scheduled_date, status, priority, checklist_template_id, created_by) VALUES
('HSI-2025-001', 1, 'food_safety', 1, '2025-11-25', 'pending', 'medium', 1, 2),
('HSI-2025-002', 2, 'food_safety', 2, '2025-11-26', 'pending', 'low', 1, 2),
('HSI-2025-003', 3, 'fire_safety', 1, '2025-11-27', 'pending', 'high', NULL, 2),
('HSI-2025-004', 4, 'sanitation', 2, '2025-11-28', 'pending', 'urgent', NULL, 2),
('HSI-2025-005', 1, 'food_safety', 1, '2025-10-15', 'completed', 'medium', 1, 2);

-- Update last inspection for establishment
UPDATE establishments SET last_inspection_date = '2025-10-15', next_inspection_due = '2026-04-15' WHERE establishment_id = 1;

-- ============================================================================
-- 12. SAMPLE VIOLATIONS (from previous inspection)
-- ============================================================================

INSERT INTO violations (inspection_id, establishment_id, violation_code, category, description, severity, corrective_action_required, corrective_action_deadline, status, reported_by, reported_at) VALUES
(5, 1, 'FS-003', 'food_handling', 'One food handler observed without hairnet and gloves', 'major', 'Provide proper protective equipment to all food handlers and enforce usage policy', '2025-11-30', 'open', 3, '2025-10-15 14:30:00'),
(5, 1, 'SAN-003', 'sanitation', 'Handwashing station lacks paper towels', 'major', 'Ensure handwashing stations are always stocked with soap and paper towels', '2025-11-20', 'resolved', 3, '2025-10-15 14:45:00');

-- ============================================================================
-- 13. NOTIFICATION TEMPLATES
-- ============================================================================

INSERT INTO notification_templates (template_key, subject, body_template, sms_template, variables) VALUES
('inspection_reminder', 'Inspection Scheduled - {{establishment_name}}', 
'Dear {{inspector_name}},\n\nYou have been assigned to conduct a {{inspection_type}} inspection at:\n\n{{establishment_name}}\n{{establishment_address}}\n\nScheduled Date: {{scheduled_date}}\nInspection Reference: {{reference_number}}\n\nPlease login to the system to view full details.\n\nThank you.',
'Inspection scheduled: {{establishment_name}} on {{scheduled_date}}. Ref: {{reference_number}}',
'["inspector_name", "establishment_name", "establishment_address", "inspection_type", "scheduled_date", "reference_number"]'),

('violation_alert', 'Violation Reported - {{establishment_name}}', 
'Dear {{owner_name}},\n\nA {{severity}} violation has been reported during the recent inspection of your establishment:\n\nViolation: {{violation_description}}\nCorrective Action Required: {{corrective_action}}\nDeadline: {{deadline}}\n\nPlease address this violation by the deadline to avoid penalties.\n\nInspection Reference: {{reference_number}}',
'URGENT: {{severity}} violation reported at {{establishment_name}}. Deadline: {{deadline}}',
'["owner_name", "establishment_name", "severity", "violation_description", "corrective_action", "deadline", "reference_number"]'),

('certificate_expiry', 'Certificate Expiring Soon - {{certificate_type}}', 
'Dear {{owner_name}},\n\nYour {{certificate_type}} certificate is expiring soon:\n\nCertificate Number: {{certificate_number}}\nExpiry Date: {{expiry_date}}\n\nPlease schedule a renewal inspection to maintain compliance.\n\nContact us to schedule an inspection.',
'Certificate expiring: {{certificate_type}} expires on {{expiry_date}}',
'["owner_name", "certificate_type", "certificate_number", "expiry_date"]');

-- ============================================================================
-- 14. ANALYTICS SNAPSHOT (Sample)
-- ============================================================================

INSERT INTO analytics_snapshots (snapshot_date, total_establishments, active_establishments, total_inspections_ytd, compliance_rate, avg_violations_per_inspection, certificates_issued_ytd, critical_violations_count) VALUES
('2025-11-21', 5, 5, 5, 60.00, 0.40, 2, 0);

-- ============================================================================
-- END OF SEED DATA
-- ============================================================================
