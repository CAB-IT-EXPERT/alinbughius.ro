<section class="reels-section section" id="in-miscare" aria-labelledby="reels-title">
  <div class="container">
    <div class="section-heading"><div><p class="eyebrow">MAI PUȚINE CUVINTE. MAI MULTĂ STARE DE BINE.</p><h2 id="reels-title">Un pic din ritmul meu.<br><em>O pauză din ritmul tău.</em></h2></div><div class="reels-intro"><p>Așa arată grija, în mișcare. Momente reale din ședințele mele, înainte să ne cunoaștem.</p><a class="text-link" href="https://www.instagram.com/terapeut.alinbughius" target="_blank" rel="noopener noreferrer"><?= icon('instagram') ?> @terapeut.alinbughius <?= icon('diagonal') ?></a></div></div>
    <div class="reels-grid">
      <?php foreach ([
        ['therapy', 'DaJMW6MI0XG', '01', 'Atenție la fiecare mișcare.', 'Lucru atent, pentru umeri și zona cervicală.'],
        ['touch', 'DbKlX-MIN6v', '02', 'Respiră. E momentul tău.', 'Un ritm mai lent. Un moment doar pentru tine.'],
        ['ritual', 'DaiuYXLq-uV', '03', 'Grija începe cu tine.', 'Puțină inspirație pentru rutina ta de bine.']
      ] as [$clip, $reelId, $number, $title, $caption]): ?>
      <article class="reel-card">
        <div class="reel-media video-shell"><video data-autoplay data-src="/assets/videos/<?= e($clip) ?>.mp4" poster="/assets/images/reel-<?= e($clip) ?>.jpg" muted loop playsinline preload="none" width="720" height="1280" aria-label="<?= e($title . ' ' . $caption) ?>"></video><div class="reel-topline"><span><?= icon('instagram') ?> ALIN, ÎN MIȘCARE</span><span><?= e($number) ?></span></div><button class="video-toggle" data-video-toggle hidden aria-label="Redă videoclipul"><span class="video-control-icon" aria-hidden="true">▶</span><span class="video-control-label">Redă</span></button><a class="reel-original" href="https://www.instagram.com/terapeut.alinbughius/reel/<?= e($reelId) ?>/" target="_blank" rel="noopener noreferrer" aria-label="Vezi videoclipul <?= e($title) ?> cu sunet pe Instagram"><?= icon('diagonal') ?></a></div>
        <div class="reel-caption"><h3><?= e($title) ?></h3><p><?= e($caption) ?></p></div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="reels-bottom"><span><?= icon('heart') ?> Momente reale, din practica lui Alin.</span><p>Clipurile rulează fără sunet. Povestea completă te așteaptă pe Instagram.</p><a href="#servicii" class="text-link">Găsește masajul potrivit <?= icon('arrow') ?></a></div>
  </div>
</section>
