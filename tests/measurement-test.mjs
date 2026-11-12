import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source = readFileSync('public/assets/measurement.js', 'utf8');
function load(hostname = 'alinbughius.ro', pathname = '/', query = '') {
  const scripts = [], events = {};
  const window = {};
  const context = {window, location:{hostname, pathname, origin:`https://${hostname}`,href:`https://${hostname}${pathname}${query}`}, URL, Date,
    document:{head:{append:script => scripts.push(script)}, createElement:() => ({}), addEventListener:(name, handler) => events[name] = handler}};
  vm.runInNewContext(source, context);
  return {scripts, events, calls:window.dataLayer || [], window, rerun:() => vm.runInNewContext(source, context)};
}
assert.equal(load('localhost').scripts.length, 0);
assert.equal(load('127.0.0.1').scripts.length, 0);
assert.equal(load('alinbughius.ro', '/admin/').scripts.length, 0);
assert.equal(load('alinbughius.ro', '/confirmare.php').scripts.length, 0);
assert.equal(load('alinbughius.ro', '/confidentialitate.php').scripts.length, 0);
const state = load();
assert.equal(state.scripts.length, 1);
assert.match(state.scripts[0].src, /id=AW-18478963280$/);
assert.equal(state.calls.filter(call => call[0] === 'consent').length, 0, 'No fabricated visitor consent');
const configs = state.calls.filter(call => call[0] === 'config');
assert.equal(configs.map(call => call[1]).join(','), 'AW-18478963280,AW-11103141014,G-E0L7GVF1B2');
assert.equal(typeof state.window.gtag, 'function');
state.rerun();
assert.equal(state.scripts.length, 1, 'Only one Google tag loader');
assert.equal(state.calls.filter(call => call[0] === 'config').length, 3, 'No duplicate config or page views');
const config = configs[0];
assert.equal(config[2].allow_ad_personalization_signals, false);
assert.equal(config[2].page_location, 'https://alinbughius.ro/');
const campaign = load('alinbughius.ro', '/', '?gclid=campaign-test&email=private@example.com&token=secret');
assert.equal(campaign.calls.find(call => call[0] === 'config')[2].page_location, 'https://alinbughius.ro/?gclid=campaign-test');
for (const [, , options] of campaign.calls.filter(call => call[0] === 'config')) {
  assert.equal(options.page_location, 'https://alinbughius.ro/?gclid=campaign-test');
  assert.equal(options.page_referrer, '');
  assert.equal(options.allow_google_signals, false);
  assert.equal(options.allow_ad_personalization_signals, false);
}
const click = (href, trusted = true) => state.events.click({isTrusted:trusted,target:{closest:() => ({href})}});
click('https://www.instagram.com/terapeut.alinbughius');
click('tel:+40773919071', false);
assert.equal(state.calls.filter(call => call[0] === 'event').length, 0);
click('tel:+40773919071');
click('https://wa.me/40773919071?text=example');
const conversions = state.calls.filter(call => call[0] === 'event' && call[1] === 'conversion');
assert.equal(conversions.length, 3);
const legacyConversions = conversions.filter(call => call[2].send_to === 'AW-11103141014/sLiECKP109YZEJb5sa4p');
assert.equal(legacyConversions.length, 2);
for (const conversion of legacyConversions) assert.deepEqual(Object.keys(conversion[2]).sort(), ['send_to', 'transport_type']);
const phoneConversion = conversions.find(call => call[2].send_to === 'AW-18478963280/4uWiCIHdobcdENC0uutE');
assert.ok(phoneConversion, 'The active Ads account receives the phone-click conversion');
assert.equal(phoneConversion[2].value, 1);
assert.equal(phoneConversion[2].currency, 'RON');
assert.deepEqual(Object.keys(phoneConversion[2]).sort(), ['currency', 'send_to', 'transport_type', 'value']);
const contacts = state.calls.filter(call => call[0] === 'event' && call[1] === 'contact_click');
assert.equal(contacts.length, 2);
assert.equal(contacts.map(call => call[2].contact_method).join(','), 'phone,whatsapp');
for (const contact of contacts) {
  assert.equal(contact[2].send_to, 'G-E0L7GVF1B2');
  assert.deepEqual(Object.keys(contact[2]).sort(), ['contact_method', 'send_to', 'transport_type']);
}
const channelEvents = state.calls.filter(call => call[0] === 'event' && ['phone_click', 'whatsapp_click'].includes(call[1]));
assert.equal(channelEvents.map(call => call[1]).join(','), 'phone_click,whatsapp_click');
for (const channelEvent of channelEvents) {
  assert.equal(channelEvent[2].send_to, 'G-E0L7GVF1B2');
  assert.deepEqual(Object.keys(channelEvent[2]).sort(), ['send_to', 'transport_type']);
}
console.log('PASS: active Ads phone conversion plus legacy contact conversion and separate GA4 contact channels share one loader, with production-only guard, sanitized metadata and no fabricated consent. No Google requests sent.');
