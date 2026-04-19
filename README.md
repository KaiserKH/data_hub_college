# College Data Hub

A comprehensive web application for managing educational forms and surveys in a college setting.

## Features

- **User Management**: Admin, Teacher, and Student roles with role-based access control
- **Class Management**: Organize students into classes
- **Form Management**: Teachers can create and manage forms with various question types
- **Response Tracking**: Track form responses and submissions from students
- **Authentication**: Secure login with password hashing and session management
- **File Upload**: Support for file attachments in form responses
- **Audit Logging**: Track all user actions for security and compliance
- **CSRF Protection**: Built-in CSRF token validation
- **Responsive Design**: Mobile-friendly dashboard interface

## Tech Stack

- **Backend**: PHP 7.4+ with PostgreSQL/MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Security**: Password hashing (bcrypt), CSRF protection, input validation
- **Database**: MySQL/PostgreSQL with PDO

## Project Structure

```
data_hub_college/
├── admin/                 # Admin dashboard and management
├── teacher/              # Teacher interface and form builder
├── student/              # Student interface and form submission
├── api/                  # REST API endpoints
├── includes/             # Core helper functions and classes
│   ├── auth.php          # Authentication helpers
│   ├── csrf.php          # CSRF protection
│   ├── db.php            # Database connection (PDO)
│   ├── functions.php     # Utility functions
│   ├── upload.php        # File upload handler
│   └── validation.php    # Input validation/sanitization
├── assets/               # Static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── uploads/          # User uploaded files
├── database/             # Database schemas and migrations
├── config.php            # Main configuration file
├── index.php             # Entry point (redirects to role dashboard)
├── login.php             # Login page
├── logout.php            # Logout handler
└── README.md             # This file
```

## Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7+ or PostgreSQL 10+
- Composer (optional, for dependency management)
- Web server (Apache, Nginx, etc.)

### Step 1: Clone the Repository

```bash
git clone https://github.com/KaiserKH/data_hub_college.git
cd data_hub_college
```

### Step 2: Set Up Environment Variables

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
APP_ENV=development
BASE_URL=http://localhost:8000

DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=your_password
DB_NAME=college_data_hub
```

### Step 3: Create Database

Option A - Using MySQL CLI:
```bash
mysql -u root -p < database/schema.sql
```

Option B - Using PhpMyAdmin or your database client:
1. Create a new database named `college_data_hub`
2. Import `database/schema.sql`

### Step 4: Set Up Web Server

**For Apache:**
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/data_hub_college
    ServerName college-hub.local
    <Directory /path/to/data_hub_college>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**For PHP Built-in Server** (development only):
```bash
php -S localhost:8000
```

### Step 5: Create Uploads Directory

```bash
mkdir -p assets/uploads
chmod 755 assets/uploads
```

### Step 6: Start the Application

Navigate to `http://localhost:8000` in your browser.

## Default Login Credentials

After setup, you can log in with:
- **Email**: admin@college.local
- **Password**: Admin@123

⚠️ **Important**: Change these credentials in production!

## User Roles & Permissions

### Admin
- Manage users (create, edit, deactivate)
- Manage classes
- View all forms and responses
- Access audit logs

### Teacher
- Create and manage forms
- View form responses and analytics
- Export data (future)
- Manage class roster

### Student
- View assigned forms
- Submit form responses
- Track submission status
- View completed forms

## Database Schema

### Core Tables

- **users**: User accounts with roles and authentication
- **classes**: Educational classes/groups
- **forms**: Survey/form definitions created by teachers
- **form_questions**: Individual questions within a form
- **responses**: Student submissions to forms
- **answers**: Individual answers to questions
- **login_attempts**: Rate limiting for login brute force protection
- **audit_log**: Track all user actions
- **notifications**: User notifications and alerts

## Configuration

All configuration is managed in `config.php`:

```php
define('DB_HOST', 'localhost');        // Database host
define('DB_USER', 'root');             // Database user
define('DB_PASS', '');                 // Database password
define('DB_NAME', 'college_data_hub');// Database name
define('SESSION_LIFETIME', 3600);      // Session timeout (seconds)
define('MAX_FILE_SIZE_BYTES', 5242880);// Max upload 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'png', 'docx']);
```

## Key Features

### Authentication & Security

- Bcrypt password hashing
- CSRF token validation on all POST/PUT requests
- SQL injection prevention through prepared statements
- XSS protection with output encoding
- Rate limiting on login attempts

### Form Management

- Multiple question types (text, textarea, multiple choice, date, file upload, etc.)
- Question shuffling option
- Anonymous response option
- Deadline management
- Auto-closing forms on deadline

### File Upload

- Secure file upload handling
- MIME type validation
- File size restrictions
- Directory traversal prevention

## API Endpoints

The `api/` directory contains RESTful API endpoints for:

- `POST /api/forms/create` - Create new form
- `GET /api/forms/:id` - Get form details
- `POST /api/responses/submit` - Submit form response
- `GET /api/responses/:id` - Get response details

## Development

### Running Tests

```bash
vendor/bin/phpunit tests/
```

### Code Standards

Code follows PSR-12 PHP Standard Recommendations.

### Running Development Server

```bash
php -S localhost:8000
```

## Troubleshooting

### Database Connection Error

Check your database credentials in `config.php` and ensure MySQL/PostgreSQL is running.

### Permission Denied on Uploads

```bash
chmod -R 755 assets/uploads
```

### Session Not Starting

Ensure PHP session.save_path is writable:

```bash
php -r "echo ini_get('session.save_path');"
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security Considerations

- Never commit `.env` with real credentials
- Use HTTPS in production
- Implement rate limiting on the web server
- Keep PHP and dependencies updated
- Regularly audit the audit_log table
- Use strong passwords
- Implement 2FA for admin accounts (future enhancement)

## Performance Optimization

- Database query optimization with proper indexing
- Response caching for read-heavy data
- Minify CSS/JS in production
- Enable gzip compression
- Use CDN for static assets
- Implement lazy loading for forms

## Future Enhancements

- Export responses to Excel/PDF
- Email notifications for form deadlines
- Two-factor authentication (2FA)
- API rate limiting
- Bulk user import from CSV
- Form templates library
- Response analytics and charts
- Webhook support

## License

This project is licensed under the MIT License. See the LICENSE file for details.

## Author

Kaiser Kolei Husbands
- GitHub: [@KaiserKH](https://github.com/KaiserKH)

## Support

For issues and questions:
1. Check existing issues on GitHub
2. Create a new issue with detailed information
3. Include error messages, steps to reproduce, and environment details

## Changelog

### Version 1.0.0 (Initial Release)
- Core functionality for form management
- User role-based access control
- Student form submission
- Admin dashboard
- Teacher interface
- Database schema and migrations