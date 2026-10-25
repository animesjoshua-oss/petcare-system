-- Adds clinic/application types and optional service discount details for the
-- existing Vet Admin and Groomer Admin flows. Discount details remain unset
-- until a clinic supplies them.

SET @pc_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'clinics' AND column_name = 'clinic_type') = 0,
    'ALTER TABLE clinics ADD COLUMN clinic_type ENUM(''veterinary'',''grooming'') NOT NULL DEFAULT ''veterinary'' AFTER admin_id',
    'SELECT 1'
);
PREPARE pc_stmt FROM @pc_sql;
EXECUTE pc_stmt;
DEALLOCATE PREPARE pc_stmt;

SET @pc_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'services' AND column_name = 'veterinarian_discount_details') = 0,
    'ALTER TABLE services ADD COLUMN veterinarian_discount_details VARCHAR(500) NULL AFTER price',
    'SELECT 1'
);
PREPARE pc_stmt FROM @pc_sql;
EXECUTE pc_stmt;
DEALLOCATE PREPARE pc_stmt;

SET @pc_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'services' AND column_name = 'groomer_discount_details') = 0,
    'ALTER TABLE services ADD COLUMN groomer_discount_details VARCHAR(500) NULL AFTER veterinarian_discount_details',
    'SELECT 1'
);
PREPARE pc_stmt FROM @pc_sql;
EXECUTE pc_stmt;
DEALLOCATE PREPARE pc_stmt;

SET @pc_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'clinic_applications' AND column_name = 'application_type') = 0,
    'ALTER TABLE clinic_applications ADD COLUMN application_type ENUM(''veterinary'',''grooming'') NOT NULL DEFAULT ''veterinary'' AFTER application_code',
    'SELECT 1'
);
PREPARE pc_stmt FROM @pc_sql;
EXECUTE pc_stmt;
DEALLOCATE PREPARE pc_stmt;
