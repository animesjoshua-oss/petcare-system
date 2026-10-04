<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Privacy Policy';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ── Page-specific styles (same legal-page system as terms.php) ── -->
<style>
/* ── Reuse legal-page layout from terms.php ── */
.legal-page {
  padding: 3rem 0 5rem;
}

.legal-hero {
  background: linear-gradient(135deg, #0F1F6E 0%, var(--primary) 60%, var(--secondary) 100%);
  padding: 3.5rem 0 2.5rem;
  text-align: center;
  color: #fff;
}

.legal-hero h1 {
  font-size: 2.25rem;
  font-weight: 800;
  margin-bottom: .5rem;
}

.legal-hero p {
  opacity: .85;
  font-size: 1rem;
  max-width: 540px;
  margin: 0 auto;
}

.legal-hero .legal-badge {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(255,255,255,.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.25);
  padding: .35rem 1rem;
  border-radius: 50px;
  font-size: .8rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.legal-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 2.5rem;
  align-items: start;
  max-width: 1100px;
  margin: 0 auto;
  padding: 2.5rem 1.5rem 0;
}

.legal-toc {
  position: sticky;
  top: 86px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
}

.legal-toc h3 {
  font-size: .8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.legal-toc ol {
  list-style: none;
  padding: 0;
  margin: 0;
  counter-reset: toc-counter;
}

.legal-toc ol li {
  counter-increment: toc-counter;
  margin-bottom: .15rem;
}

.legal-toc ol li a {
  display: flex;
  align-items: flex-start;
  gap: .5rem;
  font-size: .82rem;
  color: var(--text-muted);
  padding: .35rem .5rem;
  border-radius: 6px;
  transition: all var(--transition);
  line-height: 1.4;
  text-decoration: none;
}

.legal-toc ol li a::before {
  content: counter(toc-counter) ".";
  font-weight: 700;
  color: var(--secondary);
  flex-shrink: 0;
  min-width: 18px;
}

.legal-toc ol li a:hover {
  background: rgba(0,212,170,.08);
  color: var(--sec-dark);
}

.legal-toc ol li a.active {
  background: rgba(0,212,170,.12);
  color: var(--sec-dark);
  font-weight: 600;
}

/* RA 10173 highlight box */
.legal-law-box {
  background: rgba(30,64,255,.05);
  border: 1px solid rgba(30,64,255,.2);
  border-left: 4px solid var(--primary);
  border-radius: var(--radius-sm);
  padding: 1rem 1.25rem;
  margin-bottom: 1.1rem;
  font-size: .875rem;
  line-height: 1.7;
  color: var(--text);
}

.legal-law-box strong {
  color: var(--primary);
}

/* Data rights grid */
.rights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
  margin: 1.1rem 0;
}

.right-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 1rem 1.1rem;
  display: flex;
  gap: .75rem;
  align-items: flex-start;
  transition: box-shadow var(--transition), transform var(--transition);
}

.right-card:hover {
  box-shadow: var(--shadow);
  transform: translateY(-2px);
}

.right-card-icon {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .9rem;
  flex-shrink: 0;
}

.right-card-body strong {
  display: block;
  font-size: .875rem;
  font-weight: 700;
  margin-bottom: .2rem;
  color: var(--text);
}

.right-card-body span {
  font-size: .8rem;
  color: var(--text-muted);
  line-height: 1.5;
}

/* Notice banner */
.legal-notice {
  background: rgba(245,158,11,.08);
  border: 1px solid rgba(245,158,11,.35);
  border-radius: var(--radius-sm);
  padding: 1rem 1.25rem;
  margin-bottom: 2rem;
  display: flex;
  gap: .75rem;
  align-items: flex-start;
  font-size: .875rem;
  color: #92400E;
  line-height: 1.6;
}

.legal-notice i {
  flex-shrink: 0;
  margin-top: .15rem;
  color: var(--warning);
}

[data-theme="dark"] .legal-notice {
  color: #fde68a;
}

/* Content area */
.legal-content {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2.5rem 2.75rem;
  box-shadow: var(--shadow-sm);
}

.legal-section {
  margin-bottom: 2.75rem;
  scroll-margin-top: 94px;
}

.legal-section:last-child {
  margin-bottom: 0;
}

.legal-section-title {
  display: flex;
  align-items: center;
  gap: .75rem;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 1rem;
  padding-bottom: .65rem;
  border-bottom: 2px solid var(--border);
}

.legal-section-num {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  background: linear-gradient(135deg, var(--secondary), var(--sec-dark));
  color: #fff;
  border-radius: 8px;
  font-size: .8rem;
  font-weight: 700;
  flex-shrink: 0;
}

.legal-section p {
  color: var(--text);
  font-size: .925rem;
  line-height: 1.8;
  margin-bottom: .9rem;
}

.legal-section p:last-child {
  margin-bottom: 0;
}

.legal-section ul,
.legal-section ol {
  padding-left: 1.4rem;
  margin-bottom: .9rem;
}

.legal-section ul { list-style: disc; }
.legal-section ol { list-style: decimal; }

.legal-section li {
  font-size: .925rem;
  line-height: 1.8;
  color: var(--text);
  margin-bottom: .3rem;
}

.legal-section strong {
  color: var(--text);
  font-weight: 600;
}

/* Info table for data types */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .875rem;
  margin: 1rem 0 .9rem;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px solid var(--border);
}

.data-table th {
  background: rgba(30,64,255,.07);
  font-weight: 700;
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--primary);
  padding: .75rem 1rem;
  text-align: left;
  border-bottom: 2px solid var(--border);
}

.data-table td {
  padding: .75rem 1rem;
  border-bottom: 1px solid var(--border);
  vertical-align: top;
  color: var(--text);
  line-height: 1.6;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.data-table tr:hover td {
  background: rgba(0,212,170,.04);
}

/* Effective date pill */
.legal-effective {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: rgba(0,212,170,.1);
  border: 1px solid rgba(0,212,170,.3);
  color: var(--sec-dark);
  font-size: .82rem;
  font-weight: 600;
  padding: .35rem 1rem;
  border-radius: 50px;
  margin-top: 1.5rem;
}

/* Contact card */
.legal-contact-card {
  background: linear-gradient(135deg, rgba(0,212,170,.06), rgba(30,64,255,.06));
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 1.25rem 1.5rem;
  margin-top: 1rem;
}

.legal-contact-card p {
  margin-bottom: .4rem !important;
}

.legal-contact-card a {
  color: var(--primary);
  font-weight: 500;
}

/* NPC contact box */
.npc-box {
  background: rgba(16,185,129,.06);
  border: 1px solid rgba(16,185,129,.25);
  border-left: 4px solid var(--success);
  border-radius: var(--radius-sm);
  padding: 1rem 1.25rem;
  margin-top: 1rem;
  font-size: .875rem;
  line-height: 1.7;
}

.npc-box strong {
  color: var(--success);
}

.npc-box a {
  color: var(--primary);
}

/* Back to top */
.back-to-top-wrap {
  text-align: center;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border);
}

/* Responsive */
@media (max-width: 900px) {
  .legal-layout { grid-template-columns: 1fr; }
  .legal-toc { position: static; }
  .rights-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 600px) {
  .legal-hero h1 { font-size: 1.7rem; }
  .legal-content { padding: 1.5rem 1.25rem; }
  .legal-section-title { font-size: 1.05rem; }
  .rights-grid { grid-template-columns: 1fr; }
  .data-table { font-size: .8rem; }
  .data-table th, .data-table td { padding: .6rem .75rem; }
}

/* TL;DR Box */
.tldr-box {
  background: linear-gradient(135deg, rgba(0,212,170,.05), rgba(30,64,255,.05));
  border: 1.5px solid rgba(0,212,170,.25);
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.tldr-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: 1.25rem;
}

.tldr-badge {
  background: linear-gradient(135deg, var(--secondary), var(--primary));
  color: #fff;
  font-size: .75rem;
  font-weight: 800;
  padding: .3rem .8rem;
  border-radius: 50px;
  letter-spacing: .05em;
  flex-shrink: 0;
}

.tldr-title {
  font-size: .95rem;
  font-weight: 700;
  color: var(--text);
}

.tldr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: .75rem;
  margin-bottom: 1rem;
}

.tldr-item {
  display: flex;
  align-items: flex-start;
  gap: .65rem;
  padding: .75rem 1rem;
  border-radius: var(--radius-sm);
  background: var(--card);
  border: 1px solid var(--border);
}

.tldr-item i {
  font-size: 1rem;
  margin-top: .1rem;
  flex-shrink: 0;
}

.tldr-good i { color: var(--success); }
.tldr-bad  i { color: var(--danger); }

.tldr-item strong {
  display: block;
  font-size: .82rem;
  font-weight: 700;
  margin-bottom: .15rem;
  color: var(--text);
}

.tldr-item span {
  font-size: .78rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.tldr-note {
  font-size: .78rem;
  color: var(--text-muted);
  margin: 0 !important;
  display: flex;
  align-items: center;
  gap: .4rem;
}

.tldr-note i { color: var(--secondary); }

@media (max-width: 600px) {
  .tldr-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ── Hero ─────────────────────────────────────────────── -->
<div class="legal-hero">
  <div class="container">
    <div class="legal-badge"><i class="fas fa-shield-alt"></i> Privacy &amp; Data Protection</div>
    <h1><i class="fas fa-shield-alt" style="margin-right:.5rem;"></i> Privacy Policy</h1>
    <p>How PetCare Clinic collects, uses, stores, and protects your personal information — in compliance with RA 10173.</p>
  </div>
</div>

<!-- ── Main layout ──────────────────────────────────────── -->
<div class="legal-page">
  <div class="legal-layout">

    <!-- Table of Contents -->
    <aside class="legal-toc" id="legalToc">
      <h3><i class="fas fa-list" style="margin-right:.4rem;"></i>Contents</h3>
      <ol>
        <li><a href="#pp-overview">Overview &amp; Legal Basis</a></li>
        <li><a href="#pp-collect">Information We Collect</a></li>
        <li><a href="#pp-pet">Pet Information Collected</a></li>
        <li><a href="#pp-appt">Appointment &amp; Veterinary Data</a></li>
        <li><a href="#pp-use">How We Use Your Information</a></li>
        <li><a href="#pp-storage">Storage &amp; Security</a></li>
        <li><a href="#pp-sharing">Sharing with Third Parties</a></li>
        <li><a href="#pp-rights">Your Privacy Rights</a></li>
        <li><a href="#pp-cookies">Cookies &amp; Web Technologies</a></li>
        <li><a href="#pp-retention">Data Retention</a></li>
        <li><a href="#pp-children">Children's Privacy</a></li>
        <li><a href="#pp-changes">Changes to This Policy</a></li>
        <li><a href="#pp-contact">Contact &amp; DPO</a></li>
      </ol>
    </aside>

    <!-- Content -->
    <main class="legal-content" id="legalContent">

      <!-- Legal professional notice -->
      <div class="legal-notice">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
          <strong>Notice:</strong> This document is a template for informational purposes. It should be reviewed, customized, and validated by a qualified legal professional before being used as official policy for PetCare Clinic.
        </div>
      </div>

      <!-- TL;DR Summary Box -->
      <div class="tldr-box">
        <div class="tldr-header">
          <span class="tldr-badge">⚡ TL;DR</span>
          <span class="tldr-title">Too long? Here's the short version</span>
        </div>
        <div class="tldr-grid">
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Hindi ibinibenta ang data mo</strong>
              <span>Ang personal info mo ay hindi ibinibenta, nirenta, o ibinibigay sa advertisers.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Ang clinic lang ang makakakita</strong>
              <span>Yung clinic na binook mo lang ang makaka-access ng iyong info — nobody else.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>May 8 rights ka under RA 10173</strong>
              <span>Access, correct, delete, object, portability — lahat ng rights mo bilang data subject.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Secure ang iyong data</strong>
              <span>HTTPS, hashed passwords, CSRF protection, at role-based access controls.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Puwede kang mag-request ng deletion</strong>
              <span>Kung gusto mong tanggalin ang account mo, ia-anonymize ang data mo within 30 days.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Walang ad tracking cookies</strong>
              <span>Session at functional cookies lang — walang third-party advertising trackers.</span>
            </div>
          </div>
          <div class="tldr-item tldr-bad">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Kinukuha namin ang govt ID mo</strong>
              <span>Required para sa account verification — visible lang sa admin, hindi sa ibang users.</span>
            </div>
          </div>
          <div class="tldr-item tldr-bad">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Hindi para sa under 18</strong>
              <span>Ang platform ay para sa adults lang. Kung minor ka, hindi ka puwedeng mag-register.</span>
            </div>
          </div>
        </div>
        <p class="tldr-note">
          <i class="fas fa-info-circle"></i>
          This is just a simplified summary. The full Privacy Policy below is the one that officially applies under RA 10173.
        </p>
      </div>

      <!-- 1. Overview & Legal Basis -->
      <section class="legal-section" id="pp-overview">
        <h2 class="legal-section-title">
          <span class="legal-section-num">1</span>
          Overview &amp; Legal Basis
        </h2>
        <p>PetCare Clinic ("we," "us," or "our") is committed to protecting the privacy and personal data of our users in accordance with applicable Philippine law. This Privacy Policy explains how we collect, use, disclose, store, and protect your personal information when you access or use the PetCare Clinic platform (the "Platform").</p>
        <div class="legal-law-box">
          <strong>Republic Act No. 10173 — Data Privacy Act of 2012 (DPA)</strong><br>
          This Privacy Policy is drafted in compliance with the <strong>Data Privacy Act of 2012 (RA 10173)</strong> and its Implementing Rules and Regulations (IRR), which govern the collection, handling, and protection of personal data in the Philippines. As a personal information controller, PetCare Clinic is responsible for ensuring that all personal data we process complies with the principles of transparency, legitimate purpose, and proportionality as required by the DPA.
        </div>
        <p>By registering an account and using the Platform, you consent to the collection, use, and disclosure of your personal data as described in this Privacy Policy. You may withdraw your consent at any time, subject to legal or contractual restrictions, by contacting our Data Protection Officer (DPO) using the contact details provided in Section 13.</p>
        <p>This Policy applies to all users of the Platform, including pet owners, veterinarians, groomers, and administrators.</p>
      </section>

      <!-- 2. Information We Collect -->
      <section class="legal-section" id="pp-collect">
        <h2 class="legal-section-title">
          <span class="legal-section-num">2</span>
          Information We Collect
        </h2>
        <p>We collect personal information that you voluntarily provide to us when you create an account, book appointments, or interact with the Platform, as well as certain data collected automatically during your use of the Platform.</p>

        <table class="data-table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Data Collected</th>
              <th>Purpose</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Account &amp; Identity</strong></td>
              <td>First name, last name, email address, phone number, profile photo, password (hashed)</td>
              <td>Account creation, authentication, and communication</td>
            </tr>
            <tr>
              <td><strong>Government ID</strong></td>
              <td>ID type, ID number, front and back photos of government-issued ID</td>
              <td>Identity verification and account security (admin review only)</td>
            </tr>
            <tr>
              <td><strong>Appointment Data</strong></td>
              <td>Preferred date/time, selected clinic, service type, appointment notes</td>
              <td>Booking, scheduling, and record-keeping</td>
            </tr>
            <tr>
              <td><strong>Pet Information</strong></td>
              <td>Pet name, species, breed, age, weight, health notes, photos, vaccination records</td>
              <td>Pet profile management and veterinary care facilitation</td>
            </tr>
            <tr>
              <td><strong>Technical Data</strong></td>
              <td>IP address, browser type, device type, OS, session data, cookies</td>
              <td>Platform security, fraud prevention, and performance analytics</td>
            </tr>
            <tr>
              <td><strong>Usage Data</strong></td>
              <td>Pages visited, features used, search queries, click paths, time on page</td>
              <td>Improving Platform features and user experience</td>
            </tr>
            <tr>
              <td><strong>Reviews &amp; Ratings</strong></td>
              <td>Star ratings, written reviews submitted about clinics or services</td>
              <td>Quality assurance and public service information</td>
            </tr>
            <tr>
              <td><strong>Communications</strong></td>
              <td>Messages sent through AI chat, support inquiries, email correspondence</td>
              <td>Customer support, service improvement</td>
            </tr>
          </tbody>
        </table>

        <p>We only collect information that is <strong>adequate, relevant, and not excessive</strong> in relation to the purposes for which it is being processed, in accordance with Section 11(c) of RA 10173.</p>
        <p>We do not knowingly collect <strong>sensitive personal information</strong> (as defined under RA 10173, such as health data, religious beliefs, or financial information) beyond what is strictly necessary for providing veterinary services. Veterinary medical records constituting health data are handled with heightened protection measures.</p>
      </section>

      <!-- 3. Pet Information -->
      <section class="legal-section" id="pp-pet">
        <h2 class="legal-section-title">
          <span class="legal-section-num">3</span>
          Pet Information Collected
        </h2>
        <p>As part of the Platform's core service, we collect and store information about your registered pets. This may include:</p>
        <ul>
          <li><strong>Basic pet details:</strong> Name, species (e.g., dog, cat), breed, date of birth or estimated age, sex, color, and distinguishing markings</li>
          <li><strong>Health information:</strong> Known medical conditions, allergies, current medications, vaccination history, and any special dietary requirements</li>
          <li><strong>Veterinary records:</strong> Diagnoses, treatment notes, prescriptions, and follow-up instructions entered by licensed veterinarians on the Platform</li>
          <li><strong>Photos:</strong> Images of your pet uploaded for profile identification purposes</li>
          <li><strong>Microchip or registration numbers:</strong> If voluntarily provided</li>
        </ul>
        <p>Pet health information is treated with the same level of care as personal health data. It is accessible only to the registered pet owner, the attending veterinarian, and authorized Platform administrators for support purposes.</p>
        <p>You are responsible for the accuracy of all pet information you submit. Inaccurate health information may negatively affect the care your pet receives from veterinary professionals.</p>
      </section>

      <!-- 4. Appointment & Veterinary Data -->
      <section class="legal-section" id="pp-appt">
        <h2 class="legal-section-title">
          <span class="legal-section-num">4</span>
          Appointment &amp; Veterinary-Related Information
        </h2>
        <p>When you book or manage appointments through the Platform, we collect and process the following appointment-related data:</p>
        <ul>
          <li>Selected clinic, veterinarian, or groomer</li>
          <li>Preferred appointment date and time</li>
          <li>Type of service requested (e.g., consultation, vaccination, grooming)</li>
          <li>Appointment status (pending, confirmed, completed, cancelled)</li>
          <li>Pre-appointment notes or instructions you provide</li>
          <li>Post-appointment veterinary notes, diagnoses, and treatment plans entered by the attending professional</li>
          <li>Ratings and reviews you submit after appointments</li>
        </ul>
        <p>Appointment records are retained to maintain a complete history of your pet's care, facilitate future visits, and support administrative and analytical functions. This data is shared with the relevant clinic and its staff as necessary to perform the booked service.</p>
        <p><strong>Important:</strong> Medical records and clinical notes are created and maintained by licensed veterinary professionals. PetCare Clinic facilitates storage and access to these records on the Platform but does not independently verify their clinical accuracy. For any concerns about the accuracy of a medical record, contact the issuing veterinarian directly.</p>
      </section>

      <!-- 5. How We Use Your Information -->
      <section class="legal-section" id="pp-use">
        <h2 class="legal-section-title">
          <span class="legal-section-num">5</span>
          How We Use Your Information
        </h2>
        <p>We process your personal data only for the specific purposes for which it was collected. These purposes include:</p>
        <ul>
          <li><strong>Account management:</strong> Creating, maintaining, securing, and verifying your account</li>
          <li><strong>Service delivery:</strong> Facilitating appointment bookings, managing pet profiles, and enabling communication between pet owners and clinic professionals</li>
          <li><strong>Identity verification:</strong> Reviewing submitted government IDs to ensure the integrity and security of the Platform</li>
          <li><strong>Notifications and communications:</strong> Sending appointment confirmations, reminders, status updates, and other service-related messages via email and in-Platform notifications</li>
          <li><strong>Customer support:</strong> Responding to inquiries, complaints, or reports submitted through the Platform</li>
          <li><strong>Platform improvement:</strong> Analyzing usage patterns and feedback to improve features, usability, and performance</li>
          <li><strong>Safety and fraud prevention:</strong> Detecting, investigating, and preventing fraudulent activity, unauthorized access, or violations of these Terms</li>
          <li><strong>Legal compliance:</strong> Fulfilling our obligations under applicable Philippine laws and regulations, including RA 10173</li>
          <li><strong>Aggregated analytics:</strong> Generating anonymized, non-identifiable statistical reports about Platform usage</li>
        </ul>
        <p>We will not use your personal data for any purpose incompatible with the purposes listed above without your prior written consent, except where required or permitted by law.</p>
        <p>We do <strong>not</strong> sell, rent, or trade your personal information to third parties for marketing purposes.</p>
      </section>

      <!-- 6. Storage & Security -->
      <section class="legal-section" id="pp-storage">
        <h2 class="legal-section-title">
          <span class="legal-section-num">6</span>
          Storage &amp; Security of Your Information
        </h2>
        <p>PetCare Clinic takes reasonable and appropriate technical and organizational measures to protect your personal data against unauthorized access, disclosure, alteration, loss, or destruction. Our security measures include:</p>
        <ul>
          <li><strong>Password hashing:</strong> User passwords are never stored in plain text; they are hashed using industry-standard one-way hashing algorithms</li>
          <li><strong>HTTPS encryption:</strong> All data transmitted between your browser and our servers is encrypted using TLS (HTTPS)</li>
          <li><strong>Access controls:</strong> Personal data and government ID photos are accessible only to authorized administrators and the relevant clinic staff; role-based access controls restrict data visibility</li>
          <li><strong>Session security:</strong> Sessions are protected using HTTP-only, SameSite cookies and strict session management policies</li>
          <li><strong>Secure file storage:</strong> Uploaded files (ID photos, pet photos, clinic images) are stored in access-controlled server directories not directly accessible via URL without authorization</li>
          <li><strong>CSRF protection:</strong> All forms are protected against Cross-Site Request Forgery using token-based validation</li>
          <li><strong>Regular monitoring:</strong> We monitor for unauthorized access attempts and apply security patches promptly</li>
        </ul>
        <p>Your personal data is stored on servers located in [Server Location — please update]. We endeavor to ensure that any data processor we engage provides equivalent data protection standards.</p>
        <p>Despite our best efforts, no method of electronic transmission or storage is 100% secure. If you believe your account has been compromised, please contact us immediately using the information in Section 13.</p>
        <p>In the event of a personal data breach that is likely to prejudice your rights and freedoms, PetCare Clinic will notify the <strong>National Privacy Commission (NPC)</strong> and affected data subjects within <strong>72 hours</strong> of discovery, in compliance with NPC Circular No. 16-03.</p>
      </section>

      <!-- 7. Sharing with Third Parties -->
      <section class="legal-section" id="pp-sharing">
        <h2 class="legal-section-title">
          <span class="legal-section-num">7</span>
          When We Share Your Information
        </h2>
        <p>We do not share your personal information with third parties except in the following circumstances:</p>
        <ul>
          <li><strong>Participating clinics and professionals:</strong> When you book an appointment, relevant booking details and pet information are shared with the selected clinic and its authorized staff for the sole purpose of providing the requested service.</li>
          <li><strong>Service providers:</strong> We may engage trusted third-party service providers (e.g., email delivery services, cloud hosting providers) who process data on our behalf. These providers are bound by data processing agreements and are not permitted to use your data for any purpose other than performing services for PetCare Clinic.</li>
          <li><strong>Legal requirements:</strong> We may disclose your information when required to do so by applicable law, court order, or government regulation, including to comply with lawful requests from law enforcement agencies.</li>
          <li><strong>Protection of rights:</strong> We may disclose data if we reasonably believe disclosure is necessary to protect the rights, property, or safety of PetCare Clinic, our users, or the public.</li>
          <li><strong>Business transfers:</strong> In the event of a merger, acquisition, or sale of assets, your personal data may be transferred to the acquiring entity, provided that it continues to be subject to equivalent data protection obligations.</li>
          <li><strong>With your consent:</strong> We may share your data with third parties for other purposes if we obtain your explicit, informed prior consent.</li>
        </ul>
        <p>Any third party with whom we share personal data is required to treat it with the same level of protection as described in this Privacy Policy and in accordance with RA 10173.</p>
        <p>We do <strong>not</strong> share personal data with advertisers, data brokers, or marketing companies.</p>
      </section>

      <!-- 8. Your Privacy Rights -->
      <section class="legal-section" id="pp-rights">
        <h2 class="legal-section-title">
          <span class="legal-section-num">8</span>
          Your Privacy Rights
        </h2>
        <p>Under the <strong>Data Privacy Act of 2012 (RA 10173)</strong>, you have the following rights as a data subject. You may exercise these rights by contacting our Data Protection Officer (DPO) at the details provided in Section 13.</p>

        <div class="rights-grid">
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-info-circle"></i></div>
            <div class="right-card-body">
              <strong>Right to be Informed</strong>
              <span>You have the right to know when and how your personal data is being collected, and the purposes for which it is processed (Section 16(a), RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-eye"></i></div>
            <div class="right-card-body">
              <strong>Right to Access</strong>
              <span>You have the right to obtain a copy of the personal data we hold about you and to know how it has been processed (Section 16(b), RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-pen-to-square"></i></div>
            <div class="right-card-body">
              <strong>Right to Rectification</strong>
              <span>You have the right to request correction of any inaccurate, incomplete, or outdated personal data we hold about you (Section 16(c), RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-trash-can"></i></div>
            <div class="right-card-body">
              <strong>Right to Erasure or Blocking</strong>
              <span>You have the right to request the deletion or blocking of your personal data in certain circumstances, such as when it is no longer necessary for the purposes for which it was collected (Section 16(d), RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-hand-paper"></i></div>
            <div class="right-card-body">
              <strong>Right to Object</strong>
              <span>You have the right to object to the processing of your personal data for direct marketing, automated processing, or profiling, and in cases where processing is based on legitimate interest (Section 16(e), RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-file-export"></i></div>
            <div class="right-card-body">
              <strong>Right to Data Portability</strong>
              <span>You have the right to receive a copy of your personal data in a structured, commonly used, and machine-readable format (Section 18, RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-shield-halved"></i></div>
            <div class="right-card-body">
              <strong>Right to Damages</strong>
              <span>You have the right to be indemnified for any damages sustained due to inaccurate, incomplete, outdated, false, unlawfully obtained, or unauthorized use of your personal data (Section 16(f), RA 10173).</span>
            </div>
          </div>
          <div class="right-card">
            <div class="right-card-icon"><i class="fas fa-ban"></i></div>
            <div class="right-card-body">
              <strong>Right to Withdraw Consent</strong>
              <span>Where processing is based on your consent, you have the right to withdraw it at any time without affecting the lawfulness of processing carried out before the withdrawal.</span>
            </div>
          </div>
        </div>

        <p>To exercise any of these rights, please submit a written request to our DPO using the contact information in Section 13. We will respond within a reasonable period as required by the DPA and its IRR, generally within <strong>15 working days</strong> of receiving a valid request.</p>
        <p>If you believe your data privacy rights have been violated, you also have the right to file a complaint directly with the <strong>National Privacy Commission (NPC)</strong>:</p>
        <div class="npc-box">
          <strong>National Privacy Commission (NPC)</strong><br>
          3/F DICT Building, C.P. Garcia Avenue, UP Technology Park Complex,<br>
          UP Campus, Diliman, Quezon City 1101, Philippines<br>
          <i class="fas fa-globe"></i> <a href="https://www.privacy.gov.ph" target="_blank" rel="noopener noreferrer">www.privacy.gov.ph</a><br>
          <i class="fas fa-envelope"></i> <a href="mailto:info@privacy.gov.ph">info@privacy.gov.ph</a>
        </div>
      </section>

      <!-- 9. Cookies -->
      <section class="legal-section" id="pp-cookies">
        <h2 class="legal-section-title">
          <span class="legal-section-num">9</span>
          Cookies &amp; Web Technologies
        </h2>
        <p>The Platform uses cookies and similar web technologies to enhance your browsing experience and maintain session state. A <strong>cookie</strong> is a small text file stored on your device by your web browser.</p>
        <p>We use the following types of cookies:</p>
        <ul>
          <li><strong>Strictly necessary cookies:</strong> Required for the Platform to function correctly, including session management (keeping you logged in) and CSRF security tokens. These cannot be disabled without affecting Platform functionality.</li>
          <li><strong>Functional cookies:</strong> Used to remember your preferences, such as dark mode settings, so you do not need to reconfigure them on each visit.</li>
          <li><strong>Analytics cookies:</strong> If enabled, used to collect anonymous, aggregated data about how users interact with the Platform to help us improve it. We do not use third-party advertising or tracking cookies.</li>
        </ul>
        <p>You can manage or disable cookies through your browser settings. Please note that disabling strictly necessary cookies will prevent you from logging in or using core Platform features. Refer to your browser's help documentation for instructions on managing cookies.</p>
        <p>We do <strong>not</strong> use cookies to serve targeted advertisements or to track your activity on third-party websites.</p>
        <p>The Platform may use browser <strong>localStorage</strong> and <strong>sessionStorage</strong> for preferences such as theme settings. This data is stored locally on your device and is not transmitted to our servers.</p>
      </section>

      <!-- 10. Data Retention -->
      <section class="legal-section" id="pp-retention">
        <h2 class="legal-section-title">
          <span class="legal-section-num">10</span>
          Data Retention
        </h2>
        <p>We retain your personal data only for as long as necessary to fulfill the purposes for which it was collected, and as required by applicable law and regulatory obligations.</p>
        <ul>
          <li><strong>Active accounts:</strong> Personal data and pet records are retained for the duration of your account's existence on the Platform.</li>
          <li><strong>Appointment records:</strong> Appointment history and veterinary records are retained for a minimum of <strong>5 years</strong> after the date of service to comply with professional veterinary record-keeping standards.</li>
          <li><strong>Government ID documents:</strong> Uploaded ID photos are retained only as long as necessary for account verification purposes. Once verification is complete, we limit access to ID photos to authorized administrators only.</li>
          <li><strong>Deleted accounts:</strong> Upon account deletion, personal identification data will be anonymized or deleted within <strong>30 days</strong>, except where retention is required by law or for dispute resolution purposes.</li>
          <li><strong>Inactive accounts:</strong> Accounts inactive for more than <strong>3 years</strong> may be subject to data review and deletion following notification to the account email address.</li>
          <li><strong>Server logs:</strong> Technical access logs are retained for up to <strong>90 days</strong> for security monitoring, then permanently deleted.</li>
          <li><strong>Marketing consents:</strong> Records of consent are retained for the duration of your account to demonstrate compliance with RA 10173.</li>
        </ul>
        <p>When personal data is no longer required, we will securely delete, anonymize, or dispose of it in a manner that protects your privacy, in accordance with Section 11(e) of RA 10173.</p>
      </section>

      <!-- 11. Children's Privacy -->
      <section class="legal-section" id="pp-children">
        <h2 class="legal-section-title">
          <span class="legal-section-num">11</span>
          Children's Privacy
        </h2>
        <p>The PetCare Clinic Platform is <strong>not intended for use by individuals under 18 years of age</strong>. We do not knowingly collect personal data from minors. Registration on the Platform requires users to be at least 18 years old or to access the Platform under parental or guardian supervision and consent.</p>
        <p>In accordance with <strong>Republic Act No. 9775 (Anti-Child Pornography Act of 2009)</strong> and the Child Safety Online provisions of RA 10175 (Cybercrime Prevention Act of 2012), we take the protection of minors online seriously.</p>
        <p>If you are a parent or legal guardian and believe that your child has provided personal data to PetCare Clinic without your knowledge or consent, please contact our DPO immediately using the details provided in Section 13. We will take prompt action to delete any such information from our records.</p>
        <p>If we discover that we have inadvertently collected personal data from a minor, we will delete it from our systems as quickly as reasonably possible.</p>
      </section>

      <!-- 12. Changes to Privacy Policy -->
      <section class="legal-section" id="pp-changes">
        <h2 class="legal-section-title">
          <span class="legal-section-num">12</span>
          Changes to This Privacy Policy
        </h2>
        <p>We may update this Privacy Policy from time to time to reflect changes in our data practices, applicable law, or Platform features. When we make changes, we will:</p>
        <ul>
          <li>Update the "Effective Date" at the bottom of this page</li>
          <li>Notify registered users via email and/or in-Platform notification for material changes</li>
          <li>Where required by RA 10173, obtain your renewed consent for any new or substantially different processing purposes</li>
        </ul>
        <p>We encourage you to review this Privacy Policy periodically. Your continued use of the Platform after changes are posted constitutes your acknowledgment of the updated Policy.</p>
        <p>If we intend to use your personal data for a purpose not originally disclosed at the time of collection, we will notify you and, where required, seek your separate consent before doing so.</p>
      </section>

      <!-- 13. Contact & DPO -->
      <section class="legal-section" id="pp-contact">
        <h2 class="legal-section-title">
          <span class="legal-section-num">13</span>
          Contact Information &amp; Data Protection Officer
        </h2>
        <p>For any questions, concerns, or requests regarding this Privacy Policy or the exercise of your data subject rights under RA 10173, please contact our designated Data Protection Officer (DPO):</p>

        <div class="legal-contact-card">
          <p><strong>PetCare Clinic — Data Protection Officer</strong></p>
          <p><i class="fas fa-user-shield" style="color:var(--secondary);width:16px;"></i> &nbsp;[DPO Name — please update]</p>
          <p><i class="fas fa-map-marker-alt" style="color:var(--secondary);width:16px;"></i> &nbsp;[Your Clinic Address], Pangasinan, Philippines</p>
          <p><i class="fas fa-envelope" style="color:var(--secondary);width:16px;"></i> &nbsp;<a href="mailto:privacy@petcareclinic.com">privacy@petcareclinic.com</a></p>
          <p><i class="fas fa-phone" style="color:var(--secondary);width:16px;"></i> &nbsp;+63-900-000-0000</p>
          <p><i class="fas fa-clock" style="color:var(--secondary);width:16px;"></i> &nbsp;Monday – Saturday, 8:00 AM – 6:00 PM (PHT)</p>
        </div>

        <p style="margin-top:1.1rem;">You may also submit a complaint to the <strong>National Privacy Commission (NPC)</strong> if you believe your data privacy rights under RA 10173 have not been upheld:</p>
        <div class="npc-box">
          <strong>National Privacy Commission (NPC)</strong><br>
          3/F DICT Building, C.P. Garcia Avenue, UP Technology Park Complex,<br>
          UP Campus, Diliman, Quezon City 1101, Philippines<br>
          <i class="fas fa-globe"></i> <a href="https://www.privacy.gov.ph" target="_blank" rel="noopener noreferrer">www.privacy.gov.ph</a><br>
          <i class="fas fa-envelope"></i> <a href="mailto:info@privacy.gov.ph">info@privacy.gov.ph</a>
        </div>

        <div class="legal-effective">
          <i class="fas fa-calendar-check"></i>
          Effective Date: September 21, 2026
        </div>

        <div class="back-to-top-wrap">
          <a href="#" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-up"></i> Back to Top
          </a>
        </div>
      </section>

    </main><!-- /.legal-content -->
  </div><!-- /.legal-layout -->
</div><!-- /.legal-page -->

<script>
// Highlight active TOC link on scroll
(function () {
  var sections = document.querySelectorAll('.legal-section');
  var tocLinks = document.querySelectorAll('.legal-toc a');
  if (!sections.length || !tocLinks.length) return;

  function onScroll() {
    var scrollY = window.scrollY + 110;
    var current = '';
    sections.forEach(function (sec) {
      if (sec.offsetTop <= scrollY) current = sec.id;
    });
    tocLinks.forEach(function (a) {
      var href = a.getAttribute('href').replace('#', '');
      a.classList.toggle('active', href === current);
    });
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
