# Health & Safety Inspections System - API Documentation

## Base URL
```
http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1
```

## Authentication
All protected endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer {your_jwt_token}
```

---

## Authentication Endpoints

### POST /auth/login
Login and get access token

**Request:**
```json
{
    "username": "juan.delacruz",
    "password": "SecurePass123!",
    "remember_me": true
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "user_id": 5,
            "username": "juan.delacruz",
            "email": "juan@lgu.gov.ph",
            "first_name": "Juan",
            "last_name": "Dela Cruz",
            "role": "inspector",
            "permissions": ["inspections.create", "inspections.read"]
        },
        "token": {
            "access_token": "eyJhbGciOiJIUzI1NiIs...",
            "token_type": "Bearer",
            "expires_in": 3600
        }
    }
}
```

### POST /auth/register
Register new user

### POST /auth/logout
Logout and invalidate token

### POST /auth/forgot-password
Request password reset

### POST /auth/reset-password
Reset password with token

### GET /auth/me
Get current user profile

---

## Inspection Endpoints

### GET /inspections
List all inspections with pagination and filters

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Results per page (default: 20)
- `status` (string): Filter by status (pending|in_progress|completed|cancelled)
- `inspection_type` (string): Filter by type
- `inspector_id` (int): Filter by inspector
- `establishment_id` (int): Filter by establishment
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date

**Response:**
```json
{
    "success": true,
    "data": {
        "data": [...],
        "pagination": {
            "total": 247,
            "page": 1,
            "per_page": 20,
            "total_pages": 13
        }
    }
}
```

### GET /inspections/{id}
Get single inspection details

### POST /inspections
Create new inspection

**Required Permissions:** `inspections.create`

**Request:**
```json
{
    "establishment_id": 15,
    "inspection_type": "food_safety",
    "scheduled_date": "2025-11-25",
    "inspector_id": 3,
    "priority": "high",
    "checklist_template_id": 2
}
```

### PUT /inspections/{id}
Update inspection

### POST /inspections/{id}/start
Start inspection

### POST /inspections/{id}/complete
Complete inspection

**Request:**
```json
{
    "overall_rating": "satisfactory",
    "inspector_notes": "All requirements met except minor sanitation issue."
}
```

### POST /inspections/{id}/upload-photo
Upload inspection photo (multipart/form-data)

### POST /inspections/{id}/checklist-response
Submit checklist responses

**Request:**
```json
{
    "responses": [
        {
            "checklist_item_id": 10,
            "response": "pass",
            "notes": "Food storage temperature properly maintained"
        },
        {
            "checklist_item_id": 11,
            "response": "fail",
            "notes": "Hand washing station lacks soap",
            "evidence_photos": ["/uploads/photo1.jpg"]
        }
    ]
}
```

### GET /inspections/schedule
Get inspection calendar/schedule

**Query Parameters:**
- `start_date` (date): Start date
- `end_date` (date): End date
- `inspector_id` (int): Filter by inspector

---

## Establishment Endpoints

### GET /establishments
List all establishments

**Query Parameters:**
- `type` (string): Filter by establishment type
- `compliance_status` (string): Filter by compliance status
- `risk_category` (string): Filter by risk category
- `barangay` (string): Filter by barangay
- `search` (string): Search by name, owner, or permit number

### GET /establishments/{id}
Get establishment details (includes contacts, permits, inspection history)

### POST /establishments
Register new establishment

**Required Permissions:** `establishments.create`

**Request:**
```json
{
    "name": "ABC Restaurant",
    "type": "food_establishment",
    "subtype": "restaurant",
    "owner_name": "Maria Santos",
    "owner_contact": "+63 912 345 6789",
    "owner_email": "maria@abcrestaurant.com",
    "manager_name": "Juan Cruz",
    "manager_contact": "+63 918 765 4321",
    "business_permit_number": "BP-2025-12345",
    "permit_issue_date": "2025-01-15",
    "permit_expiry_date": "2025-12-31",
    "address_street": "123 Main Street",
    "address_barangay": "San Isidro",
    "address_city": "Quezon City",
    "address_postal_code": "1100",
    "gps_latitude": 14.6760,
    "gps_longitude": 121.0437,
    "employee_count": 15,
    "floor_area_sqm": 150,
    "operating_hours": {
        "monday": "10:00-22:00",
        "tuesday": "10:00-22:00"
    },
    "risk_category": "medium"
}
```

### PUT /establishments/{id}
Update establishment

### POST /establishments/{id}/contacts
Add contact person

### POST /establishments/{id}/permits
Add permit

### GET /establishments/map
Get establishments for map view (returns only establishments with GPS coordinates)

---

## Violation Endpoints

### GET /violations
List all violations

**Query Parameters:**
- `status` (string): Filter by status (open|in_progress|resolved|waived)
- `severity` (string): Filter by severity (minor|major|critical)
- `establishment_id` (int): Filter by establishment

### POST /violations
Report new violation

**Required Permissions:** `violations.create`

**Request:**
```json
{
    "inspection_id": 45,
    "establishment_id": 15,
    "violation_code": "FS-001",
    "category": "food_handling",
    "description": "Improper food storage temperature",
    "severity": "major",
    "corrective_action_required": "Install proper refrigeration unit",
    "corrective_action_deadline": "2025-12-15",
    "evidence_photos": ["/uploads/violation1.jpg", "/uploads/violation2.jpg"]
}
```

### POST /violations/{id}/resolve
Mark violation as resolved

**Request:**
```json
{
    "resolution_notes": "Refrigeration unit installed and verified working"
}
```

---

## Certificate Endpoints

### GET /certificates
List all certificates

**Query Parameters:**
- `status` (string): Filter by status (valid|expired|suspended|revoked)
- `establishment_id` (int): Filter by establishment

### POST /certificates
Issue new certificate

**Required Permissions:** `certificates.issue`

**Request:**
```json
{
    "establishment_id": 15,
    "inspection_id": 45,
    "certificate_type": "food_safety",
    "expiry_date": "2026-11-21",
    "conditions": [
        "Valid for dine-in and takeout operations only",
        "Subject to quarterly inspections"
    ]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "certificate": {
            "certificate_id": 89,
            "certificate_number": "CERT-2025-000089",
            "establishment_name": "ABC Restaurant",
            "certificate_type": "food_safety",
            "issue_date": "2025-11-21",
            "expiry_date": "2026-11-21",
            "status": "valid",
            "qr_code_data": "eyJjZXJ0aWZpY2F0ZV9udW1iZXIiOi..."
        },
        "message": "Certificate issued successfully"
    }
}
```

### GET /certificates/{id}
Get certificate details

### GET /certificates/verify/{certificate_number}
Verify certificate (PUBLIC - no authentication required)

**Response:**
```json
{
    "success": true,
    "data": {
        "certificate": {
            "certificate_number": "CERT-2025-000089",
            "establishment_name": "ABC Restaurant",
            "certificate_type": "food_safety",
            "issue_date": "2025-11-21",
            "expiry_date": "2026-11-21",
            "status": "valid"
        },
        "is_valid": true
    }
}
```

---

## Analytics Endpoints

### GET /analytics/dashboard
Get dashboard metrics

**Required Permissions:** `analytics.read`

**Response:**
```json
{
    "success": true,
    "data": {
        "metrics": {
            "inspections": {
                "total": 247,
                "pending": 18,
                "in_progress": 5,
                "completed": 224,
                "this_year": 247
            },
            "establishments": {
                "total": 156,
                "active": 150,
                "compliant": 142,
                "high_risk": 8
            },
            "violations": {
                "total": 45,
                "open": 5,
                "critical": 2,
                "major": 12
            },
            "certificates": {
                "total": 89,
                "valid": 85,
                "this_month": 15,
                "expiring_soon": 3
            }
        }
    }
}
```

### GET /analytics/inspection-trends
Get inspection trends over time

**Query Parameters:**
- `months` (int): Number of months to include (default: 6)

### GET /analytics/violations-by-category
Get violation statistics by category

### GET /analytics/compliance-report
Generate compliance report

**Required Permissions:** `reports.generate`

**Query Parameters:**
- `start_date` (date): Report start date
- `end_date` (date): Report end date

---

## Error Responses

All errors follow this format:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message",
        "details": {
            "field_name": ["Validation error message"]
        }
    },
    "meta": {
        "timestamp": "2025-11-21T10:30:00Z"
    }
}
```

### Common Error Codes
- `VALIDATION_ERROR` (400): Invalid input data
- `UNAUTHORIZED` (401): Authentication required
- `FORBIDDEN` (403): Insufficient permissions
- `NOT_FOUND` (404): Resource not found
- `ACCOUNT_LOCKED` (429): Too many failed login attempts
- `INTERNAL_ERROR` (500): Server error

---

## Permission Matrix

| Permission | Super Admin | Admin | Senior Inspector | Inspector | Establishment Owner | Public |
|-----------|-------------|-------|------------------|-----------|---------------------|--------|
| inspections.create | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| inspections.read | ✅ | ✅ | ✅ | ✅ | ✅ (own) | ❌ |
| inspections.update | ✅ | ✅ | ✅ | ✅ (own) | ❌ | ❌ |
| establishments.create | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| establishments.read | ✅ | ✅ | ✅ | ✅ | ✅ (own) | ❌ |
| establishments.update | ✅ | ✅ | ✅ | ❌ | ✅ (own) | ❌ |
| violations.create | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| violations.read | ✅ | ✅ | ✅ | ❌ | ✅ (own) | ❌ |
| violations.update | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| certificates.issue | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| certificates.read | ✅ | ✅ | ✅ | ✅ | ✅ (own) | ❌ |
| analytics.read | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| reports.generate | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## Rate Limiting
- **Limit:** 100 requests per minute per user
- **Headers:**
  - `X-RateLimit-Limit`: Total requests allowed
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Time when limit resets (Unix timestamp)

---

## Postman Collection
Import the Postman collection file: `HEALTHANDSAFETYINSPECTION.postman_collection.json`

---

## Support
For issues and questions, contact the LGU IT Department.

**Version:** 1.0  
**Last Updated:** November 21, 2025
