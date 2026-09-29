<?php
define('PUBLIC_ADS_MEASUREMENT', true);
require __DIR__ . '/_bootstrap.php';
if (!in_array(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), ['/', '/index.php'], true)) {
    http_response_code(404);
    exit('Pagina nu a fost găsită.');
}
$reviewsUrl = 'https://maps.app.goo.gl/6DYJuahdJBCoshC68?g_st=aw';
function icon(string $name, string $class = ''): string {
    $paths = [
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'diagonal' => '<path d="M6 18 18 6M6 6h12v12"/>',
        'pin' => '<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'phone' => '<path d="m7 3 3 5-3 2c1.5 3 4 5.5 7 7l2-3 5 3c0 3-2 4-4 4C10 20 4 14 3 7c0-2 1-4 4-4Z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'chat' => '<path d="M21 11.5a9 9 0 0 1-13 8L3 21l1.5-5A9 9 0 1 1 21 11.5Z"/><path d="M8 8c1 4 3 6 7 7l1-2-2-1-1 1-2-2 1-1-1-2Z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 5h2m4 0h2"/>',
        'home' => '<path d="m3 10 9-7 9 7M5 9v12h14V9m-10 12v-8h6v8"/>',
        'award' => '<circle cx="12" cy="8" r="5"/><path d="m8 12-2 9 6-3 6 3-2-9"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 6 9 7 9-7"/>',
        'heart' => '<path d="M20 5c-3-3-7-1-8 2-1-3-5-5-8-2s-1 7 8 14c9-7 11-11 8-14Z"/>',
        'gift' => '<path d="M3 9h18v4H3zM5 13v8h14v-8M12 9v12"/><path d="M12 9S4 9 5 5c1-4 7 0 7 4Zm0 0s8 0 7-4c-1-4-7 0-7 4Z"/>',
    ];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alin Bughius · Masaj terapeutic la domiciliu în București & Ilfov</title>
  <meta name="description" content="Masaj terapeutic la domiciliu, cu Alin Bughius, în București și Ilfov. 200 lei / 60 minute. Vezi toate serviciile, recenziile și abonamentele cu 10% reducere.">
  <meta name="theme-color" content="#164d40">
  <meta property="og:title" content="Alin Bughius · Starea de bine vine acasă">
  <meta property="og:description" content="Masaj terapeutic la domiciliu în București și Ilfov. Descoperă serviciile, prețurile și solicită o programare.">
  <meta property="og:type" content="website">
  <link rel="canonical" href="https://alinbughius.ro/">
  <link rel="icon" type="image/svg+xml" href="/assets/logo-mark.svg">
  <link rel="preload" href="/assets/fonts/dm-sans-400.ttf" as="font" type="font/ttf" crossorigin>
  <link rel="stylesheet" href="/assets/style.css?v=2">
  <link rel="stylesheet" href="/assets/refined.css?v=2">
  <link rel="stylesheet" href="/assets/experience.css?v=1">
  <link rel="stylesheet" href="/assets/footer.css?v=1">
  <link rel="stylesheet" href="/assets/details.css?v=12">
  <link rel="stylesheet" href="/assets/motion.css?v=1">
  <link rel="stylesheet" href="/assets/hero-reference.css?v=2">
  <script src="/assets/site.js?v=7" defer></script>
  <script src="/assets/video.js?v=1" defer></script>
  <script src="/assets/carousel.js?v=3" defer></script>
  <script src="/assets/measurement.js?v=3" defer></script>
</head>
<body class="home-page">
<a class="skip-link" href="#continut">Sari la conținut</a>
<div class="topline"><div class="container"><span><?= icon('pin') ?> Masaj la domiciliu · București & Ilfov</span><a href="https://www.instagram.com/terapeut.alinbughius" target="_blank" rel="noopener noreferrer"><?= icon('instagram') ?><span>Un pic de inspirație, pe Instagram</span><?= icon('diagonal') ?></a></div></div>
<header class="header">
  <div class="container header-inner">
    <a class="brand" href="/" aria-label="Alin Bughius — acasă"><img src="/assets/logo-mark.svg" alt="" width="46" height="46"><span>Alin Bughius<small>MASAJ & STARE DE BINE</small></span></a>
    <button class="menu-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Deschide meniul"><span></span><span></span></button>
    <nav id="main-nav" aria-label="Meniu principal"><p class="nav-mobile-heading">O PAUZĂ BUNĂ ÎNCEPE AICI</p><a href="#servicii"><span class="nav-order" aria-hidden="true">01</span>Servicii & prețuri<?= icon('diagonal', 'nav-arrow') ?></a><a href="#recenzii"><span class="nav-order" aria-hidden="true">02</span>Recenzii<?= icon('diagonal', 'nav-arrow') ?></a><a href="#in-miscare" class="nav-video-link"><span class="nav-order" aria-hidden="true">03</span>Alin, în mișcare<?= icon('diagonal', 'nav-arrow') ?></a><a href="#despre"><span class="nav-order" aria-hidden="true">04</span>Despre mine<?= icon('diagonal', 'nav-arrow') ?></a><a href="#contact"><span class="nav-order" aria-hidden="true">05</span>Contact<?= icon('diagonal', 'nav-arrow') ?></a><div class="nav-mobile-footer"><button class="button button-dark" data-book>Alege momentul tău <?= icon('arrow') ?></button><span><?= icon('pin') ?> Masaj la domiciliu · București & Ilfov</span></div></nav>
    <button class="button button-dark header-book" data-book>Programează-te <?= icon('diagonal') ?></button>
  </div>
</header>
<button class="menu-backdrop" type="button" tabindex="-1" aria-hidden="true" aria-label="Închide meniul"></button>
<main id="continut">
  <section class="hero hero--reference">
    <div class="hero-visual">
      <figure class="hero-photo hero-static-photo"><img src="/assets/images/hero-reference-background.webp" alt="Masaj de relaxare într-o atmosferă calmă" width="1024" height="1536" fetchpriority="high"></figure>
    </div>
    <div class="container hero-grid">
      <div class="hero-copy">
        <p class="eyebrow"><span></span> MASAJ TERAPEUTIC LA DOMICILIU</p>
        <h1><span class="hero-heading-main">O pauză pentru corp.</span><em>Un bine pentru tine.</em></h1>
        <p class="hero-description"><span>Lasă tensiunea zilei în urmă. Masaj</span> <span>personalizat, cu Alin Bughius, în</span> <span>confortul casei tale.</span></p>
        <p class="hero-location"><?= icon('pin') ?> București & Ilfov <span>·</span> Echipament inclus</p>
        <div class="hero-price"><strong>200 <span>lei</span></strong><span class="price-divider"></span><span><b>60 de minute</b><br>de masaj terapeutic</span></div>
        <div class="hero-actions"><button class="button button-mint" data-book="terapeutic">Rezervă-ți momentul <?= icon('arrow') ?></button><a class="text-link" href="#servicii">Servicii & prețuri <?= icon('diagonal') ?></a></div>
        <a href="<?= e($reviewsUrl) ?>" class="hero-social-proof" target="_blank" rel="noopener noreferrer"><span class="google-proof-icon"><img src="/assets/google-g.png" alt="Google" width="25" height="25"></span><span><span class="stars" aria-label="5 stele">★★★★★</span><span class="proof-label">Peste 60 de recenzii pe Google</span></span><?= icon('diagonal') ?></a>
        <div class="hero-contact-actions" aria-label="Contact și programări">
          <a class="hero-call" href="tel:+40773919071"><?= icon('phone') ?><span>Sună</span></a>
          <a class="hero-whatsapp" href="https://wa.me/40773919071" target="_blank" rel="noopener noreferrer"><img src="/assets/whatsapp-logo.svg" alt="" width="90" height="90"><span>WhatsApp</span></a>
          <button class="button button-dark" data-book>Programează-te <?= icon('calendar') ?></button>
        </div>
      </div>
    </div>
  </section>
  <section class="therapist-gallery-section" aria-label="Fotografii cu Alin în timpul ședințelor de masaj">
    <div class="container therapist-gallery-layout">
      <div class="therapist-gallery-intro"><p class="eyebrow">MASAJ TERAPEUTIC LA DOMICILIU</p><h2>Grijă, în fiecare <em>atingere.</em></h2><p>Alin Bughius, în timpul ședințelor de masaj.</p></div>
      <div class="hero-photo hero-gallery therapist-gallery" data-hero-gallery role="region" aria-roledescription="carusel" aria-label="Alin Bughius în timpul ședințelor de masaj">
        <div class="hero-gallery-track" data-hero-track>
          <figure class="hero-gallery-slide hero-gallery-slide--profile is-active" data-hero-slide aria-hidden="false"><img src="/assets/images/alin.jpg" alt="Alin Bughius, terapeutul care vine la tine acasă" width="1086" height="1448" loading="lazy" draggable="false"></figure>
          <figure class="hero-gallery-slide" data-hero-slide aria-hidden="true"><img src="/assets/images/hero-masaj-1.webp" alt="Ședință de masaj terapeutic realizată de Alin Bughius" width="1086" height="1448" loading="lazy" draggable="false"></figure>
          <figure class="hero-gallery-slide" data-hero-slide aria-hidden="true"><img src="/assets/images/hero-masaj-2.webp" alt="Mobilizare asistată în timpul unei ședințe de masaj" width="1087" height="1447" loading="lazy" draggable="false"></figure>
          <figure class="hero-gallery-slide" data-hero-slide aria-hidden="true"><img src="/assets/images/hero-masaj-3.webp" alt="Alin Bughius în timpul unui masaj de relaxare" width="1086" height="1448" loading="lazy" draggable="false"></figure>
          <figure class="hero-gallery-slide" data-hero-slide aria-hidden="true"><img src="/assets/images/hero-masaj-4.webp" alt="Masaj terapeutic pentru zona cervicală" width="1086" height="1448" loading="lazy" draggable="false"></figure>
          <figure class="hero-gallery-slide" data-hero-slide aria-hidden="true"><img src="/assets/images/hero-masaj-5.webp" alt="Ședință de reflexoterapie realizată de Alin Bughius" width="1152" height="2048" loading="lazy" draggable="false"></figure>
          <figure class="hero-gallery-slide" data-hero-slide aria-hidden="true"><img src="/assets/images/hero-masaj-6.webp" alt="Masaj de relaxare la domiciliu realizat de Alin Bughius" width="1152" height="2048" loading="lazy" draggable="false"></figure>
        </div>
        <button class="hero-gallery-arrow hero-gallery-prev" type="button" data-hero-prev aria-label="Fotografia precedentă"><?= icon('arrow') ?></button>
        <button class="hero-gallery-arrow hero-gallery-next" type="button" data-hero-next aria-label="Fotografia următoare"><?= icon('arrow') ?></button>
        <div class="hero-gallery-dots" role="group" aria-label="Alege fotografia"><?php for ($photo = 1; $photo <= 7; $photo++): ?><button type="button" data-hero-dot="<?= $photo - 1 ?>" aria-label="Fotografia <?= $photo ?> din 7" aria-current="<?= $photo === 1 ? 'true' : 'false' ?>"><span></span></button><?php endfor; ?></div>
        <span class="hero-gallery-count" aria-hidden="true"><b data-hero-current>01</b><i></i>07</span>
        <div class="hero-portrait-caption"><span><small>TERAPEUTUL TĂU</small><strong>Alin Bughius</strong><span>Cu grijă pentru oameni, din 2018.</span></span><a class="portrait-video-link" href="#in-miscare"><span>▶</span>Vezi cum lucrez</a></div>
        <p class="sr-only" data-hero-status aria-live="polite">Fotografia 1 din 7</p>
      </div>
    </div>
  </section>
  <section class="trust-strip" aria-label="Grijă pentru tine, la fiecare ședință"><div class="container">
    <div class="trust-item"><span class="trust-icon"><?= icon('award') ?></span><div><h3>Terapeut certificat</h3><p>Pregătire de specialitate</p></div></div>
    <div class="trust-item"><span class="trust-icon"><?= icon('home') ?></span><div><h3>Echipament inclus</h3><p>Vin cu tot ce este necesar</p></div></div>
    <div class="trust-item"><span class="trust-icon"><?= icon('heart') ?></span><div><h3>În ritmul tău</h3><p>Atenție la nevoile tale</p></div></div>
    <div class="trust-item"><span class="trust-icon"><?= icon('pin') ?></span><div><h3>București & Ilfov</h3><p>Starea de bine vine acasă</p></div></div>
  </div></section>

  <section class="reviews-section section" id="recenzii">
    <div class="container">
      <div class="section-heading compact"><div><p class="eyebrow">OAMENI REALI. EXPERIENȚE REALE.</p><h2>Ei au simțit <em>diferența.</em></h2></div><a class="google-review-link" href="<?= e($reviewsUrl) ?>" target="_blank" rel="noopener noreferrer"><img src="/assets/google-g.png" alt="Google" width="23" height="23"><span>Citește recenziile</span><?= icon('diagonal') ?></a></div>
      <div class="reviews-carousel" role="region" aria-roledescription="carusel" aria-label="Părerile clienților">
      <div class="reviews-grid" id="reviews-track" tabindex="0" aria-label="Recenzii Google. Glisează sau folosește săgețile pentru a le explora.">
        <?php foreach ([
          ['Ion Mai Antal', 'IA', 'Sunt foarte mulțumit de ședințele făcute în ultmii 2 ani. Felicitări încă o dată!'],
          ['Ana Vaduva', 'AV', 'Mă simt de fiecare dată cu forțe proaspete după un ritual de relaxare și detensionare. Recomand cu drag!'],
          ['Alina Lapanja', 'AL', 'Masajul pentru relaxare făcut ca la carte, mi-a dat stare de bine. Recomand cu căldură!'],
          ['Lavinia Gheorghiu', 'LG', 'Recomand să încercați o terapie cu Alin! Tehnicile pe care le cunoaște sunt foarte diferite față de ceea ce am mai încercat și a meritat pe deplin. Recomand'],
          ['Mitrofan Laura', 'ML', 'Am avut o experiență foarte plăcută. A venit punctual, a creat o atmosferă relaxantă și profesionistă iar masajul a fost exact ce aveam nevoie pentru durerile de spate de la birou. După ședință m-am simțit relaxată si revigorata. Recomand cu încredere!'],
          ['Alina Necula', 'AN', 'Am fost foarte mulțumita de serviciile oferite. Maseurul a demonstrat profesionalism, atenție și o bună cunoaștere a tehnicilor de masaj. Ședința a fost relaxantă și eficientă. Recomand cu încredere. Chiar aveam nevoie de un astfel de masaj!'],
          ['Ana-Maria Iordache', 'AI', 'O experienta excelenta. Masajul propriu-zis a fost exact ce aveam nevoie, o combinație perfectă între presiune fermă și mișcări relaxante. Alin a lucrat în mod special pe zona spatelui și a umerilor, unde acumulam cel mai mult stres. Daca suferi de dureri de spate, cervicale recomand cu incredere.'],
          ['IULIA LOREDANA Ganceanu', 'IG', 'O experiență excelentă de la început până la final. Masajul a fost exact ce aveam nevoie – realizat cu atenție, tehnică foarte bună și multă grijă pentru client. Recomand cu încredere tuturor celor care vor să se relaxeze cu adevărat.'],
        ] as $review): ?>
        <figure class="review"><div class="review-top"><div class="stars" aria-label="5 din 5 stele">★★★★★</div><img src="/assets/google-g.png" alt="Google" width="24" height="24"></div><blockquote>„<?= e($review[2]) ?>”</blockquote><figcaption><span class="review-avatar"><?= e($review[1]) ?></span><span><?= e($review[0]) ?><small>Recenzie de la client</small></span><?= icon('check') ?></figcaption></figure>
        <?php endforeach; ?>
      </div>
      <div class="reviews-controls" hidden><span class="reviews-counter"><strong>01</strong><span>/ 08</span></span><div class="reviews-dots" aria-label="Alege pagina de recenzii"></div><div class="reviews-arrows"><button type="button" data-review-prev aria-label="Recenzia precedentă" aria-controls="reviews-track"><?= icon('arrow') ?></button><button type="button" data-review-next aria-label="Recenzia următoare" aria-controls="reviews-track"><?= icon('arrow') ?></button></div></div>
      </div>
    </div>
  </section>

  <?php require APP_ROOT . '/app/sections/reels.php'; ?>

  <section class="services-section section" id="servicii">
    <div class="container">
      <div class="section-heading"><div><p class="eyebrow">ALEGE CE ÎȚI FACE BINE</p><h2>Un masaj potrivit <em>pentru tine.</em></h2><p>Serviciul, durata și prețul. Tot ce ai nevoie ca să alegi în liniște.</p></div><div class="pricing-toggle" role="group" aria-label="Tipul de tarif"><button class="active" data-pricing="single" aria-pressed="true">O ședință</button><button data-pricing="package" aria-pressed="false">Abonament <span>−10%</span></button></div></div>
      <div class="services-grid">
      <?php $idx = 0; foreach ($services as $key => $service): $extra = $idx++ >= 6; ?>
        <article class="service-card<?= $extra ? ' extra-service' : '' ?>"<?= $extra ? ' hidden' : '' ?>>
          <div class="service-image"><img src="/assets/images/<?= e($service['image']) ?>" alt="Ședință de <?= e(strtolower($service['name'])) ?>" width="720" height="480" loading="lazy"><?php if ($key === 'terapeutic'): ?><span class="service-badge">Pentru corpul tău, cu grijă</span><?php endif; ?><span class="duration"><?= icon('clock') ?> <?= e($service['duration']) ?></span></div>
          <div class="service-body"><p class="service-tag"><?= e($service['tag']) ?></p><h3><?= e($service['name']) ?></h3><p class="service-description"><?= e($service['description']) ?></p><?php if (!empty($service['benefits'])): ?><details class="service-more"><summary><?= !empty($service['pillars']) ? 'Vezi metoda și beneficiile' : 'Vezi beneficiile' ?> <span>+</span></summary><div class="service-more-content"><?php if (!empty($service['method_intro'])): ?><p><?= e($service['method_intro']) ?></p><?php endif; ?><?php if (!empty($service['pillars'])): ?><ul><?php foreach ($service['pillars'] as [$title, $text]): ?><li><strong><?= e($title) ?>:</strong> <?= e($text) ?></li><?php endforeach; ?></ul><h4>Ce obții?</h4><?php else: ?><h4>Beneficii</h4><?php endif; ?><ul><?php foreach ($service['benefits'] as $benefit): ?><li><?php if (is_array($benefit)): ?><strong><?= e($benefit[0]) ?>:</strong> <?= e($benefit[1]) ?><?php else: ?><?= e($benefit) ?><?php endif; ?></li><?php endforeach; ?></ul></div></details><?php endif; ?><div class="service-bottom"><div class="service-price"><strong data-single="<?= e($service['price']) ?>" data-package="<?= e($service['package']) ?>"><?= e($service['price']) ?></strong><span> lei<small data-session-count="<?= e($service['sessions']) ?>">/ ședință</small></span></div><button class="round-button" data-book="<?= e($key) ?>" aria-label="Programează <?= e($service['name']) ?>"><?= icon('diagonal') ?></button></div><p class="package-saving" hidden><?= e($service['sessions']) ?> ședințe · <s><?= e($service['price'] * $service['sessions']) ?> lei</s> · economisești <?= e($service['price'] * $service['sessions'] - $service['package']) ?> lei</p></div>
        </article>
      <?php endforeach; ?>
      </div>
      <div class="services-footer"><button class="button button-outline" id="show-services" aria-expanded="false">Vezi toate cele 8 tipuri de masaj <span>+</span></button><p><?= icon('pin') ?> Deplasare: +30 lei / vizită în sectoarele 1, 4, 5, 6 și Ilfov.<br>Fără taxă de deplasare în sectoarele 2 și 3.</p></div>
      <div class="subscription-banner"><div class="subscription-number">−10<span>%</span></div><div><p class="eyebrow">MAI MULT TIMP PENTRU STAREA TA DE BINE</p><h3>Grija pentru tine merită continuitate.</h3><p>Alege un abonament de 4 ședințe sau 6 ședințe pentru masaj anticelulitic.<br>Ai 10% reducere față de prețul ședințelor individuale.</p></div><button class="text-link" data-show-packages>Descoperă abonamentele <?= icon('arrow') ?></button></div>
    </div>
  </section>

  <?php require APP_ROOT . '/app/sections/home-experience.php'; ?>

  <section class="about-section section" id="despre"><div class="container about-grid">
    <div class="about-photo"><img src="/assets/images/alin.jpg" alt="Alin Bughius, terapeut maseur, în spațiul de lucru" width="1086" height="1448" loading="lazy"><div class="experience-badge"><span>Din 2018</span><small>cu grijă pentru oameni</small></div></div>
    <div class="about-copy"><p class="eyebrow">UN OM, ÎNAINTE DE TOATE</p><h2>Salut, sunt Alin.<br><em>Mă bucur să te cunosc.</em></h2><p>Născut pe 7 septembrie 1989 în județul Vaslui, am început călătoria mea profesională în masaj în 2018, după o perioadă de explorare și dezvoltare personală. Am absolvit Școala de Masaj Cristiana, iar de atunci fiecare sesiune de masaj mi-a întărit convingerea că aceasta este calea mea.</p><p>Consider că meseria de maseur este un dar primit de la Dumnezeu, iar prin munca mea mă conectez profund cu energia celor din jur. Îmi place să creez un spațiu de armonie și relaxare, iar fiecare sesiune este un proces unic în care mă las ghidat de flow-ul momentului.</p><div class="signature">Alin Bughius</div><div class="diplomas"><p><?= icon('award') ?> Pregătire pe care poți conta</p><div><?php foreach (['terapeutic' => 'Masaj terapeutic', 'deep-tissue' => 'Deep Tissue', 'lomi-lomi' => 'Lomi-Lomi'] as $slug => $label): ?><a href="/assets/images/diploma-<?= e($slug) ?>.jpg" class="diploma-link" data-lightbox data-caption="<?= e($label) ?>" target="_blank" rel="noopener"><img src="/assets/images/diploma-<?= e($slug) ?>.jpg" alt="Diplomă <?= e($label) ?>" width="180" height="120" loading="lazy"><span><?= e($label) ?> <?= icon('diagonal') ?></span></a><?php endforeach; ?></div></div></div>
  </div></section>

  <section class="how-section section"><div class="container"><div class="section-heading centered"><p class="eyebrow">SIMPLU, DE LA PRIMUL PAS</p><h2>Tu alegi momentul.<br><em>Eu vin la tine.</em></h2></div><div class="steps"><div><span class="step-number">01</span><?= icon('calendar') ?><h3>Alegi un interval liber</h3><p>Selectezi masajul, una sau mai multe sesiuni consecutive, apoi o zi și o oră disponibile în timp real.</p></div><div><span class="step-number">02</span><?= icon('chat') ?><h3>Primești confirmarea</h3><p>Intervalul este reținut imediat. Primești cererea pe e-mail, apoi confirmarea finală de la Alin.</p></div><div><span class="step-number">03</span><?= icon('home') ?><h3>Te bucuri de pauza ta</h3><p>Vin cu masa de masaj și materialele necesare. Tu pregătești un spațiu liniștit, în care te simți bine.</p></div></div></div></section>

  <section class="gift-section"><div class="container gift-inner"><span class="gift-icon"><?= icon('gift') ?></span><div><p class="eyebrow">UN CADOU CARE SE SIMTE</p><h2>Oferă cuiva drag <em>o pauză.</em></h2><p>Un voucher de masaj, un moment doar pentru el. Alegem împreună serviciul și valoarea.</p></div><a class="button button-dark" href="https://wa.me/40773919071?text=Bun%C4%83%2C%20Alin!%20A%C8%99%20dori%20un%20voucher%20cadou%20pentru%20masaj." target="_blank" rel="noopener noreferrer">Vreau un voucher cadou <?= icon('diagonal') ?></a></div></section>

  <?php require APP_ROOT . '/app/sections/session-guide.php'; ?>

  <section class="faq-section section"><div class="container faq-grid"><div><p class="eyebrow">CÂTEVA LUCRURI BINE DE ȘTIUT</p><h2>Ai întrebări?<br><em>E firesc.</em></h2><p>Îți răspund cu drag și personal.</p><a class="text-link" href="https://wa.me/40773919071" target="_blank" rel="noopener noreferrer">Scrie-mi pe WhatsApp <?= icon('arrow') ?></a></div><div class="faq-list">
    <details><summary>Ce trebuie să pregătesc pentru masaj?<span>+</span></summary><p>Un spațiu liniștit în care încape masa de masaj și pot lucra confortabil în jurul ei. Vin cu masa și materialele necesare; stabilim împreună orice alte detalii înainte de ședință.</p></details>
    <details><summary>În ce zone te deplasezi?<span>+</span></summary><p>În București și Ilfov. Pentru sectoarele 2 și 3, deplasarea este inclusă. În sectoarele 1, 4, 5, 6 și în Ilfov se adaugă 30 lei pentru fiecare vizită, inclusiv la abonamente.</p></details>
    <details><summary>Programarea este confirmată imediat?<span>+</span></summary><p>Calendarul afișează doar orele libere. După trimitere, intervalul ales este reținut și primești imediat cererea pe e-mail. Alin verifică detaliile și îți trimite separat confirmarea finală.</p></details>
    <details><summary>Cum funcționează abonamentul cu 10% reducere?<span>+</span></summary><p>Reducerea se aplică la suma ședințelor individuale: 4 ședințe pentru majoritatea serviciilor și 6 pentru masaj anticelulitic. De exemplu, 4 masaje terapeutice costă 720 lei, față de 800 lei individual. Taxa de deplasare, dacă este cazul, se adaugă la fiecare vizită. Stabilim împreună programul ședințelor și plata.</p></details>
    <details><summary>Nu știu ce tip de masaj să aleg.<span>+</span></summary><p>Scrie-mi sau sună-mă și discutăm înainte de programare. Dacă ai o problemă medicală ori recomandări de la medic, spune-mi înainte de ședință. Masajul nu înlocuiește consultația sau tratamentul medical.</p></details>
    <details><summary>Pot modifica data unei programări?<span>+</span></summary><p>Da, contactează-mă telefonic sau pe WhatsApp cât mai devreme, ca să putem găsi împreună un alt interval disponibil.</p></details>
  </div></div></section>

</main>
<footer class="footer" id="contact">
  <div class="container">
    <div class="footer-invitation">
      <div><p class="eyebrow"><span></span> STAREA DE BINE ÎNCEPE CU TINE</p><h2>Mai lasă lumea să aștepte.<br><em>Acum e timpul tău.</em></h2></div>
      <div class="footer-booking"><button class="button button-mint" data-book>Alege momentul tău <?= icon('arrow') ?></button><span>Un masaj bun. În confortul casei tale.</span></div>
    </div>
    <div class="footer-main">
      <div class="footer-brand-column">
        <a class="brand" href="/" aria-label="Alin Bughius — pagina principală"><img src="/assets/logo-mark.svg" alt="" width="53" height="53"><span>Alin Bughius<small>MASAJ & STARE DE BINE</small></span></a>
        <p>Grijă, atenție și un moment de liniște.<br>Masaj la domiciliu, în ritmul tău.</p>
        <span class="footer-area"><?= icon('pin') ?> București & Ilfov</span>
        <a class="footer-instagram" href="https://www.instagram.com/terapeut.alinbughius" target="_blank" rel="noopener noreferrer" aria-label="Urmărește-l pe Alin pe Instagram"><?= icon('instagram') ?><span>Ne vedem și pe Instagram</span><?= icon('diagonal') ?></a>
      </div>
      <nav class="footer-navigation" aria-label="Navigare în subsol"><h3>Descoperă</h3><a href="#servicii">Servicii & prețuri <?= icon('diagonal') ?></a><a href="#despre">Omul din spatele masajului <?= icon('diagonal') ?></a><a href="#recenzii">Poveștile clienților <?= icon('diagonal') ?></a><button data-book>Solicită o programare <?= icon('diagonal') ?></button></nav>
      <div class="footer-contact-column"><h3>Hai să vorbim</h3><a class="footer-phone" href="tel:+40773919071">0773 919 071 <?= icon('diagonal') ?></a><a class="footer-email" href="mailto:contact@alinbughius.ro">contact@alinbughius.ro</a><a class="footer-whatsapp" href="https://wa.me/40773919071" target="_blank" rel="noopener noreferrer"><span class="footer-chat-icon"><?= icon('chat') ?></span><span><strong>Mai simplu, pe WhatsApp</strong><small>Îmi spui ce ai nevoie. Găsim momentul.</small></span><?= icon('arrow') ?></a></div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Alin Bughius. Toate drepturile rezervate.</span><a href="/confidentialitate.php">Confidențialitate & cookies</a></div>
  </div>
  <div class="agency-signature"><a href="https://cab-it.ro/" target="_blank" rel="noopener noreferrer" aria-label="Designed by CAB-IT — cab-it.ro"><span>Designed by</span><img src="/assets/cab-it-symbol.webp" alt="" width="32" height="32" loading="lazy"><strong>cab-it.ro</strong></a></div>
</footer>
<div class="mobile-actions"><a class="mobile-call" href="tel:+40773919071"><?= icon('phone') ?><span>Sună</span></a><a class="mobile-whatsapp" href="https://wa.me/40773919071" target="_blank" rel="noopener noreferrer"><img src="/assets/whatsapp-logo.svg" alt="" width="37" height="37"><span>WhatsApp</span></a><button class="button button-dark" data-book>Programează-te <?= icon('calendar') ?></button></div>

<dialog id="booking-dialog" class="booking-dialog" aria-labelledby="booking-title"><div class="dialog-header"><a class="brand mini" href="/"><img src="/assets/logo-mark.svg" alt="" width="38" height="38"><span>Alin Bughius</span></a><button class="close-button" data-close aria-label="Închide formularul">×</button></div><div class="booking-intro"><p class="eyebrow">PROGRAMARE ONLINE, SIMPLĂ ȘI CLARĂ</p><h2 id="booking-title">Alege momentul <em>potrivit.</em></h2><p>Calendarul îți arată numai intervalele libere, actualizate în timp real.</p></div>
  <form id="booking-form" action="/api/booking.php" method="post">
    <input type="hidden" name="csrf" id="csrf"><input type="hidden" name="request_id" id="request-id">
    <div class="honey" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="form-grid"><label class="full">Ce tip de masaj îți dorești?<select name="service" id="service" required><?php foreach($bookingServices as $key => $s): ?><option value="<?= e($key) ?>" data-price="<?= e($s['price']) ?>" data-package="<?= e($s['package']) ?>" data-sessions="<?= e($s['sessions']) ?>" data-duration="<?= e($s['duration']) ?>" data-max-booking-sessions="<?= (int) $s['max_booking_sessions'] ?>" data-session-break="<?= (int) $s['session_break_minutes'] ?>" data-buffer="<?= (int) $s['buffer_minutes'] ?>"><?= e($s['name']) ?> · <?= e($s['duration']) ?></option><?php endforeach; ?></select></label>
      <input type="hidden" name="plan" value="single">
      <label class="full session-count-field">Câte sesiuni dorești?<select name="sessions" id="booking-sessions" required><option value="1">1 sesiune</option></select><small>Poți rezerva mai multe sesiuni consecutive, de exemplu pentru două persoane. Prețul și timpul se calculează automat.</small></label>
      <section class="availability-picker full" aria-labelledby="availability-title"><div class="availability-heading"><div><span>Pasul următor</span><strong id="availability-title">Alege ziua și ora</strong></div><small>Ora României</small></div><input type="hidden" name="date" id="booking-date" required><input type="hidden" name="time" id="booking-time" required><div id="available-days" class="available-days" aria-label="Zile disponibile"></div><div id="available-times" class="available-times" aria-label="Ore disponibile"></div><p id="availability-status" class="availability-status" role="status" aria-live="polite">Încărcăm calendarul disponibil…</p></section>
      <label>Numele tău<input name="name" autocomplete="name" placeholder="Prenume și nume" minlength="2" maxlength="100" required></label><label>Telefon<input type="tel" name="phone" autocomplete="tel" placeholder="07xx xxx xxx" minlength="8" maxlength="22" required></label>
      <label class="full">Adresa ta de e-mail<input type="email" name="email" autocomplete="email" placeholder="nume@exemplu.ro" maxlength="180" required><small>Aici primești cererea înregistrată și confirmarea programării.</small></label>
      <label class="full">Unde are loc ședința?<select name="zone" id="zone" required><option value="">Alege sectorul sau Ilfov</option><option value="sector-1">București, Sector 1 · +30 lei / vizită</option><option value="sector-2">București, Sector 2 · deplasare inclusă</option><option value="sector-3">București, Sector 3 · deplasare inclusă</option><option value="sector-4">București, Sector 4 · +30 lei / vizită</option><option value="sector-5">București, Sector 5 · +30 lei / vizită</option><option value="sector-6">București, Sector 6 · +30 lei / vizită</option><option value="ilfov">Ilfov · +30 lei / vizită</option></select></label>
      <p class="field-note full">Stabilim adresa exactă când te contactez. Nu este necesar să trimiți informații medicale aici.</p>
    </div>
    <div class="booking-estimate" aria-live="polite"><div><span id="estimate-label">O ședință · 60 min</span><strong><span id="estimate-price">200</span> lei</strong></div><p id="estimate-note">Alege zona pentru calculul deplasării.</p></div>
    <label class="privacy-check"><input type="checkbox" name="privacy" value="1" required><span>Am citit <a href="/confidentialitate.php" target="_blank" rel="noopener">informarea privind datele personale</a> și sunt de acord să fiu contactat pentru această solicitare.</span></label>
    <p class="form-status" id="form-status" role="status" aria-live="polite"></p>
    <button class="button button-dark submit-button" type="submit">Trimite cererea de programare <?= icon('arrow') ?></button><p class="form-disclaimer">Fără plată online. Intervalul este reținut imediat și confirmat apoi de Alin.</p>
  </form><div class="booking-success" id="booking-success" hidden tabindex="-1"><div class="success-icon"><?= icon('check') ?></div><h3>Intervalul este al tău.</h3><p id="success-message"></p><p>Alin verifică detaliile și îți trimite confirmarea finală pe e-mail.</p><a class="button button-outline" href="https://wa.me/40773919071" target="_blank" rel="noopener noreferrer">Vorbește cu Alin <?= icon('chat') ?></a><button class="text-link" type="button" data-close>Înapoi la site</button></div>
</dialog>
<dialog id="image-dialog" class="image-dialog" aria-label="Diplomă profesională"><button class="close-button" data-close aria-label="Închide diploma">×</button><img id="diploma-image" alt="Diplomă profesională"><p id="diploma-caption"></p></dialog>
</body>
</html>
