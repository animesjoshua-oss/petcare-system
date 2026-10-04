<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Terms of Service';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ── Page-specific styles ───────────────────────────── -->
<style>
/* Legal page layout */
.legal-page {
  padding: 3rem 0 5rem;
}

.legal-hero {
  background: linear-gradient(135deg, #0F1F6E 0%, var(--primary) 60%, var(--secondary) 100%);
  padding: 3.5rem 0 2.5rem;
  margin-bottom: 0;
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
  max-width: 520px;
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

/* Two-column layout: TOC sidebar + content */
.legal-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 2.5rem;
  align-items: start;
  max-width: 1100px;
  margin: 0 auto;
  padding: 2.5rem 1.5rem 0;
}

/* Sticky TOC */
.legal-toc {
  position: sticky;
  top: 86px; /* below navbar */
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
  color: var(--primary);
  flex-shrink: 0;
  min-width: 18px;
}

.legal-toc ol li a:hover {
  background: rgba(30,64,255,.07);
  color: var(--primary);
}

.legal-toc ol li a.active {
  background: rgba(30,64,255,.1);
  color: var(--primary);
  font-weight: 600;
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

/* Section */
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
  background: linear-gradient(135deg, var(--primary), var(--secondary));
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

.legal-section ul {
  list-style: disc;
}

.legal-section ol {
  list-style: decimal;
}

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
  background: linear-gradient(135deg, rgba(30,64,255,.06), rgba(0,212,170,.06));
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

/* Back to top */
.back-to-top-wrap {
  text-align: center;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border);
}

/* Responsive */
@media (max-width: 900px) {
  .legal-layout {
    grid-template-columns: 1fr;
  }
  .legal-toc {
    position: static;
    order: 2; /* push below content on mobile? actually keep above */
    order: 0;
  }
}

@media (max-width: 600px) {
  .legal-hero h1 { font-size: 1.7rem; }
  .legal-content { padding: 1.5rem 1.25rem; }
  .legal-section-title { font-size: 1.05rem; }
}

/* TL;DR Box */
.tldr-box {
  background: linear-gradient(135deg, rgba(30,64,255,.05), rgba(0,212,170,.05));
  border: 1.5px solid rgba(30,64,255,.2);
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
  background: linear-gradient(135deg, var(--primary), var(--secondary));
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

.tldr-note i { color: var(--primary); }

@media (max-width: 600px) {
  .tldr-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ── Hero ─────────────────────────────────────────────── -->
<div class="legal-hero">
  <div class="container">
    <div class="legal-badge"><i class="fas fa-file-contract"></i> Legal Document</div>
    <h1><i class="fas fa-file-contract" style="margin-right:.5rem;"></i> Terms of Service</h1>
    <p>Please read these terms carefully before using PetCare Clinic's services.</p>
  </div>
</div>

<!-- ── Main layout ──────────────────────────────────────── -->
<div class="legal-page">
  <div class="legal-layout">

    <!-- Table of Contents -->
    <aside class="legal-toc" id="legalToc">
      <h3><i class="fas fa-list" style="margin-right:.4rem;"></i>Contents</h3>
      <ol>
        <li><a href="#acceptance">Acceptance of Terms</a></li>
        <li><a href="#services">Description of Services</a></li>
        <li><a href="#accounts">User Accounts &amp; Registration</a></li>
        <li><a href="#pet-info">Pet &amp; Medical Information</a></li>
        <li><a href="#appointments">Appointments, Cancellation &amp; Rescheduling</a></li>
        <li><a href="#clinic-policy">Clinic Policies &amp; Responsibilities</a></li>
        <li><a href="#user-conduct">User Responsibilities &amp; Prohibited Activities</a></li>
        <li><a href="#payments">Payments &amp; Fees</a></li>
        <li><a href="#liability">Limitation of Liability</a></li>
        <li><a href="#ip">Intellectual Property</a></li>
        <li><a href="#termination">Account Termination</a></li>
        <li><a href="#changes">Changes to These Terms</a></li>
        <li><a href="#contact-terms">Contact Information</a></li>
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
              <strong>Booking platform lang kami</strong>
              <span>Hindi kami ang actual na vet o groomer — nagko-connect lang kami sa inyo.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Libre ang paggamit</strong>
              <span>Walang bayad ang platform. Ang bayad sa vet/groomer ay direkta sa kanila.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Pwede kang mag-cancel</strong>
              <span>I-cancel lang ng maaga (24 hrs bago) para hindi maapektuhan ang clinic.</span>
            </div>
          </div>
          <div class="tldr-item tldr-good">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Ikaw ang may-ari ng iyong content</strong>
              <span>Ang mga reviews at pet photos mo ay sa iyo pa rin — limited use lang namin.</span>
            </div>
          </div>
          <div class="tldr-item tldr-bad">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Bawal mag-fake ng info</strong>
              <span>Fake account, fake ID, o maling pet info = termination ng account.</span>
            </div>
          </div>
          <div class="tldr-item tldr-bad">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Hindi kami liable sa clinic</strong>
              <span>Ang clinic ay independent — ang dispute sa serbisyo ay sa kanila mo resolbahin.</span>
            </div>
          </div>
          <div class="tldr-item tldr-bad">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Hindi pang-medikal advice ang AI</strong>
              <span>Ang AI namin ay para sa general info lang — kumonsulta pa rin sa vet.</span>
            </div>
          </div>
          <div class="tldr-item tldr-bad">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Bawal i-hack o i-abuse ang platform</strong>
              <span>Any misuse = ban + possible legal action under Philippine law.</span>
            </div>
          </div>
        </div>
        <p class="tldr-note">
          <i class="fas fa-info-circle"></i>
          This is just a simplified summary. The full legal terms below are the ones that officially apply.
        </p>
      </div>

      <!-- 1. Acceptance of Terms -->
      <section class="legal-section" id="acceptance">
        <h2 class="legal-section-title">
          <span class="legal-section-num">1</span>
          Acceptance of Terms
        </h2>
        <p>By accessing or using the PetCare Clinic website and its services (collectively, the "Platform"), you agree to be bound by these Terms of Service ("Terms"). These Terms constitute a legally binding agreement between you ("User," "you," or "your") and PetCare Clinic ("we," "us," or "our").</p>
        <p>If you do not agree to all of these Terms, you must not access or use the Platform. Your continued use of the Platform following any updates to these Terms constitutes your acceptance of the revised Terms.</p>
        <p>By creating an account, you confirm that:</p>
        <ul>
          <li>You are at least 18 years of age, or are accessing the Platform under the supervision of a parent or legal guardian.</li>
          <li>You have the legal capacity to enter into a binding contract.</li>
          <li>You have read, understood, and agreed to these Terms in their entirety.</li>
        </ul>
      </section>

      <!-- 2. Description of Services -->
      <section class="legal-section" id="services">
        <h2 class="legal-section-title">
          <span class="legal-section-num">2</span>
          Description of Services
        </h2>
        <p>PetCare Clinic is an online platform that connects pet owners with registered veterinary clinics and grooming centers in the Philippines. Through the Platform, users may:</p>
        <ul>
          <li>Create and manage a personal account</li>
          <li>Register one or more pets and maintain pet health records</li>
          <li>Search for and view information about participating veterinary clinics and grooming centers</li>
          <li>Book, reschedule, or cancel appointments with veterinarians and groomers</li>
          <li>View appointment status and history</li>
          <li>Receive notifications and updates related to their pets and appointments</li>
          <li>Access AI-assisted pet care guidance (informational purposes only)</li>
          <li>Rate and review veterinary and grooming services</li>
        </ul>
        <p>The Platform serves as an <strong>intermediary booking and information system</strong> only. PetCare Clinic does not directly employ veterinarians or groomers. The actual veterinary care and grooming services are provided by independent, participating clinics and professionals. We do not make any representations or warranties regarding the quality of services provided by these third-party professionals.</p>
        <p>AI-generated responses and pet care suggestions on the Platform are for <strong>general informational purposes only</strong> and should not be treated as professional veterinary advice. Always consult a licensed veterinarian for medical concerns about your pet.</p>
      </section>

      <!-- 3. User Accounts & Registration -->
      <section class="legal-section" id="accounts">
        <h2 class="legal-section-title">
          <span class="legal-section-num">3</span>
          User Accounts &amp; Registration
        </h2>
        <p>To access most features of the Platform, you must create a user account. During registration, you agree to:</p>
        <ul>
          <li>Provide accurate, current, and complete information about yourself</li>
          <li>Submit valid government-issued identification for account verification</li>
          <li>Keep your account information up to date at all times</li>
          <li>Maintain the confidentiality of your account credentials (username and password)</li>
          <li>Notify us immediately of any unauthorized use of your account</li>
          <li>Take full responsibility for all activities that occur under your account</li>
        </ul>
        <p>Account activation is subject to administrator review and approval. We reserve the right to deny, suspend, or deactivate any account at our sole discretion, particularly if we have reason to believe that information provided is false, inaccurate, or incomplete.</p>
        <p>You may not create more than one account per person, transfer your account to another person, or use another person's account without their express written permission.</p>
        <p><strong>Account Security:</strong> You are solely responsible for maintaining the security of your account. PetCare Clinic will not be liable for any losses or damages arising from your failure to safeguard your login credentials.</p>
      </section>

      <!-- 4. Pet & Medical Information -->
      <section class="legal-section" id="pet-info">
        <h2 class="legal-section-title">
          <span class="legal-section-num">4</span>
          Pet &amp; Medical Information
        </h2>
        <p>The Platform allows you to enter and store information about your pets, including but not limited to species, breed, age, weight, health conditions, vaccination records, and veterinary history. By submitting this information, you:</p>
        <ul>
          <li>Represent and warrant that the information you provide is accurate and complete to the best of your knowledge</li>
          <li>Acknowledge that inaccurate information may affect the quality of care your pet receives</li>
          <li>Grant PetCare Clinic and participating clinic staff permission to access your pet's information for the purpose of providing services</li>
          <li>Understand that pet health information provided on the Platform does not substitute for an in-person veterinary examination</li>
        </ul>
        <p>Medical records and diagnoses entered by licensed veterinarians through the Platform remain the professional records of those veterinarians. PetCare Clinic does not verify the accuracy of any medical record entered on the Platform.</p>
        <p>You are solely responsible for the accuracy of all pet information you enter. Providing false or misleading health information about your pet that results in harm to the pet, other animals, or clinic staff may be grounds for account termination.</p>
      </section>

      <!-- 5. Appointments -->
      <section class="legal-section" id="appointments">
        <h2 class="legal-section-title">
          <span class="legal-section-num">5</span>
          Appointments, Cancellation &amp; Rescheduling
        </h2>
        <p><strong>Booking:</strong> Appointment requests submitted through the Platform are subject to confirmation by the respective clinic. A booking is not confirmed until you receive a confirmation notice from PetCare Clinic or the clinic directly. PetCare Clinic does not guarantee appointment availability.</p>
        <p><strong>Cancellation Policy:</strong> If you need to cancel an appointment, you must do so through the Platform or by contacting the clinic directly as early as possible. We strongly recommend cancelling no later than <strong>24 hours before the scheduled appointment time</strong> to avoid inconvenience to the clinic and other pet owners waiting for available slots.</p>
        <p><strong>Rescheduling:</strong> Rescheduling requests are subject to availability and clinic approval. The Platform may facilitate rescheduling requests, but final confirmation rests with the individual clinic.</p>
        <p><strong>No-Shows:</strong> Repeated failure to attend confirmed appointments without prior cancellation may result in restrictions on your ability to make future bookings through the Platform, at our sole discretion.</p>
        <p><strong>Clinic-Initiated Changes:</strong> Clinics may cancel or reschedule appointments due to unforeseen circumstances, such as emergencies or staff unavailability. PetCare Clinic will endeavor to notify you promptly of any such changes through the Platform's notification system, but is not liable for any inconvenience caused by clinic-initiated appointment changes.</p>
      </section>

      <!-- 6. Clinic Policies & Responsibilities -->
      <section class="legal-section" id="clinic-policy">
        <h2 class="legal-section-title">
          <span class="legal-section-num">6</span>
          Clinic Policies &amp; Responsibilities
        </h2>
        <p>Each participating clinic listed on the Platform is an independent legal entity. These clinics set their own service standards, fees, and operational policies. By booking an appointment with a clinic through the Platform, you acknowledge that:</p>
        <ul>
          <li>The clinic's own policies and procedures apply to your visit</li>
          <li>PetCare Clinic is not a party to your service contract with the clinic</li>
          <li>PetCare Clinic is not liable for any acts, omissions, negligence, or misconduct of any clinic, veterinarian, or groomer</li>
          <li>Any disputes regarding services rendered must be resolved directly with the clinic</li>
        </ul>
        <p>PetCare Clinic makes reasonable efforts to verify that clinics listed on the Platform are duly registered and licensed to operate. However, we do not continuously monitor compliance and cannot guarantee that all clinic information is current or accurate.</p>
        <p>If you encounter unsafe, unethical, or illegal conduct at a clinic listed on our Platform, please report it to us using the contact information provided in these Terms.</p>
      </section>

      <!-- 7. User Responsibilities & Prohibited Activities -->
      <section class="legal-section" id="user-conduct">
        <h2 class="legal-section-title">
          <span class="legal-section-num">7</span>
          User Responsibilities &amp; Prohibited Activities
        </h2>
        <p>You agree to use the Platform only for lawful purposes and in a manner consistent with these Terms. You must not:</p>
        <ul>
          <li>Provide false, misleading, or fraudulent personal, pet, or identification information</li>
          <li>Impersonate any person or entity, or misrepresent your affiliation with any person or entity</li>
          <li>Attempt to gain unauthorized access to the Platform, its servers, or databases</li>
          <li>Use automated scripts, bots, scrapers, or crawlers to collect data from the Platform</li>
          <li>Transmit any viruses, malware, or other harmful computer code</li>
          <li>Harass, abuse, or threaten other users, clinic staff, or PetCare Clinic personnel</li>
          <li>Post or submit any content that is defamatory, obscene, offensive, or violates any applicable law</li>
          <li>Use the Platform for commercial solicitation or spam without prior written consent</li>
          <li>Reproduce, distribute, or create derivative works from Platform content without authorization</li>
          <li>Interfere with or disrupt the integrity or performance of the Platform</li>
          <li>Circumvent any security, access control, or authentication measures on the Platform</li>
          <li>Use another user's account or share your account credentials with third parties</li>
        </ul>
        <p>Violation of any of the above may result in immediate suspension or termination of your account, and may expose you to civil or criminal liability under applicable Philippine law, including but not limited to Republic Act No. 10175 (Cybercrime Prevention Act of 2012).</p>
      </section>

      <!-- 8. Payments & Fees -->
      <section class="legal-section" id="payments">
        <h2 class="legal-section-title">
          <span class="legal-section-num">8</span>
          Payments &amp; Fees
        </h2>
        <p>Currently, registration and use of the core booking features of the PetCare Clinic Platform is provided at no charge to pet owners. This may change in the future, and we will provide advance notice of any changes to our fee structure.</p>
        <p><strong>Service Fees:</strong> Fees for veterinary consultations, grooming services, and other professional services are set independently by each participating clinic and are payable directly to the clinic. PetCare Clinic does not process payments for services rendered by clinics unless explicitly stated otherwise on the Platform.</p>
        <p><strong>Refunds:</strong> Refund policies for veterinary or grooming services are determined entirely by the individual clinics. PetCare Clinic has no authority over, and accepts no liability for, any clinic's refund or payment dispute decisions.</p>
        <p>If we introduce paid features or subscription plans in the future, separate payment terms will be presented to you at that time and will require your explicit acceptance before any charges are incurred.</p>
      </section>

      <!-- 9. Limitation of Liability -->
      <section class="legal-section" id="liability">
        <h2 class="legal-section-title">
          <span class="legal-section-num">9</span>
          Limitation of Liability
        </h2>
        <p>To the fullest extent permitted by applicable law, PetCare Clinic, its officers, directors, employees, agents, and affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to:</p>
        <ul>
          <li>Loss of profits, data, or goodwill</li>
          <li>Service interruptions or Platform downtime</li>
          <li>Unauthorized access to your account or personal data</li>
          <li>Any acts or omissions of participating clinics, veterinarians, or groomers</li>
          <li>Any reliance on AI-generated content or suggestions on the Platform</li>
          <li>Any errors, inaccuracies, or omissions in user-provided or clinic-provided content</li>
        </ul>
        <p>The Platform is provided on an <strong>"as is"</strong> and <strong>"as available"</strong> basis without warranties of any kind, either express or implied, including without limitation warranties of merchantability, fitness for a particular purpose, or non-infringement.</p>
        <p>Nothing in these Terms limits or excludes our liability for death or personal injury caused by our own negligence, fraud, or any other liability that cannot be excluded or limited under applicable Philippine law.</p>
      </section>

      <!-- 10. Intellectual Property -->
      <section class="legal-section" id="ip">
        <h2 class="legal-section-title">
          <span class="legal-section-num">10</span>
          Intellectual Property
        </h2>
        <p>All content, features, and functionality on the Platform — including but not limited to text, graphics, logos, icons, images, audio clips, software, and source code — are the exclusive property of PetCare Clinic or its content suppliers and are protected by applicable intellectual property laws of the Republic of the Philippines and international conventions.</p>
        <p>You are granted a limited, non-exclusive, non-transferable, and revocable license to access and use the Platform for your personal, non-commercial use only. You may not:</p>
        <ul>
          <li>Copy, reproduce, or redistribute any part of the Platform</li>
          <li>Modify, adapt, translate, or create derivative works</li>
          <li>Reverse engineer, decompile, or disassemble any software component</li>
          <li>Use any trademarks, service marks, or logos without prior written consent</li>
        </ul>
        <p><strong>User-Generated Content:</strong> By submitting content to the Platform (such as reviews, pet photos, or profile information), you grant PetCare Clinic a non-exclusive, royalty-free, worldwide license to use, display, and reproduce that content solely for the purpose of operating and improving the Platform. You retain ownership of your content and may request its removal through the account settings or by contacting us.</p>
      </section>

      <!-- 11. Account Termination -->
      <section class="legal-section" id="termination">
        <h2 class="legal-section-title">
          <span class="legal-section-num">11</span>
          Account Termination
        </h2>
        <p><strong>By You:</strong> You may deactivate or delete your account at any time by contacting us through the contact information below. Upon account deletion, your personal data will be handled in accordance with our Privacy Policy.</p>
        <p><strong>By PetCare Clinic:</strong> We reserve the right to suspend or permanently terminate your account and access to the Platform at any time, with or without notice, for any reason, including but not limited to:</p>
        <ul>
          <li>Violation of these Terms or any applicable law</li>
          <li>Submission of false or fraudulent information</li>
          <li>Conduct that is harmful to other users, clinics, or PetCare Clinic</li>
          <li>Non-compliance with account verification requirements</li>
          <li>Extended inactivity</li>
        </ul>
        <p>Upon termination, your right to use the Platform immediately ceases. Provisions of these Terms that by their nature should survive termination shall continue to apply, including but not limited to sections on Intellectual Property, Limitation of Liability, and any outstanding payment obligations.</p>
      </section>

      <!-- 12. Changes to Terms -->
      <section class="legal-section" id="changes">
        <h2 class="legal-section-title">
          <span class="legal-section-num">12</span>
          Changes to These Terms
        </h2>
        <p>PetCare Clinic reserves the right to modify or replace these Terms at any time. When we do, we will update the "Effective Date" at the bottom of this page and, where appropriate, notify registered users via email or through the Platform's notification system.</p>
        <p>It is your responsibility to review these Terms periodically. Your continued use of the Platform after the posting of revised Terms constitutes your acceptance of the changes. If you do not agree to the modified Terms, you must stop using the Platform.</p>
        <p>For material changes that significantly affect your rights, we will make reasonable efforts to provide at least 30 days' advance notice before the changes take effect.</p>
      </section>

      <!-- 13. Contact -->
      <section class="legal-section" id="contact-terms">
        <h2 class="legal-section-title">
          <span class="legal-section-num">13</span>
          Contact Information
        </h2>
        <p>If you have any questions, concerns, or complaints regarding these Terms of Service, please contact us:</p>
        <div class="legal-contact-card">
          <p><strong>PetCare Clinic</strong></p>
          <p><i class="fas fa-map-marker-alt" style="color:var(--primary);width:16px;"></i> &nbsp;[Your Clinic Address], Pangasinan, Philippines</p>
          <p><i class="fas fa-envelope" style="color:var(--primary);width:16px;"></i> &nbsp;<a href="mailto:legal@petcareclinic.com">legal@petcareclinic.com</a></p>
          <p><i class="fas fa-phone" style="color:var(--primary);width:16px;"></i> &nbsp;+63-900-000-0000</p>
          <p><i class="fas fa-clock" style="color:var(--primary);width:16px;"></i> &nbsp;Monday – Saturday, 8:00 AM – 6:00 PM (PHT)</p>
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
