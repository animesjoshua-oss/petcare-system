<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Common Questions';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container" style="max-width:900px;padding:3rem 1rem 4rem;">
  <div style="margin-bottom:2rem;">
    <span class="badge badge-primary">PetCare+ Clinic Hub</span>
    <h1 style="margin:.75rem 0 .5rem;">Common Questions</h1>
    <p style="color:var(--text-muted);line-height:1.7;">PetCare+ helps pet owners find veterinary clinics, request appointments, manage pet profiles, and review care information in one place. Clinics use the platform to coordinate appointments and keep authorized pet records organized.</p>
  </div>
  <section style="display:grid;gap:1rem;margin-top:1rem;">
    <details class="card"><summary class="card-body" style="cursor:pointer;font-weight:700;">What is the purpose of PetCare+?</summary><div class="card-body" style="padding-top:0;">PetCare+ connects pet owners with verified veterinary clinics. Owners can manage pet profiles, request appointments, and keep care information organized, while clinics coordinate appointments and maintain authorized medical records.</div></details>
    <details class="card"><summary class="card-body" style="cursor:pointer;font-weight:700;">How do pet owners create an account?</summary><div class="card-body" style="padding-top:0;">Register with an email and password, then sign in to create pet profiles, search clinics, and request appointments.</div></details>
    <details class="card"><summary class="card-body" style="cursor:pointer;font-weight:700;">How to apply Vet Clinic in the system</summary><div class="card-body" style="padding-top:0;"><p>Submit the clinic details, the veterinarian who will be its Vet Admin, a license number, and a clear copy of the required credentials. A Super Admin verifies the documents and emails the decision to the submitted address.</p><p>If approved, PetCare+ creates the clinic listing and Vet Admin account. The email entered on the application becomes the Vet Admin's sign-in email, and the one-time password is sent to that address. The Vet Admin can update the public profile and hours, manage appointments and pet records, and add veterinarian and groomer accounts with optional license or credential files.</p><a href="<?= APP_URL ?>/pages/apply-clinic.php" class="btn btn-primary"><i class="fas fa-hospital-user"></i> Apply Vet Clinic</a></div></details>
  </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
