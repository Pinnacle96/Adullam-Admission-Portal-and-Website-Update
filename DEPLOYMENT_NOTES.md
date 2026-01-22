# Deployment Checklist

## 1. Database Configuration
- Edit `db.php` in the root directory.
- Update the **Live** section with your production database credentials (Host, DB Name, User, Password).
- Ensure the `$host`, `$db`, `$user`, and `$pass` variables are uncommented for the live environment.

## 2. File Permissions
- Ensure the `uploads/` directory and its subdirectories (`documents/`, `images/`) have write permissions.
- Recommended permission: `755` (or `777` if necessary on some shared hosts).

## 3. Database Indexes
- The database tables have been optimized with indexes for faster performance.
- When migrating, ensure you export the **structure and data** (which includes indexes) from your local database to the live database.

## 4. Server Requirements
- PHP 7.4 or higher.
- Extensions: `pdo`, `pdo_mysql`, `json`, `gd` (optional, for images).
- Apache web server with `mod_rewrite` enabled (for `.htaccess` support).

## 5. URL Rewriting
- Ensure the `.htaccess` file in the root directory is uploaded. It handles clean URLs (removing `.php` extensions).

## 6. Error Reporting
- `submit_application.php` and other files have error reporting disabled for production (`display_errors` set to 0).
- If you encounter 500 errors, check the `error_log` file or temporarily enable `display_errors` in the specific file to debug.
