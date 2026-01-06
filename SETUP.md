# Quick Setup Guide

## Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Composer (for Twig)

## Installation Steps

### 1. Install Composer Dependencies
```bash
composer install
```

This will install Twig template engine.

### 2. Create Database
1. Create a MySQL database
2. Import `schema.sql`:
   ```bash
   mysql -u your_username -p your_database < schema.sql
   ```

### 3. Configure Database Connection
Create `includes/db.php`:
```php
<?php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### 4. Set Permissions
Ensure the `cache/` directory is writable:
```bash
chmod 755 cache
```

Or create it if it doesn't exist - it will be auto-created on first run.

### 5. Access the Application
Open your browser and navigate to:
```
http://localhost/my_website_project/
```

## Troubleshooting

**Twig not found error:**
- Run `composer install` to install dependencies
- Check that `vendor/autoload.php` exists

**Cache directory errors:**
- Create `cache/` directory manually
- Set permissions: `chmod 755 cache`

**Database connection errors:**
- Verify `includes/db.php` has correct credentials
- Check MySQL service is running

