(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const menu = $('.menu-toggle');
  const nav = $('#main-nav');
  const menuBackdrop = $('.menu-backdrop');
  const mobileMenu = window.matchMedia('(max-width:700px)');
  const menuBackground = $$('main, .footer, .mobile-actions, .topline');
  const setMenu = (open, returnFocus = true) => {
    menu.setAttribute('aria-expanded', String(open));
    menu.setAttribute('aria-label', open ? 'Închide meniul' : 'Deschide meniul');
    nav.classList.toggle('open', open);
    document.body.classList.toggle('menu-open', open);
    menuBackground.forEach(element => { element.inert = open; });
    if (open) nav.querySelector('a')?.focus({preventScroll:true});
    else if (returnFocus) menu.focus({preventScroll:true});
  };
  menu?.addEventListener('click', () => setMenu(menu.getAttribute('aria-expanded') !== 'true'));
  menuBackdrop?.addEventListener('click', () => setMenu(false));
  $$('#main-nav a').forEach(a => a.addEventListener('click', () => { if (mobileMenu.matches) setMenu(false, false); }));
  mobileMenu.addEventListener('change', event => { if (!event.matches) setMenu(false, false); });
  document.addEventListener('keydown', event => {
    if (menu.getAttribute('aria-expanded') !== 'true') return;
    if (event.key === 'Escape') { event.preventDefault(); setMenu(false); }
    if (event.key !== 'Tab') return;
    const items = $$('a, button', $('.header')).filter(element => element.offsetParent !== null);
    const first = items[0], last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });

  let pricing = 'single';
  const setPricing = value => {
    pricing = value;
    $$('[data-pricing]').forEach(button => { const active = button.dataset.pricing === value; button.classList.toggle('active', active); button.setAttribute('aria-pressed', String(active)); });
    $$('[data-single]').forEach(price => { price.textContent = price.dataset[value]; });
    $$('[data-session-count]').forEach(label => { label.textContent = value === 'single' ? '/ ședință' : `/ ${label.dataset.sessionCount} ședințe`; });
    $$('.package-saving').forEach(el => { el.hidden = value !== 'package'; });
  };
  $$('[data-pricing]').forEach(button => button.addEventListener('click', () => setPricing(button.dataset.pricing)));
  $('#show-services')?.addEventListener('click', event => { const button = event.currentTarget; const show = button.getAttribute('aria-expanded') !== 'true'; $$('.extra-service').forEach(card => { card.hidden = !show; }); button.setAttribute('aria-expanded', String(show)); button.innerHTML = show ? 'Arată serviciile principale <span>−</span>' : 'Vezi toate cele 8 tipuri de masaj <span>+</span>'; });
  $('[data-show-packages]')?.addEventListener('click', () => { setPricing('package'); if ($('#show-services').getAttribute('aria-expanded') === 'false') $('#show-services').click(); $('#servicii').scrollIntoView({behavior:'smooth'}); });

  const dialog = $('#booking-dialog');
  const form = $('#booking-form');
  let tokenPromise;
  let submitted = false;
  let availabilityController;
  let availabilityTimer;
  const sessionInput = $('#booking-sessions');
  const fetchToken = async () => {
    tokenPromise = fetch('/api/token.php', {credentials:'same-origin', cache:'no-store'}).then(async response => { if (!response.ok) throw new Error('Formularul nu poate fi pregătit acum. Reîncearcă sau sună la 0773 919 071.'); const data = await response.json(); $('#csrf').value = data.csrf; $('#request-id').value = data.request_id; return data; });
    return tokenPromise;
  };
  const availabilityStatus = message => { $('#availability-status').textContent = message; };
  const selectTime = (button, date) => {
    $$('.time-chip', $('#available-times')).forEach(item => item.classList.toggle('selected', item === button));
    $('#booking-date').value = date;
    $('#booking-time').value = button.dataset.time;
    availabilityStatus(`Ai ales ${button.dataset.dayLabel}, la ${button.dataset.time}.`);
  };
  const renderTimes = day => {
    const container = $('#available-times');
    container.replaceChildren();
    day.slots.forEach(time => {
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'time-chip'; button.dataset.time = time; button.dataset.dayLabel = `${day.weekday}, ${day.label}`;
      button.textContent = time; button.addEventListener('click', () => selectTime(button, day.date)); container.append(button);
    });
    $('#booking-date').value = day.date; $('#booking-time').value = '';
    availabilityStatus(`Alege una dintre cele ${day.slots.length} ore libere pentru ${day.weekday.toLowerCase()}, ${day.label}.`);
  };
  const selectDay = (button, day) => {
    $$('.day-chip', $('#available-days')).forEach(item => { const active = item === button; item.classList.toggle('selected', active); item.setAttribute('aria-pressed', String(active)); });
    renderTimes(day);
  };
  const loadAvailability = async (preserveSelection = false) => {
    const previousDate = preserveSelection ? $('#booking-date').value : '';
    const previousTime = preserveSelection ? $('#booking-time').value : '';
    availabilityController?.abort(); availabilityController = new AbortController();
    $('#available-days').replaceChildren(); $('#available-times').replaceChildren();
    $('#booking-date').value = ''; $('#booking-time').value = ''; availabilityStatus('Încărcăm intervalele disponibile…');
    try {
      const sessions = Math.max(1, Number(sessionInput?.value) || 1);
      const response = await fetch(`/api/availability.php?service=${encodeURIComponent($('#service').value)}&sessions=${sessions}`, {cache:'no-store', signal:availabilityController.signal});
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'Calendarul nu este disponibil momentan.');
      if (!data.days.length) { availabilityStatus('Nu există intervale libere în perioada următoare. Sună-l pe Alin pentru o variantă personalizată.'); return; }
      const container = $('#available-days');
      data.days.forEach(day => {
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'day-chip'; button.setAttribute('aria-pressed', 'false');
        button.innerHTML = `<span>${day.weekday}</span><strong>${day.label}</strong><small>${day.slots.length} ore libere</small>`;
        button.addEventListener('click', () => selectDay(button, day)); container.append(button);
      });
      const rememberedDay = data.days.find(day => day.date === previousDate && day.slots.includes(previousTime));
      const targetDay = rememberedDay || data.days[0];
      const targetButton = $$('.day-chip', container)[data.days.indexOf(targetDay)];
      selectDay(targetButton, targetDay);
      if (rememberedDay) {
        const targetTime = $$('.time-chip', $('#available-times')).find(item => item.dataset.time === previousTime);
        if (targetTime) selectTime(targetTime, rememberedDay.date);
      }
    } catch (error) {
      if (error.name !== 'AbortError') availabilityStatus(error.message || 'Calendarul nu a putut fi încărcat. Reîncearcă.');
    }
  };
  const syncSessionOptions = (preserve = false) => {
    const option = $('#service').selectedOptions[0];
    if (!option || !sessionInput) return;
    const maximum = Math.max(1, Number(option.dataset.maxBookingSessions) || 1);
    const previous = preserve ? Math.min(maximum, Math.max(1, Number(sessionInput.value) || 1)) : 1;
    sessionInput.replaceChildren();
    for (let count = 1; count <= maximum; count += 1) sessionInput.append(new Option(count === 1 ? '1 sesiune' : `${count} sesiuni`, String(count)));
    sessionInput.value = String(previous);
  };
  const updateEstimate = () => {
    const option = $('#service').selectedOptions[0];
    const zone = $('#zone').value;
    const transport = zone && !['sector-2', 'sector-3'].includes(zone) ? 30 : 0;
    const base = Number(option.dataset.price);
    const sessions = Math.max(1, Number(sessionInput?.value) || 1);
    const sessionWord = sessions === 1 ? 'sesiune' : 'sesiuni';
    const between = Math.max(0, Number(option.dataset.sessionBreak) || 0);
    const subtotal = base * sessions;
    $('#estimate-label').textContent = `${sessions} ${sessionWord} · ${option.dataset.duration} fiecare`;
    $('#estimate-price').textContent = String(subtotal + transport);
    const pauseNote = sessions > 1 ? ` · pauză ${between} min între sesiuni` : '';
    $('#estimate-note').textContent = !zone ? `${sessions} × ${base} lei${pauseNote}. Alege zona pentru deplasare.` : transport ? `${sessions} × ${base} lei + 30 lei deplasare${pauseNote}.` : `${sessions} × ${base} lei · deplasare inclusă${pauseNote}.`;
  };
  $$('[data-book]').forEach(button => button.addEventListener('click', () => {
    if (menu.getAttribute('aria-expanded') === 'true') setMenu(false, false);
    if (submitted) { form.reset(); syncSessionOptions(); form.hidden = false; $('#booking-success').hidden = true; $('#form-status').textContent = ''; tokenPromise = null; submitted = false; }
    if (button.dataset.book) $('#service').value = button.dataset.book;
    syncSessionOptions(); updateEstimate(); dialog.showModal(); document.body.classList.add('modal-open'); loadAvailability();
    if (!tokenPromise) fetchToken().catch(error => { tokenPromise = null; $('#form-status').textContent = error.message; });
    clearInterval(availabilityTimer); availabilityTimer = setInterval(() => { if (dialog.open) loadAvailability(true); }, 30000);
  }));
  $$('dialog').forEach(modal => {
    $$('[data-close]', modal).forEach(button => button.addEventListener('click', () => modal.close()));
    modal.addEventListener('close', () => { document.body.classList.remove('modal-open'); if (modal === dialog) { clearInterval(availabilityTimer); availabilityController?.abort(); } });
    modal.addEventListener('click', event => { if (event.target === modal) { const rect = modal.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) modal.close(); } });
  });
  $('#zone')?.addEventListener('change', updateEstimate);
  $('#service')?.addEventListener('change', () => { syncSessionOptions(); updateEstimate(); loadAvailability(); });
  sessionInput?.addEventListener('change', () => { updateEstimate(); loadAvailability(); });
  form?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    if (!$('#booking-date').value || !$('#booking-time').value) { availabilityStatus('Alege o zi și o oră liberă înainte de a trimite cererea.'); $('#available-days').scrollIntoView({behavior:'smooth', block:'center'}); return; }
    const button = $('[type=submit]', form); const original = button.innerHTML;
    button.disabled = true; button.textContent = 'Se trimite cererea…'; $('#form-status').textContent = '';
    try {
      if (!tokenPromise) await fetchToken(); else await tokenPromise;
      const response = await fetch(form.action, {method:'POST', body:new FormData(form), credentials:'same-origin'});
      const data = await response.json();
      if (!response.ok || !data.ok) { if (response.status === 403) { tokenPromise = null; $('#csrf').value = ''; } if (response.status === 409) loadAvailability(); throw new Error(data.message || 'Cererea nu a putut fi trimisă. Te rugăm să încerci din nou.'); }
      submitted = true; form.hidden = true; $('#booking-success').hidden = false; $('#success-message').textContent = data.message; $('#booking-success').focus(); dialog.scrollTop = 0;
    } catch(error) { $('#form-status').textContent = error.message || 'Conexiunea s-a întrerupt. Reîncearcă; cererea nu va fi duplicată.'; }
    finally { button.disabled = false; button.innerHTML = original; }
  });
  $$('[data-lightbox]').forEach(link => link.addEventListener('click', event => { event.preventDefault(); $('#diploma-image').src = link.href; $('#diploma-image').alt = `Diplomă ${link.dataset.caption}`; $('#diploma-caption').textContent = link.dataset.caption; $('#image-dialog').showModal(); document.body.classList.add('modal-open'); }));

  // Progressive enhancement: content stays visible without JS or with reduced motion.
  const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!motionPreference.matches && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('motion-enabled');
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      });
    }, {threshold:0.08, rootMargin:'0px 0px -30px 0px'});
    const targets = $$('.section-heading, .reviews-carousel, .trust-item, .service-card, .subscription-banner, .about-photo, .about-copy, .steps > div, .gift-inner, .reel-card, .reels-bottom, .home-experience-photo, .home-experience-copy, .guide-grid > article, .guide-note, .faq-grid > div:first-child, .faq-list details, .footer-invitation, .footer-main > *, .footer-bottom');
    targets.forEach((element, index) => {
      element.classList.add('scroll-reveal');
      if (element.matches('.trust-item, .service-card, .steps > div, .reel-card, .guide-grid > article, .footer-main > *')) {
        const siblings = [...element.parentElement.children];
        element.dataset.motionOrder = String(siblings.indexOf(element) % 3);
      }
      observer.observe(element);
    });
    // Keyboard navigation never lands on visually concealed content.
    document.addEventListener('focusin', event => event.target.closest('.scroll-reveal')?.classList.add('is-revealed'));
    motionPreference.addEventListener('change', event => { if (event.matches) { observer.disconnect(); document.documentElement.classList.remove('motion-enabled'); } });
  }
})();
