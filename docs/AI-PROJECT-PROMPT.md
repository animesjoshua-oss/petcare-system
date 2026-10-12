# PetCare+ Clinic Hub - Complete AI Development Prompt

## 🎯 Project Overview

**Project Name:** PetCare+ Clinic Hub  
**Version:** 1.0.0  
**Status:** Active Development (February 2024 - Present)  
**Type:** Web-based Pet Clinic Management System  
**Purpose:** Connect pet owners with veterinary clinics and groomers, with appointment booking, health records management, and AI-powered pet health assistance.

---

## 🛠️ Technology Stack

### Backend
- **Language:** PHP 8.x
- **Database:** MySQL 8.x
- **Architecture:** MVC-like structure with modular includes
- **Authentication:** Session-based with role-based access control (RBAC)
- **Password Security:** bcrypt hashing via `password_hash()`
- **Database Layer:** PDO with prepared statements

### Frontend
- **HTML/CSS:** Vanilla CSS with CSS custom properties (variables)
- **JavaScript:** Vanilla ES6+ (no frameworks)
- **Responsive Design:** Mobile-first approach (breakpoints: 768px, 480px)
- **Theme System:** Dark/light mode with localStorage persistence

### External Services & APIs
- **AI Service:** Groq API (llama-3.3-70b-versatile model)
  - Primary: `GROQ_API_KEY` for pet health chatbot
  - Vision: `meta-llama/llama-4-scout-17b-16e-instruct` for image analysis
- **Email:** PHPMailer with Gmail SMTP (OTP verification, password resets)
- **Maps:** Google Maps API for clinic locations

### Development Environment
- **Server:** Apache (XAMPP)
- **Package Manager:** Composer
- **Version Control:** Git
- **IDE/Editor:** VS Code (recommended)

### Dependencies (Composer)
```json
{
  "require": {
    "phpmailer/phpmailer": "^6.9"
  }
}
```

---

## 📁 Project Structure

```
petcare-clinic-hub/
│
├── index.php                    # Homepage/Landing page
├── clinic.php                   # Single clinic detail view
├── clinics.php                  # Browse all clinics
├── profile.php                  # User profile management
├── notifications.php            # User notifications center
│
├── auth/                        # Authentication & Authorization
│   ├── login.php               # User login
│   ├── register.php            # New user registration
│   ├── logout.php              # Session termination
│   ├── verify-otp.php          # Email OTP verification
│   ├── forgot-password.php     # Password recovery request
│   ├── reset-password.php      # Password reset with token
│   ├── id-verification.php     # ID document verification
│   └── unauthorized.php        # Access denied page
│
├── api/                         # AJAX API Endpoints
│   ├── appointments.php        # CRUD for appointments
│   ├── chat.php                # AI chatbot backend
│   ├── favorites.php           # Favorite clinics management
│   └── notifications.php       # Notification handling
│
├── dashboard/                   # Role-Based Dashboards
│   ├── owner/                  # Pet Owner Dashboard
│   │   ├── index.php           # Dashboard home
│   │   ├── pets.php            # Pet management
│   │   ├── pet-detail.php      # Single pet view
│   │   ├── book.php            # Appointment booking
│   │   ├── appointments.php    # View appointments
│   │   ├── favorites.php       # Favorite clinics
│   │   ├── rate.php            # Rate services
│   │   ├── ratings.php         # View ratings
│   │   └── ai.php              # AI assistant
│   │
│   ├── vet/                    # Veterinarian Dashboard
│   │   ├── index.php           # Dashboard home
│   │   ├── appointments.php    # Manage appointments
│   │   ├── records.php         # Pet health records
│   │   ├── profile.php         # Vet profile management
│   │   ├── ratings.php         # View ratings
│   │   └── ai.php              # AI assistant
│   │
│   ├── groomer/                # Groomer Dashboard
│   │   ├── index.php           # Dashboard home
│   │   ├── appointments.php    # Manage appointments
│   │   ├── profile.php         # Groomer profile
│   │   ├── ratings.php         # View ratings
│   │   └── ai.php              # AI assistant
│   │
│   └── admin/                  # Vet Admin Dashboard
│       ├── index.php           # Dashboard home
│       ├── users.php           # User management
│       ├── clinics.php         # Clinic management
│       ├── vets.php            # Veterinarian management
│       ├── groomers.php        # Groomer management
│       ├── services.php        # Service catalog
│       ├── appointments.php    # All appointments
│       ├── ratings.php         # Rating moderation
│       ├── reports.php         # Analytics & reports
│       └── ai.php              # AI system management
│
├── assets/                      # Frontend Assets
│   ├── css/
│   │   ├── main.css            # Main stylesheet
│   │   ├── book.css            # Booking interface styles
│   │   └── pcai_widget.css     # AI chatbot widget styles
│   ├── js/
│   │   ├── main.js             # Core JavaScript
│   │   └── pcai_widget.js      # AI chatbot widget
│   └── logo/                   # Images and logos
│
├── includes/                    # PHP Core Components
│   ├── config.php              # Configuration constants
│   ├── db.php                  # Database connection
│   ├── functions.php           # Helper functions
│   ├── mailer.php              # Email functions (PHPMailer)
│   ├── header.php              # Site header component
│   ├── header-auth.php         # Auth page header
│   ├── footer.php              # Site footer component
│   ├── sidebar_owner.php       # Owner sidebar navigation
│   ├── sidebar_vet.php         # Vet sidebar navigation
│   ├── sidebar_groomer.php     # Groomer sidebar navigation
│   ├── sidebar_admin.php       # Vet Admin sidebar navigation
│   ├── clinic_card.php         # Clinic card component
│   ├── pet_card.php            # Pet card component
│   ├── empty_state.php         # Empty state UI component
│   ├── stat_widgets.php        # Dashboard statistics widgets
│   ├── otp_ui.php              # OTP input UI component
│   └── appt_actions.php        # Appointment action buttons
│
├── database/                    # Database Schema
│   └── FULL_SETUP.sql          # Fresh database schema and seed data
│
├── docs/                        # Documentation
│   ├── README.md               # Setup instructions
│   ├── accounts.txt            # Test account credentials
│   ├── _FILE-STRUCTURE.md      # File organization guide
│   └── AI-PROJECT-PROMPT.md    # This file
│
├── uploads/                     # User Uploads
│   └── [pet photos, ID docs]
│
├── vendor/                      # Composer Dependencies
│
├── .htaccess                    # Apache configuration
├── composer.json                # PHP dependencies
├── composer.lock                # Dependency lock file
├── .gitignore                   # Git ignore rules
├── .editorconfig                # Editor configuration
└── .prettierrc                  # Code formatting rules
```

---

## 🗄️ Database Schema

### Database Name: `petcare_clinic_hub`
### Character Set: `utf8mb4_unicode_ci`
### Engine: InnoDB

### Core Tables

#### 1. `roles`
User roles for access control
- `id` (PK)
- `name` (admin, pet_owner, veterinarian, groomer)
- `description`
- `created_at`

#### 2. `users`
All system users
- `id` (PK)
- `role_id` (FK → roles)
- `first_name`, `last_name`
- `email` (unique)
- `phone`
- `password_hash` (bcrypt)
- `profile_photo`
- `address`, `city`, `province`
- `is_active`, `is_email_verified`, `id_verified`
- `id_document_type`, `id_document_number`
- `id_document_path`, `id_document_path_back`
- `id_verified_at`
- `email_verification_token`
- `password_reset_token`, `password_reset_expires`
- `failed_login_attempts`, `locked_until`
- `last_login`
- `created_at`, `updated_at`

#### 3. `clinics`
Veterinary and grooming clinics
- `id` (PK)
- `admin_id` (FK → users)
- `name`, `description`
- `address`, `city`, `province`
- `phone`, `email`
- `latitude`, `longitude`
- `logo`, `banner_image`
- `operating_hours` (JSON)
- `is_active`, `is_verified`
- `created_at`, `updated_at`

#### 4. `services`
Services offered by clinics
- `id` (PK)
- `clinic_id` (FK → clinics)
- `name`, `description`
- `type` (veterinary, grooming)
- `price`
- `duration_minutes`
- `is_available`
- `created_at`, `updated_at`

#### 5. `veterinarians`
Veterinarian profiles
- `id` (PK)
- `user_id` (FK → users)
- `clinic_id` (FK → clinics)
- `license_number`
- `specialization`
- `experience_years`
- `bio`
- `consultation_fee`
- `is_available`
- `created_at`, `updated_at`

#### 6. `groomers`
Groomer profiles
- `id` (PK)
- `user_id` (FK → users)
- `clinic_id` (FK → clinics)
- `specialties`
- `experience_years`
- `bio`
- `hourly_rate`
- `is_available`
- `created_at`, `updated_at`

#### 7. `pets`
Pet records
- `id` (PK)
- `owner_id` (FK → users)
- `name`, `species`, `breed`
- `date_of_birth`, `gender`
- `weight_kg`, `color`
- `microchip_number`
- `photo`
- `medical_notes`, `allergies`
- `is_active`
- `created_at`, `updated_at`

#### 8. `appointments`
Appointment bookings
- `id` (PK)
- `pet_id` (FK → pets)
- `owner_id` (FK → users)
- `service_id` (FK → services)
- `provider_id` (FK → users - vet or groomer)
- `appointment_date`
- `start_time`, `end_time`
- `status` (pending, confirmed, completed, cancelled)
- `notes`, `cancellation_reason`
- `created_at`, `updated_at`

#### 9. `pet_health_records`
Medical history
- `id` (PK)
- `pet_id` (FK → pets)
- `vet_id` (FK → veterinarians)
- `appointment_id` (FK → appointments)
- `visit_date`
- `diagnosis`, `treatment`
- `prescriptions`
- `next_visit_date`
- `attachments`
- `created_at`, `updated_at`

#### 10. `ratings`
Service ratings and reviews
- `id` (PK)
- `appointment_id` (FK → appointments)
- `rater_id` (FK → users)
- `rated_entity_type` (veterinarian, groomer, clinic)
- `rated_entity_id`
- `rating` (1-5)
- `review_text`
- `created_at`

#### 11. `favorite_clinics`
User favorites
- `id` (PK)
- `user_id` (FK → users)
- `clinic_id` (FK → clinics)
- `created_at`

#### 12. `notifications`
User notifications
- `id` (PK)
- `user_id` (FK → users)
- `type` (appointment, rating, system)
- `title`, `message`
- `related_entity_type`, `related_entity_id`
- `is_read`
- `created_at`

#### 13. `ai_summaries`
AI-generated summaries
- `id` (PK)
- `entity_type` (appointment, pet, health_record)
- `entity_id`
- `summary_type`
- `content`
- `created_at`

#### 14. `ai_recommendations`
AI recommendations
- `id` (PK)
- `user_id` (FK → users)
- `recommendation_type`
- `content`
- `priority`
- `status`
- `created_at`

---

## 👥 User Roles & Permissions

### 1. **Pet Owner** (`pet_owner`)
**Capabilities:**
- Register and manage account
- Add and manage pets
- Browse clinics and services
- Book appointments with vets/groomers
- View pet health records
- Rate and review services
- Save favorite clinics
- Chat with AI health assistant
- Receive notifications

**Dashboard Access:**
- `/dashboard/owner/`

### 2. **Veterinarian** (`veterinarian`)
**Capabilities:**
- Manage professional profile
- View assigned appointments
- Access pet health records
- Create/update medical records
- Respond to appointment requests
- View ratings and reviews
- Use AI assistant for diagnostics
- Receive appointment notifications

**Dashboard Access:**
- `/dashboard/vet/`

### 3. **Groomer** (`groomer`)
**Capabilities:**
- Manage professional profile
- View assigned appointments
- Respond to grooming requests
- View ratings and reviews
- Manage availability
- Receive notifications

**Dashboard Access:**
- `/dashboard/groomer/`

### 4. **Vet Admin** (`admin`)
**Capabilities:**
- Full system access
- User management (CRUD)
- Clinic management
- Vet/groomer approval and management
- Service catalog management
- View all appointments
- Monitor ratings
- Generate reports and analytics
- System configuration

**Dashboard Access:**
- `/dashboard/admin/`

---

## 🔐 Authentication & Security

### Authentication Flow
1. **Registration** (`auth/register.php`)
   - Validate input (email uniqueness, password strength)
   - Hash password with `password_hash()`
   - Create user record with `is_email_verified=0`
   - Generate OTP and send via email
   - Redirect to OTP verification

2. **Email Verification** (`auth/verify-otp.php`)
   - User enters 6-digit OTP
   - Verify OTP against database
   - Set `is_email_verified=1`
   - Create session

3. **Login** (`auth/login.php`)
   - Validate credentials
   - Check `failed_login_attempts` (max 5)
   - Verify password with `password_verify()`
   - Check email verification status
   - Create session with user data
   - Update `last_login` timestamp

4. **Password Reset**
   - Request: `auth/forgot-password.php` - sends reset link
   - Reset: `auth/reset-password.php` - validates token and updates password

5. **ID Verification** (`auth/id-verification.php`)
   - Upload government ID (front/back)
   - Admin reviews and approves
   - Sets `id_verified=1`

### Session Management
```php
$_SESSION['user_id']
$_SESSION['role']
$_SESSION['first_name']
$_SESSION['last_name']
$_SESSION['email']
```

### Security Measures
- **Password Hashing:** bcrypt via `password_hash()`
- **SQL Injection Prevention:** PDO prepared statements
- **XSS Prevention:** `htmlspecialchars()` on output
- **CSRF Protection:** Session tokens (to be implemented)
- **Rate Limiting:** Failed login attempts tracking
- **Session Security:** httponly, samesite cookies
- **File Upload Validation:** Type and size checks

---

## 🤖 AI Features (Groq Integration)

### PetCare AI Assistant
**Model:** llama-3.3-70b-versatile  
**Purpose:** Pet health consultation chatbot

**Features:**
- Symptom analysis
- Health recommendations
- Emergency guidance
- Vaccination reminders
- Nutrition advice
- Behavioral questions

**Implementation:**
- Frontend: `assets/js/pcai_widget.js`
- Backend: `api/chat.php`
- Styling: `assets/css/pcai_widget.css`

**API Integration:**
```php
// Groq API endpoint
$url = 'https://api.groq.com/openai/v1/chat/completions';

// Request structure
{
  "model": "llama-3.3-70b-versatile",
  "messages": [
    {"role": "system", "content": "You are a pet health assistant..."},
    {"role": "user", "content": "My dog is limping..."}
  ],
  "temperature": 0.7,
  "max_tokens": 1000
}
```

**System Prompt:**
```
You are PetCare AI, a compassionate and knowledgeable pet health assistant. 
You provide evidence-based advice for pet owners about their pets' health, 
behavior, and wellness. Always emphasize when veterinary attention is needed.
```

---

## 📧 Email System (PHPMailer)

### Configuration
```php
MAIL_HOST: smtp.gmail.com
MAIL_PORT: 587 (TLS)
MAIL_USER: techytropa@gmail.com
MAIL_PASS: [App Password - 16 chars]
```

### Email Types
1. **OTP Verification** - Registration email verification
2. **Password Reset** - Password recovery link
3. **Appointment Confirmation** - Booking confirmation
4. **Appointment Reminder** - 24h before appointment
5. **Appointment Cancellation** - Cancellation notice

### Template Structure
```php
include 'includes/mailer.php';
sendEmail(
    $to,
    $subject,
    $htmlBody,
    $plainTextBody
);
```

---

## 🎨 Frontend Guidelines

### CSS Architecture
- **CSS Custom Properties** for theming
- **Mobile-first** responsive design
- **BEM-like** naming convention
- **Dark mode** support via CSS variables

### Dark Mode Implementation
```css
:root {
  --bg-primary: #ffffff;
  --text-primary: #1a1a1a;
}

[data-theme="dark"] {
  --bg-primary: #1a1a1a;
  --text-primary: #ffffff;
}
```

### JavaScript Patterns
- **ES6+ syntax**
- **Fetch API** for AJAX
- **Event delegation**
- **Module pattern** for organization

### Responsive Breakpoints
```css
/* Mobile: default */
/* Tablet: 768px */
@media (max-width: 768px) { }
/* Mobile: 480px */
@media (max-width: 480px) { }
```

---

## 🚀 Development Workflow

### Initial Setup
1. **Clone Repository**
   ```bash
   git clone [repository-url]
   cd petcare-clinic-hub
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   - Create database: `petcare_clinic_hub`
   - Import: `database/FULL_SETUP.sql`

4. **Configure Environment**
   - Edit `includes/config.php`
   - Set database credentials
   - Add API keys

5. **Start Server**
   - Start XAMPP (Apache + MySQL)
   - Visit: `http://localhost/petcare-clinic-hub - web bagong bago`

### Test Accounts
See `docs/accounts.txt` for complete list

**Quick Access:**
- Vet Admin: `admin@petcareplus.com` / `admin123`
- Vet: `vet@petcareplus.com` / `petcare123`
- Groomer: `groomer@petcareplus.com` / `petcare123`

### Common Development Tasks

#### Creating a New Page
1. Create PHP file in appropriate directory
2. Include header: `include '../includes/header.php';`
3. Add authentication check
4. Implement page logic
5. Include footer: `include '../includes/footer.php';`

#### Adding an API Endpoint
1. Create file in `/api/` directory
2. Start with authentication check
3. Validate input
4. Process request
5. Return JSON response

#### Database Query Pattern
```php
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Handle error
}
```

---

## 🐛 Known Issues & TODO

### Known Issues
- [ ] Pagination needed for clinic listings
- [ ] N+1 query issues in some dashboards
- [ ] File upload size validation inconsistent
- [ ] Mobile navigation UX needs improvement
- [ ] Session timeout handling could be better

### TODO / Roadmap
- [ ] Add unit tests (PHPUnit)
- [ ] Implement CSRF tokens
- [ ] Add appointment calendar view
- [ ] Implement real-time chat
- [ ] Add push notifications
- [ ] Create mobile app (PWA)
- [ ] Add payment integration
- [ ] Implement advanced search filters
- [ ] Add multi-language support
- [ ] Create admin analytics dashboard

---

## 📝 Code Style Guidelines

### PHP
- **PSR-12** coding standards
- Use type declarations when possible
- Meaningful variable names
- Comment complex logic
- Error handling with try-catch

### JavaScript
- **ES6+** modern syntax
- `const` and `let` (no `var`)
- Arrow functions preferred
- Async/await for promises
- JSDoc comments for functions

### SQL
- UPPERCASE for SQL keywords
- Prepared statements always
- Meaningful table/column names
- Indexes on foreign keys
- Comments for complex queries

### File Naming
- **PHP:** `snake_case.php`
- **CSS:** `kebab-case.css`
- **JS:** `snake_case.js`
- **Images:** `kebab-case.jpg`

---

## 🔧 Environment Variables

### Development (.env equivalent in config.php)
```php
APP_ENV=development
APP_URL=http://localhost/petcare-clinic-hub - web bagong bago
DB_HOST=localhost
DB_NAME=petcare_clinic_hub
DB_USER=root
DB_PASS=

GROQ_API_KEY=your_groq_api_key_here
MAIL_USER=techytropa@gmail.com
MAIL_PASS=[App Password]
GOOGLE_MAPS_KEY=your_google_maps_key_here
```

### Production
```php
APP_ENV=production
APP_URL=https://petcareclinic.yzz.me
DB_HOST=sql207.yzz.me
DB_NAME=yzzme_42232698_petcareclinic
DB_USER=yzzme_42232698
DB_PASS=U6EaRXTpkbS5
```

---

## 📦 Deployment Checklist

### Pre-Deployment
- [ ] Switch `APP_ENV` to 'production' in config.php
- [ ] Update database credentials
- [ ] Update `APP_URL`
- [ ] Disable error display
- [ ] Test all user flows
- [ ] Backup database
- [ ] Review security headers
- [ ] Optimize assets (minify CSS/JS)
- [ ] Test email delivery

### Deployment Steps
1. Export database from local
2. Create database on production server
3. Import database
4. Upload files via FTP/SFTP
5. Run composer install on server
6. Set file permissions (uploads folder)
7. Test functionality
8. Monitor error logs

### Post-Deployment
- [ ] Test login/registration
- [ ] Verify email delivery
- [ ] Test appointment booking
- [ ] Check AI chatbot
- [ ] Verify all CRUD operations
- [ ] Test on mobile devices
- [ ] Monitor server logs

---

## 🧪 Testing Guidelines

### Manual Testing
1. **Authentication Flow**
   - Register new account
   - Verify OTP
   - Login/logout
   - Password reset

2. **Pet Owner Features**
   - Add pet
   - Browse clinics
   - Book appointment
   - Rate service

3. **Provider Features**
   - Update profile
   - Manage appointments
   - Add health records (vet only)

4. **Vet Admin Features**
   - User management
   - Clinic approval
   - View reports

### Browser Testing
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS)
- Chrome Mobile (Android)

---

## 📚 Key Functions Reference

### Authentication (includes/functions.php)
```php
isLoggedIn()                    // Check if user is authenticated
requireLogin()                  // Redirect if not logged in
hasRole($role)                  // Check user role
logout()                        // Destroy session
```

### Database (includes/db.php)
```php
$pdo                           // Global PDO instance
getPDO()                       // Get database connection
```

### Email (includes/mailer.php)
```php
sendEmail($to, $subject, $html, $text)
sendOTP($email, $otp)
sendPasswordReset($email, $token)
```

### Utilities (includes/functions.php)
```php
sanitizeInput($data)           // Clean user input
formatDate($date)              // Format date for display
uploadFile($file, $directory)  // Handle file uploads
generateOTP()                  // Generate 6-digit OTP
generateToken()                // Generate secure token
```

---

## 🎯 AI Assistant Instructions

When working on this project, you should:

### General Approach
1. **Understand the context** - Review relevant files before making changes
2. **Follow existing patterns** - Match the codebase style and structure
3. **Maintain security** - Always use prepared statements, validate input, escape output
4. **Test thoroughly** - Consider edge cases and error scenarios
5. **Document changes** - Comment complex logic and update docs

### File Modification Guidelines
- **Never expose sensitive data** - Keep API keys and passwords in config.php
- **Use PDO prepared statements** - Never concatenate SQL
- **Escape output** - Use `htmlspecialchars()` for user data
- **Validate input** - Check all user input server-side
- **Handle errors gracefully** - Use try-catch and log errors

### Common Tasks

#### Adding a New Feature
1. Plan the feature (UI, database, API)
2. Update database schema if needed
3. Create/modify necessary files
4. Test with different user roles
5. Update documentation

#### Fixing a Bug
1. Reproduce the bug
2. Identify root cause
3. Implement fix
4. Test the fix
5. Check for similar issues elsewhere

#### API Endpoint Development
1. Create file in `/api/`
2. Add authentication check
3. Validate input parameters
4. Execute business logic
5. Return proper JSON response
6. Handle errors appropriately

#### UI Development
1. Match existing design patterns
2. Ensure responsive design
3. Support dark mode
4. Add proper form validation
5. Include loading states

### Code Review Checklist
- [ ] Security: SQL injection, XSS, CSRF protected?
- [ ] Authentication: Proper role checks?
- [ ] Validation: Input validated and sanitized?
- [ ] Error Handling: Errors caught and logged?
- [ ] Database: Using prepared statements?
- [ ] Performance: Queries optimized?
- [ ] UI/UX: Responsive and accessible?
- [ ] Documentation: Code commented appropriately?

---

## 🆘 Troubleshooting

### Common Issues

**Issue:** Can't login after registration
- Check if OTP was sent (check mail logs)
- Verify `is_email_verified` is set to 1
- Check session is started properly

**Issue:** Database connection failed
- Verify XAMPP MySQL is running
- Check credentials in config.php
- Ensure database exists

**Issue:** AI chatbot not responding
- Check `GROQ_API_KEY` is valid
- Verify API endpoint is accessible
- Check PHP curl extension is enabled

**Issue:** Emails not sending
- Verify Gmail App Password is correct
- Check SMTP settings in config.php
- Enable less secure apps (if needed)

**Issue:** File uploads failing
- Check uploads folder permissions
- Verify `MAX_FILE_SIZE` in config
- Check PHP upload_max_filesize

---

## 📖 Additional Resources

### Documentation
- [PHP Manual](https://www.php.net/manual/en/)
- [MySQL Reference](https://dev.mysql.com/doc/)
- [PDO Tutorial](https://www.php.net/manual/en/book.pdo.php)
- [PHPMailer Docs](https://github.com/PHPMailer/PHPMailer)
- [Groq API Docs](https://console.groq.com/docs)

### Learning Resources
- PHP The Right Way
- OWASP Security Guidelines
- REST API Best Practices
- SQL Optimization Techniques

---

## 📄 License & Credits

**License:** Educational/Portfolio Use Only  
**Author:** [Your Name]  
**Started:** February 2024  
**Last Updated:** December 2024

**Credits:**
- PHPMailer for email functionality
- Groq for AI capabilities
- Google Maps for location services
- Built with ☕ and Stack Overflow

---

## 🔄 Version History

**v1.0.0** (Current)
- Initial release
- Core features implemented
- Multi-role dashboard system
- AI chatbot integration
- Email notifications
- Appointment booking system

---

## 💡 Development Philosophy

This project follows these principles:

1. **Security First** - Always validate, sanitize, and escape
2. **User Experience** - Make it intuitive and responsive
3. **Code Quality** - Write maintainable, readable code
4. **Performance** - Optimize queries and assets
5. **Documentation** - Document as you code
6. **Testing** - Test thoroughly before deploying

---

**End of Project Prompt**

This document should be used as a comprehensive reference when working with AI assistants on the PetCare+ Clinic Hub project. Keep it updated as the project evolves.
