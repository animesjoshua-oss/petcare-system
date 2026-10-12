# UNIVERSIDAD DE DAGUPAN
## SCHOOL OF INFORMATION TECHNOLOGY EDUCATION
## CAPSTONE ALPHA TESTING FEEDBACK FORM

☐ PASSED ☐ CONDITIONAL PASSED ☐ RE-ALPHA

**Alpha target:** The alpha target is at least 50% of all listed features complete and working, or the one designated main feature complete and working. The panel verifies completion during the live demonstration. Partial, planned, and unshown features do not count.

---

## Project Details

| Project details | Entry |
|----------------|-------|
| **Project title** | PetCare Plus Veterinary Clinic Hub |
| **Team members** | ALBA, LAWRENCE M. - Developer |
| | BERNABE, JOHN PREX B. - Developer |
| | ESCOSIO, JERO D. - Developer |
| | JOVEN, FRANCIS RAFAEL O. - Developer |
| | PASTORAL, MARK JOSHUA M. - Developer |
| **Alpha testing date** | [Start date - End date] |

---

## 1. Project Overview and Objectives

The PetCare Plus Veterinary Clinic Hub addresses the operational challenges faced by veterinary clinics in Pangasinan that rely on manual, paper-based processes for appointment scheduling and pet record management. The system is a centralized web-based platform designed for pet owners, veterinarians, groomers, and clinic Vet administrators. It enables online appointment booking, digital pet profile management, AI-assisted medical record summarization for veterinarians, and an AI chatbot to help pet owners find suitable veterinary services. The platform aims to improve accessibility, organization, and overall efficiency of pet care services across Pangasinan while supporting evidence-based veterinary decision-making.

---

## 2. Scope and Delimitations

### Full Scope Statement

The scope of this study is to develop a centralized web-based PetCare Plus Veterinary Clinic Hub that consolidates information about veterinary clinics within Pangasinan. Pet owners can register, search for veterinary clinic services, book appointments online, manage pet profiles, use an AI chatbot to find and recommend veterinary clinics that offer their requested services, and leave feedback about the veterinary clinic's services. Veterinarians can manage appointments, pet medical records, use AI-assistant for summarizing pet record, while groomers can manage their profile to show grooming services, view upcoming grooming appointments and manage the status. Clinic Vet administrators manage clinic information, and provide veterinarian and groomer accounts to employees. And the super admin is responsible for adding and verifying veterinary clinics that will be added in the system.

### Scope: What the system covers

- **Geographic Coverage:** Registered veterinary clinics within Pangasinan province
- **User Types:** Pet owners, veterinarians, groomers, clinic Vet administrators, and super admin
- **Core Functions:**
  - User registration and authentication
  - Online appointment scheduling and management
  - Digital pet profile creation and management
  - Pet medical record storage and retrieval
  - AI-assisted medical record summarization (via Groq API)
  - AI chatbot for clinic recommendation (via Groq API)
  - Clinic search and discovery with Google Maps integration
  - Service rating and feedback system
  - Clinic information management
  - Appointment status tracking
- **Platform:** Web-based application accessible via modern web browsers
- **Technology Stack:** PHP 8.0+, MySQL 8.0+, HTML, CSS, JavaScript
- **External Integrations:** Groq API (AI features), Google Maps API (clinic locator), PHPMailer (email notifications)

### Delimitations: Boundaries and exclusions

- **Geographic Limitation:** System is limited to registered veterinary clinics within Pangasinan only
- **No Online Payment:** The system does not include online payment integration
- **No Emergency Booking:** Emergency or urgent appointment booking is not supported
- **No Automated Reminders:** Automated vaccination reminders are not included
- **No Teleconsultation:** Remote video consultation services are not provided
- **No Home Visit Services:** Scheduling of home visit services is not supported
- **No Mobile Application:** System does not have a dedicated mobile application port
- **Internet Dependency:** System requires active internet connection to function
- **AI Limitations:** AI-assistant responses may contain errors and require verification by veterinarians and pet owners
- **Record Access Restriction:** Pet profiles and medical records can only be accessed by the owner and by the registered veterinary clinic for each specific appointment separately; records cannot be accessed by other registered clinics
- **Data Privacy Compliance:** System complies with Republic Act No. 10173 (Data Privacy Act of 2012)

---

## 3. User Roles

| Role | Main actions or permissions |
|------|---------------------------|
| **Pet Owner** | Register account, search for clinics and services, book appointments online, create and manage pet profiles, view pet medical records, use AI chatbot for clinic recommendations, rate and provide feedback on veterinary services, manage appointment schedules |
| **Veterinarian** | View and manage appointments, access pet medical records for assigned appointments, update medical records, use AI-assistant to generate summaries of pet medical records, manage clinical information, view appointment schedules |
| **Groomer** | View and manage grooming appointments, access pet profile information for assigned appointments, display grooming services offered, update grooming service details, manage appointment status |
| **Clinic Vet Administrator** | Manage clinic information and profile, create and manage veterinarian accounts, create and manage groomer accounts, view clinic appointments, manage clinic services, update clinic details and operating hours |
| **Super Admin** | Add new veterinary clinics to the system, verify veterinary clinic registrations, manage system-wide settings, oversee all clinic registrations across Pangasinan |

---

## 4. Complete Feature List

List every feature in the stated scope, including incomplete features. Identify exactly one main feature before the alpha test. Use Complete, Partial, or Planned for the team status. Add rows or a continuation sheet as needed.

| No | Main? | Feature and expected outcome | User role | Status | Panel Status |
|----|-------|----------------------------|-----------|--------|--------------|
| 1 | **Y** | **Online Appointment Booking System** - Pet owners can search for available veterinary clinics, select desired services (veterinary consultation, grooming, vaccination, etc.), choose available time slots, and submit appointment requests. System confirms booking and displays appointment details. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |
| 2 | N | User Registration and Authentication - Users can create accounts with role-based access (pet owner, veterinarian, groomer, clinic Vet admin, super admin). System validates credentials and provides secure login/logout functionality with OTP verification via PHPMailer. | All Users | [Complete/Partial/Planned] | [Done/Not Done] |
| 3 | N | Pet Profile Management - Pet owners can create comprehensive pet profiles including name, breed, age, medical history, and photos. Profiles are editable and accessible for future appointments. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |
| 4 | N | Clinic Search and Discovery - Users can search for veterinary clinics by location, services offered, ratings, and availability. Results display clinic information with Google Maps integration showing clinic locations. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |
| 5 | N | AI Chatbot for Clinic Recommendations - Pet owners can interact with AI chatbot to describe their pet care needs and receive personalized clinic recommendations based on requested services via Groq API integration. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |
| 6 | N | Appointment Management Dashboard - Users can view upcoming appointments, appointment history, modify appointments, and cancel bookings. System displays appointment status (pending, confirmed, completed, cancelled). | Pet Owner, Veterinarian, Groomer | [Complete/Partial/Planned] | [Done/Not Done] |
| 7 | N | Medical Record Management - Veterinarians can create, view, and update pet medical records including diagnoses, treatments, prescriptions, and consultation notes. Records are securely stored and linked to specific appointments. | Veterinarian | [Complete/Partial/Planned] | [Done/Not Done] |
| 8 | N | AI-Assisted Medical Record Summarization - Veterinarians can use AI-assistant powered by Groq API to generate concise summaries of pet medical records, supporting faster information retrieval and clinical decision-making. | Veterinarian | [Complete/Partial/Planned] | [Done/Not Done] |
| 9 | N | Service Rating and Feedback System - Pet owners can rate veterinary services and provide written feedback after completed appointments. Ratings are displayed on clinic profiles to help establish credibility and service quality. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |
| 10 | N | Groomer Service Management - Groomers can display available grooming services (nail cutting, hair trimming, bathing, etc.), view upcoming grooming appointments, access pet profile information for scheduled appointments, and update appointment status. | Groomer | [Complete/Partial/Planned] | [Done/Not Done] |
| 11 | N | Clinic Profile Management - Clinic Vet administrators can create and update clinic information including name, address, contact details, operating hours, services offered, photos, and clinic description. | Clinic Vet Administrator | [Complete/Partial/Planned] | [Done/Not Done] |
| 12 | N | Staff Account Management - Clinic Vet administrators can create and manage accounts for veterinarians and groomers employed at their clinic, assign roles and permissions. | Clinic Vet Administrator | [Complete/Partial/Planned] | [Done/Not Done] |
| 13 | N | Clinic Registration and Verification - Super admin can add new veterinary clinics to the system, verify clinic credentials and documentation, approve or reject clinic registration requests, ensuring only legitimate clinics are listed. | Super Admin | [Complete/Partial/Planned] | [Done/Not Done] |
| 14 | N | Google Maps Integration - System displays clinic locations on interactive maps, allowing pet owners to visualize clinic proximity and get directions. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |
| 15 | N | Email Notifications - System sends automated email notifications for account verification (OTP), appointment confirmations, appointment reminders, and appointment status changes via PHPMailer. | All Users | [Complete/Partial/Planned] | [Done/Not Done] |
| 16 | N | Responsive Web Interface - System provides user-friendly, responsive design compatible with desktop and mobile browsers (Chrome, Firefox, Edge) ensuring consistent experience across devices. | All Users | [Complete/Partial/Planned] | [Done/Not Done] |
| 17 | N | Appointment History and Records - Users can access complete history of past appointments including dates, services received, attending veterinarian/groomer, and associated medical records. | Pet Owner, Veterinarian, Groomer | [Complete/Partial/Planned] | [Done/Not Done] |
| 18 | N | Password Recovery - Users can reset forgotten passwords through secure email-based password recovery process using OTP verification. | All Users | [Complete/Partial/Planned] | [Done/Not Done] |
| 19 | N | Clinic Dashboard Analytics - Clinic Vet administrators can view appointment statistics, service utilization data, and rating summaries for their clinic. | Clinic Vet Administrator | [Complete/Partial/Planned] | [Done/Not Done] |
| 20 | N | Favorite Clinics - Pet owners can mark clinics as favorites for quick access and easier rebooking with preferred service providers. | Pet Owner | [Complete/Partial/Planned] | [Done/Not Done] |

---

**50% route met:** ☐ Yes ☐ No  
**Designated main feature verified complete:** ☐ Yes ☐ No  
**Minimum alpha target met through either route:** ☐ Yes ☐ No

---

## Feedback and Recommendations

[Panel will provide detailed feedback here during alpha testing]

___________________________________________________________________________________________________________

___________________________________________________________________________________________________________

___________________________________________________________________________________________________________

___________________________________________________________________________________________________________

___________________________________________________________________________________________________________

___________________________________________________________________________________________________________

---

**Reason for re-alpha:** _________________________________________________________________

**Conditions or re-alpha focus:** __________________________________________________________

**Panelist signature:** ________________________________  **Date:** __________________

---

## 5. High Level System Architecture

The PetCare Plus Veterinary Clinic Hub employs a three-tier architecture consisting of presentation layer, application layer, and data layer.

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                          │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐      │
│  │   Pet    │  │Veterina- │  │ Groomer  │  │  Clinic  │      │
│  │  Owner   │  │   rian   │  │          │  │Vet Admin │      │
│  │  (Web    │  │  (Web    │  │  (Web    │  │  (Web    │      │
│  │Browser)  │  │ Browser) │  │ Browser) │  │ Browser) │      │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘      │
│       │             │              │              │            │
└───────┼─────────────┼──────────────┼──────────────┼────────────┘
        │             │              │              │
        │   HTML / CSS / JavaScript Interface      │
        │             │              │              │
┌───────┼─────────────┼──────────────┼──────────────┼────────────┐
│       │             │              │              │            │
│       └─────────────┴──────────────┴──────────────┘            │
│                          │                                     │
│              APPLICATION LAYER (PHP 8.0+)                      │
│                          │                                     │
│  ┌────────────────────────────────────────────────┐           │
│  │        Business Logic & Controllers            │           │
│  │  • User Authentication & Authorization         │           │
│  │  • Appointment Scheduling Engine               │           │
│  │  • Pet Profile Management                      │           │
│  │  • Medical Record Management                   │           │
│  │  • Service Rating System                       │           │
│  │  • Notification Manager (PHPMailer)            │           │
│  └───────┬────────────────────────────────┬───────┘           │
│          │                                │                   │
│          │                                │                   │
│  ┌───────▼───────┐              ┌────────▼────────┐           │
│  │  External     │              │  External       │           │
│  │  API Layer    │              │  API Layer      │           │
│  │               │              │                 │           │
│  │  Groq API     │              │  Google Maps    │           │
│  │  • AI Chatbot │              │  API            │           │
│  │  • Med Record │              │  • Clinic       │           │
│  │    Summary    │              │    Locator      │           │
│  └───────────────┘              └─────────────────┘           │
│          │                                                     │
└──────────┼─────────────────────────────────────────────────────┘
           │
┌──────────▼─────────────────────────────────────────────────────┐
│                        DATA LAYER                              │
│                                                                │
│               MySQL Database (MySQL 8.0+)                      │
│                                                                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐     │
│  │  Users   │  │   Pets   │  │  Clinics │  │Appoint-  │     │
│  │          │  │          │  │          │  │  ments   │     │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘     │
│                                                                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐     │
│  │ Medical  │  │ Services │  │  Ratings │  │Messages/ │     │
│  │ Records  │  │          │  │          │  │  Notif   │     │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘     │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### Architecture Description

**Presentation Layer:**
- User-facing web interface built with HTML, CSS, and JavaScript
- Accessible via modern web browsers (Chrome, Firefox, Edge)
- Responsive design for desktop and mobile browsers
- Separate dashboards for each user role

**Application Layer:**
- PHP 8.0+ server-side processing
- Business logic for appointment scheduling, user authentication, pet profile management
- Integration controllers for external APIs (Groq AI, Google Maps)
- PHPMailer for automated email notifications (OTP, appointment confirmations)
- Session management and role-based access control

**Data Layer:**
- MySQL 8.0+ relational database
- Stores user accounts, pet profiles, clinic information, appointments, medical records, ratings, and notifications
- Normalized database schema ensuring data integrity and efficient queries

**External Integrations:**
- **Groq API:** Powers AI chatbot for clinic recommendations and AI-assisted medical record summarization
- **Google Maps API:** Provides clinic location mapping and visualization
- **PHPMailer:** Handles automated email delivery for authentication and notifications

---

## 6. Database Entity Diagram

### Entity Diagram

```
┌─────────────┐
│    USERS    │
│─────────────│
│ user_id (PK)│───────┐
│ username    │       │
│ password    │       │
│ email       │       │
│ role        │       │
│ status      │       │
└─────────────┘       │
                      │
                      │ 1:N
                      │
        ┌─────────────▼──────┐
        │       PETS         │
        │────────────────────│
        │ pet_id (PK)        │───────┐
        │ owner_id (FK)      │       │
        │ name               │       │
        │ breed              │       │
        │ age                │       │
        │ medical_history    │       │
        └────────────────────┘       │
                                     │
                                     │ 1:N
                                     │
                     ┌───────────────▼──────────────┐
                     │       APPOINTMENTS           │
                     │──────────────────────────────│
                     │ appointment_id (PK)          │
                     │ pet_id (FK)                  │
                     │ clinic_id (FK)               │
                     │ vet_id (FK)                  │
                     │ groomer_id (FK)              │
                     │ service_type                 │
                     │ appointment_date             │
                     │ appointment_time             │
                     │ status                       │
                     └───────┬──────────────────────┘
                             │
           ┌─────────────────┼─────────────────┐
           │ 1:N             │ 1:N             │ 1:N
           │                 │                 │
┌──────────▼────────┐ ┌──────▼────────┐ ┌─────▼──────────┐
│ MEDICAL_RECORDS   │ │   RATINGS     │ │  NOTIFICATIONS │
│───────────────────│ │───────────────│ │────────────────│
│ record_id (PK)    │ │ rating_id (PK)│ │ notif_id (PK)  │
│ appointment_id(FK)│ │ appt_id (FK)  │ │ user_id (FK)   │
│ diagnosis         │ │ user_id (FK)  │ │ appt_id (FK)   │
│ treatment         │ │ clinic_id(FK) │ │ message        │
│ prescription      │ │ rating_score  │ │ status         │
│ notes             │ │ feedback      │ │ timestamp      │
│ created_by (FK)   │ │ created_at    │ └────────────────┘
└───────────────────┘ └───────────────┘

┌─────────────────┐                    ┌──────────────────┐
│    CLINICS      │                    │  VETERINARIANS   │
│─────────────────│                    │──────────────────│
│ clinic_id (PK)  │────┐               │ vet_id (PK)      │
│ name            │    │               │ user_id (FK)     │
│ address         │    │               │ clinic_id (FK)   │
│ contact         │    │               │ specialization   │
│ description     │    │               │ license_number   │
│ latitude        │    │               └──────────────────┘
│ longitude       │    │
│ verified        │    │               ┌──────────────────┐
└─────────────────┘    │               │    GROOMERS      │
                       │               │──────────────────│
                       │ 1:N           │ groomer_id (PK)  │
                       │               │ user_id (FK)     │
                       └───────────────│ clinic_id (FK)   │
                                       │ services_offered │
                                       └──────────────────┘

┌─────────────────┐           1:N
│    SERVICES     │◄──────────────────┐
│─────────────────│                   │
│ service_id (PK) │                   │
│ clinic_id (FK)  │───────────────────┘
│ service_name    │
│ service_type    │
│ description     │
│ price           │
└─────────────────┘

┌─────────────────┐
│   FAVORITES     │
│─────────────────│
│ favorite_id(PK) │
│ user_id (FK)    │
│ clinic_id (FK)  │
│ created_at      │
└─────────────────┘
```

### Key Relationships:

- **Users → Pets:** One user (pet owner) can have multiple pets (1:N)
- **Pets → Appointments:** One pet can have multiple appointments (1:N)
- **Clinics → Appointments:** One clinic can have multiple appointments (1:N)
- **Appointments → Medical Records:** One appointment can have multiple medical records (1:N)
- **Appointments → Ratings:** One appointment can have one rating (1:1 or 1:N)
- **Clinics → Services:** One clinic can offer multiple services (1:N)
- **Clinics → Veterinarians:** One clinic can have multiple veterinarians (1:N)
- **Clinics → Groomers:** One clinic can have multiple groomers (1:N)
- **Users → Favorites:** One user can favorite multiple clinics (M:N through junction table)

---

## 7. Flowchart for Each User Role

### User Role: Pet Owner

```
                           START
                             │
                             ▼
                    ┌────────────────┐
                    │  Load Homepage │
                    └────────┬───────┘
                             │
                    ┌────────▼────────┐
                    │  Logged In?     │
                    └────┬────────┬───┘
                         │ No     │ Yes
                    ┌────▼───┐    │
                    │ Login/ │    │
                    │Register│    │
                    └────┬───┘    │
                         │        │
                         └────┬───┘
                              │
                    ┌─────────▼──────────┐
                    │  Pet Owner         │
                    │  Dashboard         │
                    └────────┬───────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌───────────────┐   ┌────────────────┐   ┌───────────────┐
│Search Clinics │   │ Manage Pets    │   │View Appoint-  │
└───────┬───────┘   └────────┬───────┘   │ments          │
        │                    │            └───────┬───────┘
        ▼                    ▼                    │
┌───────────────┐   ┌────────────────┐           │
│Use AI Chatbot │   │Create/Edit Pet │           │
│for Recommend. │   │Profile         │           │
└───────┬───────┘   └────────┬───────┘           │
        │                    │                    │
        ▼                    ▼                    ▼
┌───────────────┐   ┌────────────────┐   ┌───────────────┐
│View Clinic    │   │Upload Pet      │   │View History   │
│Details on Map │   │Photo & Info    │   │& Status       │
└───────┬───────┘   └────────┬───────┘   └───────┬───────┘
        │                    │                    │
        ▼                    ▼                    │
┌───────────────┐   ┌────────────────┐           │
│Select Service │   │Save Pet        │           │
│Type & Date    │   │Profile         │           │
└───────┬───────┘   └────────────────┘           │
        │                                         │
        ▼                                         │
┌───────────────┐                                 │
│Submit Appoint-│                                 │
│ment Request   │                                 │
└───────┬───────┘                                 │
        │                                         │
        ▼                                         │
┌───────────────┐                                 │
│Receive        │                                 │
│Confirmation   │                                 │
└───────┬───────┘                                 │
        │                                         │
        └─────────────────┬───────────────────────┘
                          │
                          ▼
                 ┌────────────────┐
                 │After Appoint-  │
                 │ment Complete   │
                 └────────┬───────┘
                          │
                          ▼
                 ┌────────────────┐
                 │Rate & Provide  │
                 │Feedback        │
                 └────────┬───────┘
                          │
                          ▼
                 ┌────────────────┐
                 │Logout          │
                 └────────┬───────┘
                          │
                          ▼
                         END
```

---

### User Role: Veterinarian

```
                           START
                             │
                             ▼
                    ┌────────────────┐
                    │  Login as      │
                    │  Veterinarian  │
                    └────────┬───────┘
                             │
                             ▼
                    ┌────────────────┐
                    │  Authenticated?│
                    └────┬───────┬───┘
                         │ No    │ Yes
                    ┌────▼───┐   │
                    │ Show   │   │
                    │ Error  │   │
                    └────────┘   │
                                 │
                    ┌────────────▼─────────┐
                    │  Veterinarian        │
                    │  Dashboard           │
                    └──────────┬───────────┘
                               │
          ┌────────────────────┼────────────────────┐
          │                    │                    │
          ▼                    ▼                    ▼
┌─────────────────┐   ┌────────────────┐   ┌──────────────┐
│View Today's     │   │View Appointment│   │ Manage       │
│Appointments     │   │Requests        │   │ Profile      │
└─────────┬───────┘   └────────┬───────┘   └──────────────┘
          │                    │
          ▼                    ▼
┌─────────────────┐   ┌────────────────┐
│Select Appoint-  │   │Accept/Decline  │
│ment             │   │Request         │
└─────────┬───────┘   └────────────────┘
          │
          ▼
┌─────────────────┐
│View Pet Profile │
│& History        │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│Conduct          │
│Consultation     │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│Create/Update    │
│Medical Record   │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│Add Diagnosis,   │
│Treatment,       │
│Prescription     │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│Use AI-Assistant?│
└────┬────────┬───┘
     │ Yes    │ No
     │        │
     ▼        │
┌─────────────┐  │
│Generate AI  │  │
│Summary of   │  │
│Record       │  │
└─────┬───────┘  │
      │          │
      ▼          │
┌─────────────┐  │
│Review &     │  │
│Verify AI    │  │
│Summary      │  │
└─────┬───────┘  │
      │          │
      └────┬─────┘
           │
           ▼
  ┌────────────────┐
  │Save Medical    │
  │Record          │
  └────────┬───────┘
           │
           ▼
  ┌────────────────┐
  │Mark Appointment│
  │Complete        │
  └────────┬───────┘
           │
           ▼
  ┌────────────────┐
  │Logout          │
  └────────┬───────┘
           │
           ▼
          END
```

---

### User Role: Groomer

```
                           START
                             │
                             ▼
                    ┌────────────────┐
                    │  Login as      │
                    │  Groomer       │
                    └────────┬───────┘
                             │
                             ▼
                    ┌────────────────┐
                    │  Authenticated?│
                    └────┬───────┬───┘
                         │ No    │ Yes
                    ┌────▼───┐   │
                    │ Show   │   │
                    │ Error  │   │
                    └────────┘   │
                                 │
                    ┌────────────▼─────────┐
                    │  Groomer             │
                    │  Dashboard           │
                    └──────────┬───────────┘
                               │
          ┌────────────────────┼────────────────────┐
          │                    │                    │
          ▼                    ▼                    ▼
┌─────────────────┐   ┌────────────────┐   ┌──────────────┐
│View Upcoming    │   │Manage Services │   │ View Profile │
│Appointments     │   │Offered         │   │              │
└─────────┬───────┘   └────────┬───────┘   └──────────────┘
          │                    │
          ▼                    ▼
┌─────────────────┐   ┌────────────────┐
│Select Grooming  │   │Add/Edit/Remove │
│Appointment      │   │Service Details │
└─────────┬───────┘   └────────────────┘
          │
          ▼
┌─────────────────┐
│View Pet Profile │
│for Appointment  │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│Perform Grooming │
│Service          │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│Update Appoint-  │
│ment Status      │
└────┬───────┬────┘
     │       │
┌────▼───┐ ┌▼────────┐
│In      │ │Completed│
│Progress│ └─────────┘
└────────┘
     │
     └──────┬────────┘
            │
            ▼
   ┌────────────────┐
   │Add Notes/      │
   │Comments        │
   └────────┬───────┘
            │
            ▼
   ┌────────────────┐
   │Logout          │
   └────────┬───────┘
            │
            ▼
           END
```

---

### User Role: Clinic Vet Administrator

```
                           START
                             │
                             ▼
                    ┌────────────────┐
                    │  Login as      │
                    │Clinic Vet Admin│
                    └────────┬───────┘
                             │
                             ▼
                    ┌────────────────┐
                    │  Authenticated?│
                    └────┬───────┬───┘
                         │ No    │ Yes
                    ┌────▼───┐   │
                    │ Show   │   │
                    │ Error  │   │
                    └────────┘   │
                                 │
                    ┌────────────▼─────────┐
                    │  Clinic Vet Admin    │
                    │  Dashboard           │
                    └──────────┬───────────┘
                               │
          ┌────────────────────┼──────────────────────┐
          │                    │                      │
          ▼                    ▼                      ▼
┌─────────────────┐   ┌────────────────┐   ┌────────────────┐
│Manage Clinic    │   │Manage Staff    │   │View Appoint-   │
│Information      │   │Accounts        │   │ments & Reports │
└─────────┬───────┘   └────────┬───────┘   └────────────────┘
          │                    │
          ▼                    │
┌─────────────────┐            │
│Update Clinic    │            │
│Profile          │            │
└─────────┬───────┘            │
          │                    │
          ▼                    │
┌─────────────────┐            │
│Edit Address,    │            │
│Contact, Hours   │            │
└─────────┬───────┘            │
          │                    │
          ▼                    ▼
┌─────────────────┐   ┌────────────────┐
│Upload Clinic    │   │Create New      │
│Photos           │   │Staff Account?  │
└─────────┬───────┘   └────┬───────┬───┘
          │                │ Vet   │ Groomer
          │                │       │
          │           ┌────▼───┐ ┌▼────────┐
          │           │Create  │ │Create   │
          │           │Vet Acc │ │Groomer  │
          │           └────┬───┘ │Account  │
          │                │     └┬────────┘
          │                │      │
          ▼                ▼      ▼
┌─────────────────┐   ┌────────────────┐
│Manage Services  │   │Assign Role &   │
│Offered          │   │Permissions     │
└─────────┬───────┘   └────────┬───────┘
          │                    │
          ▼                    ▼
┌─────────────────┐   ┌────────────────┐
│Add/Edit/Remove  │   │Send Account    │
│Service Details  │   │Credentials     │
└─────────┬───────┘   └────────────────┘
          │
          └──────────┬─────────────────┘
                     │
                     ▼
            ┌────────────────┐
            │View Analytics  │
            │& Statistics    │
            └────────┬───────┘
                     │
                     ▼
            ┌────────────────┐
            │Logout          │
            └────────┬───────┘
                     │
                     ▼
                    END
```

---

### User Role: Super Admin

```
                           START
                             │
                             ▼
                    ┌────────────────┐
                    │  Login as      │
                    │  Super Admin   │
                    └────────┬───────┘
                             │
                             ▼
                    ┌────────────────┐
                    │  Authenticated?│
                    └────┬───────┬───┘
                         │ No    │ Yes
                    ┌────▼───┐   │
                    │ Show   │   │
                    │ Error  │   │
                    └────────┘   │
                                 │
                    ┌────────────▼─────────┐
                    │  Super Admin         │
                    │  Dashboard           │
                    └──────────┬───────────┘
                               │
          ┌────────────────────┼────────────────────┐
          │                    │                    │
          ▼                    ▼                    ▼
┌─────────────────┐   ┌────────────────┐   ┌──────────────┐
│View All Clinics │   │View Pending    │   │System        │
│in Pangasinan    │   │Registrations   │   │Settings      │
└─────────────────┘   └────────┬───────┘   └──────────────┘
                               │
                               ▼
                      ┌────────────────┐
                      │Select Clinic   │
                      │Registration    │
                      └────────┬───────┘
                               │
                               ▼
                      ┌────────────────┐
                      │Review Clinic   │
                      │Documentation   │
                      └────────┬───────┘
                               │
                               ▼
                      ┌────────────────┐
                      │Verify:         │
                      │- License       │
                      │- Location      │
                      │- Contact Info  │
                      └────┬───────┬───┘
                           │       │
                      ┌────▼───┐ ┌▼────────┐
                      │Approve │ │Reject   │
                      │Clinic  │ │Clinic   │
                      └────┬───┘ └┬────────┘
                           │      │
                           │      ▼
                           │ ┌────────────┐
                           │ │Send        │
                           │ │Rejection   │
                           │ │Notice      │
                           │ └────────────┘
                           │
                           ▼
                  ┌────────────────┐
                  │Add Clinic to   │
                  │System          │
                  └────────┬───────┘
                           │
                           ▼
                  ┌────────────────┐
                  │Assign Clinic   │
                  │Vet Admin Acct  │
                  └────────┬───────┘
                           │
                           ▼
                  ┌────────────────┐
                  │Send Credentials│
                  │to Clinic       │
                  └────────┬───────┘
                           │
                           ▼
                  ┌────────────────┐
                  │Clinic Now      │
                  │Visible to Users│
                  └────────┬───────┘
                           │
                           ▼
                  ┌────────────────┐
                  │Logout          │
                  └────────┬───────┘
                           │
                           ▼
                          END
```

---

## Document Prepared By:

**Team Members:**
- ALBA, LAWRENCE M.
- BERNABE, JOHN PREX B.
- ESCOSIO, JERO D.
- JOVEN, FRANCIS RAFAEL O.
- PASTORAL, MARK JOSHUA M.

**Program:** Bachelor of Science in Information Technology  
**Institution:** Universidad de Dagupan - School of Information Technology Education  
**Project:** PetCare Plus Veterinary Clinic Hub  
**Expected Completion:** December 2026

---

**Note to Panel:** This alpha testing document is prepared based on the Rapid Application Development (RAD) methodology employed in this capstone project. The system follows a three-tier architecture and integrates external APIs (Groq for AI features, Google Maps for location services, and PHPMailer for notifications). All features listed comply with Republic Act No. 10173 (Data Privacy Act of 2012), Republic Act No. 8485 as amended by RA 10631 (Animal Welfare Act), and Republic Act No. 8792 (Electronic Commerce Act).
