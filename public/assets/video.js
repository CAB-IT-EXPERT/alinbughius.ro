(() => {
  'use strict';
  const videos = [...document.querySelectorAll('video[data-autoplay]')];
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const saveData = navigator.connection?.saveData;
  const visible = new Set();
  const manualPause = new WeakSet();
  const load = video => {
    if (!video.getAttribute('src')) { video.src = video.dataset.src; video.load(); }
  };
  const play = video => {
    load(video);
    video.muted = true;
    video.play().catch(() => { /* Poster and explicit play remain usable when autoplay is blocked. */ });
  };
  videos.forEach(video => {
    const toggle = video.closest('.video-shell').querySelector('[data-video-toggle]');
    toggle.hidden = false;
    const update = () => {
      const playing = !video.paused;
      toggle.setAttribute('aria-label', playing ? 'Pune videoclipul pe pauză' : 'Redă videoclipul');
      toggle.querySelector('.video-control-label').textContent = playing ? 'Pauză' : 'Redă';
      toggle.querySelector('.video-control-icon').textContent = playing ? 'Ⅱ' : '▶';
      video.closest('.video-shell').classList.toggle('is-playing', playing);
    };
    video.addEventListener('play', update);
    video.addEventListener('pause', update);
    video.addEventListener('error', () => { toggle.hidden = true; });
    toggle.addEventListener('click', () => {
      if (video.paused) { manualPause.delete(video); play(video); }
      else { manualPause.add(video); video.pause(); }
    });
  });
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      const video = entry.target;
      if (entry.isIntersecting && entry.intersectionRatio >= .25) {
        visible.add(video);
        if (!reduced.matches && !saveData && !manualPause.has(video) && !document.hidden) play(video);
      } else { visible.delete(video); video.pause(); }
    }), {threshold:[0,.25,.6]});
    videos.forEach(video => observer.observe(video));
  }
  document.addEventListener('visibilitychange', () => {
    videos.forEach(video => {
      if (document.hidden) video.pause();
      else if (visible.has(video) && !manualPause.has(video) && !reduced.matches && !saveData) play(video);
    });
  });
  reduced.addEventListener('change', event => { if (event.matches) videos.forEach(video => video.pause()); });
})();
