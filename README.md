# Survey Management System

A modern, scalable Laravel-based survey management application built. Create, manage, and analyze surveys with a powerful web interface and comprehensive API.

## Requirements

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8.0+
- Docker (optional)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/juandiegozb/surveys-backend.git
cd surveys-backend
```

### 2. Quick Setup

Use our Makefile for easy installation:

```bash
make install
```

This command will:
- Install PHP dependencies via Composer
- Install Node.js dependencies via npm
- Copy environment configuration
- Generate application key
- Run database migrations
- Seed the database with sample data
- Build frontend assets

### 3. Start Development Server

```bash
make dev
```

This starts the Laravel development server and Vite for asset compilation.

### 4. Run Tests (Optional)

In a separate terminal:

```bash
make test
```

## Docker Setup (Alternative)

If you prefer using Docker:

```bash
# Start Docker containers
docker-compose up -d

# Run installation inside container
docker-compose exec app make install
```

## Accessing the Application

Once installed, you can access:

- **Web Interface**: http://localhost:8080
- **API Documentation**: http://localhost:8080/api/docs
- **Dashboard**: http://localhost:8080/dashboard
- **Surveys Management**: http://localhost:8080/surveys
- **Questions Management**: http://localhost:8080/questions

## API Routes

### Survey Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/surveys` | List all surveys |
| `POST` | `/api/v1/surveys` | Create a new survey |
| `GET` | `/api/v1/surveys/{uuid}` | Get survey details |
| `PUT` | `/api/v1/surveys/{uuid}` | Update survey |
| `DELETE` | `/api/v1/surveys/{uuid}` | Delete survey |
| `POST` | `/api/v1/surveys/{uuid}/responses` | Submit survey response |
| `GET` | `/api/v1/surveys/{uuid}/responses` | Get survey responses |
| `GET` | `/api/v1/surveys/{uuid}/analytics` | Get survey analytics |

### Question Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/questions` | List all questions |
| `POST` | `/api/v1/questions` | Create a new question |
| `GET` | `/api/v1/questions/{uuid}` | Get question details |
| `PUT` | `/api/v1/questions/{uuid}` | Update question |
| `DELETE` | `/api/v1/questions/{uuid}` | Delete question |

### Question Types Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/question-types` | List all question types |
| `GET` | `/api/v1/question-types/{id}` | Get question type details |

### Answer Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/answers` | List all answers |
| `POST` | `/api/v1/answers` | Create a new answer |
| `GET` | `/api/v1/answers/{id}` | Get answer details |
| `PUT` | `/api/v1/answers/{id}` | Update answer |
| `DELETE` | `/api/v1/answers/{id}` | Delete answer |

## 🛠️ Make Commands

Our Makefile provides convenient commands for development:

### Installation & Setup
```bash
make install          # Full installation (dependencies, env, migrations, etc.)
make clean-install    # Clean installation (removes vendor, node_modules first)
```

### Development
```bash
make dev             # Start development server with Vite
make serve           # Start Laravel server only
make watch           # Watch and compile assets
make build           # Build assets for production
```

### Database
```bash
make migrate         # Run database migrations
make migrate-fresh   # Fresh migrations (drops all tables)
make seed            # Seed database with sample data
make migrate-seed    # Run migrations and seed
```

### Testing & Quality
```bash
make test           # Run all tests
make test-unit      # Run unit tests only
make test-feature   # Run feature tests only
make test-coverage  # Run tests with coverage report
make pint           # Fix code style with Laravel Pint
make phpstan        # Run PHPStan static analysis
```

### Docker
```bash
make docker-up      # Start Docker containers
make docker-down    # Stop Docker containers
make docker-build   # Build Docker images
make docker-logs    # View container logs
```

### Utilities
```bash
make clear-cache    # Clear all Laravel caches
make optimize       # Optimize application for production
make key-generate   # Generate new application key
make help           # Show all available commands
```

## Database Structure

### Main Tables

- **surveys**: Store survey information
- **questions**: Store question definitions
- **question_types**: Define available question types
- **answers**: Store survey responses
- **survey_questions**: Pivot table linking surveys and questions

## Sample API Usage

### Create a Survey

```bash
curl -X POST http://localhost:8080/api/v1/surveys \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Customer Satisfaction Survey",
    "description": "Please help us improve our services",
    "status": "active",
    "is_public": true
  }'
```

### Submit a Response

```bash
curl -X POST http://localhost:8080/api/v1/surveys/{uuid}/responses \
  -H "Content-Type: application/json" \
  -d '{
    "responses": {
      "question-uuid-1": {
        "value": "Very satisfied"
      },
      "question-uuid-2": {
        "value": "Great service!"
      }
    }
  }'
```

## Configuration

### Environment Variables

Key environment variables in `.env`:

```env
APP_NAME="Survey Management System"
APP_URL=http://localhost:8080
DB_DATABASE=surveys_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Queue Configuration
QUEUE_CONNECTION=database

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
```

## Troubleshooting

### Common Issues

#### 1. Installation Fails

**Problem**: `make install` command fails
```bash
# Solution: Clean install
make clean-install

# Or manually:
rm -rf vendor node_modules
composer install
npm install
```

#### 2. Database Connection Error

**Problem**: "Connection refused" or database errors
```bash
# Check database is running
mysql -u root -p

# Reset database
make migrate-fresh
make seed
```

#### 3. Permission Errors

**Problem**: Storage/cache permission issues
```bash
# Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or use Docker
make docker-fix-permissions
```

#### 4. Assets Not Loading

**Problem**: CSS/JS files not loading
```bash
# Rebuild assets
npm run build

# Or in development
make watch
```

#### 5. Livewire Component Errors

**Problem**: Livewire components not working
```bash
# Clear all caches
make clear-cache

# Republish Livewire assets
php artisan livewire:publish --assets
```

#### 6. API Routes Not Working

**Problem**: 404 errors on API endpoints
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Check routes
php artisan route:list --path=api
```

### Development Tips

1. **Enable Debug Mode**: Set `APP_DEBUG=true` in `.env` for detailed error messages
2. **Check Logs**: View logs at `storage/logs/laravel.log`
3. **Database Issues**: Use `php artisan tinker` to test database queries
4. **Queue Jobs**: Run `php artisan queue:work` for background jobs

## Testing

### Running Tests

```bash
# All tests
make test

# Specific test types
make test-unit        # Unit tests
make test-feature     # Feature tests
make test-coverage    # With coverage
```

### Test Structure

```
tests/
├── Feature/          # Integration tests
│   ├── SurveyTest.php
│   └── QuestionTest.php
├── Unit/            # Unit tests
│   ├── Models/
│   └── Services/
└── TestCase.php     # Base test class
```

## Documentation

- **API Documentation**: Available at `/api/documentation` when running
- **Code Documentation**: Generated with phpDoc
- **Architecture Docs**: See `/docs` folder for detailed architecture documentation



---
