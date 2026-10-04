# Database Import Instructions

## ⚠️ IMPORTANT: Select Database First!

The SQL file does NOT include a USE statement to avoid permission errors.

## ✅ Correct Import Steps

### For Production (yzz.me):

1. **Login to phpMyAdmin** via cPanel
2. **IMPORTANT: Click on `yzzme_43078279` database** in the LEFT SIDEBAR
   - This is the key step! Don't skip this!
3. Click **Import** tab (top menu)
4. **Choose File**: Select `COMPLETE_DATABASE_SETUP.sql`
5. Scroll down and click **Go** button
6. Wait for import to complete (1-2 minutes)
7. **Done!** ✅

### Why Select Database First?

The error `#1044 - Access denied` happens when:
- You try to run `USE database_name` command
- But your hosting doesn't allow this command
- **Solution:** Select the database in phpMyAdmin sidebar BEFORE importing

This way, phpMyAdmin knows which database to use without needing the USE command.

### For Local Development:

If you want to use this on localhost, edit the SQL file first:

1. Open `COMPLETE_DATABASE_SETUP.sql`
2. Find this section (around line 24):
   ```sql
   USE `yzzme_43078279`;
   ```
3. **Comment it out** and uncomment the local lines:
   ```sql
   -- USE `yzzme_43078279`;
   
   CREATE DATABASE IF NOT EXISTS `petcare_clinic_hub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE `petcare_clinic_hub`;
   ```
4. Save and import

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

## ⚙️ Alternative Import Methods

### Command Line (Production):
```bash
mysql -h localhost -u yzzme_43078279 -p yzzme_43078279 < COMPLETE_DATABASE_SETUP.sql
```
Then enter password: `4DfvsKy9U01X`

### Command Line (Local):
```bash
mysql -u root -p petcare_clinic_hub < COMPLETE_DATABASE_SETUP.sql
```

## ⚠️ Important Notes

1. **For Production (yzz.me):**
   - Database name: `yzzme_43078279`
   - Username: `yzzme_43078279`
   - SQL file is pre-configured - just import!

2. **For Local Development:**
   - Edit the SQL file to use local database
   - Default username is usually `root` with no password

3. **File Size:**
   - The SQL file is comprehensive and may take 1-2 minutes to import
   - Don't close the browser during import

## 🐛 Troubleshooting

**Error: "Table already exists"**
- The SQL file drops existing tables first
- If you still get this error, manually delete all tables and re-import

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
