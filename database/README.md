# Database Migrations

## How to Apply Migrations

Migrations in this folder need to be manually run against your database.

### Using phpMyAdmin:
1. Open phpMyAdmin in your browser (usually at http://localhost/phpmyadmin)
2. Select your `petcare_clinic_hub` database
3. Click on the "SQL" tab
4. Copy the contents of the migration file
5. Paste into the SQL query box
6. Click "Go" to execute

### Using MySQL command line:
```bash
mysql -u root -p petcare_clinic_hub < database/migrations/add_half_star_ratings.sql
```

## Available Migrations

### add_half_star_ratings.sql
**Purpose:** Adds support for half-star ratings (0.5 increments like 1.5, 2.5, 3.5, etc.)

**Changes:**
- Modifies `ratings` table columns from `tinyint` to `decimal(2,1)`:
  - `clinic_rating`
  - `vet_rating`
  - `groomer_rating`
- Note: `clinics.average_rating` already supports decimals (3,2)

**Impact:** 
- Existing integer ratings will be preserved (e.g., 5 becomes 5.0)
- New ratings can use half-star values (0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0)
- No data loss
- Frontend automatically supports clicking on left/right half of stars

**Required:** Yes - This migration must be run for the half-star rating feature to work properly.
