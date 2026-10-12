# PetCare+ Clinic Hub

PetCare+ is a PHP and MySQL web application for finding local veterinary and grooming clinics, booking appointments, and managing pet care records. It includes role based dashboards for pet owners, veterinarians, groomers, and administrators.

## Features

- Clinic search, profiles, services, and favorites
- Appointment booking, cancellation, status updates, and ratings
- Pet profiles and health records
- Email verification and account approval
- Vet Admin, veterinarian, groomer, and pet owner dashboards
- Optional Groq powered chat and pet image assistance
- Dark mode

## Start locally

Use the step by step XAMPP setup in [LOCAL_SETUP.md](LOCAL_SETUP.md). The project uses PHP 8.1+, MySQL, PDO, cURL, and Composer dependencies. AI and email integrations need their own credentials; the rest of the site can be explored without an AI key.

## Project map

| Path | Purpose |
| --- | --- |
| `auth/` | Registration, login, OTP, password reset, and verification |
| `api/` | AJAX endpoints and protected admin ID document delivery |
| `dashboard/` | Role specific application pages |
| `includes/` | Shared configuration, database, mail, helpers, and page components |
| `assets/` | Frontend CSS and JavaScript |
| `database/` | SQL setup, schema updates, and seed data |
| `cron/` | Scheduled appointment and inactivity tasks |
| `tests/run.php` | Lightweight CLI checks for security and shared helper behavior |

## Run checks

```sh
php tests/run.php
php -l api/appointments.php
node --check assets/js/main.js
node --check assets/js/pcai_widget.js
```

For development notes, setup details, and known limitations, see [DEVELOPMENT.md](DEVELOPMENT.md).
