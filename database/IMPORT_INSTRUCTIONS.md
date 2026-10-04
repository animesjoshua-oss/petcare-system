# 📥 Database Import Instructions

## ⚠️ CRITICAL: Read This First!

The SQL file **DOES NOT** select a database automatically.  
**You MUST select it manually in phpMyAdmin.**

---

## ✅ Step-by-Step Import (yzz.me Production)

### 1. Login to phpMyAdmin
- Go to your cPanel
- Click on **phpMyAdmin**

### 2. Select Database (MOST IMPORTANT STEP!)
- Look at the **LEFT SIDEBAR**
- **Click on `yzzme_43078279`** (your database name)
- The database name should be highlighted/selected

### 3. Import the File
- Click the **Import** tab at the top
- Click **Choose File** button
- Select `COMPLETE_DATABASE_SETUP.sql`
- Scroll down
- Click **Go** button

### 4. Wait for Completion
- Import takes 1-2 minutes
- Don't close the browser
- You'll see a success message when done

---

## 🎯 Why You Must Select Database First

**The Problem:**
- Error: `#1046 - No database selected` means phpMyAdmin doesn't know which database to use
- Error: `#1044 - Access denied` means the hosting blocks `USE database` commands

**The Solution:**
- Manually click the database name in the sidebar BEFORE importing
- This tells phpMyAdmin which database to use
- No USE command needed in the SQL file

---

## 📋 What Gets Imported

After successful import, you'll have:

### Tables Created:
- ✅ users, roles
- ✅ clinics, services
- ✅ pets, pet_health_records
- ✅ appointments, ratings
- ✅ veterinarians, groomers
- ✅ notifications, favorites
- ✅ ai_recommendations, ai_summaries

### Demo Data:
- ✅ 1 Admin account
- ✅ 1 Pet Owner account with demo pet
- ✅ 16+ Sample clinics in Pangasinan
- ✅ All user roles configured

---

## 🔑 Login After Import

### Admin Dashboard:
```
Email: admin@petcareplus.com
Password: petcare123
URL: https://petcareplus.yzz.me/dashboard/admin/
```

### Pet Owner Dashboard:
```
Email: owner.demo@petcareplus.com
Password: petcare123
URL: https://petcareplus.yzz.me/dashboard/owner/
```

---

## 🐛 Troubleshooting

### Error: "No database selected"
**Fix:** You didn't select the database first.  
Go back to step 2 and click on `yzzme_43078279` in the sidebar.

### Error: "Access denied for user"
**Fix:** Wrong credentials in config.php  
Check `includes/config.php` has correct database credentials.

### Error: "Table already exists"
**Fix:** Database has old tables.  
The SQL file drops tables first, but if this fails:
1. Manually delete all tables in phpMyAdmin
2. Re-import the SQL file

### Import Timeout
**Fix:** File too large for server limits.  
Contact your hosting support to increase:
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`

Or use command line:
```bash
mysql -h localhost -u yzzme_43078279 -p yzzme_43078279 < COMPLETE_DATABASE_SETUP.sql
```
Password: `4DfvsKy9U01X`

---

## ✅ Verify Success

After import, check in phpMyAdmin:

1. Click on `yzzme_43078279` database
2. You should see **20+ tables** listed
3. Click on `users` table
4. Click **Browse** tab
5. You should see 2 users (admin and demo owner)

If you see the tables and data, **import was successful!** ✅

---

## 📞 Need Help?

- GitHub: https://github.com/techytropa-prog/petcare-clinic-hub
- Open an issue with screenshot of any errors
