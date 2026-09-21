<?php
declare(strict_types=1);

$catalog = [
    'terapeutic' => [
        'name' => 'Masaj terapeutic', 'duration' => '60 min', 'minutes' => 60, 'price' => 200, 'sessions' => 4, 'package' => 700,
        'image' => 'terapeutic.jpg', 'tag' => 'Relaxare profundă și recuperare',
        'description' => 'Masajul terapeutic este dedicat relaxării profunde și recuperării corpului. Ajută la ameliorarea durerilor de spate, tensiunii cervicale, contracturilor musculare și stărilor de oboseală acumulate din stresul zilnic.',
        'benefits' => [
            ['Reducerea stresului și a anxietății', 'Scade nivelul cortizolului (hormonul stresului) și stimulează eliberarea de endorfine, inducând o stare profundă de bine și relaxare mentală.'],
            ['Creșterea flexibilității și a mobilității', 'Destinde țesuturile moi și musculatura rigidă, redând amplitudinea normală de mișcare a articulațiilor.'],
            ['Calmarea durerilor cronice și acute', 'Scapi eficient de durerea de spate, de disconfortul din zona lombară sau cervicală și ameliorezi migrenele cauzate de tensiunea musculară.'],
            ['Calitatea somnului', 'Ajută la relaxarea sistemului nervos, facilitând un somn mai profund și mai odihnitor.'],
        ],
    ],
    'deep-tissue' => [
        'name' => 'Deep Tissue', 'duration' => '60 min', 'minutes' => 60, 'price' => 250, 'sessions' => 4, 'package' => 850,
        'image' => 'deep-tissue.jpg', 'tag' => 'Abordarea inteligentă a eliberării musculare',
        'description' => 'Uită de mitul că masajul profund trebuie să fie dureros. Terapia Deep Tissue folosește un model modern, neuro-mio-fascial: lucrăm încet, strat cu strat, comunicând direct cu sistemul tău nervos pentru a elibera tensiunea în mod natural.',
        'method_intro' => 'Prin conceptul de Atingere Inteligentă, transformăm terapia într-un dialog eficient, bazat pe 4 piloni:',
        'pillars' => [
            ['Viteză adaptată', 'Mișcări lente care elimină reflexele de apărare ale corpului.'],
            ['Presiune graduală', 'Forță constantă care relaxează țesutul fără să atace mușchiul.'],
            ['Comunicare activă', 'Ne ghidăm după o scară a durerii pentru a rămâne doar în zona eliberatoare și plăcută.'],
            ['Intenție și știință', 'Activăm mecanismele interne care blochează durerea cronică.'],
        ],
        'benefits' => ['Eliminarea blocajelor profunde și a rigidității musculare.', 'Restabilirea mobilității și a libertății de mișcare.', 'Reducerea stresului fizic prin resetarea sistemului nervos.'],
    ],
    'relaxare' => [
        'name' => 'Masaj de relaxare', 'duration' => '60 min', 'minutes' => 60, 'price' => 200, 'sessions' => 4, 'package' => 720,
        'image' => 'relaxare.jpg', 'tag' => 'O pauză doar pentru tine',
        'description' => 'Oprește-te puțin. Respiră. Dă-i corpului un restart. Masajul de relaxare eliberează tensiunea acumulată, liniștește corpul și mintea și te ajută să te reconectezi cu starea de bine. La final, te poți simți refăcut, relaxat și ca nou.',
        'benefits' => ['Reduce tensiunea și stresul', 'Relaxează musculatura', 'Stimulează circulația', 'Îmbunătățește starea de bine', 'Favorizează relaxarea profundă', 'Îți oferă o stare de prospețime și energie'],
    ],
    'lomi-lomi' => [
        'name' => 'Lomi-Lomi', 'duration' => '90–120 min', 'minutes' => 120, 'price' => 250, 'sessions' => 4, 'package' => 850,
        'image' => 'lomi-lomi.jpg', 'tag' => 'Relaxare profundă',
        'description' => 'Descoperă masajul sacru hawaiian Lomi-Lomi, un ritual al „mâinilor iubitoare” creat pentru a-ți oferi o relaxare profundă și o resetare spirituală completă. Inspirat de mișcările fluide ale valurilor oceanului, terapeutul folosește atingeri continue și ritmice – uneori cu antebrațele și coatele – pentru a elibera tensiunea musculară și a calma sistemul nervos.',
        'benefits' => ['🌿 Reducerea stresului și a tensiunii', '💆 Relaxarea profundă a musculaturii', '🧘 Reconectare cu tine și cu propriul corp', '✨ Regăsirea liniștii și a echilibrului interior', '🔄 Un adevărat restart pentru trup și minte', '🌙 O stare de bine și relaxare care te ajută să te simți refăcut și ca nou'],
    ],
    'anticelulitic' => [
        'name' => 'Masaj anticelulitic', 'duration' => '60 min', 'minutes' => 60, 'price' => 170, 'sessions' => 6, 'package' => 900,
        'image' => 'anticelulitic.jpg', 'tag' => 'Îngrijire cu consecvență',
        'description' => 'Masajul anticelulitic este o tehnică dinamică, menită să stimuleze circulația, drenajul și metabolismul țesuturilor. Prin manevre specifice și profunde, ajută la îmbunătățirea aspectului pielii și la redarea unei senzații de tonus și ușurință.',
        'benefits' => ['Reduce aspectul de „coajă de portocală”', 'Stimulează circulația sanguină și limfatică', 'Sprijină drenajul și eliminarea retenției de lichide', 'Contribuie la tonifierea și fermitatea pielii', 'Îmbunătățește aspectul și textura pielii', 'Oferă o senzație de ușurință și tonus'],
    ],
    'reflexoterapie' => [
        'name' => 'Reflexoterapie', 'duration' => '50 min', 'minutes' => 50, 'price' => 160, 'sessions' => 4, 'package' => 560,
        'image' => 'reflexoterapie.jpg', 'tag' => 'Confort, pas cu pas',
        'description' => 'Oferă-i corpului un moment de resetare și susține-i procesele naturale de detoxifiere. Reflexoterapia stimulează zonele reflexe ale tălpilor prin presiuni specifice, contribuind la relaxare, echilibru și revitalizare.',
        'benefits' => ['Susține detoxifierea naturală', 'Stimulează circulația', 'Favorizează relaxarea profundă', 'Contribuie la regenerarea organismului', 'Reduce senzația de oboseală și tensiune', 'Oferă o stare de echilibru și revitalizare'],
    ],
    'suedez' => [
        'name' => 'Masaj suedez', 'duration' => '60 min', 'minutes' => 60, 'price' => 200, 'sessions' => 4, 'package' => 700,
        'image' => 'suedez.jpg', 'tag' => 'Echilibru pentru corp',
        'description' => 'Tehnici clasice și mișcări ritmice pentru relaxarea musculaturii. O ședință adaptată ritmului și preferințelor tale.',
        'benefits' => ['💆 Relaxarea și detensionarea musculaturii', '🌿 Reducerea stresului și a oboselii acumulate', '🩸 Stimularea circulației sanguine', '🧘 Diminuarea tensiunii fizice și mentale', '✨ Îmbunătățirea stării generale de bine', '🔄 Susținerea recuperării după efort și perioade solicitante', '🌙 Relaxare profundă și un somn mai odihnitor', '🌸 O senzație de ușurință, revitalizare și echilibru în întregul corp'],
    ],
    'drenaj-limfatic' => [
        'name' => 'Drenaj limfatic', 'duration' => '90 min', 'minutes' => 90, 'price' => 230, 'sessions' => 4, 'package' => 800,
        'image' => 'drenaj.jpg', 'tag' => 'Mișcări blânde, ritm lent',
        'description' => 'Drenajul limfatic este o tehnică blândă și ritmică, menită să stimuleze circulația limfei și să susțină procesul natural de eliminare a excesului de lichide din țesuturi. Este ideal pentru o senzație de ușurință, relaxare și revitalizare a întregului corp.',
        'benefits' => ['Reduce senzația de picioare grele și umflate', 'Susține drenarea excesului de lichide', 'Stimulează circulația limfatică', 'Contribuie la detoxifierea naturală a organismului', 'Ajută la regenerarea și revitalizarea țesuturilor', 'Oferă o stare de relaxare și ușurință'],
    ],
];

foreach ($catalog as &$service) {
    $service['package'] = (int) round($service['price'] * $service['sessions'] * 0.9);
}
unset($service);
return $catalog;
