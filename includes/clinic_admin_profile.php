<?php
function ensureClinicAdminProfilesTable(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS clinic_admin_profiles (
        user_id INT(10) UNSIGNED NOT NULL,
        specialization VARCHAR(255) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_clinic_admin_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
