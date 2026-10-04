# Database Import Instructions

## 🎯 How to Fix "No database selected" Error

The error happens because the SQL file doesn't specify which database to use. Here's how to fix it:

## ✅ Method 1: Select Database in phpMyAdmin (RECOMMENDED)

1. **Login to phpMyAdmin**
   - For yzz.me hosting: Go to your cPanel → phpMyAdmin
   - For localhost: Go to http://localhost/phpmyadmin

2. **Select Your Database**
   - Click on your database name in the left sidebar
   - For production: Click on `yzzme_43078279`
   - For localhost: Click on `petcare_clinic_hub` (or create it first)

3. **Import the SQL File**
   - Click the **Import** tab at the top
   - Click **Choose File** and select `COMPLETE_DATABASE_SETUP.sql`
   - Scroll down and click **Go**
   - Wait for the import to complete ✅

## ✅ Method 2: Add USE Statement (Alternative)

If you prefer to modify the SQL file:

1. Open `COMPLETE_DATABASE_SETUP.sql` in a text editor
2. Find these lines near the top:
```sql
-- CREATE DATABASE IF NOT EXISTS `petcare_clinic_hub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `petcare_clinic_hub`;
```

3. **For Production (yzz.me):** Uncomment and change to:
```sql
USE `yzzme_43078279`;
```

4. **For Localhost:** Uncomment both lines:
```sql
CREATE DATABASE IF NOT EXISTS `petcare_clinic_hub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `petcare_clinic_hub`;
```

5. Save the file and import

## ✅ Method 3: Command Line

### For Production (yzz.me):
```bash
mysql -h localhost -u yzzme_43078279 -p yzzme_43078279 < COMPLETE_DATABASE_SETUP.sql
```
Then enter password: `4DfvsKy9U01X`

### For Localhost:
```bash
# Create database first
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS petcare_clinic_hub"

# Import the file
mysql -u root -p petcare_clinic_hub < COMPLETE_DATABASE_SETUP.sql
```

## 📋 What Gets Imported

The SQL file contains:
- ✅ All database tables (users, clinics, pets, appointments, etc.)
- ✅ Demo user accounts
- ✅ Sample clinics in Dagupan/Calasiao, Pangasinan
- ✅ Demo pet data
- ✅ Initial roles and permissions

## 🔑 Demo Login Credentials

After successful import, you can login with:

**Admin Account:**
- Email: `admin@petcareplus.com`
- Password: `petcare123`

**Pet Owner Account:**
- Email: `owner.demo@petcareplus.com`
- Password: `petcare123`

## ⚠️ Important Notes

1. **For Production Hosting (yzz.me):**
   - Database name: `yzzme_43078279`
   - Username: `yzzme_43078279`
   - The database already exists - just select it before importing

2. **For Local Development:**
   - You may need to create the database first
   - Default username is usually `root` with no password

3. **File Size:**
   - The SQL file is comprehensive and may take 1-2 minutes to import
   - Don't close the browser during import

## 🐛 Troubleshooting

**Error: "Table already exists"**
- The SQL file drops existing tables first, but if you get this error:
- Delete all tables in the database first, then re-import

**Error: "Access denied"**
- Check your database username and password
- Make sure the database user has full permissions

**Import timeout:**
- Increase PHP max_execution_time in php.ini
- Or import via command line instead

## ✅ Verify Import Success

After importing, check if these tables exist:
- users
- roles
- clinics
- pets
- appointments
- services
- veterinarians
- groomers

You should see **demo data** including:
- 1 admin user
- 1 pet owner user
- 16+ demo clinics
- 1 demo pet

---

**Need Help?** Open an issue on GitHub or contact support.
