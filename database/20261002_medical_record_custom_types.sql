-- Allow vets to enter a free-text medical record type.
ALTER TABLE pet_health_records
  MODIFY record_type VARCHAR(100) NOT NULL DEFAULT 'checkup';
