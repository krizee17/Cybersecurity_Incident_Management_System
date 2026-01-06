# Cybersecurity Incident Management System

A comprehensive web-based system for logging, managing, and tracking cybersecurity incidents with full CRUD operations, search functionality, and security features.

## Features

### Core Functionality
- **CRUD for Incidents**: Create, Read, Update, Delete incidents with the following fields:
  - Incident type
  - Date & time
  - Affected system
  - Severity (Low, Medium, High, Critical)
  - Resolution notes
  - Status tracking (Detected → Investigating → Resolved)

- **CRUD for Systems/Assets**: Manage servers, applications, and databases
  - System name
  - System type (Server, Application, Database)
  - Description

- **Incident Status Tracking**: Visual status progression with live updates

- **Search Functionality**: Search incidents by:
  - Type
  - Severity
  - Status
  - Date range
  - Affected system

### AJAX Features
- **Live Status Updates**: Click on status badges to update incident status without page reload
- **Auto-refresh Dashboard**: Dashboard counters automatically refresh every 30 seconds

### Security Features
- **XSS Protection**: All user input is sanitized and output is escaped using `htmlspecialchars()` (via Twig's escape filter)
- **CSRF Protection**: All forms and AJAX requests use CSRF tokens
- **SQL Injection Prevention**: All database queries use prepared statements
- **Audit Logging**: All create, update, and delete operations are logged with:
  - Table name and record ID
  - Action type
  - User identifier (IP address)
  - Old and new values

### Template Engine
- **Twig**: The project uses Twig template engine for clean separation of logic and presentation
- All HTML is in `.twig` template files
- PHP files handle business logic and data processing
- Templates automatically escape output to prevent XSS attacks

## File Structure

```
my_website_project/
├── includes/
│   ├── db.php              # Database connection (you need to create this)
│   ├── security.php        # Security utilities (CSRF, XSS protection, audit logging)
│   └── twig.php            # Twig template engine configuration
├── templates/              # Twig templates
│   ├── base.html.twig      # Base template with layout
│   ├── dashboard.html.twig # Dashboard template
│   ├── search.html.twig    # Search template
│   ├── incidents/
│   │   ├── list.html.twig  # Incident list template
│   │   ├── create.html.twig # Create incident template
│   │   ├── view.html.twig  # View incident template
│   │   └── edit.html.twig  # Edit incident template
│   └── systems/
│       ├── list.html.twig  # System list template
│       ├── create.html.twig # Create system template
│       ├── view.html.twig  # View system template
│       └── edit.html.twig  # Edit system template
├── api/
│   ├── dashboard_stats.php # AJAX endpoint for dashboard statistics
│   ├── update_status.php   # AJAX endpoint for status updates
│   └── get_csrf_token.php  # AJAX endpoint for CSRF token
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   └── js/
│       └── main.js         # JavaScript for AJAX and interactivity
├── cache/                  # Twig cache directory (auto-generated)
├── vendor/                 # Composer dependencies (auto-generated)
├── index.php               # Dashboard
├── incidents.php           # List all incidents
├── incident_create.php     # Create new incident
├── incident_view.php       # View incident details
├── incident_edit.php       # Edit incident
├── incident_delete.php     # Delete incident
├── systems.php             # List all systems
├── system_create.php       # Create new system
├── system_view.php         # View system details
├── system_edit.php         # Edit system
├── system_delete.php       # Delete system
├── search.php              # Search incidents
├── schema.sql              # Database schema
├── composer.json           # Composer dependencies
└── README.md               # This file
```

## Setup Instructions

### 1. Database Setup

1. Create a MySQL database for the project
2. Import the schema from `schema.sql`:
   ```sql
   mysql -u your_username -p your_database < schema.sql
   ```
   Or execute the SQL statements in `schema.sql` using phpMyAdmin or your preferred MySQL client.

### 2. Database Connection

Create `includes/db.php` with your database connection:

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

### 3. Install Twig Template Engine

The project uses Twig for templating. Install dependencies using Composer:

1. **Install Composer** (if not already installed):
   - Download from https://getcomposer.org/
   - Or use: `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php`

2. **Install Twig**:
   ```bash
   composer install
   ```
   
   This will install Twig and create the `vendor/` directory.

3. **Create cache directory**:
   ```bash
   mkdir cache
   chmod 755 cache
   ```
   
   Or create the `cache/` directory manually and ensure it's writable by the web server.

### 4. Web Server Configuration

- Ensure PHP 7.4+ is installed
- Ensure Composer is installed
- Place the project in your web server directory (e.g., `htdocs`, `www`, or `public_html`)
- Ensure the web server has read/write permissions, especially for the `cache/` directory

### 5. Initial Data (Optional)

You may want to add some initial systems before creating incidents:

```sql
INSERT INTO systems (name, type, description) VALUES
('Web Server 01', 'Server', 'Main web server'),
('Customer Database', 'Database', 'Primary customer database'),
('Email Application', 'Application', 'Corporate email system');
```

## Usage

### Creating an Incident

1. Navigate to **Incidents** → **Create New Incident**
2. Fill in all required fields (marked with *)
3. Click **Create Incident**

### Managing Systems

1. Navigate to **Systems** → **Add New System**
2. Enter system details
3. Systems can be edited or deleted (deletion fails if incidents reference the system)

### Searching Incidents

1. Navigate to **Search**
2. Use any combination of filters
3. Click **Search** to view results

### Live Status Updates

- On the incidents list or detail page, click any status badge to cycle through: Detected → Investigating → Resolved
- The update happens via AJAX without page reload
- Dashboard automatically refreshes to show updated counts

## Security Implementation Details

### XSS Protection
- All user input is sanitized using `sanitizeInput()` function
- All output is escaped using `escape()` function which uses `htmlspecialchars()`
- Resolution notes are properly escaped before display

### CSRF Protection
- CSRF tokens are generated and stored in session
- All forms include a hidden CSRF token field
- All POST requests (forms and AJAX) verify the CSRF token
- Tokens are regenerated on each request

### SQL Injection Prevention
- All database queries use prepared statements with parameter binding
- User input is never directly concatenated into SQL queries
- Integer inputs are cast using `intval()`

### Audit Logging
- Every CREATE, UPDATE, and DELETE operation is logged
- Audit log includes:
  - Table name and record ID
  - Action type (CREATE, UPDATE, UPDATE_STATUS, DELETE)
  - User identifier (IP address)
  - Old and new values (JSON format)
  - Timestamp

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- JavaScript must be enabled for AJAX features
- Responsive design works on mobile devices

## Notes

- The system uses sessions for CSRF token management
- User identification is based on IP address (`$_SERVER['REMOTE_ADDR']`)
- For production, consider implementing proper user authentication
- The audit log table can be queried to track all changes:
  ```sql
  SELECT * FROM audit_log ORDER BY timestamp DESC;
  ```

## Troubleshooting

### Database Connection Errors
- Verify `includes/db.php` has correct credentials
- Ensure MySQL service is running
- Check database name matches

### CSRF Token Errors
- Ensure sessions are working (check PHP session configuration)
- Clear browser cookies if issues persist

### AJAX Not Working
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Ensure JavaScript is enabled

### Twig Template Errors
- Ensure Composer dependencies are installed: `composer install`
- Check that the `cache/` directory exists and is writable
- Verify `vendor/autoload.php` exists
- Check PHP error logs for specific Twig errors
- If in production, set `'debug' => false` and `'auto_reload' => false` in `includes/twig.php`

## License

This project is created for educational purposes as a university assignment.

