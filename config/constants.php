<?php

declare(strict_types=1);

/**
 * System Constants
 * Health & Safety Inspections System
 */

// Application Constants
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Health & Safety Inspections');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH', ROOT_PATH . '/cache');

// User Roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_SENIOR_INSPECTOR', 'senior_inspector');
define('ROLE_INSPECTOR', 'inspector');
define('ROLE_ESTABLISHMENT_OWNER', 'establishment_owner');
define('ROLE_PUBLIC', 'public');

// Inspection Types
define('INSPECTION_TYPE_FOOD_SAFETY', 'food_safety');
define('INSPECTION_TYPE_BUILDING_SAFETY', 'building_safety');
define('INSPECTION_TYPE_WORKPLACE_SAFETY', 'workplace_safety');
define('INSPECTION_TYPE_FIRE_SAFETY', 'fire_safety');
define('INSPECTION_TYPE_SANITATION', 'sanitation');

// Inspection Status
define('INSPECTION_STATUS_PENDING', 'pending');
define('INSPECTION_STATUS_IN_PROGRESS', 'in_progress');
define('INSPECTION_STATUS_COMPLETED', 'completed');
define('INSPECTION_STATUS_CANCELLED', 'cancelled');
define('INSPECTION_STATUS_FAILED', 'failed');

// Establishment Types
define('ESTABLISHMENT_TYPE_FOOD', 'food_establishment');
define('ESTABLISHMENT_TYPE_BUILDING', 'building');
define('ESTABLISHMENT_TYPE_WORKPLACE', 'workplace');
define('ESTABLISHMENT_TYPE_PUBLIC_SPACE', 'public_space');
define('ESTABLISHMENT_TYPE_HEALTHCARE', 'healthcare_facility');

// Violation Severity
define('VIOLATION_SEVERITY_MINOR', 'minor');
define('VIOLATION_SEVERITY_MAJOR', 'major');
define('VIOLATION_SEVERITY_CRITICAL', 'critical');

// Violation Status
define('VIOLATION_STATUS_OPEN', 'open');
define('VIOLATION_STATUS_IN_PROGRESS', 'in_progress');
define('VIOLATION_STATUS_RESOLVED', 'resolved');
define('VIOLATION_STATUS_WAIVED', 'waived');

// Certificate Types
define('CERTIFICATE_TYPE_FOOD_SAFETY', 'food_safety');
define('CERTIFICATE_TYPE_FIRE_SAFETY', 'fire_safety');
define('CERTIFICATE_TYPE_BUILDING_OCCUPANCY', 'building_occupancy');
define('CERTIFICATE_TYPE_SANITARY_PERMIT', 'sanitary_permit');

// Certificate Status
define('CERTIFICATE_STATUS_VALID', 'valid');
define('CERTIFICATE_STATUS_EXPIRED', 'expired');
define('CERTIFICATE_STATUS_SUSPENDED', 'suspended');
define('CERTIFICATE_STATUS_REVOKED', 'revoked');

// Priority Levels
define('PRIORITY_LOW', 'low');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_HIGH', 'high');
define('PRIORITY_URGENT', 'urgent');

// Risk Categories
define('RISK_CATEGORY_LOW', 'low');
define('RISK_CATEGORY_MEDIUM', 'medium');
define('RISK_CATEGORY_HIGH', 'high');

// Compliance Status
define('COMPLIANCE_STATUS_COMPLIANT', 'compliant');
define('COMPLIANCE_STATUS_NON_COMPLIANT', 'non_compliant');
define('COMPLIANCE_STATUS_SUSPENDED', 'suspended');
define('COMPLIANCE_STATUS_REVOKED', 'revoked');

// File Upload
define('MAX_FILE_SIZE', (int)($_ENV['MAX_FILE_SIZE'] ?? 10485760)); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Rate Limiting
define('RATE_LIMIT_REQUESTS', (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100));
define('RATE_LIMIT_WINDOW', (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60)); // seconds

// Session
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600)); // 1 hour
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'HSI_SESSION');

// JWT
define('JWT_EXPIRATION', (int)($_ENV['JWT_EXPIRATION'] ?? 3600)); // 1 hour
define('JWT_REFRESH_EXPIRATION', 604800); // 7 days

// Password Requirements
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Account Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCKOUT_DURATION', 1800); // 30 minutes

// Date/Time Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F j, Y');
define('DISPLAY_DATETIME_FORMAT', 'F j, Y g:i A');

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_UNPROCESSABLE', 422);
define('HTTP_TOO_MANY_REQUESTS', 429);
define('HTTP_SERVER_ERROR', 500);

// Error Codes
define('ERROR_VALIDATION', 'VALIDATION_ERROR');
define('ERROR_UNAUTHORIZED', 'UNAUTHORIZED');
define('ERROR_FORBIDDEN', 'FORBIDDEN');
define('ERROR_NOT_FOUND', 'NOT_FOUND');
define('ERROR_INVALID_CREDENTIALS', 'INVALID_CREDENTIALS');
define('ERROR_ACCOUNT_LOCKED', 'ACCOUNT_LOCKED');
define('ERROR_ACCOUNT_INACTIVE', 'ACCOUNT_INACTIVE');
define('ERROR_TOKEN_EXPIRED', 'TOKEN_EXPIRED');
define('ERROR_RATE_LIMIT', 'RATE_LIMIT_EXCEEDED');
define('ERROR_SERVER', 'SERVER_ERROR');

// Notification Types
define('NOTIFICATION_INSPECTION_REMINDER', 'inspection_reminder');
define('NOTIFICATION_VIOLATION_ALERT', 'violation_alert');
define('NOTIFICATION_CERTIFICATE_EXPIRY', 'certificate_expiry');
define('NOTIFICATION_ASSIGNMENT', 'assignment');
define('NOTIFICATION_FOLLOW_UP', 'follow_up');
