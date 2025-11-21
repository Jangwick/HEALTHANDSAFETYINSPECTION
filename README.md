# Health & Safety Inspections System

A comprehensive digital platform for Local Government Units (LGUs) in the Philippines to manage establishment inspections, compliance monitoring, and health/safety certification issuance.

## 🚀 Features

- **Authentication & User Management**: Secure login with JWT tokens and RBAC
- **Inspection Management**: Schedule, conduct, and track inspections
- **Establishment Registry**: Centralized database of all establishments
- **Violations & Findings**: Document and track compliance violations
- **Certification & Permitting**: Issue and verify digital certificates
- **Checklist & Templates**: Standardized inspection checklists
- **Inspector Management**: Manage inspector profiles and schedules
- **Analytics & Reporting**: Data-driven insights and compliance trends
- **Integration Hub**: Connect with other public safety modules
- **Notifications**: Automated alerts via email/SMS
- **Document Management**: Store and organize inspection documents

## 🛠️ Technology Stack

- **Backend**: PHP 8.2+ with PDO (MySQL)
- **Frontend**: HTML5 + Tailwind CSS v4 + Vanilla JavaScript
- **Charts**: Chart.js
- **Database**: MySQL 8.0+
- **Authentication**: PHP Sessions + JWT tokens
- **Architecture**: MVC with Service Layer

## 📋 Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (optional, for dependency management)
- XAMPP/WAMP/LAMP stack (recommended for local development)

## 🔧 Installation

### 1. Clone or Download

```bash
cd c:\xampp\htdocs
# Your files are already in HEALTHANDSAFETYINSPECTION folder
```

### 2. Configure Environment

```bash
# Copy the example environment file
copy .env.example .env

# Edit .env file and update database credentials
# DB_HOST=localhost
# DB_NAME=health_safety_inspections
# DB_USER=root
# DB_PASS=
```

### 3. Create Database

Open phpMyAdmin or MySQL command line:

```sql
CREATE DATABASE health_safety_inspections CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run Migrations

```sql
# In MySQL or phpMyAdmin, run the migration file:
# database/migrations/001_create_tables.sql
```

### 5. Seed Initial Data

```sql
# Run the seed file:
# database/seeds/001_initial_data.sql
```

### 6. Set Permissions

```bash
# Ensure these directories are writable:
chmod -R 755 logs/
chmod -R 755 public/uploads/
chmod -R 755 cache/
```

For Windows (XAMPP):
- Right-click on `logs`, `public/uploads`, and `cache` folders
- Properties → Security → Edit → Add write permissions for IUSR and IIS_IUSRS

### 7. Configure Virtual Host (Optional)

Add to Apache `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName healthsafety.local
    DocumentRoot "c:/xampp/htdocs/HEALTHANDSAFETYINSPECTION/public"
    
    <Directory "c:/xampp/htdocs/HEALTHANDSAFETYINSPECTION/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to Windows `hosts` file (`C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1    healthsafety.local
```

### 8. Access the Application

```
http://localhost/HEALTHANDSAFETYINSPECTION/public
```

Or if using virtual host:
```
http://healthsafety.local
```

## 👤 Default Users

After seeding, you can login with:

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| `superadmin` | `Admin@123` | Super Admin | Full system access |
| `admin` | `Admin@123` | Admin | Module administration |
| `juan.delacruz` | `Admin@123` | Senior Inspector | Lead inspector |
| `maria.santos` | `Admin@123` | Inspector | Field inspector |
| `pedro.reyes` | `Admin@123` | Establishment Owner | Business owner |

**⚠️ IMPORTANT**: Change these passwords immediately in production!

## 📁 Project Structure

```
HEALTHANDSAFETYINSPECTION/
├── config/
│   ├── bootstrap.php       # Application bootstrap
│   ├── constants.php       # System constants
│   ├── database.php        # Database connection
│   └── jwt.php            # JWT configuration
├── database/
│   ├── migrations/        # Database schema
│   └── seeds/             # Seed data
├── logs/                  # Application logs
├── public/
│   ├── assets/           # Static assets
│   ├── uploads/          # File uploads
│   ├── views/            # HTML views
│   ├── .htaccess         # Rewrite rules
│   └── index.php         # Entry point
├── src/
│   ├── Controllers/      # Request handlers
│   ├── Middleware/       # Auth/RBAC middleware
│   ├── Models/           # Data models
│   ├── Routes/           # API/Web routes
│   ├── Services/         # Business logic
│   └── Utils/            # Helper classes
├── .env.example          # Environment template
├── .gitignore
└── README.md
```

## 🔐 API Authentication

All protected API endpoints require a Bearer token:

```bash
# Login to get token
curl -X POST http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Admin@123"}'

# Use token in subsequent requests
curl -X GET http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/inspections \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 📚 API Documentation

### Health Check
```
GET /api/v1/health
```

### Authentication
```
POST /api/v1/auth/login
POST /api/v1/auth/register
POST /api/v1/auth/logout
GET  /api/v1/auth/me
PUT  /api/v1/auth/profile
```

### Inspections
```
GET    /api/v1/inspections
POST   /api/v1/inspections
GET    /api/v1/inspections/{id}
PUT    /api/v1/inspections/{id}
POST   /api/v1/inspections/{id}/start
POST   /api/v1/inspections/{id}/complete
```

### Establishments
```
GET    /api/v1/establishments
POST   /api/v1/establishments
GET    /api/v1/establishments/{id}
PUT    /api/v1/establishments/{id}
```

Full API documentation: [Coming soon]

## 🧪 Testing

### Manual Testing
1. Access health check: `http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1/health`
2. Login with default credentials
3. Test creating an inspection
4. Upload photos to an inspection
5. Generate a certificate

### Database Testing
```sql
-- Check if tables were created
SHOW TABLES;

-- Check sample data
SELECT * FROM users;
SELECT * FROM establishments;
SELECT * FROM inspections;
```

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ JWT token authentication
- ✅ Role-based access control (RBAC)
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ CSRF token validation
- ✅ Rate limiting
- ✅ Account lockout after failed attempts
- ✅ Audit logging

## 📊 Role Hierarchy

1. **Super Admin** - Full system access
2. **Admin** - Module management
3. **Senior Inspector** - Lead inspector with assignment powers
4. **Inspector** - Field inspector
5. **Establishment Owner** - View own data
6. **Public** - Certificate verification only

## 🚧 Troubleshooting

### Database Connection Error
- Check `.env` file credentials
- Ensure MySQL is running
- Verify database exists

### 404 Not Found
- Check `.htaccess` files exist
- Enable `mod_rewrite` in Apache
- Verify document root path

### Permission Denied
- Check folder permissions on `logs/`, `uploads/`, `cache/`
- On Windows, ensure IIS/Apache user has write access

### JWT Token Errors
- Check `JWT_SECRET_KEY` in `.env`
- Ensure token is passed in `Authorization: Bearer TOKEN` header

## 📝 License

This project is developed for Local Government Units in the Philippines.

## 👥 Support

For support, contact your system administrator or LGU IT department.

---

**Version**: 1.0.0  
**Last Updated**: November 21, 2025
