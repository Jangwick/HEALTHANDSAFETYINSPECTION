# API Testing Guide

## Overview
This guide provides comprehensive instructions for testing all API endpoints in the Health & Safety Inspection System.

## Prerequisites

### 1. Environment Setup
- XAMPP with PHP 8.2+ installed
- MySQL 8.0+ database running
- Apache server started

### 2. Database Setup
```bash
# Import database schema
mysql -u root -p healthinspection < database/migrations/schema.sql

# Import seed data
mysql -u root -p healthinspection < database/seeds/seeds.sql
```

### 3. Configuration
Ensure `.env` file is configured:
```env
DB_HOST=localhost
DB_NAME=healthinspection
DB_USER=root
DB_PASS=
JWT_SECRET=your-256-bit-secret-key-here
MAIL_FROM=noreply@healthinspection.local
```

## Using Postman Collection

### Import Collection
1. Open Postman
2. Click **Import** button
3. Select `postman_collection.json`
4. Collection will be imported with all endpoints

### Configure Environment Variables
1. Create new environment in Postman
2. Add variables:
   - `base_url`: `http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1`
   - `jwt_token`: (will be auto-filled after login)

### Authentication Flow
1. **Login Request**:
   ```
   POST {{base_url}}/auth/login
   Body: {"email": "admin@example.com", "password": "password123"}
   ```

2. Copy JWT token from response
3. Set `jwt_token` environment variable
4. All subsequent requests will use this token

## Manual Testing with cURL

### Authentication Endpoints

#### 1. Login
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "full_name": "System Administrator",
      "role": "admin"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

#### 2. Register
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newuser@example.com",
    "password": "SecurePass123!",
    "full_name": "New User",
    "role_id": 4,
    "contact_number": "+639171234567"
  }'
```

#### 3. Get Current User
```bash
curl -X GET http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Inspection Endpoints

#### 1. List Inspections
```bash
curl -X GET "http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/inspections?page=1&per_page=20&status=scheduled" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### 2. Create Inspection
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/inspections \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "establishment_id": 1,
    "inspector_id": 2,
    "inspection_type": "routine",
    "scheduled_date": "2024-02-15 10:00:00",
    "checklist_id": 1
  }'
```

#### 3. Start Inspection
```bash
curl -X PATCH http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/inspections/1/start \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### 4. Complete Inspection
```bash
curl -X PATCH http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/inspections/1/complete \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "result": "passed",
    "score": 95,
    "notes": "All requirements met"
  }'
```

### Establishment Endpoints

#### 1. Create Establishment
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/establishments \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sample Restaurant",
    "type": "restaurant",
    "address": "123 Main Street",
    "city": "Manila",
    "barangay": "Ermita",
    "owner_name": "John Doe",
    "contact_number": "+639171234567",
    "email": "owner@restaurant.com",
    "latitude": 14.5995,
    "longitude": 120.9842
  }'
```

#### 2. List Establishments
```bash
curl -X GET "http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/establishments?page=1&type=restaurant" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### 3. Map View
```bash
curl -X GET "http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/establishments/map?latitude=14.5995&longitude=120.9842&radius=5" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Violation Endpoints

#### 1. Create Violation
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/violations \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "inspection_id": 1,
    "establishment_id": 1,
    "violation_type": "sanitation",
    "severity": "medium",
    "description": "Inadequate food storage",
    "corrective_action": "Install proper refrigeration"
  }'
```

#### 2. Resolve Violation
```bash
curl -X PATCH http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/violations/1/resolve \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "resolution_notes": "Refrigeration unit installed and inspected"
  }'
```

### Certificate Endpoints

#### 1. Issue Certificate
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/certificates \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "inspection_id": 1,
    "establishment_id": 1,
    "certificate_type": "health_permit",
    "valid_from": "2024-01-01",
    "valid_until": "2024-12-31"
  }'
```

#### 2. Verify Certificate (Public - No Auth Required)
```bash
curl -X GET http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/certificates/verify/CERT-2024-000001
```

### Document Upload

#### Upload Document
```bash
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/documents \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "file=@/path/to/document.pdf" \
  -F "entity_type=inspection" \
  -F "entity_id=1" \
  -F "title=Inspection Report"
```

## Testing Checklist

### ✅ Authentication Tests
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (should fail)
- [ ] Register new user
- [ ] Get current user profile
- [ ] Logout
- [ ] Access protected endpoint without token (should fail)

### ✅ Inspection Tests
- [ ] List all inspections
- [ ] Filter inspections by status
- [ ] Create new inspection
- [ ] Get inspection details
- [ ] Start inspection
- [ ] Complete inspection with results
- [ ] Upload inspection photos
- [ ] Submit checklist responses

### ✅ Establishment Tests
- [ ] Create new establishment
- [ ] List establishments with pagination
- [ ] Filter establishments by type
- [ ] Get establishment details with history
- [ ] Update establishment info
- [ ] Add contact person
- [ ] Add permit
- [ ] Map view with GPS filtering

### ✅ Violation Tests
- [ ] Create violation during inspection
- [ ] List violations by establishment
- [ ] Filter violations by severity
- [ ] Resolve violation
- [ ] Upload violation photos

### ✅ Certificate Tests
- [ ] Issue certificate after passed inspection
- [ ] List certificates by establishment
- [ ] Verify certificate using reference number (public)
- [ ] Check certificate expiration

### ✅ Analytics Tests
- [ ] Get dashboard metrics
- [ ] Inspection trends (monthly)
- [ ] Violations by category
- [ ] Compliance report

### ✅ Checklist Tests
- [ ] Create new checklist template
- [ ] List checklists by category
- [ ] Get checklist with items
- [ ] Update checklist
- [ ] Archive checklist

### ✅ Inspector Tests
- [ ] List inspectors
- [ ] Get available inspectors for date
- [ ] Filter by specialization
- [ ] Get inspector performance metrics
- [ ] Manage inspector schedule

### ✅ Notification Tests
- [ ] Get user notifications
- [ ] Mark notification as read
- [ ] Mark all as read
- [ ] Get unread count
- [ ] Update notification preferences

### ✅ Document Tests
- [ ] Upload document (PDF, images, etc)
- [ ] List documents by entity
- [ ] Download document
- [ ] Update document metadata
- [ ] Delete document
- [ ] View access logs

### ✅ Integration Tests
- [ ] Generate API key
- [ ] List API keys
- [ ] Revoke API key
- [ ] Register webhook
- [ ] Trigger webhook manually
- [ ] View webhook delivery logs
- [ ] View integration logs

## Common Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "email": "Email is required",
    "password": "Password must be at least 8 characters"
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "error": "Invalid or expired token"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "error": "Insufficient permissions"
}
```

### 404 Not Found
```json
{
  "success": false,
  "error": "Resource not found"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "error": "An internal error occurred"
}
```

## Performance Testing

### Load Testing with Apache Bench
```bash
# Test login endpoint
ab -n 100 -c 10 -p login.json -T application/json \
  http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/auth/login

# Test inspection listing
ab -n 1000 -c 50 -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/inspections
```

### Stress Testing Recommendations
- Test with 100+ concurrent users
- Monitor database query performance
- Check memory usage during file uploads
- Test webhook delivery under load

## Security Testing

### Security Checklist
- [ ] SQL injection prevention (test with `' OR '1'='1`)
- [ ] XSS prevention (test with `<script>alert('xss')</script>`)
- [ ] CSRF token validation
- [ ] JWT expiration handling
- [ ] File upload validation (attempt dangerous file types)
- [ ] Rate limiting on authentication endpoints
- [ ] Password strength requirements
- [ ] Role-based access control (RBAC)

## Automated Testing

See `tests/` directory for PHPUnit test suites:
- `tests/Unit/` - Unit tests for services
- `tests/Integration/` - Integration tests for API flows
- `tests/Feature/` - End-to-end feature tests

Run tests:
```bash
./vendor/bin/phpunit tests/
```

## Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check MySQL is running
   - Verify credentials in `.env`
   - Ensure database exists

2. **"JWT token invalid"**
   - Token may be expired (1 hour lifetime)
   - Re-login to get fresh token
   - Check JWT_SECRET is configured

3. **"File upload failed"**
   - Check `public/uploads/` directory permissions (755)
   - Verify file size limits in `php.ini`
   - Ensure allowed file types

4. **"Webhook delivery failed"**
   - Check webhook URL is accessible
   - Verify firewall settings
   - Check webhook logs for errors

## Support

For issues or questions:
- Review API documentation: `API_DOCUMENTATION.md`
- Check application logs: `logs/app.log`
- Inspect database queries: Enable query logging in PDO
