(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const menu = $('.menu-toggle');
  const nav = $('#main-nav');
  const menuBackdrop = $('.menu-backdrop');
  const mobileMenu = window.matchMedia('(max-width:700px)');
  const menuBackground = $$('main, .footer, .mobile-actions, .topline');
  // The reference hero has its own contact row. Show the fixed mobile row only
  // after leaving that section, so the two sets of controls never overlap.
  const referenceHero = $('.hero--reference');
  if (referenceHero && 'IntersectionObserver' in window) {
    document.body.classList.add('has-reference-hero');
    const contactObserver = new IntersectionObserver(([entry]) => {
      document.body.classList.toggle('hero-passed', entry.boundingClientRect.bottom <= 0);
    });
    contactObserver.observe(referenceHero);
  }
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

  const heroGallery = $('[data-hero-gallery]');
  if (heroGallery) {
    const track = $('[data-hero-track]', heroGallery);
    const slides = $$('[data-hero-slide]', heroGallery);
    const dots = $$('[data-hero-dot]', heroGallery);
    const currentLabel = $('[data-hero-current]', heroGallery);
    const status = $('[data-hero-status]', heroGallery);
    const previous = $('[data-hero-prev]', heroGallery);
    const next = $('[data-hero-next]', heroGallery);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const slideCount = slides.length;
    let index = 0;
    let position = 1;
    let timer = 0;
    let resumeTimer = 0;
    let transitionTimer = 0;
    let isVisible = false;
    let isHovering = false;
    let hasFocus = false;
    let isDragging = false;
    let isTransitioning = false;
    let pointerId = null;
    let startX = 0;
    let startY = 0;
    let dragX = 0;

    const stopAutoplay = () => {
      window.clearInterval(timer);
      timer = 0;
    };
    const canAutoplay = () => isVisible && !reduceMotion.matches && !isHovering && !hasFocus && !isDragging && document.visibilityState === 'visible';
    const startAutoplay = () => {
      stopAutoplay();
      if (!canAutoplay()) return;
      timer = window.setInterval(() => move(1, false), 1700);
    };
    const scheduleAutoplay = (delay = 3500) => {
      stopAutoplay();
      window.clearTimeout(resumeTimer);
      resumeTimer = window.setTimeout(startAutoplay, delay);
    };
    const updateInterface = (announce = false) => {
      slides.forEach((slide, slideIndex) => {
        const active = slideIndex === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', String(!active));
      });
      dots.forEach((dot, dotIndex) => dot.setAttribute('aria-current', String(dotIndex === index)));
      if (currentLabel) currentLabel.textContent = String(index + 1).padStart(2, '0');
      if (announce && status) status.textContent = `Fotografia ${index + 1} din ${slideCount}`;
    };
    const setTrackPosition = (animate = true) => {
      if (!animate) track.style.transition = 'none';
      else track.style.removeProperty('transition');
      track.style.transform = `translate3d(${-position * 100}%,0,0)`;
      if (!animate) {
        track.getBoundingClientRect();
        track.style.removeProperty('transition');
      }
    };
    const settleLoop = () => {
      if (position > slideCount) position = ((position - 1) % slideCount) + 1;
      else if (position < 1) position = ((position - 1) % slideCount + slideCount) % slideCount + 1;
      else return;
      setTrackPosition(false);
      [...track.children].forEach(slide => slide.classList.remove('is-active'));
      track.children[position]?.classList.add('is-active');
    };
    const completeTransition = () => {
      window.clearTimeout(transitionTimer);
      transitionTimer = 0;
      isTransitioning = false;
      settleLoop();
    };
    const move = (direction, announce = true) => {
      if (isTransitioning || slideCount < 2) return;
      isTransitioning = true;
      position += direction;
      index = (index + direction + slideCount) % slideCount;
      [...track.children].forEach(slide => slide.classList.remove('is-active'));
      track.children[position]?.classList.add('is-active');
      setTrackPosition(true);
      updateInterface(announce);
      window.clearTimeout(transitionTimer);
      transitionTimer = window.setTimeout(completeTransition, 750);
      if (announce) scheduleAutoplay();
    };
    const goTo = target => {
      if (isTransitioning || target === index) return;
      index = target;
      position = target + 1;
      [...track.children].forEach(slide => slide.classList.remove('is-active'));
      track.children[position]?.classList.add('is-active');
      setTrackPosition(true);
      updateInterface(true);
      isTransitioning = true;
      window.clearTimeout(transitionTimer);
      transitionTimer = window.setTimeout(completeTransition, 750);
      scheduleAutoplay();
    };

    if (slideCount > 1) {
      const before = slides[slideCount - 1].cloneNode(true);
      const after = slides[0].cloneNode(true);
      before.removeAttribute('data-hero-slide');
      after.removeAttribute('data-hero-slide');
      before.setAttribute('aria-hidden', 'true');
      after.setAttribute('aria-hidden', 'true');
      before.classList.remove('is-active');
      after.classList.remove('is-active');
      track.prepend(before);
      track.append(after);
      setTrackPosition(false);
      updateInterface();

      track.addEventListener('transitionend', event => {
        if (event.target !== track || event.propertyName !== 'transform') return;
        completeTransition();
      });
      previous?.addEventListener('click', () => move(-1));
      next?.addEventListener('click', () => move(1));
      dots.forEach(dot => dot.addEventListener('click', () => goTo(Number(dot.dataset.heroDot))));
      heroGallery.addEventListener('keydown', event => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        move(event.key === 'ArrowLeft' ? -1 : 1);
      });

      heroGallery.addEventListener('pointerdown', event => {
        if (event.button !== 0 || event.target.closest('button, a')) return;
        pointerId = event.pointerId;
        startX = event.clientX;
        startY = event.clientY;
        dragX = 0;
        isDragging = true;
        isTransitioning = false;
        window.clearTimeout(transitionTimer);
        heroGallery.classList.add('is-dragging');
        heroGallery.setPointerCapture?.(pointerId);
        stopAutoplay();
        window.clearTimeout(resumeTimer);
      });
      heroGallery.addEventListener('pointermove', event => {
        if (!isDragging || event.pointerId !== pointerId) return;
        dragX = event.clientX - startX;
        const dragY = event.clientY - startY;
        if (Math.abs(dragY) > Math.abs(dragX) && Math.abs(dragY) > 10) return;
        const width = heroGallery.clientWidth || 1;
        track.style.transform = `translate3d(${(-position * width) + dragX}px,0,0)`;
      });
      const finishDrag = (event, cancelled = false) => {
        if (!isDragging || (event.pointerId !== undefined && event.pointerId !== pointerId)) return;
        const width = heroGallery.clientWidth || 1;
        const threshold = Math.min(95, Math.max(42, width * .12));
        isDragging = false;
        heroGallery.classList.remove('is-dragging');
        heroGallery.releasePointerCapture?.(pointerId);
        pointerId = null;
        track.style.removeProperty('transition');
        if (!cancelled && Math.abs(dragX) >= threshold) move(dragX < 0 ? 1 : -1);
        else {
          setTrackPosition(true);
          scheduleAutoplay();
        }
        dragX = 0;
      };
      heroGallery.addEventListener('pointerup', event => finishDrag(event));
      heroGallery.addEventListener('pointercancel', event => finishDrag(event, true));
      heroGallery.addEventListener('lostpointercapture', event => finishDrag(event, true));

      heroGallery.addEventListener('mouseenter', () => { isHovering = true; stopAutoplay(); });
      heroGallery.addEventListener('mouseleave', () => { isHovering = false; scheduleAutoplay(1800); });
      heroGallery.addEventListener('focusin', () => { hasFocus = true; stopAutoplay(); });
      heroGallery.addEventListener('focusout', event => {
        if (heroGallery.contains(event.relatedTarget)) return;
        hasFocus = false;
        scheduleAutoplay();
      });
      document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' ? startAutoplay() : stopAutoplay());
      reduceMotion.addEventListener('change', () => reduceMotion.matches ? stopAutoplay() : startAutoplay());

      if ('IntersectionObserver' in window) {
        const galleryObserver = new IntersectionObserver(entries => {
          isVisible = entries[0]?.isIntersecting && entries[0].intersectionRatio >= .32;
          if (isVisible) startAutoplay();
          else stopAutoplay();
        }, {threshold:[0, .32, .65]});
        galleryObserver.observe(heroGallery);
      } else {
        isVisible = true;
        startAutoplay();
      }
    }
  }

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
