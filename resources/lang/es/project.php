<?php

/*
 | Textos de la página pública «Proyecto YURA» (ruta /feria, vista resources/views/home.blade.php).
 | Cada afirmación de esta página debe poder comprobarse en el código o en docs/ (no se inventan
 | cifras, resultados, testimonios ni datos de contacto). El archivo qu/project.php debe tener
 | exactamente las mismas claves.
 */

return [

    'meta' => [
        'title' => 'Proyecto YURA | Robot que practica quechua y cuaderno digital',
        'description' => 'YURA: robot con reconocimiento de voz que acompaña la práctica oral del quechua en primaria, con cuaderno digital de notas para docentes y familias.',
    ],

    'hero' => [
        'eyebrow' => 'Proyecto YURA',
        'title' => 'YURA: el robot que escucha y acompaña la práctica del quechua en primaria',
        'lead' => 'YURA es un robot con reconocimiento de voz que practica quechua oral con niñas y niños de primaria. Cada resultado llega al cuaderno pedagógico digital del docente, y las familias lo consultan en la web y en la app.',
        'cta_login' => 'Ingresar',
        'cta_panel' => 'Ir a mi panel',
        'cta_faq' => 'Preguntas frecuentes',
        'flow_label' => 'Las tres piezas de YURA',
        'flow' => [
            ['title' => 'Robot YURA', 'text' => 'Hace preguntas en quechua y escucha las respuestas.'],
            ['title' => 'Cuaderno digital', 'text' => 'Guarda la nota por trimestre y materia.'],
            ['title' => 'Web y app', 'text' => 'La familia consulta el avance.'],
        ],
    ],

    'problem' => [
        'title' => 'El quechua se aprende hablándolo',
        'intro' => 'El quechua se mantiene vivo cuando se habla. En un aula numerosa no siempre hay tiempo para que cada niña y cada niño responda en voz alta, y registrar esa participación también toma tiempo. YURA nace para ayudar en eso.',
        'challenge_title' => 'El reto en el aula',
        'challenge' => [
            'Cuando el curso es numeroso, cada estudiante tiene pocas oportunidades de hablar.',
            'Anotar a mano la participación y la nota de todos lleva tiempo al docente.',
            'La familia no siempre puede ver a tiempo cómo le va a su hija o hijo.',
        ],
        'proposal_title' => 'Lo que propone YURA',
        'proposal' => [
            'Un robot que hace preguntas de vocabulario en quechua, escucha las respuestas en voz alta y las evalúa.',
            'Un cuaderno pedagógico digital donde la nota llega sola, ordenada por trimestre y materia.',
            'Una web y una app donde cada familia consulta el avance de sus hijas e hijos.',
        ],
        'note' => 'YURA es un apoyo al trabajo del docente, no lo reemplaza. Es un proyecto educativo en desarrollo: aquí contamos lo que ya funciona, sin prometer resultados que todavía no se han medido.',
    ],

    'how' => [
        'title' => 'Cómo funciona, paso a paso',
        'intro' => 'Del aula a la casa, el recorrido es siempre el mismo.',
        'steps' => [
            ['title' => 'El docente crea la actividad', 'text' => 'Desde su panel elige el curso y paralelo, la materia, el trimestre y qué practicar: saludos, colores, números, familia, animales y más. La plataforma forma los grupos y sortea quiénes responden.'],
            ['title' => 'El robot practica con el grupo', 'text' => 'YURA recibe la actividad, llama a cada estudiante por su nombre, hace preguntas de vocabulario en quechua, escucha las respuestas y calcula el resultado con un diccionario de quechua.'],
            ['title' => 'La nota vuelve al cuaderno digital', 'text' => 'Cada grupo recibe una nota sobre 100, y todos sus integrantes la reciben en la casilla HACER que eligió el docente. Si la actividad se cancela o falla, no se guarda ninguna nota parcial.'],
            ['title' => 'La familia la ve en la web y en la app', 'text' => 'Con su cuenta, la familia consulta la asistencia, los promedios y las actividades del robot de sus hijas e hijos.'],
        ],
        'step_label' => 'Paso',
        'note' => 'El docente puede cancelar una actividad en curso o reintentar una que falló; el reintento conserva los mismos grupos.',
    ],

    'benefits' => [
        'title' => 'Qué gana cada quien',
        'intro' => 'YURA está pensado para las personas que participan del proceso educativo.',
        'cards' => [
            [
                'label' => 'Familias',
                'title' => 'Saber cómo van sus hijas e hijos',
                'items' => [
                    'Consultan notas por trimestre, asistencia y actividades del robot desde la web o la app.',
                    'Ven solo a sus hijas e hijos: el acceso depende de un vínculo autorizado por el colegio.',
                    'El portal se puede leer en castellano y en quechua.',
                ],
            ],
            [
                'label' => 'Docentes y directivos',
                'title' => 'Menos papeleo, más práctica oral',
                'items' => [
                    'El robot toma la práctica oral y deja la nota registrada en el cuaderno.',
                    'Cuaderno por trimestre y materia, con historial de cambios de cada nota.',
                    'Reportes en PDF y Excel, y asistencia por curso y paralelo.',
                    'La dirección gestiona usuarios, cursos, paralelos y año escolar, con registro de auditoría.',
                ],
            ],
            [
                'label' => 'Jurado, aliados y prensa',
                'title' => 'Tecnología al servicio de una lengua originaria',
                'items' => [
                    'La plataforma ya cubre el recorrido completo: crear la actividad, recibir el resultado del robot, guardar la nota y mostrarla a la familia.',
                    'Cada pregunta sale de un diccionario de quechua con una regla fija, sin inventar preguntas ni respuestas.',
                    'Cuida los datos de niñas y niños: al robot solo se le envía el nombre corto de cada estudiante, nunca cédula, datos de la familia, claves ni audios.',
                    'El intercambio entre la plataforma y el robot está documentado en un contrato técnico (versión 1.2).',
                ],
                'link' => 'Leer cómo cuidamos los datos',
            ],
        ],
    ],

    'features' => [
        'title' => 'Lo que incluye hoy',
        'intro' => 'Todo esto ya está disponible en la plataforma.',
        'items' => [
            ['icon' => 'fa-book-open', 'title' => 'Cuaderno pedagógico digital', 'text' => 'Notas por trimestre y materia, organizadas en Ser, Saber y Hacer, con historial de cambios.'],
            ['icon' => 'fa-calendar-check', 'title' => 'Asistencia', 'text' => 'Registro de asistencia por curso y paralelo, con reporte en PDF.'],
            ['icon' => 'fa-robot', 'title' => 'Actividades del robot', 'text' => 'Crear, seguir el estado, cancelar y reintentar actividades; la nota llega a la casilla Hacer elegida.'],
            ['icon' => 'fa-people-group', 'title' => 'Panel para familias', 'text' => 'Asistencia, promedios y actividades del robot de cada hija o hijo vinculado.'],
            ['icon' => 'fa-file-pdf', 'title' => 'Reportes en PDF y Excel', 'text' => 'Listas de estudiantes en PDF y Excel; notas y asistencia en PDF.'],
            ['icon' => 'fa-mobile-screen-button', 'title' => 'App móvil', 'text' => 'Consulta de notas y asistencia para las familias, con vistas también para docentes y administración.'],
            ['icon' => 'fa-layer-group', 'title' => 'Gestión por año escolar', 'text' => 'Cada gestión guarda sus propias notas, asistencia y actividades; la administración puede cerrarla y reabrirla.'],
            ['icon' => 'fa-user-shield', 'title' => 'Cuentas por rol y auditoría', 'text' => 'Administración, docentes y familias ven solo lo que les corresponde; las acciones de administración quedan registradas.'],
            ['icon' => 'fa-language', 'title' => 'Castellano y quechua', 'text' => 'El portal ofrece un selector de idioma entre castellano y quechua.'],
        ],
        'app_link' => 'Descargar la app',
    ],

    'stats' => [
        'title' => 'YURA en cifras',
        'intro' => 'Conteos reales de la plataforma. No incluyen datos personales.',
        'students' => 'Estudiantes',
        'teachers' => 'Docentes',
        'activities' => 'Actividades del robot completadas',
        'grades' => 'Notas registradas',
    ],

    'media' => [
        'title' => 'Mira a YURA',
        'intro' => 'Fotos y video del robot.',
        'photo_alt' => 'Fotografía :n del robot YURA',
        'video_title' => 'Video de YURA',
        'video_text' => 'Mira un video corto del robot.',
        'video_cta' => 'Ver el video',
    ],

    'faq' => [
        'title' => 'Preguntas frecuentes',
        'intro' => 'Si no encuentras tu duda, escríbenos.',
        'items' => [
            ['id' => 'needs', 'q' => '¿Qué necesita mi hija o hijo para participar?', 'a' => 'Nada en casa. La práctica con el robot la organiza el docente en el colegio, y la niña o el niño solo responde en voz alta. La familia necesita una cuenta únicamente si quiere consultar el avance.'],
            ['id' => 'who', 'q' => '¿Quién puede ver las notas?', 'a' => 'Solo personas autorizadas. Cada docente ve a sus estudiantes, la administración del colegio puede ver a todos y cada familia ve únicamente a sus hijas e hijos vinculados. Las notas no se publican en este sitio.'],
            ['id' => 'audio', 'q' => '¿Se guardan las voces o los audios de las niñas y los niños?', 'a' => 'La plataforma no recibe audios: del robot solo le llega el resultado de la actividad (respuestas correctas, incorrectas o sin responder, y la nota del grupo). Tampoco le envía al robot cédulas, datos de la familia ni contraseñas, solo el nombre corto de cada estudiante. Si necesitas más detalle, escríbenos.'],
            ['id' => 'offline', 'q' => '¿Funciona sin internet?', 'a' => 'La plataforma web y la app necesitan conexión a internet. El robot también se conecta para recibir la actividad; luego usa un diccionario guardado en su propio equipo y, si la conexión falla al terminar, conserva el resultado y lo envía cuando vuelve.'],
            ['id' => 'access', 'q' => '¿Cómo pido acceso?', 'a' => 'No hay registro abierto: las cuentas las crea la administración de cada unidad educativa. Si eres docente o familia, pide tu usuario en tu colegio. Si quieres llevar YURA a tu colegio, escríbenos desde el final de esta página.'],
            ['id' => 'price', 'q' => '¿Es gratis?', 'a' => 'Este sitio no publica precios. YURA es un proyecto educativo: escríbenos y te explicamos las condiciones de uso.'],
            ['id' => 'app', 'q' => '¿Hay una app para el celular?', 'a' => 'Sí. La app móvil permite a las familias consultar las notas, la asistencia y las actividades del robot de sus hijas e hijos.'],
            ['id' => 'fail', 'q' => '¿Qué pasa si la actividad falla o se cancela?', 'a' => 'No se guarda ninguna nota parcial. El docente puede reintentar una actividad que falló, con los mismos grupos, o cancelar una que está en curso.'],
        ],
        'link_privacy' => 'Ver la página de privacidad',
        'link_app' => 'Descargar la app',
    ],

    'cta' => [
        'title' => 'Lleva YURA a tu colegio',
        'text' => 'Cuéntanos tu caso y te explicamos cómo empezar. Si ya tienes cuenta, ingresa a tu panel.',
    ],

];
