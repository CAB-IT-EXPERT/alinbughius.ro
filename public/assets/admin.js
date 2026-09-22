(() => {
  'use strict';
  const sidebar = document.querySelector('#admin-sidebar');
  const backdrop = document.querySelector('[data-sidebar-close]');
  const setSidebar = open => { sidebar?.classList.toggle('open', open); backdrop?.classList.toggle('open', open); document.body.style.overflow = open ? 'hidden' : ''; };
  document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => setSidebar(true));
  backdrop?.addEventListener('click', () => setSidebar(false));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') setSidebar(false); });
  document.querySelector('#exception-type')?.addEventListener('change', event => {
    document.querySelectorAll('.exception-hours').forEach(field => { field.hidden = event.target.value !== 'custom'; });
  });
  const fixedStep = document.querySelector('[name="slot_step_minutes"]');
  if (fixedStep) {
    const minutes = Number(fixedStep.value) || 30;
    const editor = document.createElement('div'); editor.className = 'step-editor';
    const value = document.createElement('input'); value.type = 'number'; value.name = 'slot_step_value'; value.required = true;
    const unit = document.createElement('select'); unit.name = 'slot_step_unit';
    unit.innerHTML = '<option value="minutes">minute</option><option value="hours">ore</option>';
    if (minutes >= 60 && minutes % 60 === 0) { value.value = String(minutes / 60); unit.value = 'hours'; } else { value.value = String(minutes); unit.value = 'minutes'; }
    const syncLimits = () => { const hours = unit.value === 'hours'; value.min = '1'; value.max = hours ? '8' : '480'; value.step = '1'; };
    unit.addEventListener('change', syncLimits); syncLimits(); editor.append(value, unit); fixedStep.replaceWith(editor);
  }
  const stepUnit = document.querySelector('[name="slot_step_unit"]');
  const stepValue = document.querySelector('[name="slot_step_value"]');
  if (stepUnit && stepValue) {
    const syncStepLimits = () => { stepValue.min = '1'; stepValue.max = stepUnit.value === 'hours' ? '8' : '480'; stepValue.step = '1'; };
    stepUnit.addEventListener('change', syncStepLimits); syncStepLimits();
  }
  const search = document.querySelector('#booking-search');
  const filter = document.querySelector('#booking-filter');
  const cards = [...document.querySelectorAll('.booking-card')];
  const el = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };
  const manualDialog = document.querySelector('#manual-booking-dialog');
  if (manualDialog) {
    const pageHeading = document.querySelector('.page-heading');
    const headingActions = el('div', 'booking-heading-actions');
    const openButton = el('button', 'primary-action manual-booking-open');
    openButton.type = 'button'; openButton.dataset.manualOpen = '';
    openButton.append(el('span', '', '＋'), document.createTextNode('Programare manuală'));
    const exportButton = el('button', 'report-export-open');
    exportButton.type = 'button';
    exportButton.setAttribute('aria-label', 'Exportă raportul programărilor în Excel');
    exportButton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 15v4h14v-4"/></svg><span><strong>Exportă</strong><small>Raport XLSX</small></span>';
    headingActions.append(openButton, exportButton);
    pageHeading?.append(headingActions);

    const exportDialog = el('dialog', 'report-export-dialog');
    const exportForm = el('form', 'report-export-form'); exportForm.method = 'dialog';
    exportForm.innerHTML = '<header><span class="report-export-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg></span><div><p class="eyebrow">RAPORT INTELIGENT</p><h2>Alege perioada</h2><p>Primești un fișier Excel complet, cu logo, formule, statistici și toate programările din interval.</p></div><button type="button" class="report-export-close" data-export-close aria-label="Închide">×</button></header><div class="report-export-body"><div class="report-periods" role="radiogroup" aria-label="Perioada raportului"></div><section class="report-custom-period" hidden><label>De la<input type="date" name="report_from"></label><span>—</span><label>Până la<input type="date" name="report_to"></label></section><aside class="report-export-preview"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1z"/></svg></span><div><small>PERIOADA SELECTATĂ</small><strong data-period-preview></strong><p>Se exportă după data programării, inclusiv încasările, sursa și notițele interne.</p></div></aside></div><footer><button type="button" class="secondary-action" data-export-close>Renunță</button><button type="submit" class="primary-action report-generate"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 15v4h14v-4"/></svg>Generează raportul</button></footer>';
    exportDialog.append(exportForm); document.body.append(exportDialog);

    const periods = [
      ['today', 'Astăzi', 'doar ziua curentă'], ['yesterday', 'Ieri', 'ziua anterioară'],
      ['last7', 'Ultimele 7 zile', 'interval rapid'], ['this_month', 'Luna aceasta', 'lună calendaristică'],
      ['last_month', 'Luna trecută', 'lună calendaristică'], ['last3m', 'Ultimele 3 luni', 'interval continuu'],
      ['last6m', 'Ultimele 6 luni', 'interval continuu'], ['last_year', 'Ultimul an', '12 luni până astăzi'],
      ['previous_year', 'Anul trecut', 'an calendaristic'], ['last2y', 'Ultimii 2 ani', '24 luni până astăzi'],
      ['all', 'Toată perioada', 'toate programările'], ['custom', 'Personalizat', 'alegi datele exact']
    ];
    const periodGrid = exportDialog.querySelector('.report-periods');
    const customPeriod = exportDialog.querySelector('.report-custom-period');
    const fromInput = exportDialog.querySelector('[name="report_from"]');
    const toInput = exportDialog.querySelector('[name="report_to"]');
    const preview = exportDialog.querySelector('[data-period-preview]');
    const generate = exportDialog.querySelector('.report-generate');
    let selectedPeriod = 'this_month';
    const pad = value => String(value).padStart(2, '0');
    const iso = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const roDate = date => new Intl.DateTimeFormat('ro-RO', {day:'2-digit', month:'short', year:'numeric'}).format(date);
    const day = (year, month, date) => new Date(year, month, date, 12);
    const boundsFor = key => {
      const now = new Date(); const today = day(now.getFullYear(), now.getMonth(), now.getDate()); let start = null; let end = null;
      if (key === 'today') start = end = today;
      if (key === 'yesterday') start = end = day(today.getFullYear(), today.getMonth(), today.getDate() - 1);
      if (key === 'last7') { start = day(today.getFullYear(), today.getMonth(), today.getDate() - 6); end = today; }
      if (key === 'this_month') { start = day(today.getFullYear(), today.getMonth(), 1); end = day(today.getFullYear(), today.getMonth() + 1, 0); }
      if (key === 'last_month') { start = day(today.getFullYear(), today.getMonth() - 1, 1); end = day(today.getFullYear(), today.getMonth(), 0); }
      if (key === 'last3m') { start = day(today.getFullYear(), today.getMonth() - 3, today.getDate() + 1); end = today; }
      if (key === 'last6m') { start = day(today.getFullYear(), today.getMonth() - 6, today.getDate() + 1); end = today; }
      if (key === 'last_year') { start = day(today.getFullYear() - 1, today.getMonth(), today.getDate() + 1); end = today; }
      if (key === 'previous_year') { start = day(today.getFullYear() - 1, 0, 1); end = day(today.getFullYear() - 1, 11, 31); }
      if (key === 'last2y') { start = day(today.getFullYear() - 2, today.getMonth(), today.getDate() + 1); end = today; }
      return {start, end};
    };
    const refreshExportPreview = () => {
      customPeriod.hidden = selectedPeriod !== 'custom';
      exportDialog.querySelectorAll('.report-period-option').forEach(button => {
        const active = button.dataset.period === selectedPeriod;
        button.classList.toggle('active', active); button.setAttribute('aria-checked', String(active));
      });
      if (selectedPeriod === 'all') preview.textContent = 'Toată perioada disponibilă în CRM';
      else if (selectedPeriod === 'custom') {
        const start = fromInput.value ? new Date(`${fromInput.value}T12:00:00`) : null;
        const end = toInput.value ? new Date(`${toInput.value}T12:00:00`) : null;
        preview.textContent = start && end ? `${roDate(start)} — ${roDate(end)}` : 'Completează ambele date';
      } else {
        const {start, end} = boundsFor(selectedPeriod);
        preview.textContent = start && end && iso(start) === iso(end) ? roDate(start) : `${roDate(start)} — ${roDate(end)}`;
      }
    };
    periods.forEach(([key, label, detail], index) => {
      const option = el('button', 'report-period-option'); option.type = 'button'; option.dataset.period = key; option.setAttribute('role', 'radio');
      option.innerHTML = `<span>${String(index + 1).padStart(2, '0')}</span><strong>${label}</strong><small>${detail}</small><i>✓</i>`;
      option.addEventListener('click', () => { selectedPeriod = key; refreshExportPreview(); if (key === 'custom') setTimeout(() => fromInput.focus(), 80); });
      periodGrid.append(option);
    });
    const now = new Date(); fromInput.value = iso(day(now.getFullYear(), now.getMonth(), 1)); toInput.value = iso(day(now.getFullYear(), now.getMonth(), now.getDate()));
    [fromInput, toInput].forEach(input => input.addEventListener('change', refreshExportPreview));
    const closeExport = () => { exportDialog.classList.remove('is-visible'); document.body.classList.remove('crm-modal-open'); setTimeout(() => { if (exportDialog.open) exportDialog.close(); }, 180); };
    exportButton.addEventListener('click', () => { document.body.classList.add('crm-modal-open'); exportDialog.showModal(); refreshExportPreview(); requestAnimationFrame(() => exportDialog.classList.add('is-visible')); });
    exportDialog.querySelectorAll('[data-export-close]').forEach(button => button.addEventListener('click', closeExport));
    exportDialog.addEventListener('click', event => { if (event.target === exportDialog) closeExport(); });
    exportDialog.addEventListener('cancel', event => { event.preventDefault(); closeExport(); });
    exportForm.addEventListener('submit', event => {
      event.preventDefault();
      if (selectedPeriod === 'custom' && (!fromInput.value || !toInput.value || fromInput.value > toInput.value)) {
        customPeriod.classList.add('has-error'); preview.textContent = 'Data de început trebuie să fie înaintea datei de sfârșit.'; return;
      }
      customPeriod.classList.remove('has-error');
      const params = new URLSearchParams({period:selectedPeriod});
      if (selectedPeriod === 'custom') { params.set('from', fromInput.value); params.set('to', toInput.value); }
      generate.disabled = true; generate.textContent = 'Se pregătește…';
      const download = document.createElement('a'); download.href = `/admin/export.php?${params}`; download.download = ''; document.body.append(download); download.click(); download.remove();
      setTimeout(() => { generate.disabled = false; generate.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 15v4h14v-4"/></svg>Generează raportul'; closeExport(); }, 1200);
    });
    refreshExportPreview();
    const manualForm = manualDialog.querySelector('.manual-booking-form');
    const serviceInput = manualDialog.querySelector('#manual-service');
    const sessionsInput = manualDialog.querySelector('#manual-sessions');
    const dateInput = manualDialog.querySelector('#manual-date');
    const timeInput = manualDialog.querySelector('#manual-time');
    const amountInput = manualDialog.querySelector('#manual-amount');
    const slotHelp = manualDialog.querySelector('#manual-slot-help');
    const submitButton = manualForm?.querySelector('[type="submit"]');
    const closeManual = () => {
      manualDialog.classList.remove('is-visible');
      document.body.classList.remove('crm-modal-open');
      setTimeout(() => { if (manualDialog.open) manualDialog.close(); }, 180);
    };
    openButton.addEventListener('click', () => {
      document.body.classList.add('crm-modal-open'); manualDialog.showModal();
      requestAnimationFrame(() => manualDialog.classList.add('is-visible'));
      setTimeout(() => manualDialog.querySelector('[name="name"]')?.focus(), 180);
    });
    manualDialog.querySelectorAll('[data-manual-close]').forEach(button => button.addEventListener('click', closeManual));
    manualDialog.addEventListener('click', event => { if (event.target === manualDialog) closeManual(); });
    manualDialog.addEventListener('cancel', event => { event.preventDefault(); closeManual(); });
    const syncManualSessions = () => {
      const option = serviceInput?.selectedOptions[0];
      const maximum = Math.max(1, Number.parseInt(option?.dataset.maxSessions || '1', 10) || 1);
      const previous = Math.min(maximum, Math.max(1, Number.parseInt(sessionsInput?.value || '1', 10) || 1));
      sessionsInput?.replaceChildren(...Array.from({length: maximum}, (_, index) => new Option(index ? `${index + 1} sesiuni consecutive` : '1 sesiune', String(index + 1), false, index + 1 === previous)));
      if (sessionsInput) sessionsInput.disabled = !serviceInput?.value;
    };
    const syncManualAmount = () => {
      const price = Number.parseInt(serviceInput?.selectedOptions[0]?.dataset.price || '0', 10) || 0;
      const sessions = Math.max(1, Number.parseInt(sessionsInput?.value || '1', 10) || 1);
      if (amountInput) amountInput.value = String(price * sessions);
    };
    const loadManualSlots = async () => {
      const service = serviceInput?.value || ''; const date = dateInput?.value || ''; const sessions = sessionsInput?.value || '';
      timeInput.replaceChildren(new Option(service && sessions && date ? 'Se încarcă…' : 'Alege serviciul, sesiunile și data', ''));
      timeInput.disabled = true;
      if (!service || !sessions || !date) { slotHelp.textContent = 'Sunt afișate numai orele în care încap toate sesiunile.'; return; }
      try {
        const response = await fetch(`/api/availability.php?service=${encodeURIComponent(service)}&date=${encodeURIComponent(date)}&sessions=${encodeURIComponent(sessions)}`, {headers:{Accept:'application/json'}});
        const payload = await response.json();
        const slots = response.ok && payload.ok && Array.isArray(payload.slots) ? payload.slots : [];
        timeInput.replaceChildren(new Option(slots.length ? 'Alege ora' : 'Nu există ore libere', ''));
        slots.forEach(time => timeInput.append(new Option(time, time)));
        timeInput.disabled = !slots.length;
        slotHelp.textContent = slots.length ? `${slots.length} intervale disponibile în această zi.` : 'Alege altă dată sau verifică programul de lucru.';
      } catch {
        timeInput.replaceChildren(new Option('Orele nu au putut fi încărcate', ''));
        slotHelp.textContent = 'Reîncearcă sau reîncarcă pagina.';
      }
    };
    serviceInput?.addEventListener('change', () => {
      syncManualSessions(); syncManualAmount();
      loadManualSlots();
    });
    sessionsInput?.addEventListener('change', () => { syncManualAmount(); loadManualSlots(); });
    dateInput?.addEventListener('change', loadManualSlots);
    syncManualSessions(); syncManualAmount();
    manualForm?.addEventListener('submit', () => { if (submitButton) { submitButton.disabled = true; submitButton.textContent = 'Se adaugă…'; } });
  }
  const crmList = document.querySelector('#crm-list');
  const countBadge = document.querySelector('.booking-toolbar>span strong');
  const toolbar = document.querySelector('.booking-toolbar');
  let sourceFilter = null;
  if (toolbar && search && filter) {
    toolbar.classList.add('crm-toolbar');
    const searchLabel = search.closest('label');
    if (searchLabel) {
      searchLabel.classList.add('crm-search-field');
      search.placeholder = 'Ex. „mâine”, „200 lei”, „12:00”, „manual”…';
      const searchCaption = el('span', 'crm-toolbar-caption', 'Caută inteligent');
      const searchControl = el('span', 'crm-search-control');
      const searchIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      searchIcon.setAttribute('viewBox', '0 0 24 24'); searchIcon.setAttribute('aria-hidden', 'true');
      searchIcon.innerHTML = '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>';
      const clearSearch = el('button', 'crm-search-clear', '×'); clearSearch.type = 'button'; clearSearch.hidden = true; clearSearch.setAttribute('aria-label', 'Șterge căutarea');
      clearSearch.addEventListener('click', () => { search.value = ''; clearSearch.hidden = true; search.focus(); applyFilters(true); });
      searchControl.append(searchIcon, search, clearSearch); searchLabel.replaceChildren(searchCaption, searchControl);
      search.addEventListener('input', () => { clearSearch.hidden = !search.value; });
    }
    filter.closest('label')?.classList.add('crm-filter-field');
    const sourceLabel = el('label', 'crm-filter-field'); sourceLabel.append(el('span', 'crm-toolbar-caption', 'Sursă'));
    sourceFilter = document.createElement('select'); sourceFilter.id = 'booking-source-filter';
    sourceFilter.append(new Option('Toate sursele', ''), new Option('Manual', 'manual'), new Option('Din site', 'site'));
    sourceLabel.append(sourceFilter);
    toolbar.insertBefore(sourceLabel, toolbar.querySelector(':scope > span'));
    const hints = el('div', 'crm-search-hints'); hints.append(el('span', '', 'CĂUTĂRI RAPIDE'));
    [['Astăzi', 'astăzi'], ['Mâine', 'mâine'], ['Neîncasate', 'neîncasat'], ['Manuale', 'manual']].forEach(([label, query]) => {
      const chip = el('button', 'crm-search-chip', label); chip.type = 'button'; chip.addEventListener('click', () => { search.value = query; search.dispatchEvent(new Event('input')); search.focus(); }); hints.append(chip);
    });
    toolbar.append(hints);
  }
  const pagination = el('nav', 'crm-pagination');
  pagination.setAttribute('aria-label', 'Pagini programări');
  const pageSummary = el('p', 'crm-page-summary'); pageSummary.setAttribute('aria-live', 'polite');
  const pageControls = el('div', 'crm-page-controls');
  pagination.append(pageSummary, pageControls);
  if (crmList && cards.length) crmList.after(pagination);
  let currentPage = 1;
  let pageSize = window.innerWidth <= 760 ? 6 : 8;

  const normalizeSearch = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('ro').replace(/[^a-z0-9:+@.-]+/g, ' ').trim();
  const dateSearchTerms = value => {
    const date = new Date(`${value}T12:00:00`); if (Number.isNaN(date.getTime())) return value;
    const today = new Date(); today.setHours(12, 0, 0, 0);
    const dayDifference = Math.round((date - today) / 86400000);
    const terms = [value, value.split('-').reverse().join('.'), value.split('-').reverse().join('/'),
      new Intl.DateTimeFormat('ro-RO', {weekday:'long', day:'numeric', month:'long', year:'numeric'}).format(date),
      new Intl.DateTimeFormat('ro-RO', {day:'numeric', month:'long'}).format(date)];
    if (dayDifference === 0) terms.push('astăzi', 'azi');
    if (dayDifference === 1) terms.push('mâine');
    if (dayDifference === 2) terms.push('poimâine');
    if (dayDifference === -1) terms.push('ieri');
    const monday = new Date(today); monday.setDate(today.getDate() - ((today.getDay() + 6) % 7));
    const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
    if (date >= monday && date <= sunday) terms.push('săptămâna aceasta', 'săptămâna curentă');
    if (date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear()) terms.push('luna aceasta', 'luna curentă');
    return terms.join(' ');
  };
  const matchingCards = () => {
    const tokens = normalizeSearch(search?.value).split(' ').filter(Boolean);
    return cards.filter(card => !(filter?.value && card.dataset.status !== filter.value)
      && !(sourceFilter?.value && card.dataset.source !== sourceFilter.value)
      && !tokens.some(token => !card.dataset.search.includes(token)));
  };
  const renderPagination = matches => {
    const pages = Math.max(1, Math.ceil(matches.length / pageSize));
    currentPage = Math.min(Math.max(1, currentPage), pages);
    const start = matches.length ? (currentPage - 1) * pageSize + 1 : 0;
    const end = Math.min(currentPage * pageSize, matches.length);
    pageSummary.textContent = matches.length ? `Afișate ${start}–${end} din ${matches.length}` : 'Nicio programare găsită';
    if (countBadge) countBadge.textContent = String(matches.length);
    pageControls.replaceChildren();
    const makeButton = (label, page, className = '') => {
      const button = el('button', `crm-page-button ${className}`, label); button.type = 'button'; button.disabled = page < 1 || page > pages;
      button.setAttribute('aria-label', className.includes('previous') ? 'Pagina anterioară' : className.includes('next') ? 'Pagina următoare' : `Pagina ${page}`);
      if (page === currentPage && !className) { button.classList.add('active'); button.setAttribute('aria-current', 'page'); }
      button.addEventListener('click', () => {
        currentPage = page; applyFilters(false);
        document.querySelector('.booking-toolbar')?.scrollIntoView({behavior:'smooth', block:'start'});
      });
      return button;
    };
    pageControls.append(makeButton('←', currentPage - 1, 'previous'));
    const visiblePages = [];
    for (let page = 1; page <= pages; page++) if (pages <= 5 || page === 1 || page === pages || Math.abs(page - currentPage) <= 1) visiblePages.push(page);
    visiblePages.forEach((page, index) => {
      if (index && page - visiblePages[index - 1] > 1) pageControls.append(el('span', 'crm-page-gap', '…'));
      pageControls.append(makeButton(String(page), page));
    });
    pageControls.append(makeButton('→', currentPage + 1, 'next'));
    pagination.hidden = false;
  };
  const applyFilters = (resetPage = true) => {
    if (resetPage) currentPage = 1;
    const matches = matchingCards();
    currentPage = Math.min(currentPage, Math.max(1, Math.ceil(matches.length / pageSize)));
    const visible = new Set(matches.slice((currentPage - 1) * pageSize, currentPage * pageSize));
    let visibleIndex = 0;
    cards.forEach(card => {
      card.hidden = !visible.has(card);
      if (!card.hidden) {
        card.style.setProperty('--page-index', String(visibleIndex++));
        card.classList.remove('crm-page-reveal'); void card.offsetWidth; card.classList.add('crm-page-reveal');
      }
    });
    renderPagination(matches);
  };
  search?.addEventListener('input', () => applyFilters(true)); filter?.addEventListener('change', () => applyFilters(true)); sourceFilter?.addEventListener('change', () => applyFilters(true));
  window.addEventListener('resize', () => {
    const nextSize = window.innerWidth <= 760 ? 6 : 8;
    if (nextSize !== pageSize) { pageSize = nextSize; applyFilters(false); }
  });

  const valueAfterLabel = (container, label) => {
    const item = [...container.children].find(child => child.querySelector('small')?.textContent.trim() === label);
    return item?.querySelector('strong, a')?.textContent.trim() || '—';
  };
  const whatsappNumber = href => {
    let digits = (href || '').replace(/\D/g, '');
    if (digits.startsWith('0')) digits = `40${digits.slice(1)}`;
    return digits;
  };
  const statusClass = value => ['pending', 'confirmed', 'completed', 'cancelled'].includes(value) ? value : 'pending';
  const financialTemplate = document.querySelector('#booking-financial-data');
  let financialData = {};
  try { financialData = JSON.parse(financialTemplate?.content?.textContent?.trim() || '{}'); } catch { financialData = {}; }
  const actionIcon = kind => {
    if (kind === 'whatsapp') {
      const image = document.createElement('img'); image.src = '/assets/whatsapp-logo.svg'; image.alt = ''; image.width = 19; image.height = 19; return image;
    }
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('aria-hidden', 'true');
    svg.innerHTML = kind === 'call'
      ? '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.56 2.81.69A2 2 0 0 1 22 16.92Z"/>'
      : '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>';
    return svg;
  };
  const makeQuickAction = (kind, label, href) => {
    const link = el('a', `crm-quick-action ${kind}`); link.href = href;
    link.append(actionIcon(kind), el('span', '', label)); return link;
  };

  cards.forEach((card, index) => {
    const trigger = card.querySelector('summary');
    const detail = card.querySelector('.booking-detail');
    const originalForm = detail?.querySelector('.booking-edit');
    const clientGrid = detail?.querySelector('.client-grid');
    if (!trigger || !detail || !originalForm || !clientGrid) return;

    card.classList.add('crm-ready');
    card.style.setProperty('--card-index', String(Math.min(index, 8)));
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');

    const recordId = originalForm.querySelector('[name="id"]')?.value || String(index);
    const financial = financialData[recordId] || {};
    const fallbackAmount = Number.parseInt(valueAfterLabel(clientGrid, 'Cost programare').replace(/[^0-9]/g, ''), 10) || 0;
    const amountValue = Number.isFinite(Number(financial.amount)) ? Math.max(0, Number(financial.amount)) : fallbackAmount;
    const paymentValue = financial.payment_status === 'paid' ? 'paid' : 'unpaid';
    const sourceValue = financial.source === 'manual' ? 'manual' : 'site';
    const personName = card.querySelector('.booking-person h2')?.textContent.trim() || 'Programare';
    const serviceMeta = card.querySelector('.booking-person p')?.textContent.trim() || '';
    const serviceName = serviceMeta.split('·')[0]?.trim() || 'Masaj';
    const summaryStatus = card.querySelector('summary .status');
    const dateInput = originalForm.querySelector('[name="date"]');
    const timeInput = originalForm.querySelector('[name="time"]');
    const sessionsInput = originalForm.querySelector('[name="sessions"]');
    const statusSelect = originalForm.querySelector('[name="status"]');
    const dateLabel = dateInput?.closest('label');
    const timeLabel = timeInput?.closest('label');
    const sessionsLabel = sessionsInput?.closest('label');
    const statusLabel = statusSelect?.closest('label');
    const notesLabel = originalForm.querySelector('.notes');
    const saveButton = originalForm.querySelector('[type="submit"]');
    if (!dateLabel || !timeLabel || !sessionsLabel || !statusLabel || !notesLabel || !dateInput || !timeInput || !sessionsInput || !statusSelect || !saveButton) return;
    const sessionsValue = Math.max(1, Number.parseInt(sessionsInput.value || financial.sessions || '1', 10) || 1);
    const unitPrice = Math.max(0, Number(financial.unit_price) || 0);
    const travel = Math.max(0, Number(financial.travel) || 0);
    card.dataset.source = sourceValue;
    card.dataset.search = normalizeSearch([
      card.dataset.search, serviceName, valueAfterLabel(clientGrid, 'Zonă'), valueAfterLabel(clientGrid, 'Referință'),
      dateSearchTerms(dateInput.value), timeInput.value, `ora ${timeInput.value}`, amountValue, `${amountValue} lei`,
      sessionsValue, `${sessionsValue} sesiuni`,
      paymentValue === 'paid' ? 'încasat plătit achitat' : 'neîncasat neplătit de încasat',
      sourceValue === 'manual' ? 'manual introdus manual telefonic' : 'din site online website',
      statusSelect.selectedOptions[0]?.textContent || ''
    ].join(' '));

    const bookingPerson = card.querySelector('.booking-person');
    const personHeading = bookingPerson?.querySelector('h2');
    if (bookingPerson && personHeading) {
      const titleRow = el('div', 'booking-title-row');
      const sourceTag = el('span', `booking-source ${sourceValue}`, sourceValue === 'manual' ? 'Manual' : 'Din site');
      personHeading.replaceWith(titleRow); titleRow.append(personHeading, sourceTag);
    }

    const cardFinancial = el('div', 'crm-card-financial');
    cardFinancial.append(el('strong', '', `${amountValue.toLocaleString('ro-RO')} lei`), el('span', `crm-payment-pill ${paymentValue}`, paymentValue === 'paid' ? 'Încasat' : 'Neîncasat'));
    card.querySelector('.booking-person')?.append(cardFinancial);

    const dialog = el('dialog', 'crm-booking-dialog');
    dialog.id = `booking-modal-${recordId}`;
    dialog.setAttribute('aria-labelledby', `booking-title-${recordId}`);
    trigger.setAttribute('aria-controls', dialog.id);

    const form = originalForm;
    form.className = 'crm-booking-form';
    form.dataset.initialStatus = statusSelect.value;
    const hiddenInputs = [...form.querySelectorAll(':scope > input[type="hidden"]')];
    form.replaceChildren(...hiddenInputs);

    const handle = el('div', 'crm-sheet-handle');
    handle.setAttribute('aria-hidden', 'true');
    const header = el('header', 'crm-modal-header');
    const avatar = el('span', 'crm-avatar', personName.slice(0, 1).toLocaleUpperCase('ro'));
    const identity = el('div', 'crm-modal-identity');
    const overline = el('span', 'crm-modal-overline', `PROGRAMARE #${valueAfterLabel(clientGrid, 'Referință').replace('#', '')}`);
    const title = el('h2', '', personName); title.id = `booking-title-${recordId}`;
    const subtitle = el('p', '', serviceMeta);
    const modalSource = el('span', `booking-source modal-source ${sourceValue}`, sourceValue === 'manual' ? 'Manual' : 'Din site');
    identity.append(overline, title, subtitle, modalSource);
    const modalStatus = summaryStatus?.cloneNode(true) || el('span', `status ${statusClass(statusSelect.value)}`, statusSelect.selectedOptions[0]?.textContent || 'În așteptare');
    modalStatus.classList.add('crm-modal-status');
    const close = el('button', 'crm-modal-close', '×'); close.type = 'button'; close.setAttribute('aria-label', 'Închide programarea'); close.dataset.crmClose = '';
    header.append(avatar, identity, modalStatus, close);

    const body = el('div', 'crm-modal-body');
    const workspace = el('main', 'crm-modal-workspace');
    const contactPanel = el('section', 'crm-panel crm-contact-panel');
    const contactHeading = el('div', 'crm-panel-heading');
    contactHeading.append(el('span', 'crm-panel-kicker', 'CLIENT'), el('h3', '', 'Date de contact'));
    clientGrid.classList.add('crm-contact-grid');
    contactPanel.append(contactHeading, clientGrid);

    const phoneLink = clientGrid.querySelector('a[href^="tel:"]');
    const emailLink = clientGrid.querySelector('a[href^="mailto:"]');
    const quickActions = el('div', 'crm-quick-actions');
    if (phoneLink) quickActions.append(makeQuickAction('call', 'Sună', phoneLink.href));
    if (phoneLink) { const wa = makeQuickAction('whatsapp', 'WhatsApp', `https://wa.me/${whatsappNumber(phoneLink.href)}`); wa.target = '_blank'; wa.rel = 'noopener'; quickActions.append(wa); }
    if (emailLink) quickActions.append(makeQuickAction('mail', 'E-mail', emailLink.href));
    contactPanel.append(quickActions);

    const schedulePanel = el('section', 'crm-panel crm-schedule-panel');
    const scheduleHeading = el('div', 'crm-panel-heading');
    scheduleHeading.append(el('span', 'crm-panel-kicker', 'PLANIFICARE'), el('h3', '', 'Momentul și starea'));
    const scheduleFields = el('div', 'crm-schedule-fields');
    dateLabel.classList.add('crm-field'); timeLabel.classList.add('crm-field'); sessionsLabel.classList.add('crm-field'); statusLabel.classList.add('crm-field', 'crm-status-field');
    scheduleFields.append(dateLabel, timeLabel, sessionsLabel);

    const fieldTitle = el('span', 'crm-field-title', 'Stare');
    const statusChoices = el('div', 'crm-status-choices');
    [...statusSelect.options].forEach(option => {
      const choice = el('button', `crm-status-choice ${statusClass(option.value)}`, option.textContent);
      choice.type = 'button'; choice.dataset.statusValue = option.value; choice.setAttribute('aria-pressed', String(option.selected));
      choice.addEventListener('click', () => {
        statusSelect.value = option.value;
        statusChoices.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button === choice)));
        modalStatus.className = `status crm-modal-status ${statusClass(option.value)}`;
        modalStatus.textContent = option.textContent;
      });
      statusChoices.append(choice);
    });
    statusSelect.hidden = true;
    statusLabel.replaceChildren(fieldTitle, statusChoices, statusSelect);
    schedulePanel.append(scheduleHeading, scheduleFields, statusLabel);

    const financePanel = el('section', 'crm-panel crm-finance-panel');
    const financeHeading = el('div', 'crm-panel-heading');
    financeHeading.append(el('span', 'crm-panel-kicker', 'FINANCIAR'), el('h3', '', 'Cost și încasare'));
    const financeFields = el('div', 'crm-finance-fields');
    const amountLabel = el('label', 'crm-money-field'); amountLabel.append(el('span', '', 'Costul programării'));
    const amountShell = el('div', 'crm-money-input');
    const amountInput = document.createElement('input'); amountInput.type = 'number'; amountInput.name = 'amount'; amountInput.min = '0'; amountInput.max = '100000'; amountInput.step = '1'; amountInput.required = true; amountInput.value = String(amountValue);
    amountShell.append(amountInput, el('span', '', 'lei')); amountLabel.append(amountShell);
    const paymentField = el('div', 'crm-payment-field'); paymentField.append(el('span', 'crm-field-title', 'Starea încasării'));
    const paymentInput = document.createElement('input'); paymentInput.type = 'hidden'; paymentInput.name = 'payment_status'; paymentInput.value = paymentValue;
    const paymentChoices = el('div', 'crm-payment-choices');
    [['unpaid', 'Neîncasat'], ['paid', 'Încasat']].forEach(([value, label]) => {
      const choice = el('button', `crm-payment-choice ${value}`, label); choice.type = 'button'; choice.dataset.paymentValue = value; choice.setAttribute('aria-pressed', String(value === paymentValue)); paymentChoices.append(choice);
    });
    paymentField.append(paymentChoices, paymentInput); financeFields.append(amountLabel, paymentField); financePanel.append(financeHeading, financeFields);

    const notesPanel = el('section', 'crm-panel crm-notes-panel');
    const notesHeading = el('div', 'crm-panel-heading');
    notesHeading.append(el('span', 'crm-panel-kicker', 'DOAR PENTRU TINE'), el('h3', '', 'Notițe interne'));
    notesLabel.classList.add('crm-notes-field');
    notesPanel.append(notesHeading, notesLabel);
    workspace.append(contactPanel, schedulePanel, financePanel, notesPanel);

    const context = el('aside', 'crm-modal-context');
    const dateCard = el('section', 'crm-context-card crm-date-card');
    dateCard.append(el('span', 'crm-context-label', 'URMĂTOAREA ÎNTÂLNIRE'));
    const liveDate = el('strong', 'crm-live-date');
    const liveTime = el('span', 'crm-live-time');
    dateCard.append(liveDate, liveTime);
    const facts = el('section', 'crm-context-card crm-facts');
    const factTitle = el('h3', '', 'Rezumat'); facts.append(factTitle);
    const factValues = {};
    [['Serviciu', serviceName], ['Sesiuni', sessionsValue === 1 ? '1 sesiune' : `${sessionsValue} sesiuni`], ['Zonă', valueAfterLabel(clientGrid, 'Zonă')], ['Cost', `${amountValue.toLocaleString('ro-RO')} lei`], ['Încasare', paymentValue === 'paid' ? 'Încasat' : 'Neîncasat'], ['Referință', valueAfterLabel(clientGrid, 'Referință')]].forEach(([label, value]) => {
      const row = el('div', 'crm-fact'); const strong = el('strong', '', value); row.append(el('span', '', label), strong); facts.append(row); factValues[label] = strong;
    });
    const hint = el('section', 'crm-context-hint');
    hint.append(el('span', '', '●'), el('p', '', 'Modificările devin active imediat după salvare. Clientul primește e-mail când confirmi sau anulezi.'));
    context.append(dateCard, facts, hint);
    body.append(workspace, context);

    amountInput.addEventListener('input', () => { const amount = Math.max(0, Number(amountInput.value) || 0); factValues.Cost.textContent = `${amount.toLocaleString('ro-RO')} lei`; });
    sessionsInput.addEventListener('change', () => {
      const sessions = Math.max(1, Number.parseInt(sessionsInput.value || '1', 10) || 1);
      amountInput.value = String(unitPrice * sessions + travel);
      amountInput.dispatchEvent(new Event('input'));
      factValues.Sesiuni.textContent = sessions === 1 ? '1 sesiune' : `${sessions} sesiuni`;
      subtitle.textContent = `${serviceName} · ${timeInput.value || '—'} · ${sessions === 1 ? '1 sesiune' : `${sessions} sesiuni`}`;
      updateDateSummary();
    });
    paymentChoices.querySelectorAll('button').forEach(choice => choice.addEventListener('click', () => {
      paymentInput.value = choice.dataset.paymentValue;
      paymentChoices.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button === choice)));
      factValues['Încasare'].textContent = choice.dataset.paymentValue === 'paid' ? 'Încasat' : 'Neîncasat';
    }));

    const footer = el('footer', 'crm-modal-footer');
    const footerText = el('p', '', 'Verifică data, ora, costul și starea înainte de salvare.');
    const footerActions = el('div', 'crm-footer-actions');
    const cancelBooking = el('button', 'crm-danger-action', statusSelect.value === 'cancelled' ? 'Programare anulată' : 'Anulează programarea');
    cancelBooking.type = 'button'; cancelBooking.disabled = statusSelect.value === 'cancelled';
    const closeSecondary = el('button', 'crm-secondary-action', 'Închide'); closeSecondary.type = 'button'; closeSecondary.dataset.crmClose = '';
    saveButton.className = 'primary-action crm-save-action'; saveButton.textContent = 'Salvează modificările';
    footerActions.append(cancelBooking, closeSecondary, saveButton); footer.append(footerText, footerActions);

    const confirmation = el('div', 'crm-cancel-confirmation'); confirmation.hidden = true;
    const confirmationCopy = el('div', ''); confirmationCopy.append(el('strong', '', 'Anulezi această programare?'), el('p', '', 'Intervalul va fi eliberat, iar clientul va primi un e-mail.'));
    const keep = el('button', 'crm-secondary-action', 'Nu, păstrează'); keep.type = 'button';
    const confirmCancel = el('button', 'crm-confirm-cancel', 'Da, anulează'); confirmCancel.type = 'button';
    confirmation.append(confirmationCopy, keep, confirmCancel);
    form.append(handle, header, body, footer, confirmation);
    dialog.append(form); document.body.append(dialog); detail.remove();

    const updateDateSummary = () => {
      const parsed = new Date(`${dateInput.value}T12:00:00`);
      liveDate.textContent = Number.isNaN(parsed.getTime()) ? dateInput.value : new Intl.DateTimeFormat('ro-RO', {weekday:'long', day:'numeric', month:'long'}).format(parsed);
      const sessions = Math.max(1, Number.parseInt(sessionsInput.value || '1', 10) || 1);
      liveTime.textContent = `${timeInput.value || '—'} · ${serviceName} · ${sessions === 1 ? '1 sesiune' : `${sessions} sesiuni`}`;
    };
    dateInput.addEventListener('change', updateDateSummary); timeInput.addEventListener('change', updateDateSummary); updateDateSummary();

    let lastFocus = null;
    const openDialog = () => {
      lastFocus = document.activeElement;
      trigger.setAttribute('aria-expanded', 'true');
      document.body.classList.add('crm-modal-open');
      dialog.showModal();
      requestAnimationFrame(() => dialog.classList.add('is-visible'));
      history.replaceState(null, '', `#${card.id}`);
      close.focus({preventScroll:true});
    };
    const closeDialog = () => {
      dialog.classList.remove('is-visible');
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) dialog.close();
      else setTimeout(() => { if (dialog.open) dialog.close(); }, 180);
    };
    trigger.addEventListener('click', event => { event.preventDefault(); openDialog(); });
    dialog.querySelectorAll('[data-crm-close]').forEach(button => button.addEventListener('click', closeDialog));
    dialog.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });
    dialog.addEventListener('cancel', event => { event.preventDefault(); closeDialog(); });
    dialog.addEventListener('close', () => {
      trigger.setAttribute('aria-expanded', 'false'); document.body.classList.remove('crm-modal-open');
      if (location.hash === `#${card.id}`) history.replaceState(null, '', location.pathname + location.search);
      lastFocus?.focus?.({preventScroll:true});
    });

    const showCancellation = () => { confirmation.hidden = false; requestAnimationFrame(() => confirmation.classList.add('show')); confirmCancel.focus(); };
    const hideCancellation = () => { confirmation.classList.remove('show'); setTimeout(() => { confirmation.hidden = true; }, 160); };
    const restoreInitialStatus = () => {
      statusSelect.value = form.dataset.initialStatus;
      const initialChoice = statusChoices.querySelector(`[data-status-value="${form.dataset.initialStatus}"]`);
      statusChoices.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button === initialChoice)));
      modalStatus.className = `status crm-modal-status ${statusClass(statusSelect.value)}`;
      modalStatus.textContent = statusSelect.selectedOptions[0]?.textContent || 'În așteptare';
      hideCancellation();
    };
    cancelBooking.addEventListener('click', showCancellation); keep.addEventListener('click', restoreInitialStatus);
    confirmCancel.addEventListener('click', () => {
      form.dataset.cancelConfirmed = 'true'; statusSelect.value = 'cancelled'; form.requestSubmit(saveButton);
    });
    form.addEventListener('submit', event => {
      if (statusSelect.value === 'cancelled' && form.dataset.initialStatus !== 'cancelled' && form.dataset.cancelConfirmed !== 'true') {
        event.preventDefault(); showCancellation(); return;
      }
      saveButton.disabled = true; saveButton.textContent = 'Se salvează…';
    });

    let dragStart = 0; let dragDistance = 0;
    handle.addEventListener('pointerdown', event => {
      if (window.innerWidth > 760) return;
      dragStart = event.clientY; dragDistance = 0; handle.setPointerCapture(event.pointerId); dialog.classList.add('is-dragging');
    });
    handle.addEventListener('pointermove', event => {
      if (!dragStart) return;
      dragDistance = Math.max(0, event.clientY - dragStart);
      form.style.setProperty('--sheet-drag', `${dragDistance}px`);
    });
    const finishDrag = () => {
      if (!dragStart) return;
      dialog.classList.remove('is-dragging'); form.style.removeProperty('--sheet-drag');
      const shouldClose = dragDistance > 110; dragStart = 0; dragDistance = 0;
      if (shouldClose) closeDialog();
    };
    handle.addEventListener('pointerup', finishDrag); handle.addEventListener('pointercancel', finishDrag);
    card._openBookingDialog = openDialog;
  });

  const hashCard = location.hash.startsWith('#booking-') ? document.querySelector(location.hash) : null;
  if (hashCard) {
    const matchIndex = matchingCards().indexOf(hashCard);
    if (matchIndex >= 0) currentPage = Math.floor(matchIndex / pageSize) + 1;
    applyFilters(false); hashCard._openBookingDialog?.();
  } else applyFilters(false);

  /* Servicii: listă compactă, editare progresivă și adăugare în popup. */
  const serviceGrid = document.querySelector('.service-admin-grid');
  if (serviceGrid) {
    const serviceForm = serviceGrid.closest('form');
    const serviceCards = [...serviceGrid.querySelectorAll('.service-admin-card')];
    const pageHeading = document.querySelector('.page-heading');
    const openAddService = el('button', 'primary-action service-add-open', '＋ Adaugă serviciu');
    openAddService.type = 'button';
    pageHeading?.append(openAddService);

    const controls = el('section', 'service-command-bar');
    const serviceCount = el('div', 'service-count');
    const activeCount = el('strong', '', '0');
    serviceCount.append(activeCount, el('span', '', ` servicii active din ${serviceCards.length}`));
    const controlActions = el('div', 'service-command-actions');
    const expandAll = el('button', '', 'Extinde toate'); expandAll.type = 'button';
    const collapseAll = el('button', '', 'Restrânge toate'); collapseAll.type = 'button';
    controlActions.append(expandAll, collapseAll); controls.append(serviceCount, controlActions);
    serviceGrid.before(controls);

    const updateServiceCount = () => {
      activeCount.textContent = String(serviceCards.filter(card => card.querySelector('input[type="checkbox"]')?.checked).length);
    };
    const setServiceExpanded = (card, expanded) => {
      card.classList.toggle('is-expanded', expanded);
      card.querySelector('.service-expand')?.setAttribute('aria-expanded', String(expanded));
    };

    serviceCards.forEach((card, index) => {
      const originalTop = card.querySelector('.service-card-top');
      const switchLabel = originalTop?.querySelector('.switch');
      const source = originalTop?.querySelector('small');
      const nameLabel = card.querySelector(':scope > label');
      const nameInput = nameLabel?.querySelector('input');
      const fields = card.querySelector('.service-fields');
      const note = card.querySelector(':scope > p');
      const activeInput = switchLabel?.querySelector('input[type="checkbox"]');
      if (!originalTop || !switchLabel || !source || !nameLabel || !nameInput || !fields || !note || !activeInput) return;

      card.style.setProperty('--service-index', String(Math.min(index, 9)));
      const top = el('div', 'service-compact-top');
      const stateGroup = el('div', 'service-state-group');
      const status = el('span', 'service-live-state');
      const identity = el('div', 'service-card-identity');
      const title = el('h3', '', nameInput.value);
      const meta = el('p');
      const expand = el('button', 'service-expand');
      expand.type = 'button'; expand.setAttribute('aria-expanded', 'false'); expand.setAttribute('aria-label', `Editează ${nameInput.value}`);
      expand.innerHTML = '<span>Editează</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>';
      source.classList.add('service-source');
      stateGroup.append(switchLabel, status);
      identity.append(title, meta);
      top.append(stateGroup, identity, source, expand);

      const body = el('div', 'service-card-body');
      nameLabel.classList.add('service-name-field');
      body.append(nameLabel, fields, note);
      card.replaceChildren(top, body);

      const duration = fields.querySelector('input[name$="[duration]"]');
      const sessionBreak = fields.querySelector('input[name$="[session_break]"]');
      const buffer = fields.querySelector('input[name$="[buffer]"]');
      const price = fields.querySelector('input[name$="[price]"]');
      const maxSessions = fields.querySelector('input[name$="[max_booking_sessions]"]');
      const refreshCard = () => {
        const isActive = activeInput.checked;
        status.textContent = isActive ? 'Activ' : 'Ascuns';
        status.className = `service-live-state ${isActive ? 'active' : 'inactive'}`;
        title.textContent = nameInput.value.trim() || 'Serviciu fără nume';
        const maximum = Math.max(1, Number.parseInt(maxSessions?.value || '1', 10) || 1);
        meta.textContent = `${duration?.value || 0} min / sesiune · max. ${maximum} ${maximum === 1 ? 'sesiune' : 'sesiuni'} · ${price?.value || 0} lei`;
        expand.setAttribute('aria-label', `Editează ${title.textContent}`);
        updateServiceCount();
      };
      const toggleCard = () => setServiceExpanded(card, !card.classList.contains('is-expanded'));
      expand.addEventListener('click', toggleCard);
      top.addEventListener('click', event => { if (!event.target.closest('button,label,input')) toggleCard(); });
      [nameInput, duration, sessionBreak, buffer, price, maxSessions].forEach(input => input?.addEventListener('input', () => { refreshCard(); card.classList.add('has-changes'); }));
      activeInput.addEventListener('change', () => { refreshCard(); card.classList.add('has-changes'); });
      refreshCard();
    });
    expandAll.addEventListener('click', () => serviceCards.forEach(card => setServiceExpanded(card, true)));
    collapseAll.addEventListener('click', () => serviceCards.forEach(card => setServiceExpanded(card, false)));

    const saveButton = serviceForm?.querySelector('.save-services');
    if (saveButton && serviceForm) {
      const saveBar = el('div', 'service-save-bar');
      const copy = el('div'); copy.append(el('strong', '', 'Modificări în servicii'), el('span', '', 'Duratele, ambele pauze și limita de sesiuni actualizează automat calendarul.'));
      saveButton.textContent = 'Salvează modificările';
      saveBar.append(copy, saveButton); serviceGrid.after(saveBar);
      serviceForm.addEventListener('submit', () => { saveButton.disabled = true; saveButton.textContent = 'Se salvează…'; });
    }

    const addSection = document.querySelector('.add-service');
    const addForm = addSection?.querySelector('form');
    if (addSection && addForm) {
      const addIntro = addSection.querySelector(':scope > div');
      const hidden = [...addForm.querySelectorAll(':scope > input[type="hidden"]')];
      const fields = [...addForm.querySelectorAll(':scope > label')];
      const submit = addForm.querySelector('[type="submit"]');
      const dialog = el('dialog', 'service-add-dialog');
      const header = el('header', 'editor-dialog-header');
      const mark = el('span', 'editor-dialog-mark', '＋');
      const heading = el('div'); heading.append(el('p', 'eyebrow', 'SERVICIU NOU'), el('h2', '', 'Adaugă un tip de masaj'), el('p', '', 'Va deveni imediat disponibil în formularul public.'));
      const close = el('button', 'editor-dialog-close', '×'); close.type = 'button'; close.dataset.serviceDialogClose = ''; close.setAttribute('aria-label', 'Închide');
      header.append(mark, heading, close);
      const body = el('div', 'editor-dialog-body');
      const info = el('div', 'editor-info-card');
      info.append(el('span', '', '01'), el('div', '', 'Completează durata unei sesiuni, pauza dintre sesiuni, pauza finală, prețul și limita de sesiuni. Serviciul va fi activ din momentul salvării.'));
      const fieldGrid = el('div', 'service-add-fields'); fields.forEach(label => fieldGrid.append(label));
      body.append(info, fieldGrid);
      const footer = el('footer', 'editor-dialog-footer');
      const cancel = el('button', 'secondary-action', 'Renunță'); cancel.type = 'button'; cancel.dataset.serviceDialogClose = '';
      submit.className = 'primary-action'; submit.textContent = 'Adaugă serviciul';
      footer.append(cancel, submit);
      addForm.className = 'service-add-form'; addForm.replaceChildren(...hidden, header, body, footer);
      dialog.append(addForm); document.body.append(dialog); addSection.remove();
      const closeDialog = () => {
        dialog.classList.remove('is-visible'); document.body.classList.remove('crm-modal-open');
        setTimeout(() => { if (dialog.open) dialog.close(); }, 180);
      };
      openAddService.addEventListener('click', () => {
        document.body.classList.add('crm-modal-open'); dialog.showModal(); requestAnimationFrame(() => dialog.classList.add('is-visible'));
        setTimeout(() => dialog.querySelector('[name="name"]')?.focus(), 170);
      });
      dialog.querySelectorAll('[data-service-dialog-close]').forEach(button => button.addEventListener('click', closeDialog));
      dialog.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });
      dialog.addEventListener('cancel', event => { event.preventDefault(); closeDialog(); });
      addForm.addEventListener('submit', () => { submit.disabled = true; submit.textContent = 'Se adaugă…'; });
    }
  }

  /* Disponibilitate: rezumat, rânduri tactile și zile speciale în popup. */
  const scheduleLayout = document.querySelector('.schedule-layout');
  if (scheduleLayout) {
    const pageHeading = document.querySelector('.page-heading');
    const weeklyPanel = scheduleLayout.querySelector(':scope > .panel');
    const settingsPanel = scheduleLayout.querySelector('.settings-panel');
    const weekRows = [...scheduleLayout.querySelectorAll('.week-row')];
    weeklyPanel?.classList.add('schedule-week-panel');
    settingsPanel?.classList.add('schedule-rules-panel');

    const summary = el('section', 'schedule-summary');
    const daysValue = el('strong'); const hoursValue = el('strong'); const horizonValue = el('strong');
    const summaryCard = (number, label, detail) => { const card = el('article'); card.append(number, el('span', '', label), el('small', '', detail)); return card; };
    summary.append(summaryCard(daysValue, 'zile active', 'în programul săptămânal'), summaryCard(hoursValue, 'interval uzual', 'prima și ultima oră'), summaryCard(horizonValue, 'rezervări în avans', 'vizibilitate pentru clienți'));
    pageHeading?.after(summary);

    const refreshScheduleSummary = () => {
      const enabled = weekRows.filter(row => row.querySelector('input[type="checkbox"]')?.checked);
      daysValue.textContent = String(enabled.length);
      const starts = enabled.map(row => row.querySelector('input[name$="[start]"]')?.value).filter(Boolean).sort();
      const ends = enabled.map(row => row.querySelector('input[name$="[end]"]')?.value).filter(Boolean).sort();
      hoursValue.textContent = starts.length ? `${starts[0]}–${ends.at(-1)}` : 'Închis';
      horizonValue.textContent = `${settingsPanel?.querySelector('[name="horizon_days"]')?.value || '—'} zile`;
    };
    weekRows.forEach((row, index) => {
      row.style.setProperty('--day-index', String(index));
      const toggle = row.querySelector('input[type="checkbox"]');
      const hours = row.querySelector('.hours');
      const dayName = row.querySelector('.day-toggle strong')?.textContent || 'Zi';
      const pill = el('span', 'day-state');
      const refreshRow = () => {
        const active = Boolean(toggle?.checked);
        row.classList.toggle('is-closed', !active); pill.textContent = active ? 'Disponibil' : 'Liber'; pill.className = `day-state ${active ? 'active' : 'closed'}`;
        hours?.setAttribute('aria-label', `Program ${dayName}`); refreshScheduleSummary();
      };
      row.insertBefore(pill, hours || null);
      toggle?.addEventListener('change', refreshRow);
      row.querySelectorAll('input[type="time"]').forEach(input => input.addEventListener('change', refreshScheduleSummary));
      refreshRow();
    });
    settingsPanel?.querySelector('[name="horizon_days"]')?.addEventListener('change', refreshScheduleSummary);
    refreshScheduleSummary();

    if (settingsPanel) {
      const heading = settingsPanel.querySelector('.panel-heading');
      const body = el('div', 'schedule-rules-body');
      [...settingsPanel.children].filter(child => child !== heading).forEach(child => body.append(child));
      const collapse = el('button', 'panel-collapse', 'Restrânge'); collapse.type = 'button'; collapse.setAttribute('aria-expanded', 'true');
      heading?.append(collapse); settingsPanel.append(body);
      collapse.addEventListener('click', () => {
        const collapsed = settingsPanel.classList.toggle('is-collapsed');
        collapse.textContent = collapsed ? 'Extinde' : 'Restrânge'; collapse.setAttribute('aria-expanded', String(!collapsed));
      });
      const save = body.querySelector('[type="submit"]');
      scheduleLayout.addEventListener('submit', () => { if (save) { save.disabled = true; save.textContent = 'Se salvează…'; } });
    }

    const exceptions = document.querySelector('.exceptions');
    const exceptionForm = exceptions?.querySelector('.exception-form');
    if (exceptions && exceptionForm) {
      exceptions.classList.add('schedule-exceptions-panel');
      const exceptionsHeading = exceptions.querySelector('.panel-heading');
      const openException = el('button', 'secondary-action exception-open', '＋ Adaugă o zi specială'); openException.type = 'button';
      exceptionsHeading?.append(openException);
      if (!exceptions.querySelector('.exception-list')) {
        const empty = el('div', 'schedule-empty'); empty.append(el('span', '', '✓'), el('strong', '', 'Nicio excepție setată'), el('p', '', 'Programul săptămânal se aplică fără modificări.')); exceptions.append(empty);
      }
      const hidden = [...exceptionForm.querySelectorAll(':scope > input[type="hidden"]')];
      const fields = [...exceptionForm.querySelectorAll(':scope > label')];
      const submit = exceptionForm.querySelector('[type="submit"]');
      const dialog = el('dialog', 'exception-dialog');
      const header = el('header', 'editor-dialog-header');
      const mark = el('span', 'editor-dialog-mark calendar-mark', '◷');
      const heading = el('div'); heading.append(el('p', 'eyebrow', 'CALENDAR'), el('h2', '', 'Adaugă o zi specială'), el('p', '', 'Blochează o zi sau stabilește un program diferit.'));
      const close = el('button', 'editor-dialog-close', '×'); close.type = 'button'; close.dataset.exceptionClose = ''; close.setAttribute('aria-label', 'Închide');
      header.append(mark, heading, close);
      const body = el('div', 'editor-dialog-body');
      const info = el('div', 'editor-info-card'); info.append(el('span', '', '●'), el('div', '', 'Excepția are prioritate față de programul săptămânal și devine activă imediat.'));
      const fieldGrid = el('div', 'exception-popup-fields'); fields.forEach(label => fieldGrid.append(label));
      body.append(info, fieldGrid);
      const footer = el('footer', 'editor-dialog-footer');
      const cancel = el('button', 'secondary-action', 'Renunță'); cancel.type = 'button'; cancel.dataset.exceptionClose = '';
      submit.className = 'primary-action'; submit.textContent = 'Salvează ziua specială';
      footer.append(cancel, submit);
      exceptionForm.className = 'exception-form exception-popup-form'; exceptionForm.replaceChildren(...hidden, header, body, footer);
      dialog.append(exceptionForm); document.body.append(dialog);
      const closeDialog = () => { dialog.classList.remove('is-visible'); document.body.classList.remove('crm-modal-open'); setTimeout(() => { if (dialog.open) dialog.close(); }, 180); };
      openException.addEventListener('click', () => { document.body.classList.add('crm-modal-open'); dialog.showModal(); requestAnimationFrame(() => dialog.classList.add('is-visible')); setTimeout(() => dialog.querySelector('[name="exception_date"]')?.focus(), 170); });
      dialog.querySelectorAll('[data-exception-close]').forEach(button => button.addEventListener('click', closeDialog));
      dialog.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });
      dialog.addEventListener('cancel', event => { event.preventDefault(); closeDialog(); });
      exceptionForm.addEventListener('submit', () => { submit.disabled = true; submit.textContent = 'Se salvează…'; });
    }
  }
})();
