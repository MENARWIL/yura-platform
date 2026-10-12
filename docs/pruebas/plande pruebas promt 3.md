# Plan de Pruebas — Proyecto YURA
## Robot interactivo para la práctica y seguimiento del aprendizaje del quechua en estudiantes de sexto de primaria — Unidad Educativa Boliviano Holandés

**Componente evaluado por este plan:** Plataforma Web (Laravel 12) y su API (MySQL/Sanctum).
**Tipo de documento:** Plan de Pruebas (planificación). Este documento **no reporta resultados de ejecución**; define qué se debe probar, cómo, con qué criterios y con qué recursos, a partir de la información disponible en el repositorio `yura-platform` y su documentación asociada.

---

## 1. Introducción

El proyecto YURA está compuesto, según la documentación disponible en el repositorio, por dos capas: (a) una **plataforma web de gestión académica** construida en Laravel 12, con autenticación, roles, CRUDs académicos y una API protegida con Laravel Sanctum; y (b) un componente temático de **robot interactivo con reconocimiento de voz (ASR)** orientado a la práctica del quechua, descrito en `docs/pruebas/identificacion-requisitos-verificables-yura.md` y en el encargo académico `consigna/TrabajoLaravel.pdf`.

Este plan de pruebas cubre **exclusivamente la plataforma web Laravel y su API MySQL/Sanctum**, es decir, la parte del sistema que existe como código fuente verificable en este repositorio. No cubre el hardware del robot, la aplicación móvil ni el motor de reconocimiento de voz, por no existir implementación de esos componentes en este repositorio (ver sección 4, "Fuera de alcance").

El plan se elabora a partir de:
- La estructura real del código (`app/`, `routes/`, `database/migrations/`, `resources/views/`, `tests/`).
- La documentación existente en el repositorio (`README.md`, `README_EQUIPO.md`, `docs/pruebas/`, `consigna/TrabajoLaravel.pdf`).
- Los hallazgos ya documentados en auditorías previas del propio repositorio (`docs/pruebas/*.md`), citados aquí únicamente como antecedentes de riesgo, no como resultados de este plan.

Este documento **no declara ninguna prueba como ejecutada**. Es un plan: describe qué se debe hacer, no qué se hizo.

---

## 2. Objetivo general y objetivos específicos

### 2.1 Objetivo general

Definir una estrategia formal de pruebas que permita verificar, de manera sistemática y trazable, que la plataforma web y la API de YURA cumplen con los requisitos funcionales, de seguridad, de integridad de datos y de usabilidad exigidos por el encargo académico y por la naturaleza sensible de los datos que gestiona (información académica de menores de edad).

### 2.2 Objetivos específicos

1. Verificar que la autenticación (web y API) y el control de acceso por rol funcionen de forma consistente y no permitan accesos no autorizados.
2. Verificar la integridad funcional de los módulos académicos (estudiantes, tutores, familiares, cursos, paralelos, asignaturas, asignaciones docentes, notas, asistencia, historial).
3. Verificar el contrato y la seguridad de la API (Sanctum), incluyendo el endpoint de integración con las actividades del robot.
4. Verificar las exportaciones de datos (PDF/Excel) en cuanto a formato, contenido y control de acceso.
5. Verificar la persistencia y las relaciones de datos entre entidades (estudiante–curso–paralelo–tutor–familiar–nota–asistencia).
6. Identificar y documentar riesgos derivados de inconsistencias conocidas en el esquema de datos (ver sección 14).
7. Establecer criterios objetivos de entrada, salida y disposición a producción, sin prejuzgar el resultado de las pruebas.

---

## 3. Alcance

Este plan cubre los siguientes módulos y funciones de la plataforma web y su API, tal como existen en el repositorio:

| Área | Incluye |
|---|---|
| Autenticación | Login/logout web (`AuthController`), login API (`Api\AuthController`), gestión de sesión, emisión y revocación de tokens Sanctum |
| Roles | Roles existentes en el sistema: `admin`, `academic`, `profesor`, `estudiante`, `padre`, `madre`, `tutor`; middleware `rol` (`CheckRole`) |
| Dashboard | Vista `dashboard.blade.php` y sus variantes por rol (incluida la rama de tutor) |
| Usuarios | CRUD de usuarios (`UserController`), alta de docentes, activación/desactivación, fotos de perfil |
| Estudiantes | CRUD de estudiantes (`StudentController`), soft delete, restauración, borrado físico, fotos |
| Tutores | Gestión de tutores/apoderados (`TutorController` y vistas `tutors/*`) |
| Familiares | Relación estudiante–familiar (`student_family_members`, `FamilyController`, vistas `family/*`) |
| Cursos | CRUD de cursos (`CourseController`) |
| Paralelos | CRUD de paralelos (`ParallelController`) |
| Asignaturas | CRUD de asignaturas (`SubjectController`) |
| Asignaciones docentes | Relación profesor–asignatura–paralelo (`TeachingAssignmentController`) |
| Notas | Cuaderno pedagógico, registro y edición de notas (`GradeController`), historial de cambios (`GradeHistory`) |
| Asistencia | Registro de asistencia (`saveAttendance`, tabla `attendance`) |
| Historial | Historial de notas por estudiante (`grades.history`) |
| Actividades del robot | Registro de actividades vía panel web (modal y página `robot/create.blade.php`) y vía API (`POST /api/v1/robot/activity`) |
| API | Endpoints de `routes/api.php` bajo Sanctum: autenticación, estudiantes, actividades del robot |
| Exportaciones | Exportación de estudiantes a PDF (Dompdf) y Excel (Maatwebsite/Excel) individual y masiva |

---

## 4. Fuera de alcance

Quedan explícitamente **fuera del alcance** de este plan, por no existir implementación verificable en este repositorio:

- **Aplicación móvil (APK)** de consulta para apoderados.
- **Robot físico** (hardware, actuadores, luces, bocinas).
- **Motor ASR** (reconocimiento automático de voz en quechua) y todo procesamiento de audio.
- **Protocolo de comunicación física** Robot ↔ ESP32 ↔ Plataforma (no existe driver serial, MQTT ni WebSocket en `app/`).
- Pruebas de usabilidad presencial con estudiantes de 11-12 años.
- Pruebas de carga/rendimiento bajo condiciones de aula real (sin instrumentación disponible en el repositorio).

Si en una fase posterior se entrega evidencia adicional (código fuente del firmware, APK, especificación del protocolo ASR), estos componentes deberán incorporarse en una revisión posterior de este plan.

---

## 5. Descripción del sistema

YURA — en su componente de plataforma web — es una aplicación monolítica desarrollada en **Laravel 12**, con vistas renderizadas del lado del servidor mediante **Blade** y una plantilla administrativa (AdminLTE/Bootstrap) integrada. Utiliza **Vite** para la compilación de assets. El almacenamiento de datos es **MySQL/MariaDB** en el entorno de trabajo habitual del equipo (según `README_EQUIPO.md`), con migraciones y *seeders* propios de Laravel para la estructura y datos iniciales.

El sistema expone dos superficies de acceso:
1. **Panel web** con sesión basada en cookies (guard `web`), destinado a administradores, personal académico, docentes, estudiantes y familiares/tutores.
2. **API REST** protegida con **Laravel Sanctum** (tokens *Bearer*), destinada a integraciones externas —en particular, el registro de resultados de actividades asociadas al robot educativo.

El dominio de datos gira en torno a una estructura académica: estudiantes agrupados en cursos y paralelos, con asignaturas y asignaciones docentes, sobre las que se registran notas (con historial de cambios) y asistencia. Cada estudiante puede tener uno o más familiares/tutores vinculados, con distintos niveles de acceso de solo lectura.

---

## 6. Módulos incluidos

1. Autenticación y sesión (web y API)
2. Gestión de roles y autorización
3. Dashboard (por rol)
4. Usuarios (administración)
5. Estudiantes (CRUD, soft/hard delete, fotos)
6. Tutores
7. Familiares (relación estudiante–familiar)
8. Cursos
9. Paralelos
10. Asignaturas
11. Asignaciones docentes
12. Notas (cuaderno pedagógico)
13. Asistencia
14. Historial de notas
15. Actividades del robot (registro vía web y vía API)
16. API (contrato, autenticación, autorización, respuestas)
17. Exportaciones (PDF y Excel, individuales y masivas)

---

## 7. Tipos de pruebas

| Tipo | Descripción aplicada a YURA |
|---|---|
| **Unitarias** | Verificación aislada de métodos de modelo (p. ej. accesores de rol/estado en `User`, cálculo de promedios, *scopes* de consulta), ejecutables con Pest/PHPUnit sobre `tests/Unit/` |
| **Funcionales** | Verificación de flujos completos de cada módulo (crear/editar/eliminar estudiante, registrar nota, marcar asistencia) a través de HTTP, usando `tests/Feature/` |
| **De validación** | Verificación de las reglas de validación de formularios y *requests* (campos requeridos, tipos, rangos, `exists`, `unique`) y de los mensajes de error mostrados |
| **De integración** | Verificación de la cadena completa ruta → controlador → modelo → base de datos → vista, y de la interacción entre módulos relacionados (p. ej. eliminar un curso con paralelos asociados) |
| **De API** | Verificación de contrato (verbos HTTP, códigos de estado, estructura JSON), autenticación Sanctum y comportamiento del endpoint de actividades del robot |
| **De autorización** | Verificación de que cada rol solo pueda acceder a las acciones y datos que le corresponden, tanto en el panel web como en la API |
| **De persistencia** | Verificación de que los datos se almacenan, actualizan y relacionan correctamente en MySQL/MariaDB, incluyendo integridad referencial y comportamiento de *soft deletes* |
| **De regresión** | Reejecución de la suite automatizada (`tests/Feature`, `tests/Unit`) tras cada cambio relevante, para detectar rupturas en funcionalidad previamente validada |
| **De seguridad** | Verificación de CSRF, XSS, inyección SQL, *mass assignment*, control de acceso a exportaciones masivas, manejo de `.env`/secretos y hash de contraseñas |
| **De rendimiento** | Medición de tiempos de respuesta de operaciones críticas (login, listados con muchos registros, generación de exportaciones) bajo carga controlada |
| **De usabilidad** | Revisión de claridad de formularios, mensajes de error/éxito, navegación por rol y comportamiento responsive de tablas y formularios |
| **De compatibilidad** | Verificación en al menos dos navegadores actualizados (p. ej. Chrome y Firefox/Edge) y en distintos anchos de pantalla (escritorio, tablet, móvil) |

---

## 8. Estrategia por riesgo

Las pruebas se priorizan según el impacto potencial de una falla, no según la facilidad de ejecución. Orden de prioridad:

**Prioridad 1 — Crítica**
- Autenticación (web y API): que no se pueda acceder sin credenciales válidas, y que una cuenta desactivada no pueda operar.
- Autorización por rol: que ningún rol pueda ver, editar o exportar datos fuera de su alcance (en particular, exportaciones masivas y listados de estudiantes).
- Protección de datos de menores: cualquier ruta que exponga nombre, notas, asistencia o datos de contacto familiar de un estudiante.

**Prioridad 2 — Alta**
- Notas: correcta persistencia, historial y que solo el docente con asignación vigente pueda calificar.
- Asistencia: correcta persistencia y asociación al estudiante/fecha/paralelo correctos.
- Relaciones de datos: estudiante–curso–paralelo, estudiante–familiar, profesor–asignatura–paralelo (integridad referencial, comportamiento ante eliminación de una entidad relacionada).

**Prioridad 3 — Media**
- CRUDs administrativos (cursos, paralelos, asignaturas, asignaciones docentes, usuarios).
- Exportaciones individuales (PDF/Excel de un estudiante).
- API del robot: idempotencia y formato de respuesta.

**Prioridad 4 — Baja**
- Usabilidad de formularios secundarios (perfil, selector de idioma).
- Compatibilidad visual entre navegadores para vistas no críticas.
- Mensajes de éxito/error duplicados u otras inconsistencias de interfaz sin impacto funcional.

Esta priorización determina el orden de ejecución sugerido en el cronograma (sección 17) y el criterio de bloqueo en los criterios de salida (sección 13).

---

## 9. Ambiente de pruebas

| Componente | Requisito |
|---|---|
| Sistema operativo | Compatible con XAMPP (Windows, según entorno de desarrollo del equipo) |
| Servidor de aplicaciones | XAMPP (Apache) o `php artisan serve` para entorno local |
| PHP | 8.2 o superior (`composer.json` exige `^8.2`) |
| Framework | Laravel 12 |
| Base de datos | MySQL o MariaDB (motor de referencia declarado en `README_EQUIPO.md`); alternativamente SQLite solo para pruebas automatizadas aisladas (`phpunit.xml` usa `:memory:`) |
| Navegador | Versión actualizada de Chrome y de al menos un navegador adicional (Firefox o Edge) |
| Red | Red local (LAN), sin dependencia de servicios externos para las pruebas de la plataforma web |
| Datos | Base de datos de pruebas aislada, distinta de cualquier base de datos con datos reales de estudiantes; poblada mediante los *seeders* del proyecto (`SchoolPilotSeeder`, `GradesAttendanceSeeder`, `RobotUserSeeder`) |
| Extensiones PHP | Deben verificarse explícitamente antes de iniciar pruebas de exportación: `gd`, `zip` y demás requeridas por `composer.json` (`barryvdh/laravel-dompdf`, `maatwebsite/excel`, `laravel/sanctum`) |

**Nota:** la disponibilidad y correcta configuración de estas extensiones y del servicio de base de datos debe verificarse como parte de la fase de preparación del entorno (sección 17), antes de iniciar cualquier ciclo de pruebas. Este plan no asume un resultado sobre dicha verificación.

---

## 10. Tecnologías utilizadas

- **PHP 8.2+** — lenguaje base del backend.
- **Laravel 12** — framework MVC, enrutamiento, Eloquent ORM, migraciones.
- **Blade** — motor de plantillas para las vistas del panel web.
- **Vite** — compilación y *bundling* de assets (CSS/JS).
- **MySQL/MariaDB** — motor de base de datos relacional de referencia.
- **Laravel Sanctum** — autenticación por token para la API.
- **Pest / PHPUnit** — *frameworks* de pruebas automatizadas ya presentes en `tests/` y configurados en `phpunit.xml`.
- **Dompdf** (`barryvdh/laravel-dompdf`) — generación de reportes en PDF.
- **Maatwebsite/Excel** (`phpoffice/phpspreadsheet`) — generación de reportes en Excel.

---

## 11. Datos de prueba anonimizados y controlados

- Todas las pruebas deben ejecutarse sobre una base de datos de pruebas dedicada, nunca sobre una base con datos reales de estudiantes de la Unidad Educativa.
- Los datos de estudiantes, familiares y docentes usados en pruebas deben ser **ficticios o anonimizados** (nombres, documentos de identidad y contactos inventados o generados mediante *factories*/*seeders*), sin reutilizar información real de menores.
- El proyecto ya cuenta con mecanismos reutilizables para esto: `database/factories/` y los *seeders* `SchoolPilotSeeder`, `GradesAttendanceSeeder` y `RobotUserSeeder`, que generan un entorno académico ficticio (curso, paralelos, estudiantes, tutores y docentes de prueba).
- Las credenciales de las cuentas de prueba generadas por los *seeders* (por ejemplo, `admin@yura.com`) deben tratarse como credenciales de entorno de pruebas únicamente y no reutilizarse en ningún entorno con datos reales.
- Cualquier archivo exportado (PDF/Excel) generado durante las pruebas debe almacenarse en una carpeta de evidencias de prueba y eliminarse o resguardarse conforme a la política de manejo de datos del equipo, dado que puede contener datos de estudiantes aunque sean ficticios.

---

## 12. Roles y responsables

Esta sección define **roles del proceso de pruebas** (no los roles del sistema, ya descritos en la sección 3). El repositorio no contiene una asignación formal de personas a estos roles; deben ser completados por el equipo o el docente responsable antes de iniciar la ejecución.

| Rol de QA | Responsabilidad | Asignado a |
|---|---|---|
| Responsable de QA / Test Lead | Coordinar el plan, priorizar riesgos, aprobar criterios de entrada/salida | *Por asignar* |
| Analista de pruebas funcionales | Ejecutar pruebas funcionales, de validación y de usabilidad sobre el panel web | *Por asignar* |
| Analista de pruebas de API/backend | Ejecutar pruebas de API, autorización, persistencia y seguridad | *Por asignar* |
| Desarrollador(es) | Corregir defectos reportados y dar soporte técnico durante las pruebas | *Por asignar* (equipo de desarrollo del proyecto) |
| Responsable de datos de prueba | Preparar y mantener aislados los datos de prueba anonimizados | *Por asignar* |
| Aprobador / Docente supervisor | Revisar el informe final y autorizar el paso a producción | *Por asignar* |

---

## 13. Criterios de entrada y salida

### 13.1 Criterios de entrada (para iniciar el ciclo de pruebas)

- El código a probar está disponible en una rama estable y compilable (`composer install` y `npm install` sin errores bloqueantes).
- El entorno de pruebas (sección 9) está configurado y verificado, incluyendo las extensiones PHP requeridas.
- La base de datos de pruebas está migrada y poblada con datos ficticios/anonimizados (sección 11).
- Este plan de pruebas ha sido revisado y aprobado por el responsable de QA.
- Los casos de prueba derivados de este plan están documentados (aunque sea en una plantilla mínima: módulo, precondición, pasos, resultado esperado).

### 13.2 Criterios de salida (para cerrar el ciclo de pruebas)

- El 100% de los casos de prioridad **Crítica** y **Alta** (sección 8) han sido ejecutados, con evidencia registrada (sección 16).
- No existen defectos abiertos de severidad **Crítica** relacionados con autenticación, autorización o exposición de datos de menores.
- Los defectos de severidad **Alta** cuentan con un plan de corrección acordado, aunque no estén todos resueltos.
- La suite automatizada (`php artisan test`) se ejecuta sin fallos sobre la rama a liberar.
- Se cuenta con un informe de resultados que distingue explícitamente pruebas ejecutadas, pruebas pendientes y pruebas no aplicables, sin resultados inventados.

---

## 14. Riesgos de pruebas

Estos riesgos se identifican **por inspección del código y la documentación existente**, no por ejecución de pruebas, y deben tratarse como advertencias a verificar durante la ejecución real, no como hallazgos confirmados por este plan.

| Riesgo | Descripción | Evidencia documental (a verificar) |
|---|---|---|
| **Inconsistencia `role` / `rol`** | El esquema de base de datos ha tenido ambos nombres de columna en distintas migraciones (`0001_01_01_000000_create_users_table.php` crea `rol`; `2026_07_02_000001_add_role_to_users.php` agrega `role`). El modelo `User` mantiene ambos campos en `$fillable` y escribe en los dos de forma sincronizada (`app/Models/User.php`, método de compatibilidad `writeCompatAttribute`). Riesgo: código nuevo o consultas directas a base de datos que usen solo uno de los dos nombres pueden leer un valor desactualizado. | `database/migrations/0001_01_01_000000_create_users_table.php`, `2026_07_02_000001_add_role_to_users.php`, `app/Models/User.php` |
| **Inconsistencia `status` / `estado`** | El mismo patrón se repite para el estado de actividad de usuarios y estudiantes: hay migraciones que renombran `estado` → `status` y otras que revierten `status` → `estado` en distintas tablas (`2026_07_10_000001_normalize_student_and_user_columns_to_english.php`). El modelo `User` también sincroniza ambos campos. Riesgo: una verificación de estado activo/inactivo implementada contra un solo nombre de columna puede comportarse de forma diferente según la ruta de código que la ejecute (web vs. API), lo que debe verificarse explícitamente como caso de prueba de autenticación. | `database/migrations/2026_07_10_000001_normalize_student_and_user_columns_to_english.php`, `2026_05_26_000001_add_estado_aprobacion_to_estudiantes.php`, `app/Models/User.php` |
| **Diferencias de comportamiento web / API** | Al existir dos superficies de acceso (guard `web` con sesión, y Sanctum con token), existe riesgo de que una misma regla de negocio (por ejemplo, verificación de estado activo, o restricción por rol en un flujo equivalente) esté implementada de forma distinta o incompleta en una de las dos superficies. Debe verificarse caso por caso, comparando el flujo web y el flujo API equivalente. | `app/Http/Controllers/AuthController.php` vs. `app/Http/Controllers/Api/AuthController.php`; controladores web vs. `app/Http/Controllers/Api/` |
| **Datos de menores de edad** | Varios módulos (estudiantes, notas, asistencia, exportaciones) contienen datos personales de estudiantes menores de edad y de sus familiares. Cualquier prueba de autorización, exportación o listado debe tratarse con prioridad crítica (ver sección 8), y las pruebas deben ejecutarse únicamente con datos ficticios/anonimizados (sección 11). |  Módulos de estudiantes, familiares, notas, asistencia y exportaciones |
| **Dependencias externas del entorno** | El sistema depende de extensiones PHP (`gd`, `zip`) y servicios externos (MySQL/MariaDB) que deben estar correctamente configurados en el entorno de pruebas. Un entorno mal configurado puede producir falsos negativos (fallas atribuidas al código cuando en realidad son del entorno) o falsos positivos (funcionalidad que "pasa" en un entorno degradado pero fallaría en producción). Debe verificarse el entorno (sección 9) antes de interpretar cualquier resultado de prueba. | `composer.json` (dependencias `barryvdh/laravel-dompdf`, `maatwebsite/excel`), configuración de PHP del servidor de pruebas |
| **Doble implementación de flujos equivalentes** | El repositorio contiene, en al menos un caso conocido, dos rutas o vistas distintas para una misma función (por ejemplo, un modal y una página completa para registrar actividades del robot). Existe riesgo de que las reglas de validación diverjan entre ambas implementaciones; deben probarse ambas por separado, no asumir que probar una cubre la otra. | `resources/views/grades/index.blade.php` (modal), `resources/views/robot/create.blade.php` (página completa) |

---

## 15. Gestión de defectos

Todo defecto encontrado durante la ejecución de este plan debe registrarse con, al menos: módulo, pasos de reproducción, resultado esperado, resultado obtenido, evidencia (sección 16) y severidad, según la siguiente escala:

| Severidad | Criterio | Ejemplos de referencia (tipo de problema, no resultado confirmado) |
|---|---|---|
| **Crítica** | Impide el uso del sistema, compromete datos de menores, o permite un acceso no autorizado a información o funciones sensibles | Acceso no autorizado a datos de estudiantes; bypass de autenticación; pérdida o corrupción de datos |
| **Alta** | Afecta una funcionalidad central del sistema, sin bloquear por completo su uso, o representa un riesgo de seguridad significativo pero acotado | Falla en el registro de notas o asistencia; inconsistencia de autorización entre web y API en un flujo secundario |
| **Media** | Afecta parcialmente una funcionalidad, con posibilidad de solución alternativa (*workaround*) | Validación de formulario incompleta; exportación con formato incorrecto pero datos correctos |
| **Baja** | Problema menor, cosmético o de mejora, sin impacto funcional | Inconsistencia visual, mensaje duplicado, texto poco claro |

El flujo sugerido de gestión es: **Registro → Triage (asignación de severidad y prioridad) → Asignación a desarrollo → Corrección → Reprueba → Cierre**. Los defectos de severidad Crítica y Alta deben reportarse al responsable de QA de forma inmediata, sin esperar al cierre del ciclo de pruebas.

---

## 16. Evidencias

Toda prueba ejecutada debe respaldarse con evidencia verificable y reproducible, apropiada al tipo de prueba:

- **Capturas de pantalla** de la interfaz, en el flujo probado y, cuando aplique, del mensaje de error o éxito mostrado.
- **Respuestas HTTP** completas (código de estado, cabeceras relevantes y cuerpo JSON) para pruebas de API, obtenidas con herramientas como `curl` o un cliente HTTP equivalente.
- **Consultas controladas a base de datos** (por ejemplo, mediante `php artisan tinker` o consultas SQL de solo lectura) para verificar persistencia y relaciones, ejecutadas exclusivamente sobre la base de datos de pruebas.
- **Logs de la aplicación** (`storage/logs/laravel.log`) relevantes al momento de la prueba, en particular ante errores inesperados.
- **Archivos exportados** (PDF/Excel) generados durante las pruebas de exportación, conservados como evidencia y tratados conforme a la sección 11.
- **Tiempos medidos** para las pruebas de rendimiento (por ejemplo, tiempo de respuesta de login, de un listado extenso o de una exportación masiva), indicando método de medición y condiciones (carga, tamaño de datos).

Toda evidencia debe quedar asociada al caso de prueba correspondiente y a la fecha real de ejecución, para mantener trazabilidad.

---

## 17. Cronograma sugerido

Cronograma orientativo por fases, expresado en semanas relativas al inicio del ciclo de pruebas (a ajustar según disponibilidad real del equipo; no representa fechas comprometidas):

| Semana | Fase | Actividades |
|---|---|---|
| 1 | Preparación | Verificar entorno (sección 9), instalar dependencias, confirmar extensiones PHP, preparar datos de prueba anonimizados, revisar y aprobar este plan |
| 2 | Pruebas de Prioridad Crítica | Autenticación (web/API), autorización por rol, protección de datos de menores en listados y exportaciones |
| 3 | Pruebas de Prioridad Alta | Notas, asistencia, relaciones de datos (estudiante–curso–paralelo–familiar–docente) |
| 4 | Pruebas de Prioridad Media | CRUDs administrativos, exportaciones individuales, API del robot |
| 5 | Pruebas de Prioridad Baja y compatibilidad | Usabilidad de formularios secundarios, compatibilidad entre navegadores y tamaños de pantalla |
| 6 | Regresión y cierre | Reejecución de la suite automatizada, reprueba de defectos corregidos, consolidación del informe final y verificación de criterios de salida (sección 13.2) |

---

## 18. Criterios para determinar si está listo para producción

El sistema podrá considerarse listo para producción únicamente cuando, de forma verificable:

1. Se cumplan íntegramente los **criterios de salida** definidos en la sección 13.2.
2. No existan defectos de severidad **Crítica** abiertos.
3. Los defectos de severidad **Alta** relacionados con autenticación, autorización o exposición de datos de menores estén corregidos y reprobados (no solo planificados).
4. El entorno de producción real haya sido verificado contra los mismos requisitos de la sección 9 (en particular, motor MySQL/MariaDB real y extensiones PHP completas), y no únicamente contra un entorno de desarrollo local.
5. La suite automatizada (`php artisan test`) se ejecute sin fallos sobre la versión exacta que se desplegará.
6. Exista evidencia documentada (sección 16) de la ejecución de todos los casos de prioridad Crítica y Alta.
7. Se haya revisado la configuración de producción relevante para seguridad (por ejemplo, que la aplicación no quede en modo de depuración expuesto), como parte de las pruebas de seguridad de este plan.

La ausencia de cualquiera de estas condiciones debe documentarse explícitamente como bloqueante, indicando cuál no se cumple y por qué.

---

## 19. Conclusiones

Este documento establece la planificación formal de pruebas para la plataforma web y la API de YURA, cubriendo los módulos de autenticación, roles, gestión académica, notas, asistencia, integración con actividades del robot, API y exportaciones, y excluyendo explícitamente los componentes de hardware, aplicación móvil y ASR por no existir evidencia de su implementación en este repositorio.

El plan identifica riesgos concretos derivados de la propia estructura del código y las migraciones del proyecto —en particular, la convivencia histórica de nombres de columna `role`/`rol` y `status`/`estado`, y la existencia de dos superficies de acceso (web y API) que deben mantenerse consistentes— y establece una estrategia de priorización basada en el impacto sobre la seguridad, la autorización y los datos de estudiantes menores de edad.

Este documento **no contiene resultados de pruebas**, no declara que se haya ejecutado ninguna prueba, y no afirma el estado real (aprobado, con errores o pendiente) de ninguna funcionalidad del sistema. Su propósito es exclusivamente definir qué debe probarse, cómo, con qué prioridad, con qué ambiente y datos, y bajo qué criterios se podrá declarar el ciclo de pruebas como cerrado y el sistema como apto para producción. La ejecución de este plan y el registro de sus resultados deben documentarse en un informe de pruebas independiente y posterior a este documento.
