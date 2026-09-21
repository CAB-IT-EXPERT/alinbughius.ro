import assert from 'node:assert/strict';
import {mkdirSync, writeFileSync} from 'node:fs';

const origin = 'http://127.0.0.1:8078';
let cookie = '';
const request = async (path, options = {}) => {
  const response = await fetch(origin + path, {...options, redirect: options.redirect || 'manual', headers:{...options.headers, Cookie:cookie}});
  const session = response.headers.getSetCookie().find(value => value.startsWith('alin_admin='));
  if (session) cookie = session.split(';')[0];
  return response;
};
const post = (path, data) => request(path, {method:'POST',body:new URLSearchParams(data)});
const loginPage = await (await fetch(origin + '/admin/')).text();
assert.match(loginPage, /Ține-mă minte/);
const protectedExport = await fetch(origin + '/admin/export.php?period=all', {redirect:'manual'});
assert.equal(protectedExport.status, 302);
const login = await post('/admin/', {action:'login',username:'admin',password:'admin',remember:'1'});
assert.equal(login.status, 303);
const rememberCookie = login.headers.getSetCookie().find(value => value.startsWith('alin_admin_remember='));
assert.ok(rememberCookie && /expires=/i.test(rememberCookie) && /httponly/i.test(rememberCookie));
const persistentDashboard = await fetch(origin + '/admin/?view=dashboard', {headers:{Cookie:rememberCookie.split(';')[0]}});
assert.equal(persistentDashboard.status, 200);
assert.match(await persistentDashboard.text(), /Tot ce urmează/);
const dashboard = await (await request('/admin/?view=dashboard', {redirect:'follow'})).text();
assert.match(dashboard, /Tot ce urmează/);
const csrf = /name="csrf" value="([^"]+)"/.exec(dashboard)?.[1];
assert.ok(csrf);
const schedule = await post('/admin/?view=schedule', {
  csrf, action:'save_schedule', horizon_days:'90', minimum_notice_minutes:'180', slot_step_value:'30', slot_step_unit:'minutes',
  'day[1][enabled]':'1','day[1][start]':'09:00','day[1][end]':'20:00',
  'day[2][enabled]':'1','day[2][start]':'09:00','day[2][end]':'20:00',
  'day[3][enabled]':'1','day[3][start]':'09:00','day[3][end]':'20:00',
  'day[4][enabled]':'1','day[4][start]':'09:00','day[4][end]':'20:00',
  'day[5][enabled]':'1','day[5][start]':'09:00','day[5][end]':'20:00',
  'day[6][start]':'09:00','day[6][end]':'20:00','day[7][start]':'09:00','day[7][end]':'20:00'
});
assert.equal(schedule.status, 303);
const availability = await (await fetch(origin + '/api/availability.php?service=terapeutic')).json();
assert.ok(availability.ok && availability.days.length);
const exceptionDate = availability.days.at(-1).date;
assert.equal((await post('/admin/?view=schedule', {csrf,action:'save_exception',exception_date:exceptionDate,exception_type:'closed',exception_note:'Test automat'})).status, 303);
assert.deepEqual((await (await fetch(origin + `/api/availability.php?service=terapeutic&date=${exceptionDate}`)).json()).slots, []);
assert.equal((await post('/admin/?view=schedule', {csrf,action:'remove_exception',date:exceptionDate})).status, 303);
let bookings = await (await request('/admin/?view=bookings', {redirect:'follow'})).text();
assert.match(bookings, /CRM PROGRAMĂRI/);
assert.match(bookings, /Client Test Local/);
assert.match(bookings, /booking-financial-data/);
const bookingId = /name="id" value="([a-f0-9]{32})"/.exec(bookings)?.[1];
const bookingDate = /name="date" value="([0-9-]+)"/.exec(bookings)?.[1];
const bookingTime = /name="time" value="([0-9:]+)"/.exec(bookings)?.[1];
assert.ok(bookingId && bookingDate && bookingTime);
assert.equal((await post('/admin/?view=bookings', {csrf,action:'update_booking',id:bookingId,date:bookingDate,time:bookingTime,status:'confirmed',amount:'275',payment_status:'paid',internal_notes:'Test financiar'})).status, 303);
bookings = await (await request('/admin/?view=bookings', {redirect:'follow'})).text();
const financialPayload = JSON.parse(/<template id="booking-financial-data">([\s\S]*?)<\/template>/.exec(bookings)?.[1] || '{}');
assert.deepEqual(financialPayload[bookingId], {amount:275,payment_status:'paid',source:'site'});

const manualAvailability = await (await fetch(origin + '/api/availability.php?service=relaxare')).json();
assert.ok(manualAvailability.ok && manualAvailability.days.some(day => day.slots.length));
const manualDay = manualAvailability.days.find(day => day.slots.length);
const manualResponse = await post('/admin/?view=bookings', {
  csrf, action:'add_manual_booking', name:'Client Manual Test', phone:'0773111222', email:'', zone:'Sector 2',
  service_id:'relaxare', date:manualDay.date, time:manualDay.slots[0], amount:'310', payment_status:'unpaid', status:'confirmed',
  internal_notes:'Programare introdusă telefonic în testul automat.'
});
assert.equal(manualResponse.status, 303);
const manualId = /#booking-([a-f0-9]{32})$/.exec(manualResponse.headers.get('location') || '')?.[1];
assert.ok(manualId);
bookings = await (await request('/admin/?view=bookings', {redirect:'follow'})).text();
assert.match(bookings, /Client Manual Test/);
const updatedFinancialPayload = JSON.parse(/<template id="booking-financial-data">([\s\S]*?)<\/template>/.exec(bookings)?.[1] || '{}');
assert.deepEqual(updatedFinancialPayload[manualId], {amount:310,payment_status:'unpaid',source:'manual'});

const financialDashboard = await (await request('/admin/?view=dashboard', {redirect:'follow'})).text();
assert.match(financialDashboard, /Încasați[\s\S]*275<i> lei<\/i>/);
assert.match(financialDashboard, /De încasat[\s\S]*310<i> lei<\/i>/);
const reportResponse = await request('/admin/export.php?period=all');
assert.equal(reportResponse.status, 200);
assert.match(reportResponse.headers.get('content-type') || '', /spreadsheetml\.sheet/);
assert.match(reportResponse.headers.get('content-disposition') || '', /raport-programari_toata-perioada\.xlsx/);
const report = Buffer.from(await reportResponse.arrayBuffer());
assert.equal(report.subarray(0, 2).toString(), 'PK');
assert.ok(report.length > 12000);
mkdirSync('.runtime', {recursive:true});
writeFileSync('.runtime/http-report-test.xlsx', report);

const invalidRange = await request('/admin/export.php?period=custom&from=2026-09-21&to=2026-09-20');
assert.equal(invalidRange.status, 422);
console.log('PASS: persistent admin login, source-tagged manual bookings with optional email, financial CRM data, dashboard, weekly schedule, date exceptions, export XLSX and CRM listing.');
