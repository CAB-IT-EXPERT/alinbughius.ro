(() => {
  'use strict';
  // Keep local previews and private pages out of the real advertising account.
  if (location.hostname !== 'alinbughius.ro' || !['/', '/index.php'].includes(location.pathname)) return;
  window.dataLayer = window.dataLayer || [];
  const gtag = function () { window.dataLayer.push(arguments); };
  const account = 'AW-11103141014';
  const conversion = `${account}/sLiECKP109YZEJb5sa4p`;
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
    page_title: 'Alin Bughius · Masaj la domiciliu'
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
    // Preserve the old site's contact-click conversion, not a new booking event.
    // Navigation continues immediately, even if Google is blocked/unavailable.
    gtag('event', 'conversion', {send_to: conversion, transport_type: 'beacon'});
  });
})();
