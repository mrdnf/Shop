/* Matjar UI helpers (vanilla + Alpine). */
(function () {
  'use strict';

  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  window.matjar = window.matjar || {};

  /* ---------- Icons ---------- */
  const icons = () => window.lucide && window.lucide.createIcons();
  window.matjar.icons = icons;

  /* ---------- Toasts ---------- */
  function toast(message, type = 'success', timeout = 3800) {
    let host = document.getElementById('toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'toast-host';
      host.className = 'toast toast-top toast-end z-[100] no-print';
      document.body.appendChild(host);
    }
    const iconName = { success: 'circle-check', error: 'circle-x', warning: 'triangle-alert', info: 'info' }[type] || 'info';
    const el = document.createElement('div');
    el.className = `alert alert-${type} shadow-xl animate-fade-up max-w-sm`;
    el.innerHTML = `<i data-lucide="${iconName}" class="size-5"></i><span></span>`;
    el.querySelector('span').textContent = message;
    host.appendChild(el);
    icons();
    setTimeout(() => { el.style.transition = 'opacity .3s, transform .3s'; el.style.opacity = '0'; el.style.transform = 'translateY(-6px)'; }, timeout);
    setTimeout(() => el.remove(), timeout + 350);
  }
  window.toast = toast;

  /* ---------- Light / dark ---------- */
  window.matjar.toggleMode = function () {
    const html = document.documentElement;
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    document.cookie = `mode=${next};path=/;max-age=31536000;samesite=lax`;
    document.dispatchEvent(new CustomEvent('matjar:mode', { detail: next }));
  };

  /* ---------- AJAX add-to-cart ---------- */
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (form.matches('[data-confirm]') && !confirm(form.dataset.confirm)) {
      e.preventDefault();
      return;
    }
    if (!form.matches('[data-ajax-cart]')) return;
    e.preventDefault();
    const btn = form.querySelector('[type=submit]');
    btn && btn.classList.add('btn-disabled');
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      });
      const data = await res.json();
      toast(data.message || 'OK', data.ok ? 'success' : 'error');
      if (data.ok) {
        document.querySelectorAll('[data-cart-count]').forEach((b) => {
          b.textContent = data.count;
          b.classList.remove('hidden');
          b.animate([{ transform: 'scale(1.6)' }, { transform: 'scale(1)' }], { duration: 400, easing: 'ease-out' });
        });
      }
    } catch (err) {
      toast('Network error', 'error');
    } finally {
      btn && btn.classList.remove('btn-disabled');
    }
  });

  /* ---------- Count-up numbers ---------- */
  function countUp(el) {
    const target = parseFloat(el.dataset.countUp);
    const decimals = parseInt(el.dataset.decimals || '0', 10);
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    const start = performance.now();
    const dur = 900;
    const step = (t) => {
      const p = Math.min(1, (t - start) / dur);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = prefix + (target * eased).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  /* ---------- Image preview for file inputs ---------- */
  document.addEventListener('change', (e) => {
    const input = e.target;
    if (!input.matches('input[type=file][data-preview]')) return;
    const img = document.querySelector(input.dataset.preview);
    const file = input.files && input.files[0];
    if (img && file) {
      img.src = URL.createObjectURL(file);
      img.classList.remove('hidden');
    }
  });

  /* ---------- Copy buttons ---------- */
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    navigator.clipboard.writeText(btn.dataset.copy).then(() => toast(btn.dataset.copied || 'Copied', 'info', 1800));
  });

  document.addEventListener('DOMContentLoaded', () => {
    icons();
    document.querySelectorAll('[data-count-up]').forEach(countUp);
    document.querySelectorAll('[data-flash]').forEach((el) => toast(el.dataset.flash, el.dataset.type || 'success'));
  });
})();
