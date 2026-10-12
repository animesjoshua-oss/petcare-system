-- ===================================================================
-- UPDATE ALL CLINICS WITH LOGO AND COVER IMAGES
-- ===================================================================
-- Run this SQL to add images to all clinics in the database
-- ===================================================================

UPDATE clinics SET 
    logo = 'assets/images/clinics/petally.png',
    cover_photo = 'assets/images/clinics/petally.png'
WHERE id = 26 AND name LIKE '%PetAlly%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/Amado.jpg',
    cover_photo = 'assets/images/clinics/Amado.jpg'
WHERE id = 27 AND name LIKE '%Animal Life%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/Protect.png',
    cover_photo = 'assets/images/clinics/Protect.png'
WHERE id = 28 AND name LIKE '%Pet Protect%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/saint.png',
    cover_photo = 'assets/images/clinics/saint.png'
WHERE id = 29 AND name LIKE '%St. Francis Veterinary%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/saint.png',
    cover_photo = 'assets/images/clinics/saint.png'
WHERE id = 30 AND name LIKE '%Saint Francis%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/mjm.png',
    cover_photo = 'assets/images/clinics/mjm.png'
WHERE id = 31 AND name LIKE '%MJM%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/Petdocs.jpg',
    cover_photo = 'assets/images/clinics/Petdocs.jpg'
WHERE id = 32 AND name LIKE '%PetDoctors%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/saint.png',
    cover_photo = 'assets/images/clinics/saint.png'
WHERE id = 33 AND name LIKE '%St. Martin%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/paws.jpg',
    cover_photo = 'assets/images/clinics/paws.jpg'
WHERE id = 34 AND name LIKE '%Paws & Purrs%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/ruralvet.png',
    cover_photo = 'assets/images/clinics/ruralvet.png'
WHERE id = 35 AND name LIKE '%RH Elegance%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/NTTVC.jpg',
    cover_photo = 'assets/images/clinics/NTTVC.jpg'
WHERE id = 37 AND name LIKE '%Nose To Tail%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/evey.jpg',
    cover_photo = 'assets/images/clinics/evey.jpg'
WHERE id = 46 AND name LIKE '%Every Paw%';

UPDATE clinics SET 
    logo = 'assets/images/clinics/DR.jpg',
    cover_photo = 'assets/images/clinics/DR.jpg'
WHERE id = 47 AND name LIKE '%D&R%';

-- ===================================================================
-- SUCCESS MESSAGE
-- ===================================================================
SELECT 'All clinic images updated successfully!' AS status,
       COUNT(*) AS total_clinics_updated
FROM clinics 
WHERE logo IS NOT NULL AND logo != '';
