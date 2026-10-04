-- ============================================================
-- FIX: Revert production URLs back to localhost
-- Run this in phpMyAdmin → petcare_clinic_hub → SQL tab
-- ============================================================

-- Fix clinics table
UPDATE clinics 
SET cover_photo = REPLACE(cover_photo, 'https://petcareclinic.yzz.me', 'http://localhost/WEB%20petcare')
WHERE cover_photo LIKE '%petcareclinic.yzz.me%';

-- Fix users table  
UPDATE users 
SET profile_photo = REPLACE(profile_photo, 'https://petcareclinic.yzz.me', 'http://localhost/WEB%20petcare')
WHERE profile_photo LIKE '%petcareclinic.yzz.me%';

-- Fix pets table
UPDATE pets 
SET photo = REPLACE(photo, 'https://petcareclinic.yzz.me', 'http://localhost/WEB%20petcare')
WHERE photo LIKE '%petcareclinic.yzz.me%';

-- Verify results
SELECT 'VERIFICATION - Should return 0:' as status;
SELECT COUNT(*) as remaining_prod_urls FROM clinics WHERE cover_photo LIKE '%petcareclinic.yzz.me%';
SELECT COUNT(*) as remaining_prod_urls FROM users WHERE profile_photo LIKE '%petcareclinic.yzz.me%';
SELECT COUNT(*) as remaining_prod_urls FROM pets WHERE photo LIKE '%petcareclinic.yzz.me%';

SELECT 'DONE! All production URLs reverted to localhost.' as status;
