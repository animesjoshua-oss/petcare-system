<?php
/**
 * PetCare+ — Premium OTP Verification UI
 *
 * Required variables before including:
 *   $otpFormAction  (string) — POST action URL (empty = current page)
 *   $otpHiddenName  (string) — hidden input name to identify the step
 *   $otpResendName  (string) — hidden input name for resend
 *   $otpBackUrl     (string) — URL for the "Back" link
 *   $otpEmail       (string) — email address shown to user (optional)
 *   $otpTitle       (string) — heading text
 *   $otpIcon        (string) — FA icon class
 *   $otpSubmitLabel (string) — submit button label
 *
 * NOTE: Backend uses 6-digit OTP. UI shows 6 boxes.
 * All PHP session, mailer, and DB logic is untouched.
 */
$otpFormAction  = $otpFormAction  ?? '';
$otpHiddenName  = $otpHiddenName  ?? 'verify_otp';
$otpResendName  = $otpResendName  ?? 'resend_otp';
$otpBackUrl     = $otpBackUrl     ?? '';
$otpEmail       = $otpEmail       ?? '';
$otpTitle       = $otpTitle       ?? 'Verify Your Email';
$otpIcon        = $otpIcon        ?? 'fa-envelope-open-text';
$otpSubmitLabel = $otpSubmitLabel ?? 'Verify & Continue';
$otpDigits      = 6; // must stay 6 to match backend generateOtp() / verifyOtp()
?>

<!-- ============================================================
     OTP VERIFICATION — Premium Animated Component
     ============================================================ -->
<div class="otp-wrap" id="otpWrap">

  <!-- Header -->
  <div class="otp-header" id="otpHeader">
    <div class="otp-icon-wrap">
      <div class="otp-icon-inner">
        <i class="fas <?= $otpIcon ?>"></i>
      </div>
      <div class="otp-icon-pulse"></div>
    </div>
    <div class="otp-tag">OTP VERIFICATION</div>
    <h2 class="otp-title"><?= htmlspecialchars($otpTitle) ?></h2>
    <p class="otp-desc">
      <?php if ($otpEmail): ?>
        Enter the 6-digit code sent to<br>
        <strong class="otp-email-highlight"><?= htmlspecialchars($otpEmail) ?></strong>
      <?php else: ?>
        Enter the 6-digit verification code<br>sent to your email address.
      <?php endif; ?>
    </p>
  </div>

  <!-- OTP Form -->
  <form method="POST" action="<?= htmlspecialchars($otpFormAction) ?>" id="otpForm" autocomplete="off">
    <?= csrfField() ?>
    <input type="hidden" name="<?= $otpHiddenName ?>" value="1">
    <input type="hidden" name="otp_full" id="otpFull">

    <!-- Digit boxes -->
    <div class="otp-boxes-outer">
      <div class="otp-boxes" id="otpBoxes">
        <?php for ($i = 0; $i < $otpDigits; $i++): ?>
        <div class="otp-box" id="otpBox<?= $i ?>">
          <input
            type="text"
            name="otp[]"
            maxlength="1"
            inputmode="numeric"
            pattern="[0-9]"
            class="otp-digit"
            autocomplete="one-time-code"
            data-index="<?= $i ?>"
            aria-label="OTP digit <?= $i + 1 ?>">
          <div class="otp-box-underline"></div>
          <div class="otp-box-glow"></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Verify button -->
    <button type="submit" class="otp-btn" id="otpSubmitBtn" disabled>
      <span class="otp-btn-icon">
        <i class="fas fa-shield-alt" id="otpBtnIcon"></i>
      </span>
      <span class="otp-btn-label" id="otpBtnLabel"><?= htmlspecialchars($otpSubmitLabel) ?></span>
      <span class="otp-btn-shimmer"></span>
    </button>
  </form>

  <!-- Resend row -->
  <div class="otp-footer-row">
    <form method="POST" action="<?= htmlspecialchars($otpFormAction) ?>" style="display:inline;">
      <?= csrfField() ?>
      <input type="hidden" name="<?= $otpResendName ?>" value="1">
      <span class="otp-resend-text">Didn't receive it?</span>
      <button type="submit" class="otp-resend-btn" id="otpResendBtn" disabled>Resend OTP</button>
    </form>
  </div>

  <!-- Timer -->
  <div class="otp-timer-wrap">
    <i class="fas fa-clock otp-timer-icon"></i>
    <span>Code expires in</span>
    <span class="otp-timer-val" id="otpTimer">10:00</span>
  </div>

  <?php if ($otpBackUrl): ?>
  <div class="otp-back-row">
    <a href="<?= htmlspecialchars($otpBackUrl) ?>" class="otp-back-link">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>
  <?php endif; ?>

  <!-- Success overlay -->
  <div class="otp-success-overlay" id="otpSuccessOverlay">
    <div class="otp-success-ring"></div>
    <div class="otp-success-check">
      <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle class="otp-check-circle" cx="26" cy="26" r="24" stroke="currentColor" stroke-width="2.5"/>
        <path class="otp-check-path" d="M14 26l8 8 16-16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <p class="otp-success-text">Verifying...</p>
  </div>

</div><!-- /.otp-wrap -->

<!-- ============================================================
     OTP JAVASCRIPT
     ============================================================ -->
<script>
(function () {
  'use strict';

  /* DOM refs */
  const boxes        = document.querySelectorAll('.otp-box');
  const digits       = document.querySelectorAll('.otp-digit');
  const fullInp      = document.getElementById('otpFull');
  const submitBtn    = document.getElementById('otpSubmitBtn');
  const btnIcon      = document.getElementById('otpBtnIcon');
  const btnLabel     = document.getElementById('otpBtnLabel');
  const resendBtn    = document.getElementById('otpResendBtn');
  const timerEl      = document.getElementById('otpTimer');
  const successOvl   = document.getElementById('otpSuccessOverlay');
  const TOTAL        = digits.length;

  let allFilled    = false;

  /* Box state helpers */
  function setBoxFocus(idx) {
    boxes.forEach((b, i) => {
      b.classList.toggle('otp-box--focus', i === idx);
      b.classList.toggle('otp-box--filled', digits[i].value !== '');
    });
  }

  function setBoxFilled(idx) {
    boxes[idx].classList.add('otp-box--filled');
    boxes[idx].classList.remove('otp-box--focus');
  }

  function animateDigitIn(idx) {
    const box = boxes[idx];
    box.classList.remove('otp-digit-pop');
    void box.offsetWidth; // reflow to restart animation
    box.classList.add('otp-digit-pop');
  }

  /* Sync hidden full input */
  function syncFull() {
    const val = [...digits].map(d => d.value).join('');
    if (fullInp) fullInp.value = val;
    return val;
  }

  /* Check if all boxes filled */
  function checkComplete() {
    const val = syncFull();
    const complete = val.length === TOTAL && /^\d+$/.test(val);

    if (complete && !allFilled) {
      allFilled = true;
      triggerAllFilledAnimation();
    } else if (!complete) {
      allFilled = false;
      submitBtn.disabled = true;
      submitBtn.classList.remove('otp-btn--ready');
    }
  }

  /* Wave animation when all boxes filled */
  function triggerAllFilledAnimation() {
    boxes.forEach((box, i) => {
      setTimeout(() => box.classList.add('otp-box--complete'), i * 60);
    });
    setTimeout(() => {
      submitBtn.disabled = false;
      submitBtn.classList.add('otp-btn--ready');
    }, 420);
  }

  /* Input events */
  digits.forEach((el, i) => {

    el.addEventListener('focus', () => {
      setBoxFocus(i);
    });

    el.addEventListener('blur', () => {
      boxes[i].classList.remove('otp-box--focus');
    });

    el.addEventListener('input', () => {
      el.value = el.value.replace(/\D/g, '').slice(0, 1);
      if (el.value) {
        setBoxFilled(i);
        animateDigitIn(i);
        if (i < TOTAL - 1) {
          digits[i + 1].focus();
        } else {
          el.blur();
        }
      }
      checkComplete();
    });

    el.addEventListener('keydown', e => {
      if (e.key === 'Backspace') {
        if (el.value) {
          el.value = '';
          boxes[i].classList.remove('otp-box--filled', 'otp-box--complete');
        } else if (i > 0) {
          digits[i - 1].focus();
          digits[i - 1].value = '';
          boxes[i - 1].classList.remove('otp-box--filled', 'otp-box--complete');
        }
        allFilled = false;
        submitBtn.disabled = true;
        submitBtn.classList.remove('otp-btn--ready');
        checkComplete();
      }
      if (e.key === 'ArrowLeft'  && i > 0)         { e.preventDefault(); digits[i - 1].focus(); }
      if (e.key === 'ArrowRight' && i < TOTAL - 1) { e.preventDefault(); digits[i + 1].focus(); }
    });

    el.addEventListener('paste', e => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData)
        .getData('text').replace(/\D/g, '').slice(0, TOTAL);
      [...text].forEach((ch, j) => {
        if (digits[j]) {
          digits[j].value = ch;
          setBoxFilled(j);
          animateDigitIn(j);
        }
      });
      digits[Math.min(text.length, TOTAL - 1)].focus();
      checkComplete();
    });

    el.addEventListener('click', () => el.select());
  });

  /* Form submit — show success overlay */
  document.getElementById('otpForm').addEventListener('submit', function () {
    if (!submitBtn.disabled) {
      successOvl.classList.add('otp-success-overlay--visible');
      btnIcon.className = 'fas fa-circle-notch fa-spin';
      btnLabel.textContent = 'Verifying...';
    }
  });

  /* Countdown timer */
  let secs = 600;
  const tick = setInterval(() => {
    secs--;
    if (secs <= 0) {
      clearInterval(tick);
      timerEl.textContent = 'Expired';
      timerEl.classList.add('otp-timer-expired');
      resendBtn.disabled = false;
      resendBtn.classList.add('otp-resend-btn--active');
      return;
    }
    const m = String(Math.floor(secs / 60)).padStart(2, '0');
    const s = String(secs % 60).padStart(2, '0');
    timerEl.textContent = m + ':' + s;
    if (secs <= 60)  timerEl.classList.add('otp-timer-urgent');
    if (secs <= 540) {
      resendBtn.disabled = false;
      resendBtn.classList.add('otp-resend-btn--active');
    }
  }, 1000);

  /* Initial focus */
  setTimeout(() => digits[0] && digits[0].focus(), 300);

})();
</script>
