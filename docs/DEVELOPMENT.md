# Developer guide

## Requirements

- PHP 8.1 or newer with PDO MySQL, cURL, and Fileinfo enabled
- MySQL or MariaDB
- Apache with `.htaccess` overrides enabled
- Composer dependencies installed from `composer.json`
- Node.js only if you want to run JavaScript syntax checks

## Local setup

1. Start Apache and MySQL in XAMPP.
2. Create the `petcare_clinic_hub` database and import `database/FULL_SETUP.sql`.
3. Import `database/seed_services.sql` and optionally `database/SEED_DEMO_DATA.sql`.
4. Review database and base URL settings in `includes/config.php` for your local install path.
5. Run `composer install` if the `vendor/` directory is not present.
6. Copy `includes/local-secrets.example.php` to the ignored `includes/local-secrets.php` and configure mail and AI credentials as needed.
7. Open the matching local URL, for example `http://localhost/WEB%20petcare/`.

Keep local secrets and uploaded identity documents out of source control. The `uploads/id_docs/` directory denies direct HTTP access. Admin identity review uses `api/admin-id-document.php`, which requires an authenticated administrator and serves only the matching image file.

## Checks

Run the focused helper and authentication utility checks with:

```sh
php tests/run.php
```

Lint all project PHP files (excluding the dependency and nested copy folders) with PowerShell:

```powershell
$files = rg --files -g '*.php' -g '!vendor/**' -g '!WEB petcare/**'
foreach ($file in $files) { php -l $file }
```

Check browser JavaScript syntax with:

```sh
node --check assets/js/main.js
node --check assets/js/pcai_widget.js
```

These checks do not replace browser and database-backed verification. For full critical-flow validation, exercise signup plus OTP, role login, clinic browsing, appointment booking and cancellation, admin ID review, and the chat widget against a local database.

## Request flow and shared code

- `includes/config.php` loads the app settings, local secrets, timezone, and session.
- `includes/db.php` owns the shared PDO connection.
- `includes/functions.php` provides authentication, CSRF, upload, formatting, and pagination helpers.
- Page templates include `includes/header.php` and `includes/footer.php` for shared navigation and frontend behavior.
- Mutating API requests must validate the session CSRF token. JSON callers send it in the `X-CSRF-Token` header; standard forms send `csrf_token`.
- Use the `app-url` metadata value for browser API paths so the app works under a subdirectory install.

## Recent fixes

- The owner dashboard previously cancelled appointments through a GET link without a CSRF token. It now submits a POST form with the session token.
- The shared appointment JSON helper used a fixed `/petcare-clinic-hub` path, while this app is installed at `/WEB petcare`. It now reads the base path from page metadata; the API accepts JSON and validates its CSRF header.
- Government ID uploads were reachable as public files. Apache now denies direct access, and admin review images are served by an authenticated endpoint that verifies the requested file belongs to that user record.
- Login previously reused the anonymous session ID, and strict session mode was disabled. Login now rotates the ID and strict mode is enabled; secure cookies are used on HTTPS.
- Pagination could divide by zero for an invalid page size and returned a floating point page count. Inputs are clamped and the count is an integer.
- Chat suggestions were wired in JavaScript but missing from the widget markup. The prompt buttons are now present, and unused endpoint-probe code was removed.

## Current limits

- Most page flows require a configured local MySQL database and cannot be covered by isolated CLI checks.
- The project contains compatibility pages under both the root and `pages/`; preserve both routes until references are migrated deliberately.
- `composer.json` includes Mailjet although the current mail implementation uses PHPMailer over SMTP. Confirm no deployment depends on Mailjet before removing it.
