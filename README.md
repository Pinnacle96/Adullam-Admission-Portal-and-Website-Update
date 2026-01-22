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
│   ├── uploads/            # User uploaded documents
│   ├── admin_dashboard.php # Main Admin Controller
│   ├── application_form.php# Multistep Student Application
│   ├── dashboard.php       # Student Dashboard Home
│   └── ...                 # Various action scripts & controllers
├── db/                     # Database SQL dumps
├── fees/                   # Fee structure PDFs
├── google-api-client/      # Google Drive API Integration
├── includes/               # Global helper functions and headers
├── db.php                  # Database connection configuration
└── index.php               # Landing page / Entry point
```

---

## 🛠 Installation & Setup

### Prerequisites
*   A local server environment like **WAMP**, **XAMPP**, or **MAMP**.
*   **PHP 7.4** or higher.
*   **MySQL** database.

### Steps
1.  **Clone/Download**: Place the project folder in your server's root directory (e.g., `c:\wamp64\www\adullam`).
2.  **Database Setup**:
    *   Create a new MySQL database named `adullam`.
    *   Import the SQL file from `db/u499616432_adullamn_cams.sql` (or the latest `.sql` file available).
3.  **Configuration**:
    *   Open `db.php` (and `dashboard/db.php` if separate) and update the database credentials:
        ```php
        $host = 'localhost';
        $db   = 'adullam';
        $user = 'root';
        $pass = ''; // Your DB password
        ```
    *   Configure SMTP settings in `dashboard/mailer.php` for email functionality.
4.  **Run**:
    *   Start your server.
    *   Navigate to `http://localhost/adullam` in your browser.

---

## 📝 Usage Guide

### For Applicants
1.  **Register**: Create an account via the registration page.
2.  **Login**: Access the student dashboard.
3.  **Apply**: Click "Start Application" to begin the 7-step process.
    *   *Note: Progress is saved automatically.*
4.  **Submit**: Review your details and submit. The application enters **Read-Only Mode** upon submission.
5.  **Wait**: Check the dashboard for status updates.

### For Admins
1.  **Login**: Access the admin panel (typically `/dashboard/admin_login.php` or verified admin account).
2.  **Review**: Navigate to "Pending Applications" to review submissions.
3.  **Action**: Use the "Admit" or "Reject" buttons to process applications.
4.  **Manage**: Use the sidebar to access Hostel, Tuition, and Settings management.

---

## 🤝 Contribution
1.  Fork the repository.
2.  Create a feature branch (`git checkout -b feature/NewFeature`).
3.  Commit your changes (`git commit -m 'Add some NewFeature'`).
4.  Push to the branch (`git push origin feature/NewFeature`).
5.  Open a Pull Request.

---

## 📄 License
This project is proprietary software developed for **RCN Theological Seminary**. Unauthorized copying, distribution, or modification is strictly prohibited.

## 👨‍� Credits & Support

This project is proudly developed by **Pinnacle Tech Hub**.

**Lead Developer:**
**Noah Abayomi**
*CEO, Pinnacle Tech Hub*

📞 **Contact**: +234 703 207 8859 (Call & WhatsApp)
