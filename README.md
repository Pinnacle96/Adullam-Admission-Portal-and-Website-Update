# Adullam Admission Portal and Website

This repository contains the public website, admission portal, student dashboard, and administrative tools for RCN Theological Seminary - Adullam.

The application is a native PHP/MySQL project. The modern admission system lives mainly in `dashboard/`, while the root folder contains public website pages and `admin/` and `user/` contain older/legacy interfaces that still exist in the codebase.

## Quick Links

- Public site: `http://localhost/adullam`
- Applicant login: `http://localhost/adullam/dashboard/applicant_login`
- Admin login page: `http://localhost/adullam/dashboard/administrator`
- Student dashboard: `http://localhost/adullam/dashboard/dashboard`
- Admin dashboard: `http://localhost/adullam/dashboard/admin_dashboard`
- Superadmin dashboard: `http://localhost/adullam/dashboard/superadmin_dashboard`
- Handoff documentation page: `http://localhost/adullam/documentation`

The clean URLs work through `.htaccess`, which rewrites extensionless URLs to matching `.php` files and, for documentation, matching `.html` files.

## What The System Does

### Public Website

Root-level PHP files render the public-facing website:

- `index.php` homepage
- `about.php`, `contact.php`, `requirements.php`
- program pages such as `cert.php`, `dip.php`, `bdiv.php`, `pgdt.php`, `masters.php`, `online_school.php`
- `admissions.php` and `admission_status.php`
- public assets in `assets/`, `fees/`, `js/`, and `includes/`

### Admission Portal

The active portal is in `dashboard/`.

Core applicant/student flows:

- account registration and email verification
- password setup and password reset by OTP
- multi-step application form
- document uploads
- recommendation requests
- admission status tracking
- admission letter download
- tuition proof upload
- hostel registration and allocation
- profile page with password change protected by email OTP

Core admin/superadmin flows:

- cohort-based admin dashboards
- applicants list with filters and exports
- application review and moderation
- applicant admission/rejection with email notifications
- tuition payment approval and onboarding
- hostel room and registration management
- document review
- program and MA focus management
- admin management for superadmins
- broadcast email
- analytics and reports
- system settings such as current cohort and registration toggles

### Legacy Areas

The folders below are older modules and should be treated carefully:

- `admin/`: older CMS/admin pages for courses, notices, applications, contact content, and subscribers.
- `user/`: older user/application portal with its own includes and PHPMailer vendor copy.
- `june2025/`: archived or copied project state.

Do not delete these folders unless the owner confirms they are no longer used in production.

## Technology Stack

- PHP 7.4 target in GitHub Actions deployment
- MySQL/MariaDB
- Apache with `mod_rewrite`
- Tailwind CSS via CDN in dashboard pages
- Vanilla JavaScript and jQuery in some admin pages
- Chart.js for dashboard charts
- SweetAlert2 for user feedback
- PHPMailer for SMTP email
- mPDF and Dompdf in Composer dependencies
- FPDF-style utilities in project/vendor areas for some PDFs
- GitHub Actions deployment to Hostinger shared hosting

## Repository Structure

```text
adullam/
  .github/workflows/          GitHub Actions deployment and rollback workflows
  admin/                      Legacy admin/CMS interface
  app-assets/                 Vendor frontend assets used by older pages
  assets/                     Public images, CSS, fonts, website assets
  dashboard/                  Main admission portal and admin dashboard
    ajax/                     JSON/AJAX endpoints
    components/               Shared dashboard navigation/sidebar components
    utils/                    Admission email and letter utilities
    db.php                    Dashboard PDO connection
    mailer.php                PHPMailer SMTP helper
  db/                         SQL dumps and schema snapshots
  deploy/hostinger/           Hostinger deployment support files
  fees/                       Public fee PDF files
  includes/                   Public website shared includes/database connection
  scripts/                    Import/maintenance scripts
  user/                       Legacy applicant/user portal
  vendor/                     Composer dependencies, ignored if not tracked locally
  .htaccess                   Clean URL rewrites and PHP limits
  composer.json               PHP package dependencies
  db.php                      Root PDO database connection
  documentation.html          Browser-friendly handoff documentation
  README.md                   This file
  site_watch.php              Error/threat watcher with email alerts
```

## Important Entry Points

### Public Site

| Path | Purpose |
| --- | --- |
| `/` or `/index` | Public homepage |
| `/admissions` | Admissions page |
| `/admission_status` | Public admission status lookup |
| `/requirements` | Admission requirements |
| `/contact` | Contact page |

### Applicant and Student

| Path | Purpose |
| --- | --- |
| `/dashboard/register` | Applicant registration |
| `/dashboard/verify` and `/dashboard/verify_link` | Email verification |
| `/dashboard/set_password` | First password setup after verification |
| `/dashboard/applicant_login` | Applicant login UI |
| `/dashboard/login` | Login AJAX handler |
| `/dashboard/student_dashboard` | Redirect/orchestration for students |
| `/dashboard/dashboard` | Student dashboard after application exists/submitted |
| `/dashboard/application_form` | Multi-step application form shell |
| `/dashboard/form_level1` to `/dashboard/form_level6` | Application step handlers/views |
| `/dashboard/profile` | Shared profile/password page |
| `/dashboard/payment_proof` | Tuition proof upload |
| `/dashboard/register_hostel_unified` | Hostel registration |
| `/dashboard/download_application` | Application PDF download |

### Admin and Superadmin

| Path | Purpose |
| --- | --- |
| `/dashboard/administrator` | Admin login page |
| `/dashboard/admin_dashboard` | Admin dashboard |
| `/dashboard/superadmin_dashboard` | Superadmin dashboard |
| `/dashboard/applicants_list` | Applicants list and filters |
| `/dashboard/applicant_view` | Application review/details |
| `/dashboard/moderate_applications` | Review queue |
| `/dashboard/manage_tuition_payments` | Tuition approval/onboarding |
| `/dashboard/manage_hostel` | Hostel registration management |
| `/dashboard/hostel_rooms_overview` | Room overview |
| `/dashboard/documents_review` | Document review |
| `/dashboard/recommendation_list` | Recommendations |
| `/dashboard/broadcast_email` | Broadcast emails |
| `/dashboard/analytics` | Cohort-based analytics |
| `/dashboard/reports_export` | Export reports |
| `/dashboard/system_settings` | Settings, superadmin only |
| `/dashboard/manage_admins` | Admin users, superadmin only |
| `/dashboard/manage_programs` | Programs/focus areas, superadmin only |

## Roles and Permissions

The main role field is `users.role`.

- `student`: applicant/student portal access.
- `admin`: admin dashboard access.
- `superadmin`: all admin access plus settings, admin management, programs, and other elevated operations.

Most dashboard files begin with a session check similar to:

```php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index");
    exit;
}
```

## Database Overview

The main database dump is:

- `db/u499616432_adullamn_cams.sql`

Other dumps exist:

- `db/camsdb.sql`
- `db/adullam (2).sql`

Use the newest trusted production export when onboarding a developer. The tracked dump may contain historical data and should be treated as sensitive.

Key modern portal tables include:

- `users`: all portal users and roles
- `applications`: application status, submission state, cohort, admission number
- `application_details`: program, study mode, demographic and academic details
- `application_personal`, `application_church`, `application_autobiography`: application step data
- `application_documents`: uploaded document paths
- `application_recommendations` and `application_references`: recommendation workflow
- `email_verification_otp` and `email_verification_tokens`: verification workflow
- `password_resets`: OTP/password reset records
- `tuition_payment`: tuition proof, amount, status, onboarding flag
- `hostel_rooms`, `hostel_registrations`, `hostel_allocations`, `hostel_settings`: hostel workflow
- `programs` and `ma_focus_areas`: program configuration
- `settings`: current cohort, registration toggles, banners, and other runtime settings
- `audit_logs`, `admin_logs`, `reviews_audit`: audit/history records

Legacy/public-site tables include `tbladmin`, `tblcourse`, `tblnotice`, `tblpage`, `tblsubscriber`, `tbluser`, `tbladmapplications`, and related older tables.

## Local Setup

### Requirements

- PHP 7.4 or newer
- MySQL or MariaDB
- Apache with `mod_rewrite`
- Composer
- WAMP/XAMPP/LAMP is fine for local development

### Clone and Install

```bash
git clone <repo-url> adullam
cd adullam
composer install
```

If using WAMP locally, place the folder at:

```text
C:\wamp64\www\adullam
```

### Database Setup

1. Create a MySQL database. The existing local config expects:

```text
u499616432_adullamn_cams
```

2. Import a database dump, usually:

```text
db/u499616432_adullamn_cams.sql
```

3. Update database credentials in:

```text
db.php
dashboard/db.php
includes/dbconnection.php
```

The root and dashboard PDO configs currently use local defaults in development:

```php
$host = 'localhost';
$db   = 'u499616432_adullamn_cams';
$user = 'root';
$pass = '';
```

### Writable Directories

Create these folders if missing and make sure the web server can write to them:

```text
uploads/
dashboard/uploads/
dashboard/logs/
dashboard/letters/admission_letters/
user/uploads/
user/userimages/
```

### Run Locally

Start Apache/MySQL and open:

```text
http://localhost/adullam
```

Clean URLs depend on `.htaccess`. If clean URLs fail:

- enable Apache `mod_rewrite`
- allow `.htaccess` overrides in Apache config
- restart Apache

## Email Configuration

Dashboard mail is handled by:

```text
dashboard/mailer.php
```

It uses PHPMailer over Gmail SMTP. The default sender is:

```text
adullamadmissions@gmail.com
```

Important:

- Move SMTP credentials to environment variables or a protected config file when possible.
- Rotate any credentials that have been committed to the repository.
- Test email flows after deployment: verification, password reset, recommendation, admission decision, tuition/onboarding, and profile password OTP.

## Site Watch Monitoring

Application-level monitoring is handled by:

```text
site_watch.php
```

It is loaded from both `db.php` and `dashboard/db.php`, so most public and dashboard PHP requests are monitored automatically. It captures:

- PHP warnings and recoverable errors
- fatal errors during shutdown
- uncaught exceptions
- suspicious request patterns such as SQL injection, XSS, path traversal, scanner user agents, exposed-file probes, and remote-code-execution probes

Alerts are written to `logs/site_watch.log` and emailed to:

- `noahabayomi14@gmail.com`
- `ngbedebarnabas@gmail.com`

Repeated identical alerts are rate-limited for 15 minutes through `logs/site_watch_alerts.json` to reduce inbox flooding. The watcher redacts passwords, OTPs, tokens, cookies, authorization headers, and similar secrets before logging or emailing.

Important limits:

- This is application-level monitoring, not a replacement for Hostinger server logs, malware scanning, backups, Cloudflare/WAF protection, or GitHub secret scanning.
- `SITE_WATCH_BLOCK_THREATS` is currently `false`, so suspicious traffic is reported but not blocked. Enable blocking only after testing to avoid locking out valid users.
- If `vendor/autoload.php` or PHPMailer is unavailable, the event is still logged but the email cannot be sent.

## File Uploads and Generated Files

Uploads and generated PDFs are intentionally preserved during deployment:

- `uploads/`
- `dashboard/uploads/`
- `dashboard/letters/admission_letters/`
- `user/uploads/`
- `user/userimages/`

Do not delete these folders on production without first taking a backup.

## Deployment

Deployment is configured through GitHub Actions:

```text
.github/workflows/
deploy/hostinger/
```

The deploy workflow:

- runs on push to `master`
- installs Composer dependencies
- packages application code
- uploads to Hostinger over SSH
- backs up current live code
- syncs the release to the live public folder
- preserves live config and writable folders
- optionally runs a health check

Required GitHub environment:

```text
adullam secret
```

Required secrets:

- `HOSTINGER_HOST`
- `HOSTINGER_USER`
- `HOSTINGER_PORT`
- `HOSTINGER_SSH_PRIVATE_KEY`
- `HOSTINGER_PUBLIC_PATH`
- `HOSTINGER_DEPLOY_PATH`

Optional secrets:

- `HOSTINGER_SSH_FINGERPRINT`
- `HOSTINGER_HEALTHCHECK_URL`
- `HOSTINGER_KEEP_BACKUPS`

See:

- `deploy/hostinger/README.md`
- `deploy/hostinger/HOSTINGER_SSH_SETUP.md`

## Deployment Preservation Rules

These live files/folders are preserved by deploy exclude rules:

- `db.php`
- `dashboard/db.php`
- `includes/dbconnection.php`
- `token.json`
- root site watch logs
- uploads and generated letter folders
- dashboard logs
- error logs

This is important because production credentials and user uploads should not be overwritten by GitHub deployment.

## Common Development Tasks

### Change Current Cohort

The current cohort is stored in `settings`:

```sql
SELECT value FROM settings WHERE `key` = 'current_cohort';
UPDATE settings SET value = 'January 2026' WHERE `key` = 'current_cohort';
```

The admin system settings page can also manage cohort-related settings.

### Open or Close Registration

Registration toggles live in `settings`, commonly:

- `registration_open`
- hostel settings in `hostel_settings`

Use `/dashboard/system_settings` as superadmin where available.

### Add Programs or MA Focus Areas

Use:

- `/dashboard/manage_programs`
- `/dashboard/manage_focus_areas`

Tables:

- `programs`
- `ma_focus_areas`

### Review Applicants

Main files:

- `dashboard/applicants_list.php`
- `dashboard/ajax/fetch_applicants.php`
- `dashboard/applicant_view.php`
- `dashboard/moderate_action.php`

### Tuition Payment Workflow

Main files:

- `dashboard/payment_proof.php`
- `dashboard/manage_tuition_payments.php`
- `dashboard/ajax/tuition_action.php`
- `dashboard/ajax/tuition_bulk_action.php`
- `dashboard/onboard.php`

Table:

- `tuition_payment`

### Hostel Workflow

Main files:

- `dashboard/register_hostel_unified.php`
- `dashboard/manage_hostel.php`
- `dashboard/hostel_rooms_overview.php`
- `dashboard/ajax/hostel_approval_action.php`
- `dashboard/reassign_student.php`

Tables:

- `hostel_rooms`
- `hostel_registrations`
- `hostel_allocations`
- `hostel_settings`

### Analytics and Reports

Main files:

- `dashboard/analytics.php`
- `dashboard/ajax/analytics_data.php`
- `dashboard/ajax/advanced_analytics.php`
- `dashboard/reports_export.php`
- `dashboard/export_excel.php`
- `dashboard/export_pdf.php`

Most modern analytics are cohort-based. Confirm new metrics include cohort filters before merging.

## Security Notes For Handoff

Before handing this project to another developer or deploying to a new server:

1. Rotate exposed database, SMTP, Google, and SSH credentials.
2. Move credentials out of tracked PHP files where possible.
3. Confirm `.gitignore` excludes dumps, uploads, logs, and private keys.
4. Audit tracked SQL dumps for sensitive user data.
5. Change default admin/superadmin passwords after import.
6. Verify uploaded files cannot execute PHP.
7. Use HTTPS in production.
8. Keep Composer packages updated where compatible with PHP 7.4.

## Coding Conventions In This Project

- Most pages are procedural PHP.
- Database access in `dashboard/` uses PDO through `dashboard/db.php`.
- Some legacy pages use mysqli through older include files.
- Dashboard UI uses Tailwind CDN.
- Some AJAX endpoints return JSON manually.
- Session-based role checks are done per page.
- Clean URLs omit `.php`.

When adding features, follow nearby file patterns unless you are deliberately refactoring.

## Known Technical Debt

- Credentials are present in some config files and should be moved to environment/config outside Git.
- There are multiple historical app areas: root public site, `dashboard/`, `admin/`, `user/`, and `june2025/`.
- There are multiple database connection files.
- Some old pages contain encoding artifacts from prior edits.
- Some dependencies are duplicated in `user/vendor` and root `vendor`.
- SQL dumps are large and may contain sensitive data.
- CI currently excludes `assets/img/` during packaging, while some deployment notes say image handling needs attention. Confirm the desired behavior before changing deployment exclusions.

## Handoff Checklist

Use this checklist when transferring the project to another developer:

- Add the developer as a GitHub collaborator.
- Give access to GitHub Actions environment secrets only if they need deployment rights.
- Share production hosting access separately from GitHub.
- Share a fresh database dump through a secure channel.
- Share SMTP/Gmail app password securely, or rotate and create a new one.
- Share Google API credentials if backup/integration work is needed.
- Confirm the live domain, Hostinger public path, and deployment path.
- Ask the developer to run local setup and log in as student/admin/superadmin.
- Ask the developer to test email, application submission, admission decision, payment proof, and hostel flows.
- Document any production-only cron jobs, especially `dashboard/onboarding_cron.php` and `dashboard/broadcast_cron.php`.

## Collaborator Handoff On GitHub

Yes, you can add the next developer as a collaborator:

1. Open the GitHub repository.
2. Go to Settings.
3. Go to Collaborators and teams.
4. Invite the developer by GitHub username or email.
5. Choose the least privilege needed:
   - Read for review only.
   - Write for development.
   - Maintain/Admin only if they will manage repo settings or deployment.

If the developer will deploy, also grant access to the GitHub Actions environment and confirm they understand the deployment workflow.

## Support Notes

Development and technical support:

- Developer: Noah Abayomi
- Role: CEO, Pinnacle Tech Hub
- Email: `noahabayomi14@gmail.com`
- Company website: `https://pinnacletechhub.com.ng`

Pinnacle Tech Hub developed this project and should be treated as the technical support/vendor contact for handoff, maintenance, and future implementation work.
