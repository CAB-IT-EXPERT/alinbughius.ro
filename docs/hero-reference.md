# Hero: referință vizuală și galerie separată

Istoric al referințelor vizuale. Versiunea curentă este descrisă în secțiunea din 7 octombrie 2026, la final.

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
