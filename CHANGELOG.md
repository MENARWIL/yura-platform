# Registro de versiones de YURA Platform

Todas las entregas del proyecto, de la más reciente a la más antigua.
El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y la numeración
[Versionado Semántico](https://semver.org/lang/es/) (MAYOR.MENOR.PARCHE):

- **PARCHE** (0.6.0 → 0.6.1): correcciones y mejoras pequeñas sobre lo ya entregado.
- **MENOR** (0.6.1 → 0.7.0): funciones nuevas.
- **MAYOR** (0.x → 1.0.0): salida oficial a producción con datos reales.

La versión que muestra la plataforma al pie se define con `APP_VERSION` en el archivo `.env`
(valor por defecto en `config/app.php`). Al publicar una versión nueva se actualizan las dos cosas
y se agrega su sección aquí.

## [0.6.1] - 2026-10-28

Mejoras sobre la 0.6.0: exportación de asistencia por fechas, profesor visible en la ficha y listas largas con búsqueda y paginación.

### Agregado
- Exportar la asistencia en PDF de una fecha a otra (con atajos Hoy, Esta semana, Este mes y Este año): detalle día por día hasta 31 días con registro y, con más, el total por estudiante con su porcentaje.
- Relaciones activas (Gestión Académica) y Usuarios con búsqueda en vivo en el servidor y 5 por página; Relaciones busca por nombre o apellidos del estudiante o del tutor, y Usuarios por nombre, apellidos, correo, teléfono o CI, con filtro por rol.

### Corregido
- La ficha del estudiante y los reportes de estudiantes (PDF y Excel) mostraban «Sin asignar» en Profesor aunque hubiera asignación docente.
- La columna Asignatura de Usuarios mostraba «-» para los profesores; ahora muestra las de sus asignaciones docentes.
- En el menú del administrador, la versión tapaba las últimas opciones; ahora el menú se desplaza y la versión queda debajo.
- Las pruebas compilan sus vistas en una carpeta propia (`storage/framework/views-testing`) para no chocar en Windows con las del navegador («Acceso denegado» al abrir una página).

## [0.6.0] - 2026-10-28

Mejoras de la revisión QA de la web y asignación docente real.

### Agregado
- Asistencia de un clic con botones P (Presente), F (Falta), R (Retraso) y L (Licencia/permiso).
- La lista del día parte con todos en Presente y se guarda con «Registrar asistencia» en una sola petición; después cada cambio se guarda al instante.
- Resumen del día (presentes, faltas, retrasos y licencias) en la pantalla de asistencia.
- Paginación de estudiantes y actividades Yura con 5, 10, 15 o 20 por página (5 por defecto), y «Estudiantes asignados» del panel del profesor de 5 en 5.
- Búsqueda de estudiantes en vivo, sin recargar la página, también por apellidos.
- Avisos flotantes comunes de guardado y de error.
- Versión de la plataforma al fondo del menú lateral y en el pie del portal público.
- Zona horaria configurable (`APP_TIMEZONE`, Bolivia por defecto).

### Cambiado
- Notas solo enteras de 1 a 100 (autoevaluación de 1 a 5), validadas en pantalla y en el servidor.
- Se quitó el botón «Guardar notas»: cada nota se guarda sola al salir de la casilla.
- Los filtros de notas y asistencia esperan a que asignatura y paralelo estén elegidos antes de cargar.
- La licencia no cuenta como falta ni como asistencia: se excluye del porcentaje.
- El profesor ve a los estudiantes de sus asignaciones docentes (web y app móvil), y los filtros de curso y paralelo muestran solo los suyos.
- Se quitaron el título repetido encima de cada pantalla y el «Integrante» de la barra superior.

### Corregido
- «Asistencia hoy» del panel del profesor siempre mostraba 0 %.
- El panel contaba como «bajo rendimiento» a estudiantes sin notas.
- Al vaciar el buscador de estudiantes la lista quedaba vacía.
- La paginación usaba un formato que no correspondía al diseño de la plataforma.

## [0.5.0] - 2026-10-26

Portal público y mejoras de las pantallas internas (pull request #4).

### Agregado
- Portal público renovado: mensaje claro, vista previa al compartir, página de privacidad y login con la marca.
- Consulta de una gestión (también cerrada) y respaldo en Excel.
- «Editar información» del estudiante (responsable, nivel, edad y género) y número de lista en notas y asistencia.
- Contraseña y confirmación de contraseña en los formularios de docentes, usuarios y tutores.

### Cambiado
- Barra de filtros común en las pantallas internas.
- Bitácora de auditoría con detalles en frases simples, número de fila y fecha al final.
- Dependencias actualizadas (Laravel, Symfony, Guzzle y librerías de PDF y Excel).

### Eliminado
- Documentos, scripts sueltos y archivos de trabajo que no forman parte del sistema.

## [0.4.0] - 2026-10-21

API de la app móvil, administración de usuarios y gestión escolar (pull request #3).

### Agregado
- API de la app v1 por roles (familia, estudiante, profesor y administrador).
- Gestión escolar: gestiones, matrículas, cierre y reapertura (solo administrador).
- Importación de listas de estudiantes y filtros por curso y paralelo en la API.

### Seguridad
- Endurecimiento de la API: vínculo familiar activo, vencimiento de tokens, límite de intentos de login y usuarios suspendidos.
- Reglas compartidas de administración de usuarios: siempre al menos un administrador activo, contraseña fuerte y auditoría.

## [0.3.0] - 2026-10-12

### Agregado
- Flujo de actividades desde el panel docente hacia el cerebro del robot y retorno de las notas completadas.

## [0.2.0] - 2026-09-15

### Agregado
- Asignaturas y asignaciones docentes.
- Paneles mejorados y cuaderno pedagógico SER/SABER/HACER.

### Cambiado
- Interfaz alineada con la identidad visual de YURA.

### Corregido
- Acceso de estudiantes y familiares, y riesgos de la API y de las actividades del robot detectados en QA.

## [0.1.0] - 2026-09-04

Primera versión del sistema escolar.

### Agregado
- Usuarios, estudiantes, cursos y paralelos, notas trimestrales y actividades de quechua.
- Panel web y administrativo, reportes en PDF y Excel.
- API protegida con Sanctum para el robot interactivo.
- Pruebas automatizadas básicas.
