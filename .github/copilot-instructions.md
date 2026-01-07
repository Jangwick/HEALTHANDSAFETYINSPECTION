# Health & Safety Inspection System - AI Coding Instructions

## Architecture & Patterns
- **MVC + Service Layer**: Logic resides in `src/Services/`. Controllers in `src/Controllers/` handle requests and responses.
- **Namespacing**: Use `HealthSafety\` namespace for all new classes (aliased to `src/`). `App\` is also supported but `HealthSafety\` is preferred for consistency with existing code.
- **Routing**:
    - **API**: Defined in `src/Routes/api.php` using an associative array `['METHOD /path' => ['Controller', 'method']]`.
    - **Web**: Defined in `src/Routes/web.php`. Views are located in `public/views/`.
- **Database**: Use direct `PDO` for queries. Services should receive `PDO` via constructor injection. Follow the pattern in `src/Services/AuthService.php`.
- **Validation**: Use `src/Utils/Validator.php`. Controllers should validate input before calling services.
- **API Responses**: Always use `HealthSafety\Utils\Response::success()` or `Response::error()` for API consistency.

## Tech Stack & Conventions
- **PHP**: Version 8.2+ with strict types (`declare(strict_types=1);`).
- **Database**: MySQL 8.0+. Migrations in `database/migrations/`, Seeds in `database/seeds/`.
- **CSS**: Tailwind CSS v4 is used in web views.
- **Error Handling**: Uses global exception/error handlers in `config/bootstrap.php`. API errors return structured JSON.

## Critical Developer Workflows
- **Environment**: Config via `.env`. Key constants defined in `config/constants.php`.
- **Development Server**: Use PHP built-in server with router: `php -S localhost:8000 -t public public/router.php`.
- **Testing**: Run tests via `./vendor/bin/phpunit`. Test configuration in `phpunit.xml`.
- **Directory Permissions**: `logs/`, `public/uploads/`, and `cache/` must be writable.

## Integration & Dependencies
- **JWT**: Managed via `firebase/php-jwt` in `src/Utils/JWTHandler.php`.
- **QR Codes**: Generated using `endroid/qr-code` for certificate verification.
- **Integration Hub**: `src/Controllers/IntegrationController.php` and `IntegrationService.php` handle external communication with other public safety modules.

## Key Files & Directories
- `src/Services/`: Business logic and database operations.
- `src/Controllers/`: Request handling and orchestration.
- `src/Utils/`: Shared utilities (`Response`, `Logger`, `Validator`).
- `public/index.php`: Main entry point.
- `config/`: Application configuration and bootstrap logic.
- `public/views/`: PHP-based UI templates.

## Code Style Examples
### Controller Pattern
```php
public function create(array $data): void {
    $errors = $this->validator->validate($data, ['name' => ['required']]);
    if ($errors) Response::error('VALIDATION_ERROR', 'Invalid data', $errors);
    $result = $this->service->create($data);
    Response::success($result, 'Created successfully', 201);
}
```

### Service Pattern
```php
public function __construct(PDO $pdo, Logger $logger) {
    $this->pdo = $pdo;
    $this->logger = $logger;
}
```
