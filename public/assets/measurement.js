(() => {
  'use strict';
  // Keep local previews and private pages out of the real measurement accounts.
  if (location.hostname !== 'alinbughius.ro' || !['/', '/index.php'].includes(location.pathname)) return;
  if (window.alinMeasurementLoaded) return;
  window.alinMeasurementLoaded = true;
  window.dataLayer = window.dataLayer || [];
  const gtag = window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
  const account = 'AW-18478963280';
  const analytics = 'G-E0L7GVF1B2';
  const legacyAccount = 'AW-11103141014';
  const legacyConversion = `${legacyAccount}/sLiECKP109YZEJb5sa4p`;
  const phoneConversion = `${account}/4uWiCIHdobcdENC0uutE`;
  const pageUrl = new URL('https://alinbughius.ro/');
  // Preserve campaign attribution, but never forward arbitrary query fields.
  const incoming = new URL(location.href);
  ['gclid', 'gbraid', 'wbraid', 'gad_source', 'gad_campaignid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach(key => {
    const value = incoming.searchParams.get(key);
    if (value) pageUrl.searchParams.set(key, value.slice(0, 256));
  });

  // Standard tag retained from the old website at the owner's request.
  // No consent state is fabricated. Legal review of this setup is required.
  gtag('js', new Date());
  gtag('config', account, {
    allow_ad_personalization_signals: false,
    allow_google_signals: false,
    // Do not forward arbitrary query strings, form fields or private links.
    page_location: pageUrl.href,
    page_referrer: '',
    page_title: 'Alin Bughiuș · Masaj la domiciliu'
  });
  // Keep the previously installed account active until its campaign is retired.
  gtag('config', legacyAccount, {
    allow_ad_personalization_signals: false,
    allow_google_signals: false,
    page_location: pageUrl.href,
    page_referrer: '',
    page_title: 'Alin Bughiuș · Masaj la domiciliu'
  });
  // GA4 shares the existing Google tag; do not load another gtag.js script.
  // Keep page metadata sanitized and existing Ads conversion routing unchanged.
  gtag('config', analytics, {
    allow_ad_personalization_signals: false,
    allow_google_signals: false,
    page_location: pageUrl.href,
    page_referrer: '',
    page_title: 'Alin Bughiuș · Masaj la domiciliu'
  });
  const script = document.createElement('script');
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${account}`;
  document.head.append(script);

  document.addEventListener('click', event => {
    if (!event.isTrusted) return;
    const link = event.target.closest('a[href]');
    if (!link) return;
    const url = new URL(link.href, location.origin);
    if (url.protocol !== 'tel:' && !(url.protocol === 'https:' && url.hostname === 'wa.me')) return;
    const contactMethod = url.protocol === 'tel:' ? 'phone' : 'whatsapp';
    // Preserve the old site's contact-click conversion, not a new booking event.
    // Navigation continues immediately, even if Google is blocked/unavailable.
    gtag('event', 'conversion', {send_to: legacyConversion, transport_type: 'beacon'});
    // The active Ads account measures only genuine phone-button clicks as calls.
    if (contactMethod === 'phone') {
      gtag('event', 'conversion', {
        send_to: phoneConversion,
        value: 1.0,
        currency: 'RON',
        transport_type: 'beacon'
      });
    }
    // Keep the combined event for continuity and add a readable event per channel.
    gtag('event', 'contact_click', {
      send_to: analytics,
      contact_method: contactMethod,
      transport_type: 'beacon'
    });
    gtag('event', `${contactMethod}_click`, {
      send_to: analytics,
      transport_type: 'beacon'
    });
  });
})();
