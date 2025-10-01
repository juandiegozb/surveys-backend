#!/bin/bash

# Initialize LocalStack S3 bucket for surveys
echo "Initializing LocalStack S3 for Survey System..."

# Wait for LocalStack to be ready
echo "Waiting for LocalStack to be ready..."
sleep 10

# Create S3 bucket
echo "Creating surveys bucket..."
aws --endpoint-url=http://localhost:4566 s3 mb s3://surveys-bucket

# Set bucket policy for public read access (for file downloads)
echo "Setting bucket policy..."
aws --endpoint-url=http://localhost:4566 s3api put-bucket-policy --bucket surveys-bucket --policy '{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::surveys-bucket/*"
    }
  ]
}'

# Enable CORS for web uploads
echo "Setting CORS policy..."
aws --endpoint-url=http://localhost:4566 s3api put-bucket-cors --bucket surveys-bucket --cors-configuration '{
  "CORSRules": [
    {
      "AllowedOrigins": ["*"],
      "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
      "AllowedHeaders": ["*"],
      "ExposeHeaders": ["ETag"],
      "MaxAgeSeconds": 3000
    }
  ]
}'

echo "LocalStack S3 setup completed!"
echo "Bucket: surveys-bucket"
echo "Endpoint: http://localhost:4566"

# Run Laravel migrations and seeders
echo "Running database migrations and seeders..."
php artisan migrate --force
php artisan db:seed --force

# Clear caches for fresh start
echo "Clearing application caches..."
php artisan route:clear
php artisan config:clear
php artisan view:clear

echo "Survey System is ready!"
echo ""
echo "   System Components Initialized:"
echo "   Database tables (surveys, questions, answers)"
echo "   Question types seeded"
echo "   S3 bucket for file uploads"
echo "   API endpoints with rate limiting protection"
echo "   Frontend web interface (Livewire)"
echo "   Public survey forms"
echo "   Admin interface for managing surveys"
echo ""
echo "Frontend Access Points:"
echo "   - Main Dashboard: http://localhost:8080/dashboard"
echo "   - Survey Management: http://localhost:8080/surveys"
echo "   - Questions Library: http://localhost:8080/questions"
echo ""
echo "API Endpoints:"
echo "   - API Health: http://localhost:8080/api/health"
echo "   - API Documentation: http://localhost:8080/api/docs"
echo "   - Surveys API: http://localhost:8080/api/surveys"
echo "   - Questions API: http://localhost:8080/api/questions"
echo ""
echo "Public Survey Access:"
echo "   - Public Survey: http://localhost:8080/survey/{uuid}"
echo "   - Short URL: http://localhost:8080/s/{uuid}"
echo ""
echo "Security Features:"
echo "   - API Rate Limiting: Active"
echo "   - Read Operations: 200/min"
echo "   - Write Operations: 60/min"
echo "   - Bulk Operations: 10/min"
echo ""
echo "Ready to Use:"
echo "   1. Visit http://localhost:8080/dashboard for main dashboard"
echo "   2. Go to http://localhost:8080/surveys to create and manage surveys"
echo "   3. Use http://localhost:8080/questions to manage question library"
echo "   4. Access API at http://localhost:8080/api/* endpoints"
