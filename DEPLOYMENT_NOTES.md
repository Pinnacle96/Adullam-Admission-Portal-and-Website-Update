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

## 7. Modal Image Deployment (CI/CD Fix)
**Problem:** Modal images appear broken on live server after CI/CD deployment.

**Root Cause:** 
- Modal HTML content stored in database contains incorrect image paths (absolute URLs or hardcoded paths)
- `assets/img/` folder IS being deployed correctly ✅
- The issue is the paths stored in `tblpage` table, not the files themselves

**Solution:**
1. **Update modal image paths in database** to use relative paths: `assets/img/modal/image.jpg`
2. **Create modal subfolder:** `assets/img/modal/` (organize modal-specific images separately)
3. **Verify CI/CD:** The `deploy/hostinger/rsync-exclude.txt` does NOT exclude `assets/img/`, so all images deploy ✅
4. **Debug broken images:** Run `/debug_modal_images.php` on live server to verify paths

**CI/CD Configuration Status:**
- ✅ `assets/img/` is NOT excluded (correct)
- ✅ Images are tracked in git
- ✅ All images deploy with each push
- ✅ No special CI/CD changes needed

**To fix broken modals:**
```sql
-- Check current modal image paths
SELECT PageDescription FROM tblpage WHERE PageType = 'home_modal';

-- Fix absolute URLs to relative paths (example)
UPDATE tblpage 
SET PageDescription = REPLACE(PageDescription, 'http://domain.com/assets/img/', 'assets/img/')
WHERE PageType = 'home_modal';
```
