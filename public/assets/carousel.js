(() => {
  'use strict';
  const track = document.querySelector('#reviews-track');
  if (!track) return;
  const carousel = track.closest('.reviews-carousel');
  const controls = carousel.querySelector('.reviews-controls');
  const previous = controls.querySelector('[data-review-prev]');
  const next = controls.querySelector('[data-review-next]');
  const dots = controls.querySelector('.reviews-dots');
  const cards = [...track.querySelectorAll('.review')];
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const autoplayDelay = 5000;
  let positions = [0];
  let active = 0;
  let frame;
  let drag = null;
  let suppressClick = false;
  let autoplayTimer = 0;
  let resumeTimer = 0;
  let isVisible = false;
  let isHovering = false;
  let hasFocus = false;

  const nearestPosition = offset => positions.reduce((nearest, position, index) => Math.abs(position - offset) < Math.abs(positions[nearest] - offset) ? index : nearest, 0);
  const update = () => {
    active = nearestPosition(track.scrollLeft);
    const canNavigate = positions.length > 1;
    previous.disabled = !canNavigate;
    next.disabled = !canNavigate;
    [...dots.children].forEach((dot, index) => dot.setAttribute('aria-current', String(index === active)));
    controls.querySelector('.reviews-counter strong').textContent = String(active + 1).padStart(2, '0');
    controls.querySelector('.reviews-counter>span').textContent = `/ ${String(positions.length).padStart(2, '0')}`;
  };
  const stopAutoplay = () => {
    window.clearInterval(autoplayTimer);
    autoplayTimer = 0;
    carousel.classList.remove('is-autoplaying');
  };
  const canAutoplay = () => positions.length > 1 && isVisible && !reduced.matches && !isHovering && !hasFocus && !drag && document.visibilityState === 'visible';
  const startAutoplay = () => {
    stopAutoplay();
    if (!canAutoplay()) return;
    carousel.classList.add('is-autoplaying');
    autoplayTimer = window.setInterval(() => go(active + 1, {wrap:true}), autoplayDelay);
  };
  const scheduleAutoplay = (delay = 7500) => {
    stopAutoplay();
    window.clearTimeout(resumeTimer);
    resumeTimer = window.setTimeout(startAutoplay, delay);
  };
  const go = (index, options = {}) => {
    if (!positions.length) return;
    const destination = options.wrap
      ? ((index % positions.length) + positions.length) % positions.length
      : Math.max(0, Math.min(positions.length - 1, index));
    if (options.user) scheduleAutoplay();
    track.scrollTo({left:positions[destination], behavior:reduced.matches ? 'instant' : 'smooth'});
  };
  const layout = () => {
    const max = track.scrollWidth - track.clientWidth;
    controls.hidden = max < 5;
    track.classList.toggle('is-draggable', max >= 5);
    const origin = cards[0].offsetLeft;
    positions = [];
    cards.forEach(card => {
      const position = Math.min(max, Math.max(0, card.offsetLeft - origin));
      if (!positions.length || Math.abs(position - positions[positions.length - 1]) > 5) positions.push(position);
    });
    dots.replaceChildren();
    positions.forEach((position, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.setAttribute('aria-label', `Pagina ${index + 1} din ${positions.length} de recenzii`);
      button.setAttribute('aria-controls', 'reviews-track');
      button.addEventListener('click', () => go(index, {user:true}));
      dots.append(button);
    });
    update();
    startAutoplay();
  };
  const finishDrag = (advance = true) => {
    if (!drag) return;
    const gesture = drag;
    drag = null;
    let destination = nearestPosition(track.scrollLeft);
    const distance = gesture.startX - gesture.lastX;
    if (advance && gesture.moved && destination === gesture.startIndex && Math.abs(distance) > Math.min(80, track.clientWidth * .2)) {
      destination = gesture.startIndex + Math.sign(distance);
    }
    suppressClick = gesture.moved;
    track.classList.remove('is-dragging');
    if (track.hasPointerCapture(gesture.id)) track.releasePointerCapture(gesture.id);
    if (gesture.moved) go(destination, {user:true});
    else scheduleAutoplay();
  };

  track.addEventListener('pointerdown', event => {
    suppressClick = false;
    scheduleAutoplay();
    // Touch keeps the browser's native swipe, momentum and vertical page scrolling.
    if (event.pointerType === 'touch' || !event.isPrimary || event.button !== 0 || !track.classList.contains('is-draggable')) return;
    if (event.target.closest('a, button, input, select, textarea')) return;
    event.preventDefault();
    drag = {id:event.pointerId, startX:event.clientX, startY:event.clientY, lastX:event.clientX, startScroll:track.scrollLeft, startIndex:nearestPosition(track.scrollLeft), moved:false};
    track.setPointerCapture(event.pointerId);
  });
  track.addEventListener('pointermove', event => {
    if (!drag || event.pointerId !== drag.id) return;
    if (!(event.buttons & 1)) { finishDrag(false); return; }
    const distance = event.clientX - drag.startX;
    if (!drag.moved && (Math.abs(distance) < 5 || Math.abs(distance) < Math.abs(event.clientY - drag.startY))) return;
    drag.moved = true;
    drag.lastX = event.clientX;
    track.classList.add('is-dragging');
    event.preventDefault();
    track.scrollLeft = drag.startScroll - distance;
  });
  track.addEventListener('pointerup', event => { if (drag?.id === event.pointerId) finishDrag(); });
  track.addEventListener('pointercancel', event => { if (drag?.id === event.pointerId) finishDrag(false); });
  track.addEventListener('lostpointercapture', event => { if (drag?.id === event.pointerId) finishDrag(false); });
  window.addEventListener('blur', () => finishDrag(false));
  track.addEventListener('dragstart', event => { if (track.classList.contains('is-draggable')) event.preventDefault(); });
  track.addEventListener('click', event => {
    if (!suppressClick || event.detail === 0) return;
    suppressClick = false;
    event.preventDefault();
    event.stopPropagation();
  }, true);
  previous.addEventListener('click', () => go(active - 1, {wrap:true, user:true}));
  next.addEventListener('click', () => go(active + 1, {wrap:true, user:true}));
  track.addEventListener('keydown', event => {
    if (event.key === 'Escape' && drag) { finishDrag(false); return; }
    if (event.target !== track || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
    event.preventDefault();
    go(event.key === 'Home' ? 0 : event.key === 'End' ? positions.length - 1 : active + (event.key === 'ArrowRight' ? 1 : -1), {wrap:true, user:true});
  });
  track.addEventListener('scroll', () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(update); }, {passive:true});
  carousel.addEventListener('mouseenter', () => { isHovering = true; stopAutoplay(); });
  carousel.addEventListener('mouseleave', () => { isHovering = false; scheduleAutoplay(2000); });
  carousel.addEventListener('focusin', () => { hasFocus = true; stopAutoplay(); });
  carousel.addEventListener('focusout', event => {
    if (carousel.contains(event.relatedTarget)) return;
    hasFocus = false;
    scheduleAutoplay();
  });
  document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' ? startAutoplay() : stopAutoplay());
  reduced.addEventListener('change', () => reduced.matches ? stopAutoplay() : startAutoplay());
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      isVisible = entries[0]?.isIntersecting && entries[0].intersectionRatio >= .28;
      if (isVisible) startAutoplay();
      else stopAutoplay();
    }, {threshold:[0, .28, .65]});
    observer.observe(carousel);
  } else {
    isVisible = true;
  }
  if ('ResizeObserver' in window) new ResizeObserver(layout).observe(track);
  else window.addEventListener('resize', layout);
  document.fonts?.ready.then(layout);
  layout();
})();
