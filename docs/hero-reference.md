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
