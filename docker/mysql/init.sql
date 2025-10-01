-- Create database and user if they don't exist
CREATE DATABASE IF NOT EXISTS surveys_db;
CREATE USER IF NOT EXISTS 'surveys_user'@'%' IDENTIFIED BY 'surveys_pass';
GRANT ALL PRIVILEGES ON surveys_db.* TO 'surveys_user'@'%';
FLUSH PRIVILEGES;

