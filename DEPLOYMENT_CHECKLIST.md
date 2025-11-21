# Deployment Checklist

## Pre-Deployment Checklist

### 1. Environment Configuration
- [ ] Create production `.env` file with secure credentials
- [ ] Generate strong JWT secret key (256-bit minimum)
- [ ] Configure production database credentials
- [ ] Set up email/SMS service credentials (if using external providers)
- [ ] Configure production domain and URLs

### 2. Database Setup
- [ ] Create production database
- [ ] Import schema: `mysql -u user -p database < database/migrations/schema.sql`
- [ ] Import seed data (if needed): `mysql -u user -p database < database/seeds/seeds.sql`
- [ ] Verify all tables created successfully
- [ ] Create database user with appropriate permissions
- [ ] Test database connection

### 3. Dependencies Installation
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Verify installations
php -r "require 'vendor/autoload.php'; echo 'Autoloader OK\n';"
```

### 4. Directory Permissions
```bash
# Create necessary directories
mkdir -p public/uploads/documents
mkdir -p public/uploads/photos
mkdir -p public/uploads/qrcodes
mkdir -p logs

# Set permissions
chmod 755 public/uploads/documents
chmod 755 public/uploads/photos
chmod 755 public/uploads/qrcodes
chmod 755 logs
chmod 644 .env
```

### 5. Security Hardening
- [ ] Disable directory listing in Apache/Nginx
- [ ] Configure HTTPS/SSL certificate
- [ ] Set secure headers (HSTS, CSP, X-Frame-Options)
- [ ] Enable CORS with whitelist only
- [ ] Configure rate limiting on authentication endpoints
- [ ] Disable PHP error display (`display_errors = Off`)
- [ ] Enable error logging (`log_errors = On`)
- [ ] Remove development files (tests/, .git/)

### 6. Apache/Nginx Configuration

#### Apache `.htaccess` (public directory)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/v1/(.*)$ index.php [QSA,L]

# Security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set X-Content-Type-Options "nosniff"
Header set Referrer-Policy "strict-origin-when-cross-origin"

# Disable directory listing
Options -Indexes

# Limit file upload size (adjust as needed)
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/HEALTHANDSAFETYINSPECTION/public;
    
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    # Disable access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

### 7. Testing
- [ ] Run unit tests: `composer test:unit`
- [ ] Run integration tests: `composer test:integration`
- [ ] Test API endpoints with Postman collection
- [ ] Verify authentication flow (register, login, logout)
- [ ] Test file uploads
- [ ] Verify email/SMS notifications
- [ ] Test webhook delivery
- [ ] Check RBAC permissions
- [ ] Test certificate generation and QR codes
- [ ] Verify database queries performance

### 8. Code Quality
- [ ] Run linting: `composer lint`
- [ ] Fix coding standard issues: `composer lint:fix`
- [ ] Review code for security vulnerabilities
- [ ] Check for SQL injection vulnerabilities
- [ ] Verify XSS prevention
- [ ] Test CSRF protection

## Deployment Steps

### 1. Backup Current System (if upgrading)
```bash
# Backup database
mysqldump -u user -p database > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/app
```

### 2. Deploy Code
```bash
# Upload files via FTP/SFTP or Git
git clone https://repository-url.git
cd HEALTHANDSAFETYINSPECTION

# Or use rsync
rsync -avz --exclude='.git' ./ user@server:/var/www/html/HEALTHANDSAFETYINSPECTION/
```

### 3. Configure Environment
```bash
# Copy and edit .env file
cp .env.example .env
nano .env

# Set proper permissions
chmod 644 .env
```

### 4. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 5. Database Migration
```bash
# Import schema
mysql -u user -p production_db < database/migrations/schema.sql

# Verify tables
mysql -u user -p production_db -e "SHOW TABLES;"
```

### 6. Test Deployment
- [ ] Access API health check endpoint
- [ ] Test login endpoint
- [ ] Verify database connectivity
- [ ] Check file upload functionality
- [ ] Test webhook delivery
- [ ] Review application logs

## Post-Deployment

### 1. Monitoring Setup
- [ ] Configure application monitoring (New Relic, DataDog, etc.)
- [ ] Set up error tracking (Sentry, Rollbar, etc.)
- [ ] Configure uptime monitoring
- [ ] Set up log aggregation
- [ ] Configure database performance monitoring

### 2. Backup Strategy
- [ ] Set up automated database backups (daily)
- [ ] Configure file backup schedule
- [ ] Test backup restoration process
- [ ] Document backup retention policy

### 3. Performance Optimization
- [ ] Enable PHP OPcache
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```
- [ ] Configure MySQL query cache
- [ ] Set up CDN for static assets (if applicable)
- [ ] Enable gzip compression
- [ ] Optimize database indexes

### 4. Security Measures
- [ ] Change default database passwords
- [ ] Rotate JWT secret keys regularly
- [ ] Configure fail2ban for brute force protection
- [ ] Set up WAF (Web Application Firewall)
- [ ] Enable SSL/TLS encryption
- [ ] Configure security monitoring alerts

### 5. Documentation
- [ ] Update API documentation with production URLs
- [ ] Document deployment process
- [ ] Create runbook for common issues
- [ ] Document disaster recovery procedures
- [ ] Train support staff on system usage

## Rollback Plan

### If Deployment Fails
1. Restore database from backup:
```bash
mysql -u user -p production_db < backup_YYYYMMDD_HHMMSS.sql
```

2. Restore previous code version:
```bash
git checkout previous-stable-tag
composer install --no-dev
```

3. Verify system functionality
4. Notify stakeholders

## Maintenance Schedule

### Daily
- Monitor application logs
- Check error rates
- Review system performance metrics

### Weekly
- Review and archive old logs
- Check disk space usage
- Review backup success/failures

### Monthly
- Update dependencies (security patches)
- Review and optimize slow queries
- Clean up old data (notifications, logs)
- Security audit

### Quarterly
- Performance review and optimization
- Disaster recovery drill
- Security penetration testing
- User access audit

## Support Contacts

- **System Administrator**: admin@yourdomain.com
- **Database Administrator**: dba@yourdomain.com
- **Security Team**: security@yourdomain.com
- **On-Call Support**: +63-XXX-XXX-XXXX

## Critical Files

- `.env` - Environment configuration
- `composer.json` - Dependencies
- `database/migrations/schema.sql` - Database schema
- `config/database.php` - Database connection
- `src/Routes/api.php` - API routes
- `public/.htaccess` - Apache configuration

## Emergency Procedures

### Database Connection Lost
1. Check database server status
2. Verify credentials in `.env`
3. Check network connectivity
4. Review MySQL error logs
5. Restart MySQL service if needed

### High Server Load
1. Identify slow queries
2. Check for DDoS attack
3. Review application logs
4. Scale resources if needed
5. Enable caching

### Security Breach
1. Immediately change all passwords
2. Rotate JWT secrets
3. Review access logs
4. Identify breach point
5. Notify affected users
6. Implement additional security measures

## Sign-Off

- [ ] Technical Lead: _________________ Date: _______
- [ ] QA Lead: _________________ Date: _______
- [ ] Security Officer: _________________ Date: _______
- [ ] Project Manager: _________________ Date: _______

---

**Deployment Date**: _______________
**Version**: 1.0.0
**Deployed By**: _______________
