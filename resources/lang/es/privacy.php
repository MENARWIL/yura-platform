<?php

/*
|--------------------------------------------------------------------------
| Página de Privacidad (BORRADOR pendiente de revisión)
|--------------------------------------------------------------------------
| Vista: resources/views/public/privacy.blade.php
| Mismas claves que resources/lang/qu/privacy.php.
|
| Todo lo que aquí se afirma consta en el código o en la documentación del
| proyecto (modelos, controladores, docs/yura-activity-contract-v1.md y
| docs/api-app-v1.md). Lo que no consta se marca como PENDIENTE.
*/

return [
    'page_title' => 'Privacidad | YURA Platform',
    'meta_description' => 'Qué datos guarda YURA Platform, quién puede verlos, para qué se usan y cómo pedir una corrección. Borrador en revisión por la unidad educativa.',

    'eyebrow' => 'Privacidad',
    'title' => 'Cómo cuidamos los datos en YURA',
    'lead' => 'Aquí explicamos, con palabras sencillas, qué datos guarda la plataforma, quién puede verlos y cómo puedes pedir que se corrijan o se eliminen.',
    'updated' => 'Última actualización del borrador: :date',
    'date_format' => ':day de :month de :year',
    'months' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],

    'pending_tag' => 'PENDIENTE',

    'draft_title' => 'Borrador pendiente de revisión por la unidad educativa y asesoría legal',
    'draft_text' => 'Este texto describe cómo funciona hoy la plataforma, según su código y su documentación. Todavía no es un documento legal definitivo. Los puntos marcados como PENDIENTE los debe completar o confirmar la unidad educativa.',

    'toc_title' => 'En esta página',
    'toc' => [
        'resumen' => 'En pocas palabras',
        'responsables' => 'Quién registra y cuida los datos',
        'datos' => 'Qué datos guarda la plataforma',
        'quien' => 'Quién puede ver los datos',
        'uso' => 'Para qué se usan',
        'compartir' => 'Con quién se comparten',
        'conservacion' => 'Cuánto tiempo se conservan',
        'derechos' => 'Cómo pedir ver, corregir o eliminar datos',
        'seguridad' => 'Cómo los protegemos',
        'cambios' => 'Cambios en este texto',
        'pendientes' => 'Lo que falta confirmar',
    ],

    // 1. En pocas palabras
    'summary' => [
        'title' => 'En pocas palabras',
        'items' => [
            'Guardamos lo necesario para llevar las notas, la asistencia y las actividades de quechua de cada estudiante.',
            'Cada persona ve solo lo que le corresponde según su rol: una familia ve únicamente a sus hijas e hijos.',
            'Esta plataforma no muestra publicidad ni usa herramientas de analítica de terceros.',
            'Puedes pedir que se corrijan o se eliminen los datos de tu hija o hijo.',
        ],
    ],

    // 2. Responsables
    'owners' => [
        'title' => 'Quién registra y cuida los datos',
        'p1' => 'YURA Platform es un proyecto educativo: un robot que practica quechua con los estudiantes y un cuaderno pedagógico digital para docentes y familias.',
        'p2' => 'Los datos de niñas, niños y adolescentes los registran la unidad educativa y el equipo del proyecto YURA. Por eso son ellos quienes deben cuidarlos y responder tus consultas.',
        'org_label' => 'Unidad educativa y equipo del proyecto',
        'org_pending' => 'Nombre oficial de la unidad educativa y del equipo responsable.',
    ],

    // 3. Datos
    'data' => [
        'title' => 'Qué datos guarda la plataforma',
        'intro' => 'Estos son los datos que el sistema guarda hoy.',
        'groups' => [
            'students' => [
                'title' => 'De cada estudiante',
                'items' => [
                    'Nombre y apellidos.',
                    'Curso y paralelo.',
                    'Edad y género.',
                    'Nivel de quechua (básico, intermedio o avanzado).',
                    'Fecha de registro y estado (activo o no).',
                    'Foto, solo si la unidad educativa decide subirla (es opcional).',
                ],
            ],
            'academic' => [
                'title' => 'Notas y asistencia',
                'items' => [
                    'Notas y observaciones por materia y trimestre.',
                    'Historial de cambios de una nota: quién la cambió, la nota anterior, la nueva y el motivo.',
                    'Asistencia por fecha y materia (presente, falta, licencia o retraso).',
                ],
            ],
            'accounts' => [
                'title' => 'De docentes, familias y administración',
                'items' => [
                    'Nombre, correo electrónico y teléfono.',
                    'Carnet de identidad (CI) de los tutores, que se pide al registrarlos.',
                    'Rol (por ejemplo docente, padre, madre o tutor) y estado de la cuenta.',
                    'Foto, solo si se sube (es opcional).',
                    'La contraseña no se guarda tal como la escribes: se guarda cifrada.',
                    'A qué estudiantes está vinculada cada familia y qué permisos tiene (ver, editar o recibir reportes).',
                ],
            ],
            'robot' => [
                'title' => 'Actividades con el robot',
                'items' => [
                    'El docente elige curso, paralelo, materia, trimestre y los estudiantes que participan.',
                    'El robot recibe solo el nombre corto de cada estudiante, el curso, el paralelo y las preguntas. Según el contrato técnico, no recibe carnet de identidad, datos de la familia, contraseñas ni audio.',
                    'El robot devuelve, por cada pregunta, si la respuesta fue correcta, los puntos y la hora. Con eso se guarda la nota de la actividad.',
                ],
                'pending' => 'Confirmar si el robot guarda grabaciones de voz en su propio equipo y por cuánto tiempo.',
            ],
            'audit' => [
                'title' => 'Registros de auditoría',
                'items' => [
                    'Cuando alguien de la administración crea, cambia, suspende o elimina una cuenta, o abre o cierra una gestión escolar, el sistema anota quién lo hizo, qué hizo, cuándo y desde qué dirección IP.',
                    'Estos registros no guardan los nombres de los estudiantes. Solo la administración puede verlos.',
                ],
            ],
            'site' => [
                'title' => 'Al usar el sitio y la app',
                'items' => [
                    'Las páginas públicas (portada, servicios, documentos y búsqueda) no piden datos personales.',
                    'El sitio usa cookies técnicas de sesión, necesarias para mantener tu ingreso y proteger los formularios. No usa cookies de publicidad ni de analítica de terceros.',
                    'Para evitar abusos, el sistema cuenta los intentos de ingreso por dirección IP: tras varios intentos fallidos hay que esperar un minuto.',
                    'Para mostrar la letra del sitio, el navegador pide la tipografía Outfit al servicio Google Fonts.',
                    'La app móvil usa las mismas cuentas. Al ingresar, guarda en el teléfono un código de acceso que vence a los 30 días (familias).',
                ],
            ],
        ],
    ],

    // 4. Quién puede ver
    'access' => [
        'title' => 'Quién puede ver los datos',
        'intro' => 'Cada rol ve solo lo que necesita para su trabajo.',
        'roles' => [
            'admin' => [
                'name' => 'Administración',
                'text' => 'Ve y gestiona todos los registros: estudiantes, cuentas, notas, asistencia y auditoría. Es el único rol que puede eliminar datos de forma definitiva y cerrar o reabrir una gestión escolar.',
            ],
            'academic' => [
                'name' => 'Coordinación académica',
                'text' => 'Ve estudiantes, notas y asistencia de la unidad educativa y administra a los familiares y tutores.',
            ],
            'teacher' => [
                'name' => 'Docentes',
                'text' => 'Ven a los estudiantes que tienen asignados. Registran notas y asistencia y programan las actividades del robot.',
            ],
            'family' => [
                'name' => 'Familias (padre, madre o tutor)',
                'text' => 'Ven solo a los estudiantes con quienes están vinculados en el sistema: sus notas, su asistencia y su progreso.',
            ],
            'student' => [
                'name' => 'Estudiantes con cuenta',
                'text' => 'Ven únicamente su propio perfil y su progreso.',
            ],
            'robot' => [
                'name' => 'Robot YURA',
                'text' => 'Recibe solo la actividad que le toca: lo que se explica en «Actividades con el robot».',
            ],
            'visitor' => [
                'name' => 'Visitantes sin cuenta',
                'text' => 'No ven datos personales. Las páginas públicas muestran información general del proyecto y, cuando se activan, cifras totales sin nombres.',
            ],
        ],
        'pending' => 'Lista de las personas que tienen cuenta de administración y de coordinación académica en la unidad educativa.',
    ],

    // 5. Para qué
    'purpose' => [
        'title' => 'Para qué se usan',
        'items' => [
            'Registrar y consultar notas y asistencia.',
            'Que las familias vean cómo avanzan sus hijas e hijos.',
            'Que los docentes lleven su cuaderno pedagógico y programen las actividades de quechua con el robot.',
            'Preparar reportes en PDF o Excel para la unidad educativa y las familias.',
            'Cuidar la seguridad del sistema y saber quién cambió qué.',
        ],
    ],

    // 6. Compartir
    'sharing' => [
        'title' => 'Con quién se comparten',
        'items' => [
            'Dentro del sistema, solo según el rol de cada persona (ver «Quién puede ver los datos»).',
            'Con el robot, únicamente lo necesario para cada actividad.',
            'Los reportes en PDF o Excel salen del sistema cuando alguien los descarga. Quien los descarga debe cuidarlos y no difundirlos.',
        ],
        'pending' => [
            'Proveedor de alojamiento: dónde están guardados los datos (servidor y país).',
            'Si la unidad educativa debe entregar datos a autoridades del sector educativo y en qué casos.',
        ],
    ],

    // 7. Conservación
    'retention' => [
        'title' => 'Cuánto tiempo se conservan',
        'pending' => 'Cuánto tiempo se conserva cada tipo de dato (estudiantes, notas, asistencia, cuentas, fotos y registros de auditoría). Lo define la unidad educativa.',
        'today' => 'Lo que el sistema permite hoy:',
        'items' => [
            'Al eliminar un estudiante o una cuenta, primero pasa a una papelera y se puede restaurar.',
            'Un administrador puede eliminarlo de forma definitiva. En ese caso también se borra su foto.',
            'Una gestión escolar cerrada no admite cambios, salvo que un administrador la reabra.',
        ],
    ],

    // 8. Derechos
    'rights' => [
        'title' => 'Cómo pedir ver, corregir o eliminar datos',
        'intro' => 'Si eres madre, padre o tutor de un estudiante, puedes pedir:',
        'items' => [
            'Ver qué datos tenemos de tu hija o hijo.',
            'Corregir un dato que esté mal.',
            'Eliminar sus datos, por ejemplo la foto.',
        ],
        'how' => 'Cómo hacerlo: habla con el docente o con la dirección de tu unidad educativa, o escríbenos por alguno de estos medios. Para cuidar a los estudiantes, antes de responder confirmaremos quién hace el pedido.',
        'contact_title' => 'Contacto',
        'email' => 'Correo',
        'whatsapp' => 'WhatsApp',
        'address' => 'Dirección',
        'hours' => 'Horario',
        'no_contact' => 'Pregunta a tu docente o en la dirección de tu unidad educativa.',
        'pending' => 'Plazo para responder a los pedidos de corrección o eliminación, y quién los atiende.',
    ],

    // 9. Seguridad
    'security' => [
        'title' => 'Cómo los protegemos',
        'items' => [
            'Las contraseñas se guardan cifradas.',
            'El ingreso tiene un límite de intentos por minuto.',
            'Una cuenta suspendida no puede ingresar.',
            'Cada familia ve solo a los estudiantes con quienes está vinculada.',
            'Los cambios importantes de cuentas y gestiones quedan en un registro de auditoría.',
        ],
        'pending' => 'Conexión segura (HTTPS): confirmar cuando el sitio tenga su dirección web definitiva.',
        'minors' => 'Como los datos son de niñas, niños y adolescentes, se pide solo lo necesario y las fotos son opcionales.',
        'minors_pending' => 'Autorización de las familias para usar fotos o imágenes de los estudiantes. La define la unidad educativa.',
    ],

    // 10. Cambios
    'changes' => [
        'title' => 'Cambios en este texto',
        'text' => 'Si algo cambia en la plataforma, actualizaremos este texto y la fecha de arriba. Cuando la unidad educativa y la asesoría legal lo revisen, aquí se publicará la versión final.',
    ],

    // 11. Pendientes
    'todo' => [
        'title' => 'Lo que falta confirmar',
        'intro' => 'Para pasar de borrador a versión final, la unidad educativa y la asesoría legal deben decidir o confirmar:',
        'items' => [
            'Nombre oficial de la unidad educativa y del equipo responsable.',
            'Cuánto tiempo se conserva cada tipo de dato.',
            'Autorización de las familias para fotos e imágenes de los estudiantes.',
            'Dónde se alojan los datos (proveedor y país) y conexión segura (HTTPS).',
            'Si el robot guarda grabaciones de voz y por cuánto tiempo.',
            'Plazo y responsable para atender pedidos de corrección o eliminación.',
            'Qué normas corresponde aplicar y cómo debe quedar redactado el texto definitivo.',
        ],
    ],

    'back_home' => 'Volver al inicio',
    'go_login' => 'Ingresar',
];
