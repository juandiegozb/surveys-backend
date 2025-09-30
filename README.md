<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Survey App

A modern REST API for survey management built with Laravel.

## Project Description

Survey App is a robust application for creating, managing, and analyzing surveys. It provides RESTful endpoints to handle users, surveys, questions, and responses, with advanced features like background processing, cloud storage, and data analytics.

### Services

- **App (PHP-FPM)**: Main container with Laravel 12 and PHP 8.3
- **Nginx**: Web server as reverse proxy
- **MySQL 8.4**: Primary database
- **Redis**: Cache and session/queue management
- **LocalStack**: AWS services simulator (S3, SQS, SNS) for development
- **Queue Worker**: Background job processor with Horizon

## Installation and Setup

### Prerequisites

- Docker and Docker Compose
- Make (optional, to use simplified commands)
- Git

### Quick Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd surveys_app
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with your specific configurations
   ```

3. **Initialize the application**
   ```bash
   make init
   ```

### Manual Installation

If you don't have Make installed:

```bash
# Start services
docker compose up -d --build

# Wait for services to be ready
sleep 10

# Install dependencies
docker compose exec app composer install --no-interaction

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate

# Run seeders
docker compose exec app php artisan db:seed
```

## Development Commands

### Available Make Commands

| Command | Description |
|---------|-------------|
| `make init` | Completely initializes the application |
| `make up` | Starts all services |
| `make down` | Stops and removes all services |
| `make restart` | Restarts all services |
| `make status` | Shows container status |
| `make logs` | Shows logs from all services |
| `make app-bash` | Access the application container |
| `make mysql-bash` | Access the MySQL container |
| `make test` | Runs tests |
| `make migrate` | Runs migrations |
| `make seed` | Runs seeders |
| `make fresh` | Resets DB with fresh data |
| `make horizon` | Starts Horizon for queues |
| `make tinker` | Access Laravel Tinker |

### Docker Compose Commands

```bash
# View service status
docker compose ps

# View logs in real time
docker compose logs -f [service]

# Execute Artisan commands
docker compose exec app php artisan [command]

# Access containers
docker compose exec [service] bash
```

## Application Access

- **Web Application**: http://localhost:8080
- **MySQL Database**: localhost:33060
- **Redis**: localhost:63790
- **LocalStack (AWS)**: http://localhost:4566
- **Horizon Dashboard**: http://localhost:8080/horizon

## Useful Commands

### LocalStack Commands
```bash
# Create S3 bucket
docker compose exec localstack awslocal s3 mb s3://survey-bucket

# List S3 buckets
docker compose exec localstack awslocal s3 ls

# Upload file to S3
docker compose exec localstack awslocal s3 cp file.txt s3://survey-bucket/

# Download file from S3
docker compose exec localstack awslocal s3 cp s3://survey-bucket/file.txt ./

# Create SQS queue
docker compose exec localstack awslocal sqs create-queue --queue-name survey-jobs

# List SQS queues
docker compose exec localstack awslocal sqs list-queues

# Send message to SQS
docker compose exec localstack awslocal sqs send-message --queue-url http://sqs.us-east-1.localhost.localstack.cloud:4566/000000000000/survey-jobs --message-body "Hello World"

# Receive messages from SQS
docker compose exec localstack awslocal sqs receive-message --queue-url http://sqs.us-east-1.localhost.localstack.cloud:4566/000000000000/survey-jobs

# Create SNS topic
docker compose exec localstack awslocal sns create-topic --name survey-notifications

# List SNS topics
docker compose exec localstack awslocal sns list-topics
```

### Redis Commands
```bash
# Access Redis CLI
make redis-cli

# Test connection
redis-cli ping
# Should return: PONG

# Set a key-value pair
redis-cli set test "Hello World"

# Get value by key
redis-cli get test

# List all keys
redis-cli keys "*"

# Check Redis memory usage
redis-cli info memory

# Monitor Redis commands in real-time
redis-cli monitor

# Clear all Redis data
redis-cli flushall

# View Redis configuration
redis-cli config get "*"
```

### Horizon Commands
```bash
# Start Horizon dashboard
make horizon

# Check Horizon status
docker compose exec app php artisan horizon:status

# Pause Horizon
docker compose exec app php artisan horizon:pause

# Continue Horizon
docker compose exec app php artisan horizon:continue

# Terminate Horizon
docker compose exec app php artisan horizon:terminate

# Restart queue workers
make queue-restart

# Process single job
docker compose exec app php artisan queue:work --once

# Process jobs with timeout
docker compose exec app php artisan queue:work --timeout=60

# View failed jobs
docker compose exec app php artisan queue:failed

# Retry failed job
docker compose exec app php artisan queue:retry [job-id]

# Clear failed jobs
docker compose exec app php artisan queue:flush
```

### Laravel Artisan Commands
```bash
# Access Laravel Tinker
make tinker

# Clear application cache
docker compose exec app php artisan cache:clear

# Clear configuration cache
docker compose exec app php artisan config:clear

# Clear route cache
docker compose exec app php artisan route:clear

# Clear view cache
docker compose exec app php artisan view:clear

# List all routes
docker compose exec app php artisan route:list

# Create new migration
docker compose exec app php artisan make:migration create_surveys_table

# Create new model
docker compose exec app php artisan make:model Survey

# Create new controller
docker compose exec app php artisan make:controller SurveyController

# Run database seeders
docker compose exec app php artisan db:seed

# Rollback migrations
docker compose exec app php artisan migrate:rollback

# Check migration status
docker compose exec app php artisan migrate:status
```

### Scout Search Commands
```bash
# Import model data to search index
docker compose exec app php artisan scout:import "App\\Models\\Survey"

# Delete and reimport all searchable data
docker compose exec app php artisan scout:flush "App\\Models\\Survey"
docker compose exec app php artisan scout:import "App\\Models\\Survey"

# Delete model data from search index
docker compose exec app php artisan scout:flush "App\\Models\\Survey"
```

### Database Commands
```bash
# Access MySQL CLI
make mysql-cli

# Create database backup
make backup-db

# Connect to database with custom query
docker compose exec mysql mysql -u survey -psurvey survey -e "SELECT * FROM users LIMIT 5;"

# Show database tables
docker compose exec mysql mysql -u survey -psurvey survey -e "SHOW TABLES;"

# Show table structure
docker compose exec mysql mysql -u survey -psurvey survey -e "DESCRIBE users;"

# Export database
docker compose exec mysql mysqldump -u survey -psurvey survey > backup.sql

# Import database
docker compose exec mysql mysql -u survey -psurvey survey < backup.sql
```

### System Monitoring Commands
```bash
# Check application health
make health

# View container resource usage
docker stats

# View container logs
make logs-app
make logs

# Check disk usage
docker system df

# Clean up unused resources
make clean

# View Laravel logs
docker compose exec app tail -f storage/logs/laravel.log

# Check PHP-FPM status
docker compose exec app php-fpm -t

# View Nginx access logs
docker compose exec nginx tail -f /var/log/nginx/access.log

# View Nginx error logs
docker compose exec nginx tail -f /var/log/nginx/error.log
```

## Detailed Services

### PHP Application (app)
- **Image**: Custom PHP 8.3-FPM Alpine
- **Port**: 9000 (internal)
- **Functions**: 
  - HTTP request processing
  - Laravel business logic
  - REST API endpoints
  - Authentication and authorization

### Nginx (nginx)
- **Image**: nginx:1.27-alpine
- **Port**: 8080 → 80
- **Functions**:
  - Reverse proxy
  - Serve static files
  - Load balancing
  - SSL termination

### MySQL (mysql)
- **Image**: mysql:8.4
- **Port**: 33060 → 3306
- **Configuration**:
  - Database: `survey`
  - User: `survey` / Password: `survey`
  - Root password: `root`
- **Optimizations**:
  - Buffer pool: 1GB
  - Redo log capacity: 1GB
  - Binary logging disabled

### Redis (redis)
- **Image**: redis:7-alpine
- **Port**: 63790 → 6379
- **Functions**:
  - Application cache
  - Session storage
  - Job queues
  - Pub/Sub for broadcasting

### LocalStack (localstack)
- **Image**: localstack/localstack:latest
- **Port**: 4566
- **Simulated services**:
  - **S3**: File storage
  - **SQS**: Message queues
  - **SNS**: Push notifications
- **Configuration**:
  - S3 Bucket: `survey-bucket`
  - SQS Queue: `survey-jobs`

### Queue Worker (queue)
- **Image**: Custom PHP 8.3-FPM Alpine
- **Command**: `php artisan horizon`
- **Functions**:
  - Background queue processing
  - Email sending
  - File processing
  - Scheduled tasks

## Testing

```bash
# Run all tests
make test

# Run specific tests
docker compose exec app php artisan test --filter=UserTest

# Run with coverage
docker compose exec app php artisan test --coverage
```

## Important Environment Variables

```env
# Application
APP_NAME=SurveyAPI
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=survey
DB_USERNAME=survey
DB_PASSWORD=survey

# Cache and Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

# AWS/LocalStack
AWS_ENDPOINT=http://localstack:4566
AWS_BUCKET=survey-bucket
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## Configuration & Validation

### Environment Configuration

After installation, ensure these key environment variables are properly set in your `.env` file:

```env
# Application
APP_NAME=SurveyAPI
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=survey
DB_USERNAME=survey
DB_PASSWORD=survey

# Cache and Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
HORIZON_BALANCE=auto
HORIZON_MAX_PROCESSES=1

# Search Configuration (OpenSearch)
SCOUT_DRIVER=opensearch
OPENSEARCH_HOST=http://opensearch:9200
OPENSEARCH_USERNAME=
OPENSEARCH_PASSWORD=
OPENSEARCH_SSL_VERIFICATION=false

# AWS/LocalStack Configuration
AWS_ACCESS_KEY_ID=test
AWS_SECRET_ACCESS_KEY=test
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=survey-bucket
AWS_URL=http://localhost:4566/survey-bucket
AWS_ENDPOINT=http://localstack:4566
AWS_USE_PATH_STYLE_ENDPOINT=true

# SQS Configuration
SQS_PREFIX=http://localstack:4566/000000000000
SQS_QUEUE=survey-jobs
AWS_ACCOUNT_ID=000000000000

# Mail Configuration (Development)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### System Validation

After running `make init`, validate your installation with these commands:

#### 1. **Container Health Check**
```bash
# Check all containers are running
make status

# Expected output should show all services as "Up" and "healthy"
# survey_app, survey_mysql, survey_redis, survey_nginx, 
#    survey_localstack, survey_queue
```

#### 2. **Application Health**
```bash
# Test web application
make health
# Should return: Status: 200

# Test application directly
curl http://localhost:8080
# Should return Laravel welcome page
```

#### 3. **Database Connectivity**
```bash
# Test database connection
docker compose exec app php artisan migrate:status
# Should show migration table with all migrations "Ran"

# Test MySQL directly
make mysql-cli
# Should connect to MySQL with survey database
```

#### 4. **Redis Connectivity**
```bash
# Test Redis connection
make redis-cli
# Then run: ping
# Should return: PONG
```

#### 5. **Queue System (Horizon)**
```bash
# Check Horizon dashboard
curl -I http://localhost:8080/horizon
# Should return: HTTP/1.1 200 OK

# Test queue processing
docker compose exec app php artisan queue:work --once
# Should process any pending jobs
```

#### 6. **LocalStack (AWS Services)**
```bash
# Test S3 service
docker compose exec localstack awslocal s3 ls
# Should list the survey-bucket

# Test SQS service  
docker compose exec localstack awslocal sqs list-queues
# Should show survey-jobs queue URL
```

#### 7. **Search Engine (OpenSearch)**
```bash
# Test OpenSearch connection (when configured)
curl http://localhost:9200/_cluster/health
# Should return cluster status JSON

# Test Scout search functionality
docker compose exec app php artisan scout:import "App\\Models\\User"
# Should import users to search index
```

### Common Validation Issues

#### **Issue: LocalStack not starting**
```bash
# Solution: Check LocalStack logs
docker compose logs localstack

# Common fix: Restart with clean volumes
make clean
make init
```

#### **Issue: Permission denied errors**
```bash
# Solution: Fix storage permissions
docker compose exec app chmod -R 775 storage bootstrap/cache
```

#### **Issue: Database connection refused**
```bash
# Solution: Ensure MySQL is fully started
docker compose logs mysql
# Wait for: "MySQL init process done. Ready for start up."

# Alternative: Restart services
make restart
```

#### **Issue: Horizon not processing jobs**
```bash
# Solution: Check Horizon status
docker compose exec app php artisan horizon:status

# Restart queue workers
make queue-restart
```

#### **Issue: OpenSearch connection failed**
```bash
# Solution: Add OpenSearch service to docker-compose.yml
# Note: OpenSearch is optional for basic functionality
# Scout will fall back to database driver if unavailable
```

### Monitoring and Logs

#### **Application Logs**
```bash
# View application logs
make logs-app

# View specific service logs
docker compose logs [service-name]

# Laravel application logs location
tail -f storage/logs/laravel.log
```

#### **Performance Monitoring**
- **Horizon Dashboard**: http://localhost:8080/horizon
- **Queue Jobs**: Monitor job processing and failures
- **Application Metrics**: Response times and error rates
- **Database Queries**: Check for N+1 problems and slow queries

### Security Validation

#### **Environment Security**
- `.env` files are not committed to git
- Strong `APP_KEY` generated
- Database credentials are secure
- AWS keys are for LocalStack only (in development)

#### **File Permissions**
```bash
# Validate correct permissions
ls -la storage/
ls -la bootstrap/cache/
# Should be writable by web server
```

**Developed by Juan Zambrano**
