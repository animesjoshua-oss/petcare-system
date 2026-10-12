# UNIVERSIDAD DE DAGUPAN
## SCHOOL OF INFORMATION TECHNOLOGY EDUCATION
## CAPSTONE ALPHA TESTING FEEDBACK FORM

**Project:** PetCare+ Clinic Hub  
**Alpha testing date:** ____________________  
**Venue / build version:** ____________________

> **Panel use:** Mark the project result and feature verification during the live demonstration. The alpha target is at least 50% of listed features complete and working, or the single designated main feature complete and working. Planned, partial, or unshown features do not count. This document does not pre-claim a pass.

**Panel result:** ☐ Passed  ☐ Conditional Passed  ☐ Re-alpha

## Project details

| Project details | Entry |
|---|---|
| Project title | PetCare+ Clinic Hub |
| Team members | ALBA, LAWRENCE M. — Developer<br>BERNABE, JOHN PREX B. — Developer<br>ESCOSIO, JERO D. — Developer<br>JOVEN, FRANCIS RAFAEL O. — Developer<br>PASTORAL, MARK JOSHUA M. — Developer |
| Testing date | ____________________ |

## 1. Project overview and objectives

PetCare+ Clinic Hub is a PHP and MySQL web application for finding veterinary and grooming clinics, booking appointments, and managing pet care records. It provides role-based experiences for pet owners, veterinarians, groomers, and clinic administrators. The project aims to make clinic discovery and appointment coordination more accessible, and to keep pet and care information organized.

## 2. Scope and delimitations

### Scope

- Account registration, sign-in, email verification, password recovery, and role-based dashboards.
- Clinic discovery, clinic profiles, services, and favorites.
- Appointment booking, cancellation, status updates, appointment history, and ratings.
- Pet profiles and health records.
- Veterinarian and groomer workflows, with clinic administration features.
- Optional AI-assisted chat and pet image assistance when configured with the required service credentials.
- Dark mode display option.

### Delimitations and dependencies

- This is a web application; users need a supported browser and network access to use hosted services.
- AI features depend on external service availability and configured credentials; they may not be available in every deployment.
- Email delivery depends on configured mail service credentials and connectivity.
- No online payment or teleconsultation capability is represented in the current project README.
- The current repository README is the source for these project details; verify scope with the team before the panel test.

## 3. User roles

| Role | Main actions |
|---|---|
| Pet owner | Find clinics, manage pets, book and track appointments, favorite clinics, and submit ratings. |
| Veterinarian | Review assigned appointments and pet health information; manage medical records and related clinic workflows. |
| Groomer | Review grooming appointments and manage groomer profile and appointment workflows. |
| Clinic administrator | Manage clinic operations, staff, services, appointments, and clinic information according to granted access. |
| Super administrator | Manage platform-level administration and clinic records according to granted access. |

## 4. Feature checklist

Mark team status before the demonstration. The panel should mark verification only after seeing the feature work in the live build. “Partial” and “Planned” do not count toward the alpha threshold.

| No. | Main? | Feature and expected outcome | User role | Status (Complete / Partial / Planned) | Panel Status (Done / Not Done) |
|---:|:---:|---|---|---|---|
| 1 | **Y** | Appointment booking and management: pet owners choose a clinic, pet, service, date, and time, submit a booking, and track its status/history; clinic staff handle supported appointment status changes. | Pet owner / clinic staff | __________ | __________ |
| 2 | N | Accounts and role-based access: registration, login/logout, email OTP verification, password recovery, and access to role-specific pages. Email needs configured mail credentials. | All roles | __________ | __________ |
| 3 | N | Email verification and account approval: verify an OTP by email and have an administrator review/approve a new account. Requires mail setup. | Pet owner / administrator | __________ | __________ |
| 4 | N | Clinic search, profiles, and services: browse/search clinics, filter by city/service, view clinic details, services, contact information, and ratings. | Public / pet owner | __________ | __________ |
| 5 | N | Pet profiles and health records: owners manage pet profiles; authorized veterinarians add and view related health records. | Pet owner / veterinarian | __________ | __________ |
| 6 | N | Groomer workflow: groomers review assigned appointments and manage supported appointment actions and profile details. | Groomer | __________ | __________ |
| 7 | N | Clinic/admin management: authorized administrators maintain clinic details, services, staff accounts, user approvals, and clinic records. | Clinic administrator / super administrator | __________ | __________ |
| 8 | N | Ratings and feedback: pet owners submit eligible appointment feedback and view submitted ratings. | Pet owner / clinic staff | __________ | __________ |
| 9 | N | Favorite clinics: owners add clinics to favorites, revisit the list, and remove favorites. | Pet owner | __________ | __________ |
| 10 | N | Optional AI assistance: configured Groq-powered assistance is available in supported owner, veterinary, groomer, or admin workflows. Requires an API key. | Pet owner / clinic staff | __________ | __________ |
| 11 | N | Optional AI assistance: owner, vet, groomer, and admin AI features call Groq when an API key is configured. | Pet owner / clinic staff | __________ | __________ |

**Complete and panel-verified feature count:** ______ / 11  
**50% route met (at least 6 of 11 verified complete):** ☐ Yes  ☐ No  
**Designated main feature verified complete:** ☐ Yes  ☐ No  
**Minimum alpha target met through either route:** ☐ Yes  ☐ No

## 5. Feedback and recommendations

**What worked well:**

____________________________________________________________________________

**Issues observed (include feature, steps, and expected/actual result):**

____________________________________________________________________________

**Recommendations / required changes:**

____________________________________________________________________________

____________________________________________________________________________

**Reason for re-alpha (if applicable):** _______________________________________________

**Conditions or focus for re-alpha:** _________________________________________________

**Panelist name and signature:** ______________________________  **Date:** ______________

## 6. High-level system architecture

PetCare+ uses a web application structure: browser-based pages and JavaScript communicate with PHP application pages and API endpoints; PHP accesses a MySQL database through the project’s shared database layer. Optional integrations include email delivery and AI services when configured. Exact deployment and integration behavior should be confirmed against the build used in the demonstration.

```text
Pet owner / veterinarian / groomer / clinic administrator
                         │
                 Web browser interface
                         │ HTTPS / HTTP
              PHP pages and API endpoints
                  ┌──────┴──────┐
                  │             │
            MySQL database   Optional external services
                             (email, AI assistance)
```

## 7. High-level role workflows

### Pet owner

Open the site → register or sign in → browse clinics/services → manage pet profile → request appointment → view appointment status/history → submit rating when eligible.

### Veterinarian

Sign in → open veterinarian dashboard → review assigned appointments → access relevant pet information → create or update health records → update supported appointment status.

### Groomer

Sign in → open groomer dashboard → review grooming appointments → view necessary pet information → manage supported appointment and profile details.

### Clinic administrator

Sign in → open clinic administration tools → maintain clinic details, services, and staff → review appointments and operational information.

### Super administrator

Sign in with authorized account → open platform administration tools → manage platform-level clinic/user records and oversight workflows.

## 8. Alpha demonstration record

| Item | Notes |
|---|---|
| Build / URL tested | |
| Browser and device | |
| Test account roles used (do not record passwords) | |
| Features demonstrated | |
| External integrations available / unavailable | |
| Defects or blockers | |

## 9. Panel decision

☐ **Passed** — alpha target demonstrated.  
☐ **Conditional Passed** — target demonstrated with listed conditions.  
☐ **Re-alpha** — target not demonstrated or critical issues require another alpha review.

**Panel comments:**

____________________________________________________________________________

____________________________________________________________________________

**Panelist signature:** ______________________________  **Date:** ______________
