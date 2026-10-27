<?php

// REVISAR: traducción pendiente de revisión por hablante nativo
// Quechua del sur de Bolivia (frases simples). Mismas claves que resources/lang/es/privacy.php.
// Los nombres de los meses van en castellano (así se usan en la práctica). Las cadenas usan comillas
// dobles porque el quechua lleva apóstrofos (p'unchaw, k'ullu, llank'ay...).

return [
    'page_title' => "Willakuyta waqaychay | YURA Platform",
    'meta_description' => "Ima willakuykunatam YURA Platform waqaychan, pikunan qhawayta atinku, imapaqmi servin chaymanta imaynatam allichayta mañakuna. Borrador, yachaywasi qhawarinankama.",

    'eyebrow' => "Willakuyta waqaychay",
    'title' => "Imaynatam willakuykunata waqaychanchik YURApi",
    'lead' => "Mana sasa simikunawan willayku: ima willakuykunatam plataforma waqaychan, pikunam qhawayta atinku, imaynatataq allichayta icha chinkachiyta mañakuyta atinki.",
    'updated' => "Borradorpa qhipa musuqchasqan: :date",
    'date_format' => ":day p'unchaw :month killapi, :year",
    'months' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],

    'pending_tag' => "MANARAQ TAKYACHISQA",

    'draft_title' => "Borrador: yachaywasi chaymanta abogadokunap yanapaynin qhawarinankukama suyashan",
    'draft_text' => "Kay qillqaqa kunan plataforma imaynatam llank'asqanta willan, códigonman chaymanta documentacionninman hina. Manaraq ley qillqa tukusqachu. «Manaraq takyachisqa» nisqa kaqkunata yachaywasi huntachinan icha takyachinan.",

    'toc_title' => "Kay p'anqapi",
    'toc' => [
        'resumen' => "Pisi simiwan",
        'responsables' => "Pikunan willakuykunata qillqan chaymanta waqaychan",
        'datos' => "Ima willakuykunatam waqaychanchik",
        'quien' => "Pikunan willakuykunata qhawayta atin",
        'uso' => "Imapaqmi servin",
        'compartir' => "Pikunawanmi rakinakun",
        'conservacion' => "Hayk'a tiempotam waqaychakun",
        'derechos' => "Imaynatam willakuyta qhawariyta, allichayta icha chinkachiyta mañakuna",
        'seguridad' => "Imaynatam cuidanchik",
        'cambios' => "Kay qillqa tikraykuna",
        'pendientes' => "Ima takyachinanchik",
    ],

    // 1. Pisi simiwan
    'summary' => [
        'title' => "Pisi simiwan",
        'items' => [
            "Sapa yachaqpa notankunapaq, asistenciankunapaq chaymanta quechua llank'ayninkunapaq necesitasqallanchiktam waqaychanchik.",
            "Sapa runa rolninman hina qhawanallantam qhawan: ayllukunaqa wawankunallatam qhawanku.",
            "Kay plataformapi manam publicidad kanchu, manataqmi hawa llaqtakunap analitica llank'ayninkuna kanchu.",
            "Wawaykip willakuyninkunata allichayta icha chinkachiyta mañakuyta atinki.",
        ],
    ],

    // 2. Responsables
    'owners' => [
        'title' => "Pikunan willakuykunata qillqan chaymanta waqaychan",
        'p1' => "YURA Platform huk yachay proyectom: robot yachaqkunawan quechuata ruwan, chaymanta yachachiqkunapaq ayllukunapaqpas digital yachachina cuadernom.",
        'p2' => "Wawakunap, waynakunap willakuyninkunata yachaywasi chaymanta YURA proyecto equipo qillqanku. Chayrayku paykunam cuidanan, tapuyniykikunatapas kutichinanku.",
        'org_label' => "Yachaywasi chaymanta proyecto equipo",
        'org_pending' => "Yachaywasip chaymanta equipop kamachikuq sutin.",
    ],

    // 3. Datos
    'data' => [
        'title' => "Ima willakuykunatam waqaychanchik",
        'intro' => "Kaykunam kunan sistemapi waqaychasqa kashan.",
        'groups' => [
            'students' => [
                'title' => "Sapa yachaqmanta",
                'items' => [
                    "Suti chaymanta apellidokuna.",
                    "Kurso chaymanta paralelo.",
                    "Edad chaymanta género (qhari icha warmi).",
                    "Quechua yachaypa nivelnin (básico, intermedio icha avanzado).",
                    "Qillqasqa p'unchaw chaymanta estado (activo icha mana).",
                    "Foto, yachaywasi churayta munaqtinlla (manam necesariochu).",
                ],
            ],
            'academic' => [
                'title' => "Notakuna chaymanta asistencia",
                'items' => [
                    "Notakuna chaymanta willanakuna sapa materiapi chaymanta sapa trimestrepi.",
                    "Nota tikrasqa willakuy: pitaq tikrarqan, ñawpaq nota, musuq nota chaymanta imarayku.",
                    "Asistencia sapa p'unchawpi chaymanta materiapi (presente, falta, licencia icha retraso).",
                ],
            ],
            'accounts' => [
                'title' => "Yachachiqkunamanta, ayllukunamanta, kamachiqkunamanta",
                'items' => [
                    "Suti, correo electrónico chaymanta telefono.",
                    "Tutorkunap carnet de identidadnin (CI), qillqachiqtinku mañasqa.",
                    "Rol (yachachiq, tayta, mama icha tutor) chaymanta cuentap estadon.",
                    "Foto, churakuqtinlla (manam necesariochu).",
                    "Pakasqa simiqa manam qillqasqayki hinachu waqaychakun: pakasqallam waqaychakun.",
                    "Ima yachaqkunawan ayllu watasqa kasqanmanta chaymanta ima permisokuna kasqanmanta (qhawayta, allichayta icha reportekunata chaskiyta).",
                ],
            ],
            'robot' => [
                'title' => "Robotwan llank'aykuna",
                'items' => [
                    "Yachachiq akllan kursota, paralelota, materiata, trimestreta chaymanta yachaqkunata.",
                    "Robotqa sapa yachaqpa pisi sutinllata, kursota, paralelota chaymanta tapuykunallata chaskin. Contrato técnico nisqanman hina, manam carnet de identidadta, ayllup willakuyninta, pakasqa simikunata nitaq audiota chaskinchu.",
                    "Robotqa sapa tapuypaq kutichiy allinchu kasqanta, puntokunata chaymanta horata kutichin. Chaywanmi llank'aypa notan waqaychakun.",
                ],
                'pending' => "Robot kikin equipompi voz grabacionkunata waqaychanmanchu, hayk'a tiempotataq, chayta takyachina.",
            ],
            'audit' => [
                'title' => "Auditoria registrokuna",
                'items' => [
                    "Kamachiypi pipas cuentata ruwaptin, tikraptin, suspendeqtin icha chinkachiptin, icha huk gestion escolarta kichaptin icha wichq'aptin, sistemaqa anotan: pitaq ruwarqan, imatam ruwarqan, haykaqtam chaymanta ima dirección IP-manta.",
                    "Kay registrokunapiqa manam yachaqkunap sutinkuna waqaychakunchu. Kamachiylla qhawayta atin.",
                ],
            ],
            'site' => [
                'title' => "Sitiota icha appta llank'achispa",
                'items' => [
                    "Kichasqa p'anqakuna (qallariy, servicios, documentos chaymanta maskay) manam kikinpaq willakuykunata mañanchu.",
                    "Sitiopiqa cookies técnicas de sesión llank'achikun, yaykusqaykita waqaychanapaq chaymanta formulariokunata cuidanapaq. Manam publicidad nitaq hawa runakunap analitica cookiesninkuna kanchu.",
                    "Mana pantaspa yaykunapaq, sistemaqa yaykuy yachaykunata IP dirección nisqawan yupan: achka kutita pantaptinqa huk minutota suyana.",
                    "Sitiop siminta rikuchinapaq, navegadorqa Outfit nisqa qillqayta Google Fonts servicioman mañan.",
                    "App móvilqa kikin cuentakunatam llank'achin. Yaykuptiyki, teléfonoykipi huk yaykuna codigota waqaychan, 30 p'unchawpi tukukuq (ayllukunapaq).",
                ],
            ],
        ],
    ],

    // 4. Pikunan qhawayta atin
    'access' => [
        'title' => "Pikunan willakuykunata qhawayta atin",
        'intro' => "Sapa rolqa llank'ayninpaq necesitasqallantam qhawan.",
        'roles' => [
            'admin' => [
                'name' => "Kamachiy",
                'text' => "Llapa registrokunata qhawan, kamachin: yachaqkunata, cuentakunata, notakunata, asistenciata chaymanta auditoriata. Paylla willakuykunata wiñaypaq chinkachiyta atin, chaymanta gestion escolarta wichq'ayta icha kichayta atin.",
            ],
            'academic' => [
                'name' => "Yachay kamachiy (coordinación académica)",
                'text' => "Yachaywasip yachaqninkunata, notakunata chaymanta asistenciata qhawan, ayllukunata tutorkunatapas kamachin.",
            ],
            'teacher' => [
                'name' => "Yachachiqkuna",
                'text' => "Paykunaman churasqa yachaqkunata qhawanku. Notakunata asistenciatapas qillqanku, robotpa llank'aykunatapas churanku.",
            ],
            'family' => [
                'name' => "Ayllukuna (tayta, mama icha tutor)",
                'text' => "Sistemapi watasqa yachaqkunallata qhawanku: notankunata, asistenciankunata chaymanta ñawpaqman riyninkuta.",
            ],
            'student' => [
                'name' => "Cuentayuq yachaqkuna",
                'text' => "Kikinpa perfilninta chaymanta ñawpaqman riyninta qhawanku.",
            ],
            'robot' => [
                'name' => "YURA robot",
                'text' => "Pay kaqlla llank'aytam chaskin: «Robotwan llank'aykuna» nisqapi willasqanchikta.",
            ],
            'visitor' => [
                'name' => "Cuentayuq mana kaqkuna",
                'text' => "Manam kikinpaq willakuykunata qhawankuchu. Kichasqa p'anqakunapi proyectomanta tukuy willakuykuna, activasqa kaptinqa, suti mana kaq yupaykunallapas kanku.",
            ],
        ],
        'pending' => "Yachaywasipi kamachiy chaymanta yachay kamachiy cuentayuqkunap listan.",
    ],

    // 5. Imapaqmi servin
    'purpose' => [
        'title' => "Imapaqmi servin",
        'items' => [
            "Notakunata chaymanta asistenciata qillqanapaq qhawanapaqpas.",
            "Ayllukuna wawankunap ñawpaqman riyninta qhawanankupaq.",
            "Yachachiqkuna yachachina cuadernonkuta apanankupaq, robotwan quechua llank'aykunata churanankupaqpas.",
            "Yachaywasipaq ayllukunapaqpas PDF icha Excel reportekunata ruwanapaq.",
            "Sistemap waqaychayninta cuidanapaq, pitaq ima tikrarqan yachanapaqpas.",
        ],
    ],

    // 6. Pikunawanmi rakinakun
    'sharing' => [
        'title' => "Pikunawanmi rakinakun",
        'items' => [
            "Sistema ukhupi, sapa runap rolninman hinalla («Pikunan willakuykunata qhawayta atin» qhaway).",
            "Robotwan, sapa llank'ayman necesitasqallanta.",
            "PDF icha Excel reportekuna sistemamanta lluqsinku pipas uraykachiqtin. Uraykachiqqa cuidanan, mana mast'arinanchu.",
        ],
        'pending' => [
            "Maypichus willakuykuna waqaychasqa kasqanmanta (servidor chaymanta suyu).",
            "Yachaywasi yachay kamachiqkunaman willakuykunata qunanchu icha manachu, imaynaña kaqtinpas.",
        ],
    ],

    // 7. Conservación
    'retention' => [
        'title' => "Hayk'a tiempotam waqaychakun",
        'pending' => "Hayk'a tiempotataq sapa willakuy waqaychakun (yachaqkuna, notakuna, asistencia, cuentakuna, fotokuna chaymanta auditoria). Yachaywasim akllanqa.",
        'today' => "Kunan sistema imatam ruwayta saqin:",
        'items' => [
            "Huk yachaqta icha cuentata chinkachiptiyki, ñawpaqta papelerapi kanqa, chaymanta kutichiyta atikun.",
            "Kamachiqmi wiñaypaq chinkachiyta atin. Hinaptinqa fotonpas chinkachisqa kanqa.",
            "Wichq'asqa gestion escolarqa manam tikrayta saqinchu, kamachiq huktawan kichanankama.",
        ],
    ],

    // 8. Derechos
    'rights' => [
        'title' => "Imaynatam willakuyta qhawariyta, allichayta icha chinkachiyta mañakuna",
        'intro' => "Yachaqpa mamanmi, taytanmi icha tutornin kanki chayqa, kayta mañakuyta atinki:",
        'items' => [
            "Wawaykip ima willakuykunatam kanchik, chayta qhawayta.",
            "Mana allin willakuyta allichayta.",
            "Willakuyninkunata chinkachiyta, ahinataq fotota.",
        ],
        'how' => "Imaynatam ruwanki: yachachiqwan icha yachaywasi directoriawan rimay, icha kay ñankunawan qillqamuwayku. Yachaqkuna cuidanapaq, kutichinchikmanta ñawpaqta pichus mañakusqanta takyachisunchik.",
        'contact_title' => "Rimanakuy",
        'email' => "Correo",
        'whatsapp' => "WhatsApp",
        'address' => "Kaypi",
        'hours' => "Horario",
        'no_contact' => "Yachachiqniykita icha yachaywasiykip directorianta tapuy.",
        'pending' => "Allichay icha chinkachiy mañakuykunata kutichinapaq tiempo, pitaq paykunata uyarin.",
    ],

    // 9. Seguridad
    'security' => [
        'title' => "Imaynatam cuidanchik",
        'items' => [
            "Pakasqa simikunaqa pakasqallam waqaychakun.",
            "Yaykuypiqa sapa minutupi pantaykunapaq huk límite kan.",
            "Suspendisqa cuentaqa manam yaykuyta atinchu.",
            "Sapa ayllu watasqa yachaqkunallatam qhawan.",
            "Cuentakunap chaymanta gestionkunap importante tikraykunan auditoria registropi qillqasqa kanku.",
        ],
        'pending' => "Segura conexión (HTTPS): sitiop definitivo web direccionnin kaptin takyachina.",
        'minors' => "Wawakunap, waynakunap willakuyninkuna kasqanrayku, necesitasqallanchiktam mañanchik, fotokunapas manam necesariochu.",
        'minors_pending' => "Ayllukunap permisonqa yachaqkunap fotonkunata icha rikch'ayninkunata llank'achinapaq. Yachaywasim akllanqa.",
    ],

    // 10. Cambios
    'changes' => [
        'title' => "Kay qillqa tikraykuna",
        'text' => "Plataformapi imapas tikraptin, kay qillqata chaymanta patapi p'unchawta musuqchasunchik. Yachaywasi chaymanta abogadokuna qhawarisqankupi, kaypi qhipa versionta willasunchik.",
    ],

    // 11. Pendientes
    'todo' => [
        'title' => "Ima takyachinanchik",
        'intro' => "Borradormanta qhipa versionman riykunapaq, yachaywasi chaymanta abogadokunap yanapayninwan kaykunata akllana icha takyachina:",
        'items' => [
            "Yachaywasip chaymanta equipop kamachikuq sutin.",
            "Hayk'a tiempotam sapa willakuy waqaychakun.",
            "Ayllukunap permisonqa yachaqkunap foto chaymanta rikch'aynin llank'achinapaq.",
            "Maypichus willakuykuna waqaychasqa kasqanmanta (proveedor chaymanta suyu) chaymanta segura conexión (HTTPS).",
            "Robot voz grabacionkunata waqaychanchu, hayk'a tiempotapas.",
            "Allichay icha chinkachiy mañakuykunata kutichinapaq tiempo chaymanta pitaq kutichinan.",
            "Ima normakuna kasqanmanta chaymanta qhipa qillqa imaynata qillqasqa kanan.",
        ],
    ],

    'back_home' => "Qallariyman kutiy",
    'go_login' => "Yaykuy",
];
