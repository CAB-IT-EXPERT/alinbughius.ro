const path = require('path');
const { pathToFileURL } = require('url');
const { chromium } = require('playwright');

const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, '.runtime', 'manual-assets');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const base = 'http://127.0.0.1:8077';

async function login(page) {
  await page.goto(`${base}/admin/`, { waitUntil: 'networkidle' });
  if (await page.locator('input[name="username"]').count()) {
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('admin');
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');
  }
}

async function shot(page, name, options = {}) {
  await page.screenshot({ path: path.join(OUT, name), animations: 'disabled', ...options });
}

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: CHROME });
  const context = await browser.newContext({ viewport: { width: 1440, height: 960 }, deviceScaleFactor: 1 });
  const page = await context.newPage();

  await page.goto(base, { waitUntil: 'networkidle' });
  await page.evaluate(() => {
    const dialog = document.querySelector('#booking-dialog');
    if (dialog && !dialog.open) dialog.showModal();
  });
  await page.waitForTimeout(900);
  await shot(page, 'public-booking.png');

  await login(page);
  await page.goto(`${base}/admin/?view=bookings`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
  await shot(page, 'crm-bookings.png');

  const firstCard = page.locator('.booking-card summary').first();
  await firstCard.click();
  await page.waitForTimeout(450);
  await shot(page, 'crm-booking-popup.png');
  await page.locator('[data-crm-close]').first().click().catch(() => {});
  await page.waitForTimeout(250);

  await page.locator('.manual-booking-open').click();
  await page.waitForTimeout(350);
  await shot(page, 'crm-manual-popup.png');
  await page.locator('[data-manual-close]').first().click();
  await page.waitForTimeout(250);

  await page.locator('.report-export-open').click();
  await page.waitForTimeout(350);
  await shot(page, 'crm-export-popup.png');
  await page.locator('[data-export-close]').first().click();

  await page.goto(`${base}/admin/?view=schedule`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  await shot(page, 'crm-schedule.png');

  await page.goto(`${base}/admin/?view=services`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  await shot(page, 'crm-services.png');

  await page.goto(`${base}/admin/?view=security`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  await shot(page, 'crm-security.png');

  const emailPage = await context.newPage();
  for (const [file, name] of [
    ['email-owner.html', 'email-owner.png'],
    ['email-client-receipt.html', 'email-client-receipt.png'],
    ['email-client-confirmed.html', 'email-client-confirmed.png'],
  ]) {
    await emailPage.goto(pathToFileURL(path.join(OUT, file)).href, { waitUntil: 'load' });
    await emailPage.setViewportSize({ width: 1160, height: 900 });
    await emailPage.waitForTimeout(250);
    await shot(emailPage, name, { fullPage: true });
  }

  const mobileContext = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 });
  const mobile = await mobileContext.newPage();
  await login(mobile);
  await mobile.goto(`${base}/admin/?view=bookings`, { waitUntil: 'networkidle' });
  await mobile.waitForTimeout(450);
  await shot(mobile, 'crm-mobile.png');
  await mobileContext.close();
  await context.close();
  await browser.close();
  process.stdout.write(`${OUT}\n`);
})().catch(error => {
  console.error(error);
  process.exit(1);
});
