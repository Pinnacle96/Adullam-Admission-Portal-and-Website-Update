# 🎓 Adullam Seminary Portal

The **Adullam Seminary Portal** is a comprehensive web-based management system designed to streamline the admission process, student management, and administrative operations for **RCN Theological Seminary - Adullam**.

This robust platform handles the entire student lifecycle—from initial application and document submission to tuition payments, hostel allocation, and academic profile management.

---

## 🚀 Key Features

### 👨‍🎓 Student Portal
*   **Multistep Application Form**: A user-friendly, 7-step application wizard with save-and-continue functionality.
*   **Real-time Status Tracking**: Students can track their admission status (Submitted, In Review, Admitted, Rejected) directly from their dashboard.
*   **Document Management**: Secure upload and management of required documents (Passport, Transcripts, Certificates).
*   **Tuition Payments**: Integrated interface for uploading and verifying tuition payment proofs.
*   **Hostel Registration**: automated checks for hostel availability and room allocation for onsite students.
*   **Admission Letters**: Automatic generation and download of official admission letters upon acceptance.

### 🛠 Admin Dashboard
*   **Application Review**: Comprehensive tools to review applicant details, documents, and references.
*   **Admission Management**: One-click admission or rejection with automated email notifications.
*   **Hostel Management**: Manage room capacities, assign students, and view occupancy reports.
*   **Financial Oversight**: Verify tuition payments and track outstanding fees.
*   **Reporting & Analytics**: Generate PDF reports, export data to Excel, and view dashboard analytics (Applicant distribution, Gender stats, etc.).
*   **System Settings**: Configure current cohorts, toggle registration availability, and manage focus areas.

### ⚙️ System Capabilities
*   **Automated Email Notifications**: Transactional emails for registration, submission, admission decisions, and password resets.
*   **PDF Generation**: Dynamic generation of application summaries and admission letters.
*   **Google Drive Backup**: Automated database and file backups to Google Drive.
*   **Security**: Role-based access control (RBAC), secure password hashing, and input validation.

---

## 💻 Technology Stack

*   **Backend**: PHP 7.4+ (Native)
*   **Frontend**: HTML5, JavaScript (Vanilla), Tailwind CSS (CDN)
*   **Database**: MySQL / MariaDB
*   **PDF Engine**: FPDF / TCPDF
*   **Email Service**: PHPMailer (SMTP)
*   **Server**: Apache (via WAMP/XAMPP/LAMP)

---

## 📂 Project Structure

```bash
adullam/
├── admin/                  # Legacy/CMS admin interface (Courses, Notices)
├── assets/                 # Public static assets (Images, CSS, Fonts)
├── dashboard/              # ⚡ CORE APPLICATION LOGIC
│   ├── components/         # Reusable UI parts (Sidebar, Navbar)
│   ├── letters/            # Generated admission letters
│   ├── uploads/            # User uploaded documents (Ignored by Git)
│   ├── admin_dashboard.php # Main Admin Controller
│   ├── application_form.php# Multistep Student Application
│   ├── dashboard.php       # Student Dashboard Home
│   └── ...                 # Various action scripts & controllers
├── db/                     # Database SQL dumps
├── fees/                   # Fee structure PDFs
├── google-api-client/      # Google Drive API Integration (Ignored by Git)
├── includes/               # Global helper functions and headers
├── vendor/                 # Composer Dependencies (Ignored by Git)
├── db.php                  # Database connection configuration
└── index.php               # Landing page / Entry point
```

---

## 🛠 Installation & Setup

### Prerequisites
*   A local server environment like **WAMP**, **XAMPP**, or **MAMP**.
*   **PHP 7.4** or higher.
*   **Composer** (for installing dependencies).
*   **MySQL** database.

### Steps
1.  **Clone/Download**: Place the project folder in your server's root directory (e.g., `c:\wamp64\www\adullam`).
2.  **Restore Dependencies**:
    Since the `vendor` folder is ignored by version control to keep the repository light, you must install dependencies manually.
    ```bash
    cd c:\wamp64\www\adullam
    composer install
    ```
    *If you do not have Composer installed, download it from [getcomposer.org](https://getcomposer.org/).*

3.  **Restore Upload Folders**:
    Ensure the following directories exist and have write permissions:
    *   `dashboard/uploads/`
    *   `dashboard/letters/admission_letters/`
    *   `dashboard/logs/`

4.  **Database Setup**:
    *   Create a new MySQL database named `adullam`.
    *   Import the SQL file from `db/u499616432_adullamn_cams.sql` (or the latest `.sql` file available).

5.  **Configuration**:
    *   Open `db.php` (and `dashboard/db.php` if separate) and update the database credentials:
        ```php
        $host = 'localhost';
        $db   = 'adullam';
        $user = 'root';
        $pass = ''; // Your DB password
        ```
    *   Configure SMTP settings in `dashboard/mailer.php` for email functionality.

6.  **Run**:
    *   Start your server.
    *   Navigate to `http://localhost/adullam` in your browser.

---

## 📝 Usage Guide

### For Applicants
1.  **Register**: Create an account via the registration page.
2.  **Dashboard**: Access the student dashboard to start a new application.
3.  **Apply**: Complete the 7-step application form.
4.  **Track**: Monitor admission status and upload proof of payment if admitted.

### For Admins
1.  **Login**: Access the admin panel (typically `/dashboard/login.php` or `/admin`).
2.  **Review**: Go to "Manage Applications" to review pending submissions.
3.  **Action**: Approve or Reject applications.
4.  **Manage**: Use the sidebar to manage payments, hostels, and system settings.
