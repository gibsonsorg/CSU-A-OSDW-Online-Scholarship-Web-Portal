# CSU Aparri Campus Scholarship and Student Financial Assistance Program
Charisma joy Cabaya,
Gibson Sabalburo,
Carl justine Pacleb
## Project Overview

The **CSU Aparri Campus Scholarship and Student Financial Assistance Program** is a comprehensive web-based management system designed to streamline the scholarship application process for Cagayan State University Aparri Campus. This system provides a centralized platform where students can discover, apply for, and track various scholarship opportunities offered by the institution, while administrators can efficiently manage, review, and process scholarship applications.

The system addresses the need for a transparent, efficient, and user-friendly platform that bridges the gap between students seeking financial assistance and the institution's diverse scholarship offerings, including academic grants, non-academic grants, sports-based scholarships, and government-funded financial aid programs.

---

## Features

### For Students

- **User Authentication**: Secure registration and login system with email verification
- **Scholarship Discovery**: Browse comprehensive catalog of available scholarships including:
  - Academic Grants
  - Non-Academic Grants (Athletics, Arts & Culture, Employee Benefits, etc.)
  - Government-Funded Scholarships
  - Private Foundation Grants
- **Application Management**: Submit detailed scholarship applications with the following information:
  - Personal Information (Name, Contact Details, Course)
  - Academic/Non-Academic Details
  - Document Upload (PDF, JPG, PNG, DOC, DOCX)
- **Duplicate Application Prevention**: Restrict students from applying multiple times for the same scholarship
- **Application Tracking**: View submission status and history
- **Responsive Dashboard**: User-friendly interface optimized for desktop and mobile devices

### For Administrators

- **Dashboard Overview**: View summary of recent applications
- **Application Management Interface**: Comprehensive admin panel to manage all submissions
- **Application Review**: 
  - View complete application details including uploaded documents
  - Assess student qualifications
  - Review submitted information
- **Application Processing**:
  - Approve qualified applications
  - Reject applications with reasons
  - Track application status (Pending, Approved, Rejected)
- **Reporting Capabilities**: Track and manage applications efficiently

### System-Wide Features

- **Database Persistence**: All applications securely stored in MySQL database
- **Email Verification**: Secure user accounts with email confirmation
- **Role-Based Access Control**: Distinct user interfaces for students and administrators
- **Data Validation**: Comprehensive input validation and error handling
- **Logging System**: Track application submissions and system activities

---

## User Roles

### Student Role
- **Access Level**: Standard User
- **Capabilities**:
  - Create account and manage profile
  - Browse all available scholarships
  - Submit scholarship applications
  - Upload supporting documents
  - Track application status
  - Cannot access admin functions

### Administrator Role
- **Access Level**: Privileged User
- **Capabilities**:
  - Access admin dashboard
  - View all submitted applications
  - Review application details
  - Approve or reject applications
  - Manage application status
  - Export application data
  - Cannot submit student applications

---

## Technology Stack

### Backend
- **Framework**: Laravel 11
- **Language**: PHP 8.1+
- **Package Manager**: Composer

### Database
- **Database System**: MySQL 5.7+
- **ORM**: Eloquent (Laravel ORM)

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Custom styling with Tailwind CSS
- **JavaScript**: Vanilla JS for interactivity
- **Build Tool**: Vite

### Additional Libraries
- **Authentication**: Laravel Breeze (included authentication scaffolding)
- **Email Verification**: Laravel built-in verification system
- **File Storage**: Laravel Storage (public/private disk support)
- **Logging**: Monolog (via Laravel)

### Development Environment
- **Server**: XAMPP (Apache, MySQL, PHP)
- **Version Control**: Git
- **Package Management**: NPM (for frontend dependencies)

---

## Installation Guide

### Prerequisites

Before installing the project, ensure you have the following installed on your system:

- **PHP 8.1 or higher**
- **Composer** (PHP package manager)
- **MySQL 5.7 or higher**
- **Node.js and NPM** (for frontend asset compilation)
- **Git** (for version control)
- **XAMPP or similar** (for local development)

### Step 1: Clone the Repository

```bash
cd c:\xampp\htdocs
git clone <repository-url> capstoneprojectupdated5.0
cd capstoneprojectupdated5.0
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csu_aparri_scholarships
DB_USERNAME=root
DB_PASSWORD=
```

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

### Step 5: Install Frontend Dependencies

```bash
npm install
```

### Step 6: Build Frontend Assets

```bash
npm run build
```

For development with automatic rebuilding:

```bash
npm run dev
```

---

## Database Setup

### Step 1: Create Database

Open phpMyAdmin or MySQL command line and create a new database:

```sql
CREATE DATABASE csu_aparri_scholarships;
```

### Step 2: Run Migrations

Execute all database migrations to create tables:

```bash
php artisan migrate
```

This will create the following tables:

- `users` - User accounts (students and admins)
- `student_profiles` - Student scholarship applications
- `cache` - Application cache
- `jobs` - Queue jobs storage

### Step 3: Seed Initial Data (Optional)

Populate the database with initial admin user:

```bash
php artisan db:seed
```

Or run specific seeder:

```bash
php artisan db:seed --class=AdminSeeder
```

### Database Schema Overview

#### Users Table
- User authentication and role management
- Tracks student and admin accounts
- Role field: 'student' or 'admin'

#### Student Profiles Table
- Stores scholarship applications
- Fields: first_name, middle_name, last_name, email, course, scholarship_name, scholarship_type, application_status, uploads (JSON), timestamps
- Application status: pending, approved, rejected

#### Additional Tables
- Cache, Jobs, and Sessions tables for application functionality

---

## How to Run Locally

### Step 1: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** server
3. Start **MySQL** server

### Step 2: Navigate to Project Directory

```bash
cd c:\xampp\htdocs\capstoneprojectupdated5.0
```

### Step 3: Start Laravel Development Server

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

### Step 4: Access the Application

**Student Access**:
- Visit: `http://localhost:8000`
- Navigate to: Register → Login
- Browse scholarships and submit applications

**Admin Access**:
- Visit: `http://localhost:8000/admin`
- Login with admin credentials
- Manage applications from dashboard

### Step 5: Monitor File Changes (Optional)

In a separate terminal, run:

```bash
npm run dev
```

This enables automatic compilation of CSS/JS changes during development.

---

## Project Structure

```
capstoneprojectupdated5.0/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── StudentProfileController.php    # Handles student applications
│   │   │   ├── ProfileController.php           # User profile management
│   │   │   └── Auth/                           # Authentication controllers
│   │   └── Requests/                           # Form request validations
│   └── Models/
│       ├── User.php                            # User model
│       └── StudentProfile.php                  # Student application model
├── database/
│   ├── migrations/                             # Database schema definitions
│   ├── seeders/                                # Database seeders
│   └── factories/                              # Model factories
├── resources/
│   ├── views/
│   │   ├── student/                            # Student pages
│   │   ├── admin/                              # Admin pages
│   │   ├── auth/                               # Authentication pages
│   │   ├── layouts/                            # Layout components
│   │   └── components/                         # Reusable components
│   ├── css/                                    # Stylesheets
│   └── js/                                     # JavaScript files
├── routes/
│   ├── web.php                                 # Web routes
│   ├── auth.php                                # Authentication routes
│   └── console.php                             # Console commands
├── config/                                     # Configuration files
├── public/                                     # Public assets (CSS, JS, images)
├── storage/                                    # File storage
├── tests/                                      # Test files
└── composer.json                               # PHP dependencies
```

---

## API Endpoints

### Authentication Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/register` | Student registration page |
| GET | `/login` | Student login page |
| POST | `/login` | Process login |
| POST | `/register` | Process registration |

### Student Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard` | Student dashboard with scholarships |
| POST | `/student-profiles` | Submit scholarship application |
| GET | `/profile` | View student profile |
| PATCH | `/profile` | Update student profile |

### Admin Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin` | Admin dashboard |
| GET | `/admin/applications/{id}` | View application details (JSON) |
| POST | `/admin/applications/{id}/approve` | Approve application |
| POST | `/admin/applications/{id}/reject` | Reject application |

---

## Key Features Implementation

### Duplicate Application Prevention

The system prevents students from applying multiple times for the same scholarship:

- **Location**: `app/Http/Controllers/StudentProfileController.php`
- **Logic**: Checks if user (identified by email) has already applied for the specific scholarship
- **Result**: Displays error message and prevents duplicate submissions

### File Upload Management

Students can upload supporting documents:

- **Allowed Formats**: PDF, JPG, JPEG, PNG, DOC, DOCX
- **Maximum Size**: 5MB per file
- **Storage**: Secure storage in `storage/app/student_profiles`
- **Access**: Admin can view uploaded documents

### Application Status Tracking

Applications progress through specific statuses:

- **Pending**: Initial submission state
- **Approved**: Reviewed and accepted by admin
- **Rejected**: Reviewed and declined by admin

---

## Security Measures

- **Password Hashing**: Bcrypt encryption for user passwords
- **CSRF Protection**: Laravel CSRF tokens on all forms
- **Email Verification**: Required email confirmation before account activation
- **Input Validation**: Server-side validation on all inputs
- **File Validation**: Type and size restrictions on uploaded files
- **SQL Injection Prevention**: Eloquent ORM parameterized queries
- **Authorization**: Middleware-based access control

---

## Future Improvements

1. **Scholarship Selection Priority System**
   - Allow students to indicate scholarship preferences
   - Implement ranking system for applications

2. **Automated Email Notifications**
   - Send email updates when applications are approved/rejected
   - Reminder emails for incomplete applications
   - Application status notifications

3. **Advanced Reporting Dashboard**
   - Generate scholarship statistics and analytics
   - Export reports in Excel/PDF formats
   - Track application trends over time

4. **Multi-Language Support**
   - Internationalization (i18n) implementation
   - Tagalog language support

5. **Enhanced Document Management**
   - Allow document preview in admin panel
   - Support for additional file formats
   - Automatic document scanning for completeness

6. **Communication System**
   - Student-admin messaging system
   - Application feedback and comments
   - Decision reasoning documentation

7. **API Development**
   - RESTful API for third-party integrations
   - Mobile application compatibility
   - Data export capabilities

8. **Performance Optimization**
   - Database query optimization
   - Caching implementation
   - Asset optimization and CDN integration

9. **Testing Coverage**
   - Unit tests for models and controllers
   - Feature tests for user workflows
   - End-to-end testing automation

10. **User Profile Enhancements**
    - Student academic history
    - Financial information management
    - Profile verification system

---

## Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check database credentials in `.env` file
- Ensure database exists: `csu_aparri_scholarships`

### "No application encryption key" Error
- Run: `php artisan key:generate`

### Missing Vendor/Node Modules
- Run: `composer install` and `npm install`

### Asset Files Not Loading
- Run: `npm run build`
- Check if `public/build` directory exists

### Permission Denied on Storage
- Ensure `storage` and `bootstrap/cache` are writable
- Run: `chmod -R 775 storage bootstrap/cache` (Linux/Mac)

### Email Verification Not Working
- Ensure MAIL_* variables are configured in `.env`
- Use mailtrap or similar service for testing

---

## Testing

### Run Tests

```bash
php artisan test
```

### Run Specific Test

```bash
php artisan test tests/Feature/ProfileTest.php
```

---

## Deployment Considerations

- Store `.env` file securely on production server
- Use HTTPS for all communications
- Enable query caching for performance
- Set appropriate file permissions
- Configure backup procedures
- Monitor error logs regularly
- Implement rate limiting for API endpoints

---

## License

This project is developed as a capstone project for Cagayan State University Aparri Campus. All rights reserved.

---

## Support & Contact

For technical support, feature requests, or bug reports, please contact the development team or submit an issue through the project repository.

---

## Author

**Project Development Team**  
Cagayan State University Aparri Campus  
March 2026

**Capstone Project**: CSU Aparri Campus Scholarship and Student Financial Assistance Program

---

## Changelog

### Version 1.0.0 (March 2026)
- Initial project release
- Student authentication and registration
- Scholarship application submission
- Admin application management
- Duplicate application prevention
- Database implementation
- User dashboard and admin panel

---

**Last Updated**: March 5, 2026  
**Project Status**: Active Development
