# Quick Setup Guide

Get College Data Hub running in 5 minutes!

## Prerequisites

- PHP 7.4+
- MySQL 5.7+ or PostgreSQL 10+
- Web server running

## Quick Start

### 1. Copy Environment File
```bash
cp .env.example .env
```

### 2. Edit `.env` with Your Database Details
```
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=college_data_hub
```

### 3. Create Database
```bash
# MySQL
mysql -u root -p < database/schema.sql

# Or manually in PhpMyAdmin:
# 1. Create database: college_data_hub
# 2. Import: database/schema.sql
```

### 4. Create Uploads Directory
```bash
mkdir -p assets/uploads
chmod 755 assets/uploads
```

### 5. Start Your Web Server

**Using PHP Built-in Server:**
```bash
php -S localhost:8000
```

**Using Apache/Nginx:**
Configure your web server to point to this directory.

### 6. Access Application

Open your browser and go to:
- **Local**: http://localhost:8000
- **Domain**: http://college-hub.local (if configured)

## Default Login

- **Email**: admin@college.local
- **Password**: Admin@123

⚠️ Change these credentials immediately in production!

## Common Issues

### "Connection refused" error
- Ensure MySQL/PostgreSQL is running
- Check DB credentials in `.env`

### "Permission denied" on uploads
```bash
chmod -R 777 assets/uploads
```

### New users can't log in
- Verify user exists in database
- Check user is_active flag is 1
- Verify password is correct

## Next Steps

1. Create classes (Admin → Classes)
2. Add teachers and students (Admin → Users)
3. Teachers create forms (Teacher Dashboard → Create Form)
4. Students submit responses (Student Dashboard)
5. Review responses and analytics

## Directory Permissions

Ensure these directories are writable:
- `assets/uploads/` - Should be 755 or 777
- `logs/` - Should be 755 or 777 (create if needed)

## File Structure

```
data_hub_college/
├── admin/          - Admin pages
├── teacher/        - Teacher pages  
├── student/        - Student pages
├── api/            - API endpoints
├── includes/       - Core functions
├── assets/         - CSS, JS, uploads
├── database/       - SQL schema
└── config.php      - Main config
```

## Documentation

- **Full Docs**: See README.md
- **API Docs**: See api/README.md
- **Troubleshooting**: See README.md#troubleshooting

## Need Help?

1. Check error logs in your web server
2. Enable debugging in `config.php`
3. Check database connection
4. Review browser console for JavaScript errors

Happy teaching! 🎓
