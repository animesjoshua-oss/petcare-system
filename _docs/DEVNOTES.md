# Developer Notes

Personal notes while building this project. Mostly for my future self when I forget why I did something.

Started: Feb 2024
Current Status: Working but needs polish

---

## Recent Work (Dec 2024)

### Form Input Styling - Dec 15, 2024
Finally fixed the input background issue after 2 days of debugging.

**Timeline:**
- Dec 13 morning: Noticed icon was invisible
- Dec 13 afternoon: Tried changing icon color - didn't help
- Dec 14 morning: Tried background: transparent - autofill overrode it
- Dec 14 afternoon: Googled for 30+ min, found webkit-box-shadow hack
- Dec 15 morning: Implemented and tested across browsers

**What I tried (in order):**
1. ❌ Icon color to black → still too light against white bg
2. ❌ Icon color to dark gray → same problem
3. ❌ Background transparent → autofill forced light blue
4. ❌ Background with !important → still overridden by browser
5. ❌ Tried outline instead of background → looked weird
6. ✅ webkit-box-shadow 1000px inset trick → FINALLY WORKS!

**Final solution:**
```css
.form-control {
  background: #252838;
  color: #ffffff;
}
.form-control:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 1000px #252838 inset !important;
  -webkit-text-fill-color: #ffffff !important;
}
```

Found the webkit-box-shadow hack on Stack Overflow after searching:
- "chrome autofill background override"
- "prevent autofill light blue"
- "force input background color chrome"

**Changed files:**
- assets/css/main.css (lines ~960-1005)

**Browser testing:**
- ✅ Chrome 120 - works perfectly
- ✅ Firefox 121 - works (doesn't need webkit prefix)
- ✅ Safari 17 - works with -webkit prefix
- ✅ Edge 120 - works (chromium based)
- ⚠️ IE11 - haven't tested (who cares about IE anyway)

### Mobile Menu z-index Fix - Dec 10
Dropdown was appearing behind modal overlays. Changed z-index from 1000 to 99999.
Probably overkill but modals use z-index: 3000 so needed something higher.

### Dark Mode Color Adjustments - Nov 28-30
Spent 3 days tweaking dark mode colors. History:

**Attempt 1 (Nov 28):** 
- Background: #000000 - TOO DARK, eye strain
- Text: #ffffff - Too harsh contrast

**Attempt 2 (Nov 29 morning):**
- Background: #1a1a1a - Still too dark
- Inputs: #2a2a2a - Too similar to background

**Attempt 3 (Nov 29 afternoon):**
- Background: #f0f0f0 - Wait this is light mode again, wtf

**Attempt 4 (Nov 30 morning):**
- Background: #0a0e1a - Too blue-ish
- Cards looked weird

**Attempt 5 (Nov 30 evening) - FINAL:**
- Background: #111318 ✅
- Cards: #1C1F2A ✅
- Inputs: #252838 ✅
- Text: #ffffff with some #94a3b8 for muted ✅

Finally looks good. Showed to 3 friends, they said it's nice.

---

## Things That Took Forever (Debugging Hell)

### 1. Autofill Styling Bug - Nov 2024 (3 hours wasted)
**Problem:** Browser autofill forcing light blue background on inputs.

**Debug process:**
```
10:00 AM - Noticed the issue
10:15 AM - Tried background: none !important; // didn't work
10:30 AM - Tried background-color: transparent !important; // nope
10:45 AM - Coffee break, frustrated
11:00 AM - Googled "how to override chrome autofill"
11:15 AM - Found some jQuery solutions (too hacky)
11:30 AM - Tried filter: none; // lol what was I thinking
11:45 AM - More googling
12:00 PM - Lunch break
01:00 PM - Found webkit-box-shadow trick on Stack Overflow
01:15 PM - Tested, IT WORKS!!!
```

**Solution:**
```css
.form-control:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 1000px #252838 inset !important;
  -webkit-text-fill-color: #ffffff !important;
}
```
This is a hack but it works. The 1000px inset shadow covers the autofill background.
Credit: Stack Overflow user "JohnDoe123" or something, forgot to save the link.

### 2. Session Timeout Issues - Oct 2024 (2 days)
Users kept getting logged out randomly after 20-30 minutes.

**Debug log:**
- Checked session.gc_maxlifetime → was 1440 (24 min)
- Checked session.cookie_lifetime → was 0 (until browser close)
- Thought it was server issue
- Contacted hosting support (waste of time)
- Realized I need to change php.ini settings
- Changed gc_maxlifetime to 86400 (24 hours)
- Still had issues sometimes
- Added session_regenerate_id() on each page load
- Added last_activity check in session
- Finally stable now

**Files changed:**
- includes/config.php
- includes/functions.php (added session_check())

### 3. Email OTP Not Sending - Nov 2024 (4 HOURS!)
This was the most frustrating bug ever.

**What happened:**
- Set up PHPMailer
- Configured with Gmail SMTP
- Tested → "SMTP Error: Could not authenticate"
- WTF, credentials are correct!

**Tried everything:**
1. Double-checked username/password (correct)
2. Enabled "Less secure app access" in Gmail
3. Still not working
4. Googled for an hour
5. Found out Google disabled "less secure apps" in 2022 (great)
6. Need to use "App Password" instead
7. Generated App Password from Google account
8. Updated config.php with new password
9. **FINALLY WORKS!**

Wasted 4 hours because Gmail's documentation is terrible.

### 4. Dark Mode Transitions Looking Janky - Sep 2024
When switching themes, everything animated at once - looked terrible.
Text, backgrounds, borders all transitioning = seizure warning.

**Solution:**
Added a `.theme-transitioning` class that applies for 350ms:
```css
html.theme-transitioning * {
  transition: background-color .3s ease,
              border-color .3s ease,
              color .3s ease !important;
}
```

Then remove the class after transition completes.
Much smoother now.

### 5. Mobile Menu Breaking on iOS Safari - Aug 2024
The backdrop-filter blur wasn't working on iPhone.

**Debug:**
- Works on Android Chrome ✅
- Works on Desktop Safari ✅  
- Broken on iOS Safari ❌

**Fix:** Need both prefixes:
```css
backdrop-filter: blur(10px);
-webkit-backdrop-filter: blur(10px);
```

Safari is the new IE. Change my mind.

### 6. Image Upload Failing Silently - Aug 2024 (frustrating)
Users uploading pet photos → nothing happens, no error.

**Investigation:**
- Checked JavaScript → upload function works
- Checked PHP → receiving $_FILES
- Checked move_uploaded_file() → returning false
- No error logs → enabled error_reporting(E_ALL)
- Found the issues:
  1. upload_max_filesize = 2M (too small for photos)
  2. post_max_size = 2M (also too small)
  3. Directory /uploads/ permission = 744 (needs 755)
  4. Directory didn't exist yet (forgot to create it)

**Fixed:**
- Changed php.ini: upload_max_filesize = 10M
- Changed php.ini: post_max_size = 10M
- mkdir uploads/pets with 755 permissions
- Added error messages to upload.php

Now works perfectly. Should've checked permissions first.

### 7. Database "MySQL server has gone away" - Jul 2024
Random database connection drops during long operations.

**Root cause:** MySQL wait_timeout too short (default 28800 = 8 hours)

**Attempted fixes:**
1. Increased wait_timeout to 86400 (24 hours) - helped but not perfect
2. Increased max_allowed_packet to 64M - helped a bit
3. Added connection check in db.php:
```php
if (!$pdo->ping()) {
    $pdo = new PDO(...); // reconnect
}
```
Wait, PDO doesn't have ping(). Used try-catch instead:
```php
try {
    $pdo->query('SELECT 1');
} catch (PDOException $e) {
    $pdo = new PDO(...); // reconnect
}
```

Still happens occasionally but less frequent. Maybe need connection pooling?

---

## Code Quality Notes

### What's Good
- Responsive design works on most devices
- Dark mode implementation is solid
- Form validation handles most edge cases
- Database structure is normalized properly

### What Could Be Better
- Some duplicate code in dashboard pages (should create more reusable components)
- CSS file is getting huge (3000+ lines) - should split into modules
- JavaScript could use some refactoring - too many global functions
- Need to add more error handling in API endpoints
- Should implement proper rate limiting for forms

---

## Known Bugs / Issues (Active List)

**CRITICAL:**
1. ~~Password icon not visible on dark background~~ **FIXED Dec 15, 2024**
   - Solution: Changed icon color to #94a3b8, added hover effect

**HIGH PRIORITY:**
2. Notifications not updating in real-time (polling every 30s, should be 10s?)
   - Might switch to WebSocket later but polling works for now
   - **Update Dec 10:** Tried reducing to 10s, server couldn't handle it
   - Keeping at 30s for now

3. File upload progress bar doesn't show on slow connections
   - Need to implement chunked upload
   - Or at least show spinner while uploading
   - Added to TODO list

**MEDIUM:**
4. Date picker styling breaks on some Android browsers (Samsung Internet)
   - Works fine on Chrome/Firefox mobile
   - Samsung browser has weird CSS issues
   - Might need custom date picker library

5. Long clinic names overflow on mobile cards
   - Need to add: text-overflow: ellipsis; white-space: nowrap;
   - Also limit title to 2 lines with line-clamp
   - Quick fix but looks ugly, need better solution

**LOW PRIORITY:**
6. Modal backdrop sometimes not covering entire screen on iOS
   - Rare issue, hard to reproduce
   - Might be related to 100vh problem on mobile Safari
   - Using 100dvh might fix but need to test

7. Password strength indicator would be nice
   - Not a bug but users keep using weak passwords
   - Should add visual feedback (red/yellow/green)
   - Maybe later

**COSMETIC:**
8. Loading spinners are inconsistent across pages
   - Some pages use CSS spinner, some use Font Awesome icon
   - Should standardize but works fine for now

9. Favicon shows default icon on some browsers
   - Need to generate all favicon sizes (16x16, 32x32, etc.)
   - Currently only have 96x96 version
   - Low priority

---

## Bugs I Fixed (Recently)

### Dec 15, 2024 - Input autofill styling
**Issue:** Browser forcing light blue background on autofilled inputs
**Fix:** webkit-box-shadow hack with 1000px inset
**Time spent:** 2 days (way too long)

### Dec 10, 2024 - Mobile menu z-index
**Issue:** Dropdown menu appearing behind modal overlays
**Fix:** Changed z-index from 1000 to 99999
**Time spent:** 5 minutes (should've checked this first)

### Dec 1, 2024 - PHP warning in dashboard
**Issue:** Undefined index 'user_role' in includes/header.php
**Fix:** Added isset() check before accessing $_SESSION['user_role']
**Time spent:** 2 minutes

### Nov 28, 2024 - Dark mode colors too harsh
**Issue:** White text on black background caused eye strain
**Fix:** Softened colors, used #252838 for inputs, #94a3b8 for muted text
**Time spent:** 3 days of tweaking (perfectionist problems)

### Nov 15, 2024 - Login redirect loop
**Issue:** After login, kept redirecting back to login page
**Fix:** session_start() was being called twice, removed duplicate
**Time spent:** 30 minutes of echo debugging

### Oct 20, 2024 - Appointment form validation
**Issue:** Could book appointments in the past
**Fix:** Added server-side date check: if ($date < date('Y-m-d'))
**Time spent:** 10 minutes
**Note:** Should've caught this in initial development

### Sep 5, 2024 - SQL injection vulnerability in search
**Issue:** Search query wasn't using prepared statements
**Fix:** Rewrote with PDO prepared statements
**Time spent:** 1 hour
**Note:** Security audit found this, glad I checked

---

## Future Improvements

### Short Term
- [ ] Add loading states for all AJAX calls
- [ ] Implement proper toast notification system
- [ ] Add form field character counters
- [ ] Fix mobile navigation z-index issues
- [ ] Add image compression before upload

### Long Term
- [ ] Migrate to PHP 8.2 and use modern syntax
- [ ] Consider using a CSS framework (or not, vanilla CSS is fine)
- [ ] Add Redis for caching
- [ ] Implement proper API rate limiting
- [ ] Add unit tests with PHPUnit
- [ ] Set up CI/CD pipeline

---

## Performance Notes

- Images are not optimized (need to add lazy loading)
- Database queries could use indexes on frequently searched columns
- No CDN for static assets yet
- Mobile performance is decent but could be better

---

## Security Checklist

- [x] SQL injection prevention (using PDO prepared statements)
- [x] XSS protection (htmlspecialchars on all outputs)
- [x] CSRF tokens on all forms
- [x] Password hashing with bcrypt
- [x] Input validation client + server side
- [ ] Rate limiting on login attempts (TODO)
- [ ] File upload MIME type validation (partially done)
- [ ] Session timeout handling (needs improvement)

---

## Deployment Notes

### Production Checklist
1. Update `APP_ENV` to 'production' in config.php
2. Disable error display: `ini_set('display_errors', 0)`
3. Enable HTTPS (Cloudflare or Let's Encrypt)
4. Set secure session cookies
5. Update API keys
6. Test email functionality
7. Backup database before going live
8. Set proper file permissions (755 for directories, 644 for files)

### Database Migrations
When deploying updates, run migrations in this order:
1. Backup current database
2. Run new migration scripts
3. Test on staging first
4. If ok, deploy to production

---

## Random Thoughts & Lessons Learned

**Dec 2024:**
- Dark mode took way longer than expected (3 days of color tweaking)
- Testing on real devices is crucial - desktop Chrome doesn't show iOS Safari bugs
- Users actually use autofill, need to account for that
- webkit prefix still matters in 2024, unfortunately

**Nov 2024:**
- The paw print background pattern was fun to create with SVG
- Should've planned mobile layout first, desktop-first was a mistake
- Email sending is harder than it should be (thanks Gmail)
- Need better error logging system

**Oct 2024:**
- The AI chatbot integration (Groq API) was surprisingly easy
- Users don't read error messages, need better visual feedback
- Loading spinners improve perceived performance even if not faster
- Animations make everything feel more polished

**Sep 2024:**
- Should've used a CSS framework? Nah, custom CSS gives more control
- But 3000+ lines in one file is getting unwieldy, need to modularize
- Git commit messages could be better, I keep writing "fix bug" 
- Documentation is boring but Future Me always appreciates it

**Aug 2024:**
- File permissions on Linux servers are annoying
- Browser testing is tedious but necessary
- Users will try to upload 50MB photos if you let them
- Always validate on both client and server side

**Jul 2024:**
- Database design is important, glad I spent time on schema planning
- Indexes make huge difference in query speed
- Need to add foreign key constraints (forgot this)
- Backup strategy is crucial, almost lost data once

**Jun 2024:**
- Bootstrap would've been faster but I wanted to learn vanilla CSS
- Flexbox is amazing once you understand it
- Grid is even better for layouts
- Naming CSS classes consistently is hard

**Earlier months:**
- Planning takes time but saves time later
- Start with MVP, add features incrementally
- User feedback is valuable (showed to 5 friends for testing)
- Perfectionism is the enemy of done

---

## Failed Attempts & Dead Ends

### Things I Tried That Didn't Work:

**1. Using jQuery for everything (June 2024)**
Initially started with jQuery, then realized I didn't need it.
Removed jQuery, rewrote in vanilla JS. File size dropped from 90KB to 15KB.

**2. Complex CSS architecture (July 2024)**
Tried to implement BEM naming convention strictly.
Gave up after 2 days because:
- Too verbose: `.clinic-card__image--large-size-variant`
- Hard to remember
- Just used simple names like `.clinic-card-img`

**3. Implementing Redis caching (August 2024)**
Spent a weekend trying to set up Redis for session management.
Hosting provider doesn't support Redis.
Gave up, using file-based sessions. Works fine.

**4. Custom JavaScript framework (September 2024)**
Started building custom "components" system for JS.
Realized I was reinventing React poorly.
Scrapped it, went back to simple DOM manipulation.

**5. Service Worker for offline mode (October 2024)**
Implemented service worker for PWA offline functionality.
Too complex for this project's needs.
Kept basic manifest.json, removed sw.js complications.

**6. Trying to make IE11 compatible (November 2024)**
Spent 3 hours debugging IE11 issues.
Realized IE11 usage is <1% globally.
Gave up. If you're using IE11, update your browser.

---

## Code Snippets That Didn't Work (Learning)

### Attempt 1: Override autofill with !important
```css
/* Didn't work - browser still overrides */
.form-control {
  background: #252838 !important;
  color: white !important;
}
```

### Attempt 2: Remove autofill completely
```css
/* Disabled autofill but bad UX */
input {
  autocomplete: off;
}
```
Users hated this, reverted.

### Attempt 3: JavaScript solution for autofill
```javascript
// Too hacky, didn't work on all browsers
document.querySelectorAll('input').forEach(input => {
  input.addEventListener('animationstart', (e) => {
    if (e.animationName === 'autofill') {
      input.style.background = '#252838';
    }
  });
});
```

### Attempt 4: Transparent input with background container
```html
<!-- Overcomplicated solution -->
<div class="input-wrapper" style="background: #252838;">
  <input style="background: transparent;">
</div>
```
Works but adds unnecessary DOM elements.

### Final Working Solution:
```css
/* This actually works! */
.form-control:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 1000px #252838 inset !important;
  -webkit-text-fill-color: #ffffff !important;
}
```

---

## Development Environment Quirks

**XAMPP on Windows:**
- Sometimes Apache won't start (port 80 conflict with Skype)
- Solution: Changed Apache port to 8080 or close Skype
- MySQL forgets to start sometimes, need manual start

**VS Code Setup:**
- PHP Intelephense extension is essential
- Prettier for auto-formatting (but breaks PHP sometimes)
- ESLint for JavaScript
- Live Server extension for quick testing

**Testing Workflow:**
1. Make changes in VS Code
2. Save (Ctrl+S)
3. Alt+Tab to browser
4. Ctrl+Shift+R (hard refresh)
5. Check console for errors
6. Repeat 100 times per day

**Debugging Tools:**
- var_dump() and die() for PHP (old school but works)
- console.log() for JavaScript (still the best)
- Chrome DevTools Network tab for AJAX
- Postman for API testing (occasionally)

---

## Resources Used

- Stack Overflow (of course)
- CSS-Tricks for flexbox/grid
- MDN Web Docs for JavaScript
- PHP.net documentation
- Figma for initial mockups
- ChatGPT for debugging help sometimes

---

**Last Updated:** December 2024  
**Time Invested:** Too many hours to count  
**Coffee Consumed:** Yes
