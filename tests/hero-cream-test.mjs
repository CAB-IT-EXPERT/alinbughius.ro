import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const {chromium} = createRequire(import.meta.url)('playwright');

// Read-only browser QA. Block Google requests and never submit a booking or call.
const origin = process.argv[2] || 'http://127.0.0.1:8077/';
const capture = process.argv.includes('--screenshots');
const executablePath = process.env.CHROME_PATH || (process.platform === 'win32' ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : undefined);
const browser = await chromium.launch({headless:true, executablePath});
async function checkServiceTypography(page, width, height) {
  const type = await page.evaluate(width => {
    const list = document.querySelector(width <= 700 ? '.hero-service-list--mobile' : '.hero-service-list--desktop');
    const intro = getComputedStyle(document.querySelector('.hero-service-intro'));
    return {
      introSize:parseFloat(intro.fontSize),introShadow:intro.textShadow,
      panelCount:document.querySelectorAll('.hero-service-copy').length,
      listBackground:getComputedStyle(list).backgroundColor,
      services:[...list.children].map(el=>{
        const style=getComputedStyle(el);
        return {size:parseFloat(style.fontSize),shadow:style.textShadow,background:style.backgroundColor};
      })
    };
  }, width);
  const clamp = (min,value,max) => Math.min(max,Math.max(min,value));
  const originalList = width <= 1100 ? Math.max(10.5,.0275*width) : height <= 950 ? clamp(14,.021*height,19) : clamp(17,.0135*width,23);
  const originalIntro = width <= 1100 ? Math.max(12,.0312*width) : height <= 950 ? clamp(16,.0245*height,22) : clamp(19,.016*width,27);
  assert.ok(Math.abs(type.introSize-originalIntro*1.2)<.02, 'Description must be exactly 20% larger');
  assert.equal(type.introShadow,'none','No shadow on the description');
  assert.equal(type.panelCount,0,'No translucent copy panel');
  assert.equal(type.listBackground,'rgba(0, 0, 0, 0)');
  assert.equal(type.services.length,width <= 700 ? 6 : 4);
  for (const service of type.services) {
    const expectedSize = width <= 700 ? Math.max(14,.0405*width) : originalList*1.25;
    assert.ok(Math.abs(service.size-expectedSize)<.02, `Service size mismatch at ${width}x${height}`);
    assert.equal(service.background,'rgba(0, 0, 0, 0)');
    if (width <= 700) {
      const shadows = [...service.shadow.matchAll(/rgb\(255, 255, 255\) (-?[\d.]+)px (-?[\d.]+)px ([\d.]+)px/g)];
      assert.equal(shadows.length,8,'White halo surrounds every glyph on mobile');
      assert.ok(shadows.every(([,x,y,blur])=>Math.abs(+x)<=1&&Math.abs(+y)<=1&&+blur===0),'Halo must remain tight, without a broad blur');
    } else assert.equal(service.shadow,'none','Phone-only shadow');
  }
}
try {
  const page = await browser.newPage({viewport:{width:390,height:900}, reducedMotion:'reduce'});
  await page.route(/https:\/\/[^/]*(googletagmanager|google-analytics|googleadservices|doubleclick)\./, route => route.abort());
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const response = await page.goto(origin, {waitUntil:'networkidle'});
  const csp = response.headers()['content-security-policy'];
  assert.match(csp, /connect-src[^;]*https:\/\/\*\.google-analytics\.com/);
  assert.match(csp, /img-src[^;]*https:\/\/\*\.google-analytics\.com/);
  assert.doesNotMatch(csp, /unsafe-inline|unsafe-eval/);
  const privateResponse = await page.request.get(new URL('/confidentialitate.php', origin).href);
  assert.doesNotMatch(privateResponse.headers()['content-security-policy'], /google|doubleclick/);
  await page.evaluate(() => document.fonts.ready);
  assert.equal(await page.locator('.header .brand small').textContent(),'MASAJ & STARE DE BINE');
  assert.equal(await page.locator('.hero-benefits li:last-child strong').textContent(),'Masaj și stare de bine fără să pleci de acasă.');
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
      const clipped = [...document.querySelectorAll('.hero-title span,.hero-service-list li,.hero-fact,.hero-benefits li,.hero-contact-actions>*,.steps>div')]
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
        heroControls:[...document.querySelectorAll('.hero-contact-actions>*')].map(item=>({height:item.getBoundingClientRect().height,display:getComputedStyle(item).display})),
        mobileActionsBackground:style('.mobile-actions').backgroundColor,
        fixedControls:[...document.querySelectorAll('.mobile-actions>*')].map(item=>({tag:item.tagName,height:item.getBoundingClientRect().height,width:item.getBoundingClientRect().width,position:getComputedStyle(item).position,visibility:getComputedStyle(item).visibility,borderRadius:getComputedStyle(item).borderRadius}))
      };
    });
    assert.equal(layout.overflow, 0, `Page overflow at ${width}`);
    assert.deepEqual(layout.clipped, [], `Clipped content at ${width}`);
    assert.equal(layout.textTouchesPanel, false, `Copy overlaps price panel at ${width}`);
    assert.match(layout.photo, width <= 700 ? /hero-spa-mobile-v2.webp$/ : /hero-spa-desktop-v2.webp$/);
    assert.equal(layout.panelBackground, width <= 700 ? 'rgba(255, 248, 233, 0.76)' : 'rgba(255, 247, 228, 0.7)', 'Preserve responsive panel transparency');
    assert.equal(layout.panelBlur, 'saturate(1.08) blur(2px)');
    assert.equal(layout.heroBackground, width <= 700 ? 'rgb(248, 241, 229)' : 'rgb(246, 240, 228)');
    assert.equal(layout.galleryBackground, 'rgb(10, 73, 58)', 'Gallery must remain green');
    assert.equal(layout.proofBackground, 'rgba(0, 0, 0, 0)');
    if (width <= 700) {
      assert.ok(layout.heroControls.every(control => control.height === 0), 'Hero contact row is removed on mobile');
      assert.ok(layout.fixedControls.every(control => control.position === 'fixed' && control.visibility === 'visible' && control.height >= 52), 'Permanent mobile controls');
      assert.equal(layout.mobileActionsBackground,'rgba(0, 0, 0, 0)','No fixed bottom bar');
      assert.equal(layout.fixedControls.length,3,'Phone, WhatsApp and booking only');
      assert.ok(layout.fixedControls.slice(0,2).every(control=>control.tag==='A'&&Math.abs(control.width-control.height)<1),'Round contact buttons');
      assert.ok(layout.fixedControls[2].tag==='BUTTON'&&layout.fixedControls[2].width>layout.fixedControls[2].height*3,'Rectangular booking button');
      assert.equal(await page.locator('.hero-mobile-benefits>li').count(),4);
      assert.equal(await page.locator('.hero-service-list--mobile>li').count(),6);
    } else {
      assert.ok(layout.heroControls.every(control => control.height >= 44), `Small contact control at ${width}`);
      assert.ok(layout.fixedControls.every(control => control.visibility === 'hidden' || control.height === 0), 'No floating controls on laptop');
    }
    assert.ok(parseFloat(layout.cardPadding) >= 24 && parseFloat(layout.cardRadius) >= 20);
    if (width > 700) assert.ok(Math.max(...layout.cards.map(c=>c.height)) - Math.min(...layout.cards.map(c=>c.height)) < 1, 'Equal-height step cards');
    await checkServiceTypography(page,width,1000);
    if (capture && [390,768,1366,1672].includes(width)) {
      await page.setViewportSize({width,height:Math.max(1000,Math.ceil(layout.hero.bottom + 90))});
      await page.screenshot({path:`.runtime/cream-hero-${width}.png`,clip:{x:0,y:0,width,height:Math.ceil(layout.hero.bottom + 90)},animations:'disabled'});
      await page.locator('.how-section').screenshot({path:`.runtime/cream-steps-${width}.png`,animations:'disabled'});
    }
    console.log(`PASS: ${width}px, correct image, panel transparency, no overflow/overlap, step cards`);
  }
  for (const [width,height] of [[1280,600],[1280,720],[1366,650],[1366,768],[1440,800],[1536,864],[1903,907],[1920,900]]) {
    await page.setViewportSize({width,height});
    await page.evaluate(async()=>{scrollTo(0,0);await new Promise(requestAnimationFrame);await document.querySelector('.hero-static-photo img').decode();});
    const fit = await page.evaluate(()=>{
      const panel = document.querySelector('.hero-details').getBoundingClientRect();
      const overlap = [...document.querySelectorAll('.hero-title,.hero-service-intro,.hero-service-list li,.hero-choice')].some(item=>{
        const range = document.createRange();range.selectNodeContents(item);
        return [...range.getClientRects()].some(r=>r.width&&r.height&&r.left<panel.right&&r.right>panel.left&&r.top<panel.bottom&&r.bottom>panel.top);
      });
      return {bottom:document.querySelector('.hero').getBoundingClientRect().bottom,overlap,overflow:document.documentElement.scrollWidth-innerWidth,controls:[...document.querySelectorAll('.hero-contact-actions>*')].map(item=>item.getBoundingClientRect().bottom)};
    });
    assert.ok(fit.bottom <= height+1, `Whole hero must fit ${width}x${height}: bottom=${fit.bottom}`);
    assert.ok(fit.controls.every(bottom=>bottom <= height-10), 'Buttons need breathing room inside first view');
    assert.equal(fit.overlap, false, `Laptop copy overlaps panel at ${width}x${height}`);
    assert.equal(fit.overflow, 0);
    await checkServiceTypography(page,width,height);
    if (capture) await page.screenshot({path:`.runtime/cream-laptop-${width}x${height}.png`,animations:'disabled'});
    console.log(`PASS: complete hero inside first view at ${width}x${height}`);
  }
  await page.setViewportSize({width:390,height:900});
  await page.locator('.mobile-actions button[data-book]').click();
  assert.equal(await page.locator('#booking-dialog').evaluate(el=>el.open), true);
  await page.locator('#booking-dialog .close-button').click();
  await page.evaluate(()=>scrollTo(0,0));
  await page.locator('.menu-toggle').click();
  assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'true');
  assert.equal(await page.locator('.mobile-actions').evaluate(el=>getComputedStyle(el).visibility), 'hidden');
  await page.locator('#main-nav a[href="#intrebari"]').click();
  assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'false');
  assert.equal(await page.evaluate(()=>location.hash), '#intrebari');
  assert.equal(await page.locator('#intrebari').count(), 1);
  assert.equal(await page.locator('.mobile-call').getAttribute('href'), 'tel:+40773919071');
  assert.equal(await page.locator('.mobile-whatsapp').getAttribute('href'), 'https://wa.me/40773919071');
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
