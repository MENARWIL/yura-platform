# Registro de versiones de YURA Platform

Todas las entregas del proyecto, de la más reciente a la más antigua.
El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y la numeración
[Versionado Semántico](https://semver.org/lang/es/) (MAYOR.MENOR.PARCHE):

- **PARCHE** (0.6.0 → 0.6.1): solo correcciones.
- **MENOR** (0.6.1 → 0.7.0): funciones nuevas.
- **MAYOR** (0.x → 1.0.0): salida oficial a producción con datos reales.

La versión que muestra la plataforma al pie se define con `APP_VERSION` en el archivo `.env`
(valor por defecto en `config/app.php`). Al publicar una versión nueva se actualizan las dos cosas
y se agrega su sección aquí.

## [0.6.0] - 2026-10-28

Mejoras de la revisión QA de la web y asignación docente real.

### Agregado
- Asistencia de un clic con botones P (Presente), F (Falta), R (Retraso) y L (Licencia/permiso).
- La lista del día parte con todos en Presente y se guarda con «Registrar asistencia» en una sola petición; después cada cambio se guarda al instante.
- Resumen del día (presentes, faltas, retrasos y licencias) en la pantalla de asistencia.
- Paginación de estudiantes y actividades Yura con 5, 10, 15 o 20 por página (5 por defecto).
- Búsqueda de estudiantes en vivo, sin recargar la página, también por apellidos.
- Avisos flotantes comunes de guardado y de error.
- Pie de página con la versión de la plataforma (pantallas internas y portal público).
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
