import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const {chromium} = createRequire(import.meta.url)('playwright');

// Read-only browser QA. Block Google requests and never submit a booking or call.
const origin = process.argv[2] || 'http://127.0.0.1:8077/';
const capture = process.argv.includes('--screenshots');
const executablePath = process.env.CHROME_PATH || (process.platform === 'win32' ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : undefined);
const browser = await chromium.launch({headless:true, executablePath});
try {
  const page = await browser.newPage({viewport:{width:390,height:900}, reducedMotion:'reduce'});
  await page.route(/https:\/\/[^/]*(googletagmanager|google-analytics|googleadservices|doubleclick)\./, route => route.abort());
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(origin, {waitUntil:'networkidle'});
  await page.evaluate(() => document.fonts.ready);
  if (capture) await mkdir('.runtime', {recursive:true});
  for (const width of [320,375,390,430,700,701,768,1024,1100,1101,1280,1366,1440,1672,1920]) {
    await page.setViewportSize({width,height:1000});
    await page.evaluate(async () => {
      scrollTo(0,0);
      await new Promise(requestAnimationFrame);
      await document.querySelector('.hero-static-photo img').decode();
    });
    const layout = await page.evaluate(() => {
      const el = selector => document.querySelector(selector);
      const style = selector => getComputedStyle(el(selector));
      const bounds = selector => {
        const r = el(selector).getBoundingClientRect();
        return {x:r.x,y:r.y,width:r.width,height:r.height,bottom:r.bottom};
      };
      const clipped = [...document.querySelectorAll('.hero-title span,.hero-fact,.hero-benefits li,.hero-contact-actions>*,.steps>div')]
        .filter(item => item.scrollWidth > item.clientWidth + 2).map(item => item.className);
      const textTouchesPanel = [...document.querySelectorAll('.hero-title,.hero-service-intro,.hero-service-list li,.hero-choice')].some(item => {
        const range = document.createRange();
        range.selectNodeContents(item);
        const panel = el('.hero-details').getBoundingClientRect();
        return [...range.getClientRects()].some(r => r.width && r.height && r.left < panel.right && r.right > panel.left && r.top < panel.bottom && r.bottom > panel.top);
      });
      return {
        overflow:document.documentElement.scrollWidth - innerWidth, clipped, textTouchesPanel,
        photo:el('.hero-static-photo img').currentSrc,
        panelBackground:style('.hero-details').backgroundColor,
        panelBlur:style('.hero-details').backdropFilter,
        heroBackground:style('.hero').backgroundColor,
        galleryBackground:style('.therapist-gallery-section').backgroundColor,
        proofBackground:style('.hero-social-proof').backgroundColor,
        header:bounds('.header'),hero:bounds('.hero'),panel:bounds('.hero-details'),
        proof:bounds('.hero-social-proof'),booking:bounds('.hero-booking-button'),
        cardPadding:style('.steps>div').paddingLeft,cardRadius:style('.steps>div').borderTopLeftRadius,
        cards:[...document.querySelectorAll('.steps>div')].map(item=>({height:item.getBoundingClientRect().height})),
        controlHeights:[...document.querySelectorAll('.hero-contact-actions>*')].map(item=>item.getBoundingClientRect().height)
      };
    });
    assert.equal(layout.overflow, 0, `Page overflow at ${width}`);
    assert.deepEqual(layout.clipped, [], `Clipped content at ${width}`);
    assert.equal(layout.textTouchesPanel, false, `Copy overlaps price panel at ${width}`);
    assert.match(layout.photo, width <= 1100 ? /hero-cream-mobile.webp$/ : /hero-cream-desktop.webp$/);
    assert.equal(layout.panelBackground, 'rgba(255, 247, 228, 0.7)', 'Preserve approved transparency');
    assert.equal(layout.panelBlur, 'saturate(1.08) blur(2px)');
    assert.equal(layout.heroBackground, 'rgb(246, 240, 228)');
    assert.equal(layout.galleryBackground, 'rgb(10, 73, 58)', 'Gallery must remain green');
    assert.equal(layout.proofBackground, 'rgba(0, 0, 0, 0)');
    assert.ok(layout.controlHeights.every(height => height >= 44), `Small contact control at ${width}`);
    assert.ok(parseFloat(layout.cardPadding) >= 24 && parseFloat(layout.cardRadius) >= 20);
    if (width > 700) assert.ok(Math.max(...layout.cards.map(c=>c.height)) - Math.min(...layout.cards.map(c=>c.height)) < 1, 'Equal-height step cards');
    if (capture && [390,768,1366,1672].includes(width)) {
      await page.setViewportSize({width,height:Math.max(1000,Math.ceil(layout.hero.bottom + 90))});
      await page.screenshot({path:`.runtime/cream-hero-${width}.png`,clip:{x:0,y:0,width,height:Math.ceil(layout.hero.bottom + 90)},animations:'disabled'});
      await page.locator('.how-section').screenshot({path:`.runtime/cream-steps-${width}.png`,animations:'disabled'});
    }
    console.log(`PASS: ${width}px, correct image, panel transparency, no overflow/overlap, step cards`);
  }
  await page.setViewportSize({width:390,height:900});
  await page.locator('.hero-booking-button').click();
  assert.equal(await page.locator('#booking-dialog').evaluate(el=>el.open), true);
  await page.locator('#booking-dialog .close-button').click();
  await page.evaluate(()=>scrollTo(0,0));
  await page.locator('.menu-toggle').click();
  assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'true');
  await page.locator('#main-nav a[href="#intrebari"]').click();
  assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'false');
  assert.equal(await page.evaluate(()=>location.hash), '#intrebari');
  assert.equal(await page.locator('#intrebari').count(), 1);
  assert.equal(await page.locator('.hero-call').getAttribute('href'), 'tel:+40773919071');
  assert.equal(await page.locator('.hero-whatsapp').getAttribute('href'), 'https://wa.me/40773919071');
  assert.equal(await page.locator('[data-hero-slide]').count(), 7);
  await page.locator('[data-hero-next]').click();
  assert.equal(await page.locator('[data-hero-current]').textContent(), '02');
  if (new URL(origin).hostname === 'alinbughius.ro') {
    const configs = await page.evaluate(()=>(window.dataLayer||[]).filter(call=>call[0]==='config').map(call=>call[1]));
    assert.deepEqual(configs, ['AW-18478963280','AW-11103141014','G-E0L7GVF1B2']);
    assert.equal(await page.locator('script[src*="googletagmanager.com/gtag/js"]').count(), 1);
  }
  assert.deepEqual(errors, []);
  console.log('PASS: menu, FAQ anchor, booking dialog, contact destinations, seven gallery images. No booking submitted or Google requests sent.');
} finally {
  await browser.close();
}
