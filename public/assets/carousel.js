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
  let positions = [0];
  let active = 0;
  let frame;
  let drag = null;
  let suppressClick = false;
  const nearestPosition = offset => positions.reduce((nearest, position, index) => Math.abs(position - offset) < Math.abs(positions[nearest] - offset) ? index : nearest, 0);
  const update = () => {
    active = nearestPosition(track.scrollLeft);
    previous.disabled = active === 0;
    next.disabled = active === positions.length - 1;
    [...dots.children].forEach((dot, index) => dot.setAttribute('aria-current', String(index === active)));
    controls.querySelector('.reviews-counter strong').textContent = String(active + 1).padStart(2, '0');
    controls.querySelector('.reviews-counter>span').textContent = `/ ${String(positions.length).padStart(2, '0')}`;
  };
  const go = index => {
    const destination = Math.max(0, Math.min(positions.length - 1, index));
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
      button.addEventListener('click', () => go(index));
      dots.append(button);
    });
    update();
  };
  const finishDrag = (advance = true) => {
    if (!drag) return;
    const gesture = drag;
    drag = null;
    let destination = nearestPosition(track.scrollLeft);
    const distance = gesture.startX - gesture.lastX;
    // An intentional short pull also advances one card; tiny movements stay put.
    if (advance && gesture.moved && destination === gesture.startIndex && Math.abs(distance) > Math.min(80, track.clientWidth * .2)) {
      destination = gesture.startIndex + Math.sign(distance);
    }
    suppressClick = gesture.moved;
    track.classList.remove('is-dragging');
    if (track.hasPointerCapture(gesture.id)) track.releasePointerCapture(gesture.id);
    if (gesture.moved) go(destination);
  };
  track.addEventListener('pointerdown', event => {
    suppressClick = false;
    // Touch retains native swipe, momentum and vertical page scrolling.
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
  previous.addEventListener('click', () => go(active - 1));
  next.addEventListener('click', () => go(active + 1));
  track.addEventListener('keydown', event => {
    if (event.key === 'Escape' && drag) { finishDrag(false); return; }
    if (event.target !== track || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
    event.preventDefault();
    go(event.key === 'Home' ? 0 : event.key === 'End' ? positions.length - 1 : active + (event.key === 'ArrowRight' ? 1 : -1));
  });
  track.addEventListener('scroll', () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(update); }, {passive:true});
  if ('ResizeObserver' in window) new ResizeObserver(layout).observe(track);
  else window.addEventListener('resize', layout);
  document.fonts?.ready.then(layout);
  layout();
})();
