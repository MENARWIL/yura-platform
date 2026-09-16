# Análisis QA del sistema YURA

## 1. Alcance del análisis

Este documento analiza la plataforma disponible en el repositorio Laravel del proyecto YURA y está orientado exclusivamente a la fase de pruebas.

Se verificó la existencia de:

- Laravel 12 y PHP 8.2.
- MySQL/MariaDB.
- Autenticación web mediante sesiones.
- API protegida con Laravel Sanctum.
- Exportaciones PDF y Excel.
- Pruebas automatizadas con Pest/PHPUnit.
- Módulos académicos, familiares y de actividades del robot.

No se encontró en este repositorio el código fuente de la APK ni del software interno del robot físico. Esos componentes requieren información y pruebas independientes.

## 2. Módulos que deben probarse

### Plataforma web

1. Autenticación y cierre de sesión.
2. Control de acceso por roles.
3. Dashboard según el rol.
4. Gestión de usuarios.
5. Gestión de estudiantes.
6. Gestión de tutores y familiares.
7. Gestión de cursos.
8. Gestión de paralelos.
9. Gestión de asignaturas.
10. Asignaciones docentes.
11. Calificaciones.
12. Asistencia.
13. Historial de calificaciones.
14. Actividades del robot YURA.
15. Exportación de estudiantes a PDF y Excel.
16. Cambio de idioma.
17. Fotografías de usuarios y estudiantes.

### API

Según las rutas disponibles, deben probarse:

- Inicio y cierre de sesión.
- Consulta del usuario autenticado.
- Dashboard.
- Consulta, registro y detalle de estudiantes.
- Actualización de puntajes.
- Consulta de hijos del tutor.
- Gestión de usuarios.
- Registro de actividades del robot.

## 3. Funcionalidades críticas

- Inicio de sesión y autorización por rol.
- Registro de estudiantes.
- Asignación de tutor principal y secundario.
- Asignación de profesor, curso, paralelo y asignatura.
- Control de capacidad máxima de paralelos.
- Registro de calificaciones.
- Registro de asistencia.
- Consulta de información por parte del tutor.
- Registro y actualización de actividades del robot.
- Cálculo de promedios y asistencia.
- Exportación de información académica.
- Protección de la API mediante tokens.

Un error en permisos, notas, asistencia o relaciones familiares puede exponer información o generar reportes académicos incorrectos.

## 4. Requisitos que requieren validación

### Requisitos funcionales

Debe comprobarse que:

- Los usuarios válidos puedan iniciar sesión.
- Los usuarios inválidos no puedan acceder.
- Los usuarios suspendidos no puedan utilizar la API.
- Cada rol acceda únicamente a sus módulos permitidos.
- Los administradores gestionen usuarios.
- El personal académico gestione cursos, asignaturas, paralelos y profesores.
- Los estudiantes se registren con sus datos académicos.
- Un estudiante pueda tener tutor principal y secundario.
- Los profesores registren notas solo en estudiantes y asignaturas autorizadas.
- La asistencia pueda registrarse y actualizarse.
- Los tutores consulten únicamente a sus hijos relacionados.
- Las actividades del robot se creen inicialmente como pendientes.
- El robot envíe resultados mediante la API.
- Los reportes reflejen correctamente notas y asistencia.
- Las exportaciones incluyan la información esperada.

### Requisitos no funcionales pendientes

Todavía deben definirse y probarse:

- Tiempo máximo de respuesta.
- Usuarios simultáneos esperados.
- Disponibilidad requerida.
- Recuperación ante caída de MySQL.
- Seguridad de contraseñas y tokens.
- Compatibilidad con navegadores.
- Compatibilidad con Android.
- Funcionamiento en red local o Internet.
- Accesibilidad para niños y padres.
- Protección de datos personales de menores.

## 5. Reglas de negocio que deben comprobarse

- Solo los roles autorizados pueden crear estudiantes, tutores y profesores.
- Un tutor debe tener rol de tutor y estar activo.
- Un profesor debe tener rol de profesor y estar activo.
- Un estudiante debe pertenecer a un paralelo válido.
- El paralelo debe pertenecer al curso seleccionado.
- No se debe superar la capacidad máxima del paralelo.
- No se debe asignar dos veces la misma asignatura al mismo paralelo.
- Un profesor solo debe operar sobre asignaciones docentes activas.
- El tutor secundario no puede ser igual al tutor principal.
- Las notas deben estar dentro de los rangos permitidos.
- La asistencia debe estar dentro de los rangos permitidos.
- Una actividad robot se crea en estado pendiente.
- No se debe crear una actividad para un paralelo sin estudiantes.
- Un tutor solo debe consultar estudiantes vinculados a él.
- Un profesor solo debe ver estudiantes asignados.

## 6. Riesgos potenciales

### Riesgos críticos

1. **Inconsistencia de nombres de campos**

   El sistema utiliza variantes como `role`/`rol`, `status`/`estado` y `active`/`activo`. Esto puede provocar accesos incorrectos o usuarios rechazados.

2. **Diferencias entre web y API**

   La validación de roles y estados no siempre usa exactamente los mismos valores en ambos canales.

3. **Actividad robot asociada al primer estudiante**

   El flujo actual obtiene el primer estudiante del paralelo. Si existen varios estudiantes, el resultado podría asociarse al estudiante equivocado.

4. **Dependencia de MySQL**

   Si MySQL está detenido o utiliza otro puerto, la aplicación no puede guardar ni consultar información.

5. **Datos personales de menores**

   Deben verificarse permisos, exposición de datos, fotografías, teléfonos, correos, notas y eliminación.

6. **Carga de fotografías**

   Deben probarse archivos grandes, formatos no permitidos, archivos corruptos y rutas de almacenamiento.

### Otros riesgos

- Promedios calculados incorrectamente.
- Duplicación de calificaciones.
- Asistencia registrada para una asignatura incorrecta.
- Exportaciones incompletas.
- Diferencias entre eliminación lógica y definitiva.
- Relaciones familiares inactivas que sigan mostrando información.
- Código duplicado para actividades del robot en distintos controladores.
- Diferencias entre nombres antiguos y nuevos de campos.

## 7. Datos recomendados para las pruebas

### Usuarios

Crear usuarios activos e inactivos de cada rol:

- Administrador.
- Académico.
- Profesor.
- Tutor.
- Padre.
- Madre.
- Estudiante.
- Robot.

También deben probarse correos duplicados, C.I. duplicada, contraseñas inválidas, usuarios eliminados lógicamente y usuarios con columnas antiguas.

### Datos académicos

- Curso con paralelo disponible.
- Paralelo lleno.
- Paralelo sin estudiantes.
- Varias asignaturas.
- Profesor con asignación activa.
- Profesor con asignación inactiva.
- Asignaciones duplicadas.

### Estudiantes

- Con tutor principal.
- Con tutor principal y secundario.
- Sin tutor.
- Con profesor.
- Sin profesor.
- Con fotografía válida.
- Con archivo inválido.
- Con nombre y apellidos con tildes.

### Notas y asistencia

- Valores 0 y 100.
- Valores fuera de rango.
- Asistencia 0% y 100%.
- Fechas válidas e inválidas.
- Calificaciones pendientes y completadas.
- Trimestres 1, 2 y 3.
- Actividades normales y robot.

### Robot

- Categorías válidas e inválidas.
- Paralelo vacío.
- Paralelo con un estudiante.
- Paralelo con varios estudiantes.
- Resultado pendiente.
- Resultado completado.
- Puntaje inválido.
- Token válido, inválido y revocado.

## 8. Tipos de pruebas recomendados

1. Pruebas unitarias para promedios, asistencia, roles y relaciones.
2. Pruebas funcionales para formularios y operaciones CRUD.
3. Pruebas de integración entre estudiantes, tutores, profesores y asignaturas.
4. Pruebas de autorización por rol.
5. Pruebas de API y códigos HTTP.
6. Pruebas de validación y mensajes de error.
7. Pruebas de regresión después de modificar usuarios, estudiantes o notas.
8. Pruebas de seguridad: manipulación de IDs, inyección, tokens y archivos.
9. Pruebas de rendimiento en dashboards, exportaciones y consultas grandes.
10. Pruebas de usabilidad para profesores, tutores y padres.
11. Pruebas de compatibilidad en navegadores y dispositivos móviles.
12. Pruebas de hardware, voz y conectividad cuando se entregue la especificación del robot.

## 9. Dependencias entre módulos

- Autenticación habilita el control de roles.
- Cursos habilitan la creación de paralelos.
- Paralelos habilitan el registro de estudiantes.
- Usuarios permiten asignar tutores y profesores.
- Asignaturas, paralelos y profesores permiten crear asignaciones docentes.
- Las asignaciones docentes habilitan notas y asistencia.
- Estudiantes, notas y asistencia alimentan los dashboards.
- Las relaciones familiares alimentan el dashboard del tutor.
- Las actividades del robot dependen de estudiantes, paralelos, asignaturas y permisos.
- Las exportaciones dependen de estudiantes y sus relaciones académicas.
- La APK y el robot dependen de la API y de tokens válidos.

## 10. Funcionalidades con mayor posibilidad de error

- Registro de estudiante con tutor recién creado.
- Selección de tutor principal y secundario.
- Compatibilidad entre columnas antiguas y nuevas.
- Control de roles y estados.
- Capacidad máxima de paralelos.
- Asignaciones docentes duplicadas o inactivas.
- Registro de notas y asistencia por profesores no autorizados.
- Promedios por trimestre.
- Promedio general del tutor.
- Consulta de hijos vinculados.
- Actividades robot en paralelos con varios estudiantes.
- Recepción de resultados del robot.
- Exportación de relaciones y calificaciones.
- Restauración de estudiantes eliminados.
- Almacenamiento de fotografías.

Existe además una posible inconsistencia entre la vista de actividades robot y un controlador antiguo: una implementación usa `subject_id` y otra usa `course_id`. Las rutas actuales deben probarse para confirmar cuál es la implementación efectiva.

## 11. Información pendiente para completar el plan de pruebas

### Usuarios y permisos

- Lista oficial de roles.
- Permisos exactos por rol.
- Diferencia oficial entre padre, madre y tutor.
- Reglas para activar, suspender y eliminar usuarios.
- Confirmar si el estudiante tiene cuenta propia.

### APK

- Código fuente o APK.
- Versión mínima de Android.
- Pantallas y funciones.
- Endpoints utilizados.
- Formato de respuestas.
- Funcionamiento sin conexión.
- Manejo de tokens y notificaciones.

### Robot físico

- Hardware y sistema operativo.
- Protocolo de comunicación.
- Formato de solicitudes y respuestas.
- Autenticación.
- Tiempo máximo de respuesta.
- Funcionamiento sin Internet.
- Reglas de reconocimiento de voz.
- Idiomas soportados.
- Fórmula del puntaje.
- Comportamiento cuando falla el reconocimiento.

### Reglas académicas

- Fórmula oficial del promedio.
- Fórmula oficial de asistencia.
- Escala de calificación.
- Número real de trimestres.
- Tipos de evaluación.
- Reglas para modificar o anular notas.
- Reglas de cierre de periodos.
- Significado de actividad pendiente y completada.

### Operación y aceptación

- Cantidad esperada de estudiantes y usuarios.
- Usuarios simultáneos.
- Entorno de producción.
- Política de respaldos.
- Recuperación ante fallos.
- Navegadores y dispositivos objetivo.
- Criterios de aceptación por módulo.
- Casos de uso oficiales.
- Responsables de validar cada módulo.
- Evidencias requeridas para la defensa o titulación.

## 12. Cobertura automatizada actual

El repositorio contiene pruebas para:

- Inicio de sesión.
- Registro de tutores.
- Registro de estudiantes.
- Asignación de familiares.
- Calificaciones.
- Asignaturas.
- Creación de profesores.
- Restricción de acceso por roles.
- Dashboard del profesor.
- Dashboard del tutor.
- Fotografías.
- Restauración de estudiantes.
- Eliminación definitiva de usuarios.

La cobertura pendiente más importante corresponde a:

- API Sanctum.
- Actividades y resultados del robot.
- Asistencia.
- Exportaciones.
- Pruebas negativas de seguridad.
- Pruebas de rendimiento.
- Integración con la APK.
- Integración con el robot físico.

## Conclusión

La plataforma web cuenta con una base funcional para gestión académica, usuarios, familias, profesores, calificaciones, asistencia y actividades robot. Para completar un plan de pruebas formal todavía es necesario proporcionar la especificación de la APK, del robot físico, las reglas académicas oficiales, los requisitos no funcionales y los criterios de aceptación.
