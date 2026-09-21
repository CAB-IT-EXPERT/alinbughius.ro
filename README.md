# Alin Bughius — masaj la domiciliu

Site PHP 8.2+ fără framework, cu design responsive, fotografiile clientului, trei videoclipuri Instagram găzduite local, calendar de programări în timp real și CRM privat.

## Pornire locală

În PowerShell, din rădăcina proiectului:

```powershell
./tools/run-local.ps1
```

Deschide `http://127.0.0.1:8077/`. Scriptul activează OpenSSL pentru PHP și folosește un certificat CA pentru conexiunile SMTP. Serverul de dezvoltare ascultă doar pe calculatorul local; nu este server de producție.

## Publicare pe găzduire

1. PHP 8.2 sau mai nou, extensie OpenSSL, HTTPS și conexiuni SMTP externe pe portul 465. PHPMailer este inclus în `vendor/`.
2. Directorul public al domeniului trebuie să fie **doar `public/`**. Păstrează directoarele `app/`, `config/`, `storage/`, `vendor/` și `tools/` în afara lui. Pe un cont cPanel cu `public_html`, copiază conținutul `public/` în `public_html/`, iar directoarele private ca directoare-surori.
3. Copiază `config/example.php` în `config/local.php` și completează parola SMTP pe server. Configurația locală existentă este privată, ignorată de Git și exclusă din pachetul de livrare. Nu o muta în directorul public și nu o include în JavaScript.
4. Directorul `storage/` trebuie să fie inscriptibil de PHP, fără acces public. Recomandare: 0700 pentru director și 0600 pentru fișierele private, dacă găzduirea permite.
5. În `site_url` configurează adresa HTTPS reală, fără slash final. Implicit: `https://alinbughius.ro`.
6. Configurează o sarcină cron la 5 minute pentru `php /cale/privata/tools/retry-mail.php`. Aceasta reîncearcă livrările nereușite în primele 24 de ore și curăță înregistrările la 90 de zile după data programării sau a cererii, oricare este mai recentă. Cronul trebuie monitorizat de administrator.
7. Verifică autentificarea fără a trimite e-mailuri: `php tools/check-smtp.php`. Apoi efectuează o programare de test controlată după publicare, inclusiv confirmarea ei. Autentificarea SMTP nu garantează livrarea în Inbox; verifică SPF/DKIM/DMARC la furnizor.
8. Verifică răspunsul 403/404 pentru `/config/local.php`, `/storage/`, `/app/` și orice arhive/fișiere private. Nu publica arhiva veche, mesajele vocale sau `.runtime/`.

## Fluxul programării și CRM

- Vizitatorul alege serviciul pentru o singură ședință, apoi numai una dintre zilele și orele libere calculate în timp real. Completează nume, telefon, **e-mail obligatoriu**, zonă și informarea de contact.
- Fiecare programare reprezintă o singură ședință. Prețul este recalculat pe server, iar deplasarea se adaugă o singură dată atunci când zona o cere.
- În momentul trimiterii, serverul verifică din nou calendarul sub blocare exclusivă și reține intervalul. Două cereri nu pot ocupa aceeași oră, chiar dacă sunt trimise simultan.
- Alin primește solicitarea pe `bughius_alin@yahoo.com`, de la `contact@alinbughius.ro`. Vizitatorul primește dovada înregistrării, apoi confirmarea finală.
- Mesajul lui Alin conține un link privat de confirmare. Deschiderea linkului nu confirmă nimic; Alin apasă explicit butonul, apoi clientul primește confirmarea pe e-mail. Linkul este valabil 30 de zile și nu trebuie distribuit.
- `/admin/` oferă dashboard, CRM cu programări, căutare, stări, reprogramare și notițe interne, program săptămânal, excepții/concedii, durate și pauze pe serviciu, servicii personalizate și schimbarea parolei.
- Contul inițial este `admin` / `admin`; parola poate fi schimbată oricând din secțiunea Securitate, fără blocarea accesului la CRM. Autentificarea are limitare de încercări, sesiune separată, cookie `HttpOnly`/`SameSite=Strict`, regenerarea sesiunii și CSRF.
- Calendarul, contul și programările sunt fișiere JSON private, scrise atomic și protejate cu lock-uri exclusive. Soluția este potrivită volumului actual; pentru echipă, mai multe locații sau trafic mare se recomandă migrare la o bază de date relațională.
- Solicitările au protecție CSRF, limitare de frecvență și deduplicare. Nu există plată online.
- Eșecurile de livrare sunt înregistrate separat pe destinatar; repetarea unei cereri nu retrimite canalele deja livrate. Dacă SMTP e indisponibil, formularul afișează situația, fără a pretinde că mesajele au ajuns.

## Design și conținut

Hero cu fotografia lui Alin, recenzii cu logo-ul Google original, secțiune cu trei Reel-uri, servicii și abonamente, beneficiile masajului acasă, prezentare și diplome, pașii programării, voucher, ghidul primei ședințe, FAQ și footer cu contact.

Animațiile respectă `prefers-reduced-motion`. Videoclipurile pornesc fără sunet numai când intră în ecran, se opresc în afara ecranului sau când fila este ascunsă, au buton de pauză și nu pornesc automat în modul de economisire a datelor. Niciun player Instagram nu este încărcat în pagină. Recenziile permit drag cu mouse-ul, swipe nativ pe telefon și navigare cu tastatura, săgeți și indicatori.

## Măsurarea păstrată din site-ul vechi

- Google Ads: `AW-11103141014`.
- Conversie pentru clic pe telefon/WhatsApp: `AW-11103141014/sLiECKP109YZEJb5sa4p`.
- Nu s-au găsit identificatori GA4, Google Tag Manager sau Meta Pixel în codul propriu al site-ului vechi. Pe găzduire era doar o pagină provizorie.
- La cererea explicită a utilizatorului, eticheta funcționează standard, fără banner și fără modul permanent denied. Nu fabricăm un acord printr-un apel consent/granted. Această configurație trebuie validată juridic; informarea nu înlocuiește consimțământul cerut de politica Google pentru UE: https://www.google.com/about/company/user-consent-policy/.
- Nu trimitem datele formularului sau tokenuri private; conversiile îmbunătățite nu au fost implementate. Eticheta se încarcă doar pe pagina principală a domeniului live, nu pe localhost, API sau confirmarea privată. Personalizarea reclamelor nu este activată de codul nou.
- Teste fără trafic Google: `node tests/measurement-test.mjs`.

## Publicare efectuată — 20 septembrie 2026

- URL: `https://alinbughius.ro/`; redirecționări 301 de la HTTP și www, cu păstrarea căii și parametrilor.
- Director FTP public: `/alinbughius.ro`; director privat dedicat: `/alinbughius-private`, separat de document root. Calea fizică a contului este `/home/cabitro`.
- FTPS cu verificarea completă a certificatului: `d108.dataserver.ro:21`. Acesta prezintă același certificat ca endpointul furnizat `ftp.cab-it.ro`; nu a fost dezactivată verificarea TLS pentru autentificare/transfer.
- Backup anterior în `.runtime/deploy-backup-20260920-205948/alinbughius.ro/`. Nu au fost modificate alte domenii din cont.
- PHP live 8.2.33, OpenSSL activ, stocare privată inscriptibilă, autentificare SMTP cu TLS verificat reușită. Nu s-au trimis mesaje reale către client pentru test.
- Teste automate live: `python tools/check-live.py` (redirecturi, acces privat blocat, API, video range). Fluxul complet de e-mailuri este verificat local cu captură, fără destinatari reali.
- **De configurat în panoul găzduirii:** cron la 5 minute, cu PHP CLI 8.2+, pentru `/home/cabitro/alinbughius-private/tools/retry-mail.php`. FTP nu configurează cron. Acesta gestionează reîncercările de e-mail și ștergerea la termen; trimiterea imediată a programărilor nu depinde de cron.

Surse autorizate: arhiva veche furnizată de client și profilul `@terapeut.alinbughius`. Cele trei clipuri sunt convertite în MP4/H.264 fără pistă audio; fiecare are link la Reel-ul original. Muzica de pe Instagram nu este redistribuită.

- `https://www.instagram.com/terapeut.alinbughius/reel/DaJMW6MI0XG/`
- `https://www.instagram.com/terapeut.alinbughius/reel/DbKlX-MIN6v/`
- `https://www.instagram.com/terapeut.alinbughius/reel/DaiuYXLq-uV/`

Fotografiile serviciilor și textele recenziilor provin din arhiva furnizată. Nu au fost inventate recenzii, număr de clienți sau rezultate terapeutice. Identitatea juridică a operatorului și informarea de confidențialitate trebuie validate de proprietar înainte de publicare.

Sigla WhatsApp din bara mobilă este activul vectorial original WhatsApp, preluat din resursa atribuită WhatsApp și documentată de Meta: https://commons.wikimedia.org/wiki/File:WhatsApp_Logo_green.svg.

## Teste

```powershell
php tests/booking-test.php
php tests/scheduler-test.php
node --check public/assets/site.js
node --check public/assets/video.js
```

Suita PHP capturează e-mailurile în `.runtime/` și nu trimite mesaje reale. Pentru testul HTTP folosește **un server separat**, cu `APP_MAIL_CAPTURE=1` și `APP_STORAGE_DIR` setat la `.runtime/test-http`, pe portul 8078, apoi rulează `node tests/http-test.mjs`. Nu activa captura pe site-ul live.

Testele verifică validarea, reducerile, deplasarea, livrarea către ambele adrese, scenariile de eroare, deduplicarea, calendarul și suprapunerile, excepțiile, serviciile personalizate, protecția linkului privat, confirmarea și accesul CRM.
