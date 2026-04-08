# TODO List

**Previous Tasks:**
- Nav made smaller: COMPLETED
- Hero background full coverage: COMPLETED

**Changes Applied:**
- hero-frame: padding 15px → 0, bg #f2f4f7 → transparent
- hero-visual-card: added height:100vh width:100%, border-radius 40px → 0 for full viewport

Refresh index.html in browser to see hero image covering full viewport div (minus fixed nav space). Both tasks complete.

## Database Setup

### Prerequisites
- Install XAMPP/WAMP or any local server with PHP and MySQL
- Start Apache and MySQL services

### Database Creation Queries

```sql
-- Create database
CREATE DATABASE terra_invest;

-- Use the database
USE terra_invest;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional: Create admin user (password: admin123)
INSERT INTO users (name, email, password, created_at) VALUES
('Admin User', 'admin@terrainvest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());
```

### Configuration
1. Update `php/config.php` with your database credentials:
   - DB_HOST: Usually 'localhost'
   - DB_USER: Usually 'root'
   - DB_PASS: Usually '' (empty) for local development
   - DB_NAME: 'terra_invest'

2. Make sure the PHP files are accessible via a web server (not just opening HTML files directly)

### Testing
- Try registering a new user
- Try logging in with the registered credentials
- Check the database to see if users are being stored correctly

### Security Notes
- Change default database password in production
- Consider using environment variables for sensitive data
- Add input sanitization and validation as needed
- Implement CSRF protection for forms
