# Hero: referință vizuală și galerie separată

Istoric al referințelor vizuale. Versiunea curentă este descrisă în secțiunea din 13 octombrie 2026, la final.

## Fundal

Fișier utilizat: `public/assets/images/hero-reference-background.webp` (1024 × 1536).

Fundal adaptat cu instrumentul integrat ImageGen, în modul de editare, pornind de la imaginea de referință furnizată de client. Nu s-a folosit modul CLI.

Instrucțiunile promptului, în rezumat: eliminarea tuturor textelor, siglelor, pictogramelor, butoanelor și elementelor de interfață din referință; păstrarea compoziției fotografice, poziției persoanelor, frunzelor estompate și fundalului verde-mentă. Rezultatul trebuie să fie numai fundalul fotografic, fără texte sau elemente UI.

Imaginea rezultată a fost convertită în WebP. Textele, logo-ul, legăturile și butoanele sunt elemente HTML separate, funcționale și accesibile.

## Slideshow

Cele șapte fotografii existente cu Alin sunt afișate imediat sub hero, nu în fundalul acestuia. Derularea automată este la 1,7 secunde când galeria este vizibilă, cu pauză la interacțiune și respectarea preferinței pentru mișcare redusă. Sunt disponibile săgeți, indicatori și glisare.

## Verificare

- Verificare vizuală mobil și desktop.
- Fără overflow orizontal la lățimile testate de 320, 390 și 1440 px.
- Toate cele șapte imagini încărcate.
- Săgeți, glisare și derulare automată verificate în browser.
- Butonul principal deschide formularul; nu a fost trimisă o programare de test.
- Verificări sintactice PHP/JavaScript și verificări read-only ale site-ului live trecute.

## Corectarea compoziției — 3 octombrie 2026

Referință: `codex-clipboard-6d6ea1c5-480f-4796-afd8-807d892af5cb.png`.
Fundalul curent este `public/assets/images/hero-reference-background-v2.webp`
(1024 × 1536), extras cu instrumentul integrat ImageGen, în modul de editare.
Versiunea anterioară a fost păstrată în proiect.

Prompt utilizat: eliminarea textelor, siglelor, pictogramelor, ratingului,
butoanelor, cardului de recenzii și navigației din referință; reconstruirea
fundalului verde-mentă în acele locuri; păstrarea poziției, scării, expresiei,
posturii și acoperirii cu prosop a persoanelor, frunzelor și transparenței
selective. Compoziția nu trebuie deplasată sau mărită. Fără texte sau UI în
imaginea finală. Nu a fost folosit modul CLI.

Prompt final, transmis instrumentului integrat:

> Edit target: attached professional therapeutic massage website reference. This is a nonsexual health/wellness service with a patient resting on a treatment table under a towel and a practitioner in a white uniform. Create a clean website background by removing all text, UI buttons, logos, icons, ratings, top bar and navigation from this exact design. Inpaint removed UI with the mint backdrop or underlying wellness photograph. Preserve existing image composition exactly: plants on upper right and lower left, practitioner white uniform and arms visible from middle right, patient supported by the treatment table in the lower third. Keep all subjects' positioning, scale, expression, treatment pose, towel coverage, room colors and selective soft mint fade unchanged. Do not move or zoom the photograph. Return the same full portrait canvas 1024 x 1536 with absolutely no typography, logos, controls or interface. Only remove interface, preserve the existing professional wellness scene.

Pe telefon, fotografia este afișată pe toată lățimea, fără mască, transparență
globală ori deplasare spre dreapta. Poziția verticală scade zona ocupată de
header în referință, ca frunzele și terapeutul să rămână lângă text. Titlul
serviciului își păstrează dimensiunea redusă; headerul, spațierile și cardul
Google revin la proporțiile compacte din referință pe toate telefoanele.

## Referința botanică — 7 octombrie 2026

Noua referință înlocuiește hero-ul verde-mentă, nu galeria cu cele șapte fotografii de sub el.
Navbarul crem, monograma AB cu frunză, bara de localizare de sub navbar, titlul alb/verde,
cardul crem și butoanele urmează noua imagine furnizată de client. Rama telefonului și
interfața iOS din referință nu fac parte din site.

- Referință: `codex-clipboard-e7bbd10c-48aa-4134-9bc5-3b8b5dc100b4.png`.
- Fotografie suport: `codex-clipboard-e3ba50a6-537f-42a7-9e91-a9ce369cb3eb.png`.
- Fundal final: `public/assets/images/hero-botanical-reference.webp`, 1004 × 1567 px, 119816 octeți.
- Stiluri: `public/assets/hero-botanical.css`.
- Monogramă vectorială: `public/assets/logo-botanical.svg`.

Fundalul fotografic a fost adaptat cu instrumentul integrat ImageGen, folosind ambele imagini.
Nu a fost folosit modul CLI. Rezultatul a fost convertit în WebP; toate textele și controalele
sunt HTML funcțional, nu parte din imagine. Fișierele vechi au fost păstrate.

Prompt final transmis instrumentului integrat:

> Use case: precise-object-edit / compositing. Create a production website BACKGROUND ASSET ONLY. Image 1 is the exact approved layout and composition reference to extract; image 2 is the original photo to preserve the therapist's real identity and treatment scene. From image 1 keep ONLY the full-bleed rectangular background of the website area BELOW the cream navbar, from the dark green location ribbon at its top to the green bottom below the contact buttons. Remove the black surroundings, phone frame, rounded device corners, status bar and cream navbar entirely. Within that remaining tall rectangle remove ALL text, location icon, cream information card, Google review capsule, booking button, phone/WhatsApp buttons and logos. Inpaint those areas with the underlying dark green / warm wood / massage photograph / bottom green atmospheric gradient. Output portrait approximately 1024 x 1600. Preserve image 1's exact relative photograph scale and positions: man's dark-haired head at x75%, y24%, white shirt and arms on the right; woman's hair and head at x56%, y49%, her back across the lower right; the whole photographic massage scene visible through upper 60%, softly fading to natural forest green across lower 35%. Left upper half very dark forest green with softly visible room greenery, suitable for white overlaid website text. Keep warm vertical wood slats behind man, foliage wall at upper right. Preserve the bright realistic decorative leaves entering from upper left and upper right, mid left around46%, and both lower edges around78–92%. Keep center bottom clear behind future buttons. Preserve therapist/client pose and identities from originals, correct hands, professional massage, client appropriately covered with towel, no new people. Crucially this is NOT a new mockup: absolutely no text, letters, icons, logos, lines, panels, cards or UI shapes anywhere. No phone frame. No additional blur on therapist. Match the original composition and colors precisely; change only removal of UI and crop away device/cream navbar.

### Adaptare și verificare

Pe telefon, compoziția este verticală, cu butonul de programare deasupra butoanelor
Sună și WhatsApp. Pe laptop și desktop, textul este în stânga, fotografia în dreapta,
iar cardul cu preț și beneficii ocupă lățimea conținutului. Ecranele intermediare au
reguli separate pentru navbar, fotografie și acțiuni.

Verificare în Chrome la 320, 390, 430, 700, 768, 1024, 1280, 1366, 1440 și 1920 px:
fără scroll orizontal ori controale tăiate. Verificare vizuală a capturilor mobile,
tabletă, laptop și desktop. Meniul mobil, linkul spre servicii, deschiderea/închiderea
formularului de programare, destinațiile telefon/WhatsApp și săgeata galeriei au fost
verificate. Nu a fost trimisă nicio programare de test.

## Varianta crem — 13 octombrie 2026

Referințe aprobate: `codex-clipboard-4bd6da36-e01d-4dd5-897c-47eddd316cf0.png`
(desktop) și `codex-clipboard-04d6dc44-4508-46d2-b7ca-792c77f7c404.png` (mobil).
Clarificările ulterioare cer crem numai deasupra galeriei, inclusiv în zona
recenziilor și a butoanelor. Galeria verde și cele șapte fotografii rămân.

- Mobil/tabletă: `public/assets/images/hero-cream-mobile.webp`, 1095 × 1437 px, 59.336 octeți.
- Laptop/desktop: `public/assets/images/hero-cream-desktop.webp`, 1784 × 882 px, 83.390 octeți.
- Stiluri izolate: `public/assets/hero-cream.css`.
- Panoul de preț/durată păstrează `rgba(255,247,228,.70)` și `saturate(1.08) blur(2px)`.
- Imaginea potrivită este selectată prin `<picture>`, cu schimbarea compoziției la 1100 px.
- Textele, recenziile, navigația și butoanele sunt HTML, nu imprimate în fotografie.
- Separat, la cererea utilizatorului, cele trei carduri cu pașii programării au
  primit spațiere, borduri, colțuri rotunjite și pictograme pe fundal discret;
  conținutul lor a rămas neschimbat.

Fundalurile au fost prelucrate în **modul editare, cu instrumentul integrat ImageGen**,
nu prin CLI. Au fost eliminate elementele UI din referințe, apoi rezultatele au fost
convertite în WebP (calitate 88). Asseturile anterioare rămân în proiect.

### Prompt final pentru mobil

> Use case: precise-object-edit. Asset type: real website mobile hero background. Edit target: supplied cream massage website mockup. Remove ALL interface, all letters and text, ALL buttons, icons, logos, review stars, rating, price card and its borders/background, slogan plaque, navigation and the dark location strip. Return ONLY the clean photographic/background layer. Crop away the top header and location strip (the first 190 pixels of the 1024x1536 reference), keeping the entire remaining composition from y=190 to bottom. Aim for a 1024x1344 portrait canvas. Lock the therapist and client exactly as shown: same real faces, hair, anatomy, white shirt, hands pressing the upper back, client resting on the towel, same scale and positions relative to the remaining crop. Do not move or enlarge the people. Reconstruct the cream wall behind text on the left and the towel/softly defocused leaves behind the removed card. Keep the cream #f6f0e4 mood, wooden slats at upper right, soft green foliage and bright natural light. Retain clean light cream negative space on the left and in the lower quarter for live HTML text/controls. Bottom should end as plain warm cream, not green. No text, numbers, icons, rectangles, cards, branding, watermarks or interface. This is a professional nonsexual therapeutic massage scene.

### Prompt final pentru desktop

> Use case: precise-object-edit. Asset type: desktop website hero photographic background, not a mockup. Edit target: supplied wide cream massage website design. Remove ALL text, navigation, logos, icons, rating/stars, slogan plaque, price/benefits panel and every button. Return ONLY the clean background photograph and cream negative space. Crop away the top 115 pixels containing the navbar and location strip, keeping the remaining full-width scene. Output a wide landscape image about 1672x826, aspect ratio approximately 2:1. CRITICAL: preserve the therapist and client exactly as in the reference: faces, identity, hair, white clothing, anatomy, bent posture, hands pressing the upper back, client resting on towel, original scale and exact placement on the right. Keep the therapist's head near the top at 68% of canvas width, client head at roughly 54% width and 55% height. Do not make the therapist smaller or center the scene. Keep warm cream negative space on the left, wooden slats and plant wall on the right, subtle plants along the sides. Reconstruct only the underlying cream wall, towel and softly defocused leaves in the areas formerly covered by interface elements. The lower quarter should softly fade into warm cream #f6f0e4, leaving clean space for real HTML controls. NO text, letters, numbers, logos, icons, UI, cards, rectangles, borders or watermark anywhere. Natural photorealistic professional therapeutic massage scene, nonsexual.

### Verificare reproductibilă

`node tests/hero-cream-test.mjs http://127.0.0.1:8077/ --screenshots`

Verifică 15 lățimi între 320 și 1920 px, selectarea imaginilor responsive,
transparența exactă a panoului, lipsa depășirilor și a suprapunerilor cu textul,
dimensiunile butoanelor și cardurile pașilor. Verifică meniul, ancora FAQ,
deschiderea/închiderea programării și galeria, fără a trimite formulare sau a
iniția apeluri. Traficul Google este blocat în test pentru a nu polua rapoartele.
Capturile pentru mobil, tabletă și desktop se salvează în `.runtime/`.

### Hero complet în primul ecran pe laptop

La cererea suplimentară din 13 octombrie, ferestrele de laptop (lățime peste
1100 px, înălțime până la 950 px) au o compoziție adaptată înălțimii disponibile
prin `svh`, incluzând navbarul și bara de localizare. Textul și spațierile se
adaptează fără scalarea globală a paginii; fotografia își păstrează proporțiile,
iar panoul rămâne la aceeași transparență. Sub 580 px înălțime, scrollul este
păstrat pentru a nu micșora excesiv conținutul. Varianta de telefon este neschimbată.

Testul verifică suplimentar că hero-ul și toate cele trei butoane încap în primul
ecran la 1280×600, 1280×720, 1366×650, 1366×768, 1440×800, 1536×864, 1903×907
(dimensiunea capturii utilizatorului) și 1920×900 px, fără suprapuneri cu panoul.

### Ajustări de lizibilitate — 23 octombrie 2026

- Cele patru servicii din hero sunt mărite cu 25% la toate breakpoint-urile. Descrierea „Alege tipul de masaj…” este mărită separat cu 20%, iar titlul își păstrează dimensiunea.
- Pe telefon (maximum 700 px), fiecare rând primește opt umbre albe de maximum 1 px, fără blur, pentru un contur fin de jur împrejurul literelor. Nu există panou sau fundal transparent în spatele textelor; desktopul nu are această umbră.
- Subtitlul logo-ului devine „MASAJ & STARE DE BINE”, iar ultimul beneficiu devine „Masaj și stare de bine fără să pleci de acasă.”, integral evidențiat ca în referință.
- Panoul de preț este retras o coloană spre dreapta pe desktop pentru a evita suprapunerile cu lista mărită. Transparența sa de 70%, fotografia, trackingul și restul paginii rămân neschimbate.
- Testul responsive verifică și aceste restricții, inclusiv potrivirea completă a hero-ului în primul ecran pe laptop.
