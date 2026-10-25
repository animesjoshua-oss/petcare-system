-- Migration: Add support for half-star ratings (0.5 increments)
-- Changes rating columns from tinyint to decimal(2,1)

ALTER TABLE `ratings` 
  MODIFY COLUMN `clinic_rating` DECIMAL(2,1) DEFAULT NULL,
  MODIFY COLUMN `vet_rating` DECIMAL(2,1) DEFAULT NULL,
  MODIFY COLUMN `groomer_rating` DECIMAL(2,1) DEFAULT NULL;

-- Note: clinics.average_rating is already DECIMAL(3,2) which supports half-star ratings
-- No change needed for the clinics table
