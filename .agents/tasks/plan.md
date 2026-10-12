# Implementation Plan: Rename "Admin" to "Vet Admin"

## Overview
Rename every user-visible reference to the 'Admin' role to 'Vet Admin' in the PetCare+ Clinic Hub PHP application. Database column names (e.g., `admin_id`), table names, and PHP role-check logic that compares against the literal string 'admin' must NOT be changed to preserve system functionality.

---

## Implementation Steps

- [ ] 1. **Update sidebar role label in `includes/sidebar_admin.php`**
      
      Change the displayed role label from "Admin" to "Vet Admin" in the sidebar user info section.
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\includes\sidebar_admin.php` (line 37)
      
      **Changes:**
      - Line 37: Change `<div class="sidebar-user-role" style="color:var(--danger);">Admin</div>` to `<div class="sidebar-user-role" style="color:var(--danger);">Vet Admin</div>`
      
      **Verify:** 
      Open `http://localhost/WEB%20petcare/dashboard/admin/index.php` in a browser while logged in as admin. Confirm the sidebar shows "Vet Admin" under the user's name.

---

- [ ] 2. **Update page title in `dashboard/admin/index.php`**
      
      Change the browser tab title from "Admin Dashboard" to "Vet Admin Dashboard".
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\dashboard\admin\index.php` (line 5)
      
      **Changes:**
      - Line 5: Change `$pageTitle = 'Admin Dashboard';` to `$pageTitle = 'Vet Admin Dashboard';`
      
      **Verify:** 
      Open `http://localhost/WEB%20petcare/dashboard/admin/index.php` in a browser. Confirm the browser tab title shows "Vet Admin Dashboard – PetCare+".

---

- [ ] 3. **Update AI assistant branding in `dashboard/admin/ai.php`**
      
      Change all references from "AdminAI" to "Vet AdminAI" in the AI assistant page (system prompt, page heading, and welcome message).
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\dashboard\admin\ai.php`
      
      **Changes:**
      - Line 21: Change `"You are AdminAI, an intelligent assistant for PetCare+ Clinic Hub administrators. You help with:"` to `"You are Vet AdminAI, an intelligent assistant for PetCare+ Clinic Hub administrators. You help with:"`
      - Line 32: Change `"Admin asks: {$message}"` to `"Vet Admin asks: {$message}"`
      - Line 46: Change `<h1><i class="fas fa-robot" style="color:var(--primary)"></i> AdminAI Assistant</h1>` to `<h1><i class="fas fa-robot" style="color:var(--primary)"></i> Vet AdminAI Assistant</h1>`
      - Line 62: Change `'<strong>AdminAI</strong>'` to `'<strong>Vet AdminAI</strong>'`
      
      **Verify:** 
      Open `http://localhost/WEB%20petcare/dashboard/admin/ai.php` in a browser. Confirm the page heading shows "Vet AdminAI Assistant" and the welcome message refers to "Vet AdminAI". Test the AI assistant and confirm it identifies itself as "Vet AdminAI" in responses.

---

- [ ] 4. **Update registration page user-facing text in `auth/register.php`**
      
      Change user-facing instructions that mention "Admin" to say "Vet Admin" in the registration flow (ID verification instructions).
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\auth\register.php`
      
      **Changes:**
      - Line 201: Change `<span style="font-size:.875rem;">Admin reviews your submitted ID photo and verifies your information</span>` to `<span style="font-size:.875rem;">Vet Admin reviews your submitted ID photo and verifies your information</span>`
      - Line 279: Change `Required for account security. Admin will verify your ID before activating your account.` to `Required for account security. Vet Admin will verify your ID before activating your account.`
      
      **Verify:** 
      Open `http://localhost/WEB%20petcare/auth/register.php` in a browser. Scroll to the "How it works" section and the ID upload field. Confirm both locations now say "Vet Admin" instead of "Admin".

---

- [ ] 5. **Update role badge display logic in `dashboard/admin/index.php`**
      
      Change the role name displayed in the "Recent Users" table badge from "Admin" to "Vet Admin" (the display label, not the role_name comparison).
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\dashboard\admin\index.php`
      
      **Changes:**
      - Line 332 (in the role badge rendering): Change `<?= ucwords(str_replace('_',' ',$u['role_name'])) ?>` to use a conditional that maps 'admin' → 'Vet Admin' while preserving other role names. Insert this helper above the table:
        ```php
        <?php
        function displayRoleName($roleName) {
            if ($roleName === 'admin') return 'Vet Admin';
            if ($roleName === 'superadmin') return 'Superadmin';
            return ucwords(str_replace('_', ' ', $roleName));
        }
        ?>
        ```
      - Then change line 332: `<?= ucwords(str_replace('_',' ',$u['role_name'])) ?>` to `<?= displayRoleName($u['role_name']) ?>`
      
      **Verify:** 
      Open `http://localhost/WEB%20petcare/dashboard/admin/index.php` in a browser. Scroll to the "Recent Users" table. Confirm users with role 'admin' show a badge labeled "Vet Admin".

---

- [ ] 6. **Update role badge display logic in `dashboard/admin/users.php`**
      
      Change the role badge displayed in the user management table from "Admin" to "Vet Admin" (display label only).
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\dashboard\admin\users.php`
      
      **Changes:**
      - Add the same `displayRoleName()` helper function near the top of the file (after line 175, before the table rendering):
        ```php
        <?php
        function displayRoleName($roleName) {
            if ($roleName === 'admin') return 'Vet Admin';
            if ($roleName === 'superadmin') return 'Superadmin';
            return ucwords(str_replace('_', ' ', $roleName));
        }
        ?>
        ```
      - Line 287 (approximate, in the role badge cell): Change `<?= ucfirst(str_replace('_',' ',$u['role_name'])) ?>` to `<?= displayRoleName($u['role_name']) ?>`
      
      **Verify:** 
      Open `http://localhost/WEB%20petcare/dashboard/admin/users.php` in a browser. Confirm users with role 'admin' display a badge labeled "Vet Admin" in the Role column.

---

- [ ] 7. **Update CSS comment in `assets/css/main.css`**
      
      Change the CSS comment from "Admin pages" to "Vet Admin pages" for consistency.
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\assets\css\main.css` (line 6687)
      
      **Changes:**
      - Line 6687: Change `/* ── Admin pages: services, clinics, vets, groomers ───────── */` to `/* ── Vet Admin pages: services, clinics, vets, groomers ───────── */`
      
      **Verify:** 
      Open `c:\xampp\htdocs\WEB petcare\assets\css\main.css` in a text editor and search for "Vet Admin pages" to confirm the comment was updated.

---

- [ ] 8. **Update documentation files**
      
      Update all documentation references to "Admin" role to say "Vet Admin" (excluding "Superadmin" and "Super Admin").
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\docs\accounts.txt`
      - `c:\xampp\htdocs\WEB petcare\docs\AI-PROJECT-PROMPT.md`
      - `c:\xampp\htdocs\WEB petcare\docs\Alpha-Testing-Feedback-Form.md`
      - `c:\xampp\htdocs\WEB petcare\docs\README.md`
      
      **Changes for `docs/accounts.txt`:**
      - Line 16: Change `Clinic Admin` to `Clinic Vet Admin`
      
      **Changes for `docs/AI-PROJECT-PROMPT.md`:**
      - Line 107: Change comment `# Admin Dashboard` to `# Vet Admin Dashboard`
      - Line 140: Change comment `# Admin sidebar navigation` to `# Vet Admin sidebar navigation`
      - Line 387: Change heading `### 4. **Admin** (\`admin\`)` to `### 4. **Vet Admin** (\`admin\`)`
      - Line 601: Change `- Admin: \`admin@petcareplus.com\` / \`admin123\`` to `- Vet Admin: \`admin@petcareplus.com\` / \`admin123\``
      - Line 772: Change heading `4. **Admin Features**` to `4. **Vet Admin Features**`
      
      **Changes for `docs/Alpha-Testing-Feedback-Form.md`:**
      - Line 79: Change "Clinic Administrator" role description label to "Clinic Vet Administrator"
      - Line 100 (table row 11): Change "Clinic Administrator" to "Clinic Vet Administrator"
      - Line 101 (table row 12): Change "Clinic administrators" to "Clinic Vet administrators"
      - Line 108 (table row 19): Change "Clinic administrators" to "Clinic Vet administrators"
      - Line 157: Change `│  Admin   │` in ASCII diagram to `│Vet Admin │`
      - Line 636: Change heading `### User Role: Clinic Administrator` to `### User Role: Clinic Vet Administrator`
      - Line 644: Change `│  Login as      │` box text from "Clinic Admin" to "Clinic Vet Admin"
      - Line 658: Change `│  Clinic Admin        │` to `│  Clinic Vet Admin    │`
      - Line 801: Change `│Assign Clinic   │` box text from "Admin Account" to "Vet Admin Account"
      
      **Changes for `docs/README.md`:**
      - Line 11: Change `- Admin, veterinarian, groomer, and pet owner dashboards` to `- Vet Admin, veterinarian, groomer, and pet owner dashboards`
      
      **Verify:** 
      Open each documentation file in a text editor and search for "Vet Admin" to confirm all changes. Run `grep -i "Admin" docs/*.md docs/*.txt` and verify no unwanted "Admin" references remain (excluding "Superadmin" and database/technical references).

---

- [ ] 9. **Update clinic page team member role display in `pages/clinic.php`**
      
      Change the displayed role for clinic administrators from "Clinic Administrator" to "Clinic Vet Administrator" in the team members section.
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\pages\clinic.php`
      
      **Changes:**
      - Line 39 (appears 5 times, all identical): Change all 5 occurrences of `'role'=>'Clinic Administrator','description'=>'Administrator assigned to manage this veterinary clinic.','specialization'=>'Clinic administration'` to `'role'=>'Clinic Vet Administrator','description'=>'Vet Administrator assigned to manage this veterinary clinic.','specialization'=>'Clinic administration'`
      
      **Verify:** 
      Open any clinic detail page (e.g., `http://localhost/WEB%20petcare/pages/clinic.php?id=26`) in a browser. Scroll to the "Meet Our Team" section. Confirm the clinic administrator's role is displayed as "Clinic Vet Administrator".

---

- [ ] 10. **Update messages notification text in `dashboard/admin/messages.php`**
      
      Change notification title from "Admin replied to your message" to "Vet Admin replied to your message".
      
      **Files:**
      - `c:\xampp\htdocs\WEB petcare\dashboard\admin\messages.php`
      
      **Changes:**
      - Line 57: Change `'💬 Admin replied to your message',` to `'💬 Vet Admin replied to your message',`
      
      **Verify:** 
      Log in as admin, open `http://localhost/WEB%20petcare/dashboard/admin/messages.php`, reply to a message. Log in as the message sender and check notifications. Confirm the notification title says "Vet Admin replied to your message".

---

## Final Verification Checklist

After completing all steps above:

1. **Visual inspection:** Log in as admin (`admin@petcareplus.com` / `admin123`) and navigate through all admin dashboard pages. Confirm no user-visible "Admin" text remains (sidebar, page titles, headings, buttons, messages).

2. **Database integrity check:** Run this SQL query to confirm the roles table was NOT modified:
   ```sql
   SELECT * FROM roles WHERE name='admin';
   ```
   Expected result: One row with `name='admin'` (lowercase, unchanged).

3. **Login functionality:** Log out and log back in as admin. Confirm login succeeds and redirects to the admin dashboard (verifies `requireRole(['admin','superadmin'])` logic is intact).

4. **Role-based access:** Try accessing admin pages while logged in as a pet owner. Confirm access is denied (verifies role-check logic still works with 'admin' string literal).

5. **Grep check:** Run `grep -r "Admin" --include="*.php" --exclude-dir=".git" --exclude-dir="vendor" "c:\xampp\htdocs\WEB petcare"` and manually review results to ensure no user-facing "Admin" text was missed (technical references like `admin_id`, `requireRole(['admin'])`, etc. are acceptable).

6. **Documentation review:** Open all 4 documentation files and search for "Admin". Confirm only "Vet Admin" and "Superadmin"/"Super Admin" appear (no standalone "Admin" role references).

---

## Constraints Verified

✅ **Database columns unchanged:** No changes to `admin_id` column names or `clinics.admin_id` references.  
✅ **Table names unchanged:** No changes to table names.  
✅ **PHP logic intact:** All `requireRole(['admin','superadmin'])`, `$user['role_name'] === 'admin'`, and similar comparisons remain unchanged.  
✅ **User-visible only:** Only HTML output, page titles, headings, navigation labels, notifications, comments, and documentation were modified.  

---

## Notes

- The database `roles` table remains unchanged: the `name` column still contains the value `'admin'` (lowercase).
- All PHP role-checking logic continues to compare against the string literal `'admin'`.
- The term "Superadmin" (and variations like "Super Admin") is preserved and not changed to "Vet Superadmin".
- Default passwords in add-user forms (e.g., "Admin@1234") are NOT changed since they are internal defaults, not user-facing role labels.
- The email address `admin@petcareplus.com` in documentation and seed data is NOT changed (it's a technical identifier, not a role label).
