/**
 * PetCare AI Widget
 * Floating chat widget for AI-powered pet care assistance
 * Supports text and image input
 * Last updated: December 2024
 */

(function () {
  'use strict';

  // ============================================================
  // DOM ELEMENTS
  // ============================================================
  const widget = document.getElementById('pcaiWidget');
  const toggle = document.getElementById('pcaiToggle');
  const closeBtn = document.getElementById('pcaiClose');
  const clearBtn = document.getElementById('pcaiClear');
  const input = document.getElementById('pcaiInput');
  const sendBtn = document.getElementById('pcaiSend');
  const messages = document.getElementById('pcaiMessages');
  const chips = document.getElementById('pcaiChips');
  const badge = document.getElementById('pcaiBadge');
  const imgInput = document.getElementById('pcaiImageInput');
  const imgBar = document.getElementById('pcaiImgPreviewBar');
  const imgThumb = document.getElementById('pcaiImgPreview');
  const imgName = document.getElementById('pcaiImgName');
  const imgRemove = document.getElementById('pcaiImgRemove');
  const csrf = widget ? widget.dataset.csrf || '' : '';
  const apiUrl = widget ? widget.dataset.apiUrl || '/api/chat.php' : '/api/chat.php';

  // Exit if widget is not in DOM
  if (!widget || !toggle) return;

  // ============================================================
  // STATE VARIABLES
  // ============================================================
  let unread = 0;
  let isOpen = false;
  let selectedFile = null;
  let wasDragged = false;
  let isSending = false;

  // ============================================================
  // WIDGET CONTROLS
  // ============================================================
  /**
   * Open the chat widget
   */
  function openWidget() {
    isOpen = true;
    widget.classList.add('open');
    widget.setAttribute('aria-hidden', 'false');
    toggle.style.display = 'none';
    unread = 0;
    badge.style.display = 'none';
    setTimeout(() => input.focus(), 300);
    scrollBottom();
  }

  /**
   * Close the chat widget
   */
  function closeWidget() {
    isOpen = false;
    widget.classList.remove('open');
    widget.setAttribute('aria-hidden', 'true');
    toggle.style.display = '';
  }

  /**
   * Scroll messages to bottom
   */
  function scrollBottom() {
    messages.scrollTop = messages.scrollHeight;
  }

  /**
   * Get current time formatted
   * @returns {string}
   */
  function timeNow() {
    return new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  }

  function addBotMsg(text) {
    if (chips) chips.style.display = 'none';
    const msg = document.createElement('div');
    msg.className = 'pcai-msg pcai-msg-bot';
    msg.innerHTML = `
      <div class="pcai-msg-avatar"><i class="fas fa-paw"></i></div>
      <div class="pcai-msg-body">
        <div class="pcai-msg-name">PetCareAI</div>
        <div class="pcai-bubble bot">${formatMsg(text)}</div>
        <div class="pcai-msg-time">${timeNow()}</div>
      </div>`;
    messages.appendChild(msg);
    scrollBottom();
    if (!isOpen) {
      unread++;
      badge.textContent = unread > 9 ? '9+' : unread;
      badge.style.display = 'flex';
    }
  }

  function addUserMsg(text) {
    const msg = document.createElement('div');
    msg.className = 'pcai-msg pcai-msg-user';
    msg.innerHTML = `
      <div class="pcai-msg-avatar"><i class="fas fa-user"></i></div>
      <div class="pcai-msg-body">
        <div class="pcai-msg-name" style="text-align:right;">You</div>
        <div class="pcai-bubble user">${escHtml(text)}</div>
        <div class="pcai-msg-time">${timeNow()}</div>
      </div>`;
    messages.appendChild(msg);
    scrollBottom();
  }

  function addTyping() {
    const el = document.createElement('div');
    el.className = 'pcai-msg pcai-msg-bot'; el.id = 'pcaiTyping';
    el.innerHTML = `
      <div class="pcai-msg-avatar"><i class="fas fa-paw"></i></div>
      <div class="pcai-msg-body">
        <div class="pcai-bubble bot" style="padding:.5rem .9rem;">
          <span class="pcai-typing-dot"></span>
          <span class="pcai-typing-dot"></span>
          <span class="pcai-typing-dot"></span>
        </div>
      </div>`;
    messages.appendChild(el);
    scrollBottom();
  }

  function removeTyping() { document.getElementById('pcaiTyping')?.remove(); }

  function escHtml(t) {
    return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function formatMsg(text) {
    text = escHtml(text);
    const lines = text.split('\n');
    let html = '', inList = false, lt = null;
    lines.forEach(line => {
      const t      = line.trim();
      const bullet = t.match(/^[\*\-] (.+)$/);
      const num    = t.match(/^\d+\. (.+)$/);
      if (bullet || num) {
        if (!inList) { html += `<${num?'ol':'ul'} style="margin:.3rem 0 .3rem 1rem;">`; inList=true; lt=num?'ol':'ul'; }
        html += `<li>${(bullet?bullet[1]:num[1]).replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')}</li>`;
      } else {
        if (inList) { html += `</${lt}>`; inList=false; }
        if (!t) html += '<br>';
        else html += `<span style="display:block;">${t.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')}</span>`;
      }
    });
    if (inList) html += `</${lt}>`;
    return html;
  }

  async function sendMessage(text) {
    if (isSending || (!text.trim() && !selectedFile)) return;
    isSending = true;
    input.value = '';
    sendBtn.disabled = true;

    if (selectedFile) {
      const imgUrl = URL.createObjectURL(selectedFile);
      const msg = document.createElement('div');
      msg.className = 'pcai-msg pcai-msg-user';
      msg.innerHTML = `
        <div class="pcai-msg-avatar"><i class="fas fa-user"></i></div>
        <div class="pcai-msg-body">
          <div class="pcai-msg-name" style="text-align:right;">You</div>
          <img src="${imgUrl}" class="pcai-img-msg" alt="pet photo">
          ${text ? `<div class="pcai-bubble user" style="margin-top:.25rem;">${escHtml(text)}</div>` : ''}
          <div class="pcai-msg-time">${timeNow()}</div>
        </div>`;
      messages.appendChild(msg);
    } else {
      addUserMsg(text);
    }

    addTyping();
    if (chips) chips.style.display = 'none';

    try {
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      if (text) fd.append('message', text);
      if (selectedFile) {
        const resized = await resizeImage(selectedFile, 800, 0.80);
        fd.append('image', resized, 'pet.jpg');
      }
      const res = await fetch(apiUrl, { method: 'POST', body: fd });
      const data = await res.json();
      addBotMsg(data.text || 'Sorry, I had trouble with that. Please try again.');
    } catch (e) {
      addBotMsg('Chat service is temporarily unavailable. Please try again.');
    } finally {
      removeTyping();
      clearImage();
      isSending = false;
      sendBtn.disabled = false;
      input.focus();
      scrollBottom();
    }
  }
  function resizeImage(file, maxSize, quality) {
    return new Promise(resolve => {
      const img = new Image();
      const url = URL.createObjectURL(file);
      img.onload = () => {
        URL.revokeObjectURL(url);
        let w = img.width, h = img.height;
        if (w > maxSize || h > maxSize) {
          const ratio = Math.min(maxSize / w, maxSize / h);
          w = Math.round(w * ratio);
          h = Math.round(h * ratio);
        }
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        canvas.toBlob(blob => resolve(blob || file), 'image/jpeg', quality);
      };
      img.onerror = () => resolve(file);
      img.src = url;
    });
  }

  function clearImage() {
    selectedFile = null;
    if (imgInput) imgInput.value = '';
    if (imgBar)   imgBar.style.display = 'none';
    if (imgThumb) imgThumb.src = '';
  }

  // Image input handler
  if (imgInput) {
    const imgBtn = document.getElementById('pcaiImgBtn');
    if (imgBtn) imgBtn.addEventListener('click', () => imgInput.click());
    imgInput.addEventListener('change', () => {
      const file = imgInput.files[0];
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        addBotMsg('That image is too large (max 5MB). Please try a smaller photo. 📸');
        imgInput.value = '';
        return;
      }
      selectedFile = file;
      const reader = new FileReader();
      reader.onload = e => {
        if (imgThumb) imgThumb.src = e.target.result;
        if (imgName)  imgName.textContent = file.name.length > 22 ? file.name.substring(0,20)+'…' : file.name;
        if (imgBar)   imgBar.style.display = 'flex';
        if (!isOpen) openWidget();
      };
      reader.readAsDataURL(file);
    });
  }

  if (imgRemove) imgRemove.addEventListener('click', clearImage);

  toggle.addEventListener('click', (e) => {
    if (wasDragged) {
      e.preventDefault();
      e.stopPropagation();
      return;
    }
    isOpen ? closeWidget() : openWidget();
  });
  closeBtn.addEventListener('click', closeWidget);

  clearBtn.addEventListener('click', () => {
    messages.querySelectorAll('.pcai-msg, .pcai-date-sep').forEach(el => el.remove());
    const welcome = document.createElement('div');
    welcome.className = 'pcai-msg pcai-msg-bot';
    welcome.innerHTML = '<div class="pcai-msg-avatar"><i class="fas fa-paw"></i></div><div class="pcai-msg-body"><div class="pcai-msg-name">PetCareAI</div><div class="pcai-bubble bot">Hi! Need help finding a groomer or have a pet care question?</div><div class="pcai-msg-time">' + timeNow() + '</div></div>';
    messages.appendChild(welcome);
    if (chips) chips.style.display = 'flex';
    clearImage();
  });

  sendBtn.addEventListener('click', () => sendMessage(input.value.trim()));
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); sendMessage(input.value.trim()); }
  });

  document.querySelectorAll('.pcai-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      if (!isOpen) openWidget();
      sendMessage(chip.dataset.msg);
    });
  });

  document.addEventListener('click', e => {
    if (isOpen && !widget.contains(e.target) && !toggle.contains(e.target)) closeWidget();
  });

  document.addEventListener('keydown', e => {
  });
  // ============================================================
  // DRAG TO MOVE � toggle button
  // ============================================================
  (function initDrag() {
    const btn      = toggle;
    const MARGIN   = 12;   // min distance from screen edge (px)
    const BTN_SIZE = 54;   // button width/height (px)
    const STORAGE_KEY = 'pcai_btn_pos';

    let dragging   = false;
    let startX, startY, startLeft, startTop;
    let didMove    = false;  // distinguish click vs drag

    // Restore saved position
    try {
      const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
      if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') {
        const validLeft = saved.left >= 0 && saved.left <= window.innerWidth  - BTN_SIZE;
        const validTop  = saved.top  >= 0 && saved.top  <= window.innerHeight - BTN_SIZE;
        if (validLeft && validTop) {
          applyPos(saved.left, saved.top);
        } else {
          localStorage.removeItem(STORAGE_KEY);
        }
      }
    } catch (e) {
      localStorage.removeItem(STORAGE_KEY);
    }

    btn.addEventListener('mousedown', onDragStart);
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragEnd);

    btn.addEventListener('touchstart', onDragStart, { passive: false });
    document.addEventListener('touchmove', onDragMove, { passive: false });
    document.addEventListener('touchend', onDragEnd);

    function getXY(e) {
      if (e.touches) return { x: e.touches[0].clientX, y: e.touches[0].clientY };
      return { x: e.clientX, y: e.clientY };
    }

    function onDragStart(e) {
      const { x, y } = getXY(e);
      const rect = btn.getBoundingClientRect();
      dragging  = true;
      didMove   = false;
      startX    = x;
      startY    = y;
      startLeft = rect.left;
      startTop  = rect.top;
      btn.classList.add('dragging');
    }

    function onDragMove(e) {
      if (!dragging) return;
      const { x, y } = getXY(e);
      const dx = x - startX;
      const dy = y - startY;

      if (!didMove && Math.abs(dx) < 5 && Math.abs(dy) < 5) return;
      didMove = true;

      if (e.cancelable) e.preventDefault(); 

      const newLeft = clamp(startLeft + dx, MARGIN, window.innerWidth  - BTN_SIZE - MARGIN);
      const newTop  = clamp(startTop  + dy, MARGIN, window.innerHeight - BTN_SIZE - MARGIN);

      applyPos(newLeft, newTop);
      repositionPanel(newLeft, newTop);
    }

    function onDragEnd() {
      if (!dragging) return;
      dragging = false;
      btn.classList.remove('dragging');

      if (didMove) {
        wasDragged = true;
        setTimeout(() => wasDragged = false, 50);

        const rect   = btn.getBoundingClientRect();
        const midX   = window.innerWidth / 2;
        
        let leftMargin = MARGIN;
        if (window.innerWidth > 768) {
          leftMargin = 260 + MARGIN;
        }

        const snapX  = rect.left < midX
          ? leftMargin
          : window.innerWidth - BTN_SIZE - MARGIN;
        const snapY  = clamp(rect.top, MARGIN, window.innerHeight - BTN_SIZE - MARGIN);

        btn.style.transition = 'left .25s cubic-bezier(.34,1.56,.64,1), top .25s cubic-bezier(.34,1.56,.64,1)';
        applyPos(snapX, snapY);
        repositionPanel(snapX, snapY);

        try { localStorage.setItem(STORAGE_KEY, JSON.stringify({ left: snapX, top: snapY })); } catch(e){}

        setTimeout(() => { btn.style.transition = ''; }, 300);
      }
    }

    function applyPos(left, top) {
      btn.style.position = 'fixed';
      btn.style.left   = left + 'px';
      btn.style.top    = top  + 'px';
      btn.style.bottom = 'auto';
      btn.style.right  = 'auto';
    }

    function repositionPanel(btnLeft, btnTop) {
      if (!widget) return;
      const panelW  = widget.offsetWidth  || 320;
      const panelH  = widget.offsetHeight || 450;
      const gap     = 12;
      const winW    = window.innerWidth;
      const winH    = window.innerHeight;

      let pLeft = btnLeft + BTN_SIZE / 2 - panelW / 2;
      let pTop  = btnTop - panelH - gap;

      let leftMargin = MARGIN;
      if (window.innerWidth > 768) {
        leftMargin = 260 + MARGIN;
      }
      pLeft = clamp(pLeft, leftMargin, winW - panelW - MARGIN);
      
      // Keep within vertical bounds, prioritize fitting on screen
      pTop = btnTop - panelH - gap;
      if (pTop < MARGIN) {
        // Try placing below if there's more room
        if (winH - (btnTop + BTN_SIZE + gap) > panelH) {
           pTop = btnTop + BTN_SIZE + gap;
        } else {
           pTop = MARGIN;
        }
      }
      pTop = clamp(pTop, MARGIN, Math.max(MARGIN, winH - panelH - MARGIN));

      widget.style.position = 'fixed';
      widget.style.left   = pLeft + 'px';
      widget.style.top    = pTop  + 'px';
      widget.style.bottom = 'auto';
      widget.style.right  = 'auto';
    }

    function clamp(val, min, max) {
      return Math.min(Math.max(val, min), max);
    }

    window.addEventListener('resize', () => {
      const rect = btn.getBoundingClientRect();
      const newLeft = clamp(rect.left, MARGIN, window.innerWidth  - BTN_SIZE - MARGIN);
      const newTop  = clamp(rect.top,  MARGIN, window.innerHeight - BTN_SIZE - MARGIN);
      applyPos(newLeft, newTop);
      repositionPanel(newLeft, newTop);
    });
  })();
})();

