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
  return {scripts, events, calls:window.dataLayer || []};
}
assert.equal(load('localhost').scripts.length, 0);
assert.equal(load('alinbughius.ro', '/confirmare.php').scripts.length, 0);
assert.equal(load('alinbughius.ro', '/confidentialitate.php').scripts.length, 0);
const state = load();
assert.equal(state.scripts.length, 1);
assert.match(state.scripts[0].src, /id=AW-18478963280$/);
assert.equal(state.calls.filter(call => call[0] === 'consent').length, 0, 'No fabricated visitor consent');
const configs = state.calls.filter(call => call[0] === 'config');
assert.equal(configs.map(call => call[1]).join(','), 'AW-18478963280,AW-11103141014');
const config = configs[0];
assert.equal(config[2].allow_ad_personalization_signals, false);
assert.equal(config[2].page_location, 'https://alinbughius.ro/');
const campaign = load('alinbughius.ro', '/', '?gclid=campaign-test&email=private@example.com&token=secret');
assert.equal(campaign.calls.find(call => call[0] === 'config')[2].page_location, 'https://alinbughius.ro/?gclid=campaign-test');
const click = (href, trusted = true) => state.events.click({isTrusted:trusted,target:{closest:() => ({href})}});
click('https://www.instagram.com/terapeut.alinbughius');
click('tel:+40773919071', false);
assert.equal(state.calls.filter(call => call[0] === 'event').length, 0);
click('tel:+40773919071');
click('https://wa.me/40773919071?text=example');
const conversions = state.calls.filter(call => call[0] === 'event');
assert.equal(conversions.length, 2);
for (const conversion of conversions) {
  assert.equal(conversion[1], 'conversion');
  assert.equal(conversion[2].send_to, 'AW-11103141014/sLiECKP109YZEJb5sa4p');
  assert.deepEqual(Object.keys(conversion[2]).sort(), ['send_to', 'transport_type']);
}
console.log('PASS: current and legacy Ads IDs, contact conversions, production-only guard, no form data and no fabricated consent. No Google requests sent.');
