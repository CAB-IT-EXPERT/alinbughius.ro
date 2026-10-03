# Hero: referință vizuală și galerie separată

Actualizare aprobată și publicată pe 29 septembrie 2026.

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
