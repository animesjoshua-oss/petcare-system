-- ===================================================================
-- COMPREHENSIVE DEMO DATA FOR PETCARE SYSTEM
-- ===================================================================
-- Run after importing FULL_SETUP.sql and seed_services.sql
-- Safe to run more than once (idempotent with NOT EXISTS checks)
-- 
-- Demo Accounts:
-- - Pet Owner: owner.demo@petcareplus.com / petcare123
-- ===================================================================

INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash,
                   is_active, is_email_verified)
SELECT r.id, 'Jamie', 'Santos', 'owner.demo@petcareplus.com', '+63-917-555-0142',
       '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', 1, 1
FROM roles r
WHERE r.name = 'pet_owner'
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'owner.demo@petcareplus.com');

INSERT INTO pets (owner_id, name, species, breed, age_years, age_months, weight_kg, gender, color, notes, is_active)
SELECT u.id, 'Milo', 'dog', 'Shih Tzu', 3, 0, 6.20, 'male', 'White and brown',
       'Demo pet profile for appointment and health record walkthrough.', 1
FROM users u
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pets p WHERE p.owner_id = u.id AND p.name = 'Milo');

-- A completed grooming appointment makes the owner AI follow-up feature available.
INSERT INTO appointments (owner_id, pet_id, clinic_id, service_id, groomer_id, appointment_type,
                          appointment_date, appointment_time, duration_minutes, status, reason, completed_at)
SELECT u.id, p.id, s.clinic_id, s.id, g.id, 'grooming', DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
       '10:00:00', COALESCE(s.duration_minutes, 60), 'completed', 'Routine bath and coat trim',
       DATE_SUB(NOW(), INTERVAL 6 MONTH)
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Milo'
JOIN services s ON s.id = (
    SELECT sx.id FROM services sx
    WHERE sx.service_type IN ('grooming', 'both') AND sx.is_active = 1
      AND EXISTS (SELECT 1 FROM groomers gx WHERE gx.clinic_id = sx.clinic_id AND gx.is_available = 1)
    ORDER BY sx.id LIMIT 1
)
JOIN groomers g ON g.id = (
    SELECT gx.id FROM groomers gx WHERE gx.clinic_id = s.clinic_id AND gx.is_available = 1
    ORDER BY gx.id LIMIT 1
)
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id = p.id AND a.status = 'completed');

-- A sample clinical record appears in both the owner health profile and vet records.
INSERT INTO pet_health_records (pet_id, vet_id, record_type, title, description, diagnosis,
                                treatment, medications, next_appointment, record_date)
SELECT p.id, v.id, 'checkup', 'Routine wellness check',
       'Demo record: normal appetite and activity reported; coat and skin checked.',
       'No concerns observed during this routine check.',
       'Continue routine preventive care and monitor weight.',
       NULL, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Milo'
JOIN veterinarians v ON v.id = (SELECT vx.id FROM veterinarians vx WHERE vx.is_available = 1 ORDER BY vx.id LIMIT 1)
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id = p.id AND hr.title = 'Routine wellness check');

-- Give the existing demo professionals profile descriptions for public clinic pages and AI matching.
UPDATE veterinarians v JOIN users u ON u.id = v.user_id
SET v.bio = COALESCE(NULLIF(v.bio, ''), 'Provides preventive care, wellness checks, and clear guidance for pet owners.')
WHERE u.email = 'vet@petcareplus.com';

UPDATE groomers g JOIN users u ON u.id = g.user_id
SET g.bio = COALESCE(NULLIF(g.bio, ''), 'Provides gentle bathing, coat care, and nail trimming, with a calm approach for nervous pets.')
WHERE u.email = 'groomer@petcareplus.com';


-- ===================================================================
-- ADDITIONAL DEMO PETS FOR OWNER
-- ===================================================================
INSERT INTO pets (owner_id, name, species, breed, age_years, age_months, weight_kg, gender, color, notes, is_active)
SELECT u.id, 'Luna', 'cat', 'Persian', 2, 0, 4.50, 'female', 'White',
       'Demo cat profile - friendly and loves attention.', 1
FROM users u
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pets p WHERE p.owner_id = u.id AND p.name = 'Luna');

INSERT INTO pets (owner_id, name, species, breed, age_years, age_months, weight_kg, gender, color, notes, is_active)
SELECT u.id, 'Charlie', 'dog', 'Golden Retriever', 5, 6, 28.50, 'male', 'Golden',
       'Demo large breed dog - energetic and playful.', 1
FROM users u
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pets p WHERE p.owner_id = u.id AND p.name = 'Charlie');

-- ===================================================================
-- DEMO APPOINTMENTS (Various Statuses)
-- ===================================================================

-- Pending appointment for next week
INSERT INTO appointments (owner_id, pet_id, clinic_id, service_id, vet_id, appointment_type,
                          appointment_date, appointment_time, duration_minutes, status, reason)
SELECT u.id, p.id, s.clinic_id, s.id, v.id, 'veterinary', DATE_ADD(CURDATE(), INTERVAL 7 DAY),
       '10:00:00', COALESCE(s.duration_minutes, 30), 'pending', 'Routine vaccination check-up'
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Luna'
JOIN services s ON s.name = 'Vaccination' AND s.is_active = 1
JOIN veterinarians v ON v.clinic_id = s.clinic_id AND v.is_available = 1
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id = p.id AND a.status = 'pending')
LIMIT 1;

-- Confirmed appointment tomorrow
INSERT INTO appointments (owner_id, pet_id, clinic_id, service_id, vet_id, appointment_type,
                          appointment_date, appointment_time, duration_minutes, status, reason)
SELECT u.id, p.id, s.clinic_id, s.id, v.id, 'veterinary', DATE_ADD(CURDATE(), INTERVAL 1 DAY),
       '14:00:00', COALESCE(s.duration_minutes, 30), 'confirmed', 'Follow-up consultation'
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Charlie'
JOIN services s ON s.name = 'General Consultation' AND s.is_active = 1
JOIN veterinarians v ON v.clinic_id = s.clinic_id AND v.is_available = 1
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id = p.id AND a.status = 'confirmed')
LIMIT 1;

-- Completed grooming appointment from 1 month ago
INSERT INTO appointments (owner_id, pet_id, clinic_id, service_id, groomer_id, appointment_type,
                          appointment_date, appointment_time, duration_minutes, status, reason, completed_at)
SELECT u.id, p.id, s.clinic_id, s.id, g.id, 'grooming', DATE_SUB(CURDATE(), INTERVAL 1 MONTH),
       '11:00:00', COALESCE(s.duration_minutes, 60), 'completed', 'Full grooming session',
       DATE_SUB(NOW(), INTERVAL 1 MONTH)
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Luna'
JOIN services s ON s.service_type IN ('grooming', 'both') AND s.is_active = 1
       AND (s.name LIKE '%Grooming%' OR s.name LIKE '%Bath%')
JOIN groomers g ON g.clinic_id = s.clinic_id AND g.is_available = 1
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id = p.id AND a.appointment_type = 'grooming' AND a.status = 'completed')
LIMIT 1;

-- ===================================================================
-- DEMO HEALTH RECORDS FOR MULTIPLE PETS
-- ===================================================================

-- Health record for Milo (existing)
INSERT INTO pet_health_records (pet_id, vet_id, record_type, title, description, diagnosis,
                                treatment, medications, next_appointment, record_date)
SELECT p.id, v.id, 'vaccination', 'Annual Vaccination - DHPP',
       'Annual booster shot for Distemper, Hepatitis, Parvovirus, and Parainfluenza.',
       'Routine vaccination completed successfully.',
       'Monitor for any adverse reactions in 24-48 hours.',
       'DHPP vaccine', DATE_ADD(CURDATE(), INTERVAL 1 YEAR), DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Milo'
JOIN veterinarians v ON v.is_available = 1
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id = p.id AND hr.record_type = 'vaccination')
LIMIT 1;

-- Health record for Luna
INSERT INTO pet_health_records (pet_id, vet_id, record_type, title, description, diagnosis,
                                treatment, medications, record_date)
SELECT p.id, v.id, 'checkup', 'Routine Wellness Check',
       'General health examination. Weight, teeth, and coat checked.',
       'Healthy condition overall. Slight tartar buildup noted.',
       'Recommend dental cleaning in 3-6 months.',
       NULL, DATE_SUB(CURDATE(), INTERVAL 45 DAY)
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Luna'
JOIN veterinarians v ON v.is_available = 1
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id = p.id AND hr.record_type = 'checkup')
LIMIT 1;

-- Health record for Charlie
INSERT INTO pet_health_records (pet_id, vet_id, record_type, title, description, diagnosis,
                                treatment, medications, next_appointment, record_date)
SELECT p.id, v.id, 'treatment', 'Minor Skin Irritation',
       'Owner reported scratching. Examination revealed minor flea irritation.',
       'Flea dermatitis - mild case',
       'Applied topical flea treatment. Advised monthly prevention.',
       'Topical flea treatment (Frontline Plus)', DATE_ADD(CURDATE(), INTERVAL 1 MONTH), DATE_SUB(CURDATE(), INTERVAL 20 DAY)
FROM users u
JOIN pets p ON p.owner_id = u.id AND p.name = 'Charlie'
JOIN veterinarians v ON v.is_available = 1
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id = p.id)
LIMIT 1;

-- ===================================================================
-- DEMO NOTIFICATIONS FOR PET OWNER
-- ===================================================================

-- Appointment confirmation notification
INSERT INTO notifications (user_id, title, message, type, link, is_read)
SELECT u.id, 'Appointment Confirmed', 
       'Your appointment for Charlie on ' || DATE_FORMAT(a.appointment_date, '%M %d, %Y') || ' has been confirmed.',
       'appointment', '/dashboard/owner/appointments.php', 0
FROM users u
JOIN appointments a ON a.owner_id = u.id AND a.status = 'confirmed'
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.user_id = u.id AND n.type = 'appointment' AND n.title = 'Appointment Confirmed')
LIMIT 1;

-- Appointment reminder notification  
INSERT INTO notifications (user_id, title, message, type, link, is_read)
SELECT u.id, 'Upcoming Appointment Reminder',
       'Reminder: You have an appointment tomorrow at 2:00 PM.',
       'reminder', '/dashboard/owner/appointments.php', 0
FROM users u
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.user_id = u.id AND n.type = 'reminder')
LIMIT 1;

-- ===================================================================
-- SUCCESS MESSAGE
-- ===================================================================
SELECT 'Demo data seeded successfully!' AS status,
       'Login as owner: owner.demo@petcareplus.com / petcare123' AS credentials,
       '3 demo pets (Milo, Luna, Charlie) created' AS pets,
       'Multiple appointments with various statuses created' AS appointments;
