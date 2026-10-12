# Local XAMPP setup

1. Start Apache and MySQL in XAMPP.
2. In phpMyAdmin, create the `petcare_clinic_hub` database and import `database/FULL_SETUP.sql`.
3. Import `database/seed_services.sql` to add the veterinary and grooming service catalog.
4. Import `database/SEED_DEMO_DATA.sql` to add a demo owner, Milo's completed grooming appointment, a sample medical record, and public staff bios. The main setup already includes clinic admin, veterinarian, and groomer accounts.
5. Set the local API key in `includes/local-secrets.php`. Copy `includes/local-secrets.example.php`, then set `LOCAL_GROQ_API_KEY` to your own Groq key. Restart Apache after changing it. The key is not included in the app source or example file.
6. Open `http://localhost/WEB%20petcare/`.

To enable OTP and account emails through Gmail, copy `includes/local-secrets.example.php` to `includes/local-secrets.php` if you have not already. Set `LOCAL_MAIL_USER` to your Gmail address and `LOCAL_MAIL_PASS` to a Google App Password (not your regular Gmail password). Google requires 2-Step Verification before it lets you create an App Password. Restart Apache after saving the file. If mail delivery fails, check the PHP/Apache error log; the registration page now keeps the account unverified and shows a resend option instead of silently skipping OTP.

## Demo logins

All demo passwords are for local development only. Change them before deploying.

- Clinic admin: `admin@petcareplus.com` / `admin123`
- Veterinarian: `vet@petcareplus.com` / `petcare123`
- Groomer: `groomer@petcareplus.com` / `petcare123`
- Pet owner: `owner.demo@petcareplus.com` / `petcare123`

The seeded owner has a pet, completed grooming appointment, and sample wellness record so the profile, history, booking, and AI follow-up screens can be explored.

## AI behavior

The owner service finder uses clinic descriptions, active service descriptions, operating hours, staff bios, specializations, and experience as its recommendation context. AI follow-up suggestions are available only for pets with at least one completed appointment. Groq features show a configuration message when no key is set.
