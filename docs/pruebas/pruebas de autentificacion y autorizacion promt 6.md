# Casos de Prueba de Autenticación y Autorización - Plataforma YURA

## 1. Confirmación de Roles Activos
Tras el análisis del modelo `User` y los controladores del sistema, se confirman los siguientes roles activos en la aplicación:
* **Administrador (`admin`)**: Acceso global y sin restricciones a todos los módulos.
* **Académico (`academic`)**: Acceso de gestión académica (usuarios, estudiantes, familiares, etc.), similar a administrador pero enfocado en la administración escolar.
* **Profesor (`profesor` / `teacher`)**: Acceso limitado a sus estudiantes asignados, paralelos y asignaturas.
* **Tutor / Padre / Madre (`tutor`, `padre`, `madre`)**: Acceso de solo lectura a los estudiantes vinculados a través de relaciones familiares.
* **Estudiante (`estudiante`)**: Acceso de solo lectura a su propia información de perfil y notas (si aplica).
* **Robot (`robot`)**: Autenticación vía API (Tokens Sanctum) para reportar resultados de actividades a los endpoints del sistema.

## 2. Requisitos de Seguridad Identificados
* **REQ-AUTH-01**: El sistema debe denegar el acceso a URLs protegidas si el usuario no ha iniciado sesión.
* **REQ-AUTH-02**: El inicio de sesión debe requerir credenciales correctas y activas.
* **REQ-AUTH-03**: El cierre de sesión debe invalidar la sesión actual e impedir el acceso con el botón "Atrás".
* **REQ-AUTH-04**: El sistema debe aplicar un control de acceso basado en roles (RBAC) estricto.
* **REQ-AUTH-05**: Un usuario con rol Tutor/Padre/Madre solo puede visualizar información de los estudiantes con los que tiene relación directa.
* **REQ-AUTH-06**: Un Profesor solo puede gestionar notas, asistencia y actividades de los estudiantes en los paralelos y asignaturas que le han sido asignadas.
* **REQ-AUTH-07**: Los endpoints API deben estar protegidos mediante tokens Sanctum válidos.

## 3. Casos de Prueba

### 3.1 Pruebas de Autenticación (Login / Logout / Sesión)

Ejecutadas el 2026-09-21 con peticiones HTTP reales (`curl`) contra `php artisan serve --port=8020` y la base de datos MySQL real (`db_yura_platform`).

| ID | Requisito | Funcionalidad | Descripción de la Prueba | Resultado Esperado | Estado | Evidencia real |
|---|---|---|---|---|---|---|
| AUTH-01 | REQ-AUTH-02 | Login | **Login correcto:** Ingresar credenciales válidas de un usuario activo. | Redirección exitosa al dashboard correspondiente a su rol. | ✅ Aprobado | `POST /login` con admin@yura.com, gestion.yura@school.bo, prof.vargas@school.bo, prof.flores@school.bo y juan.quispe@school.bo → 302; `GET /dashboard` posterior con cada sesión → 200 en los 5 casos. |
| AUTH-02 | REQ-AUTH-02 | Login | **Contraseña incorrecta:** Ingresar un correo válido con contraseña errónea. | Error "Credenciales incorrectas", acceso denegado. | ✅ Aprobado | `POST /login` admin@yura.com + password errónea → 302 (vuelve a `/login`), mensaje real renderizado: "Las credenciales proporcionadas no coinciden con nuestros registros."; `GET /dashboard` posterior → 302 (no autenticado). |
| AUTH-03 | REQ-AUTH-02 | Login | **Usuario inexistente:** Intentar login con un correo que no está registrado. | Error "Credenciales incorrectas", acceso denegado. | ✅ Aprobado | `POST /login` con correo inexistente → 302, mismo mensaje genérico que AUTH-02 (no revela si el usuario existe o no, buena práctica de seguridad). |
| AUTH-04 | REQ-AUTH-02 | Login | **Campos vacíos:** Enviar el formulario de login sin correo ni contraseña. | Mensajes de validación de campos obligatorios, no se envía petición. | ✅ Aprobado | `POST /login` con `email=` y `password=` vacíos → 302, mensaje de validación real: "The email field is required." (Nota: mensaje en inglés — el resto de la app es bilingüe es/qu; este `$request->validate()` de `AuthController::login()` no usa mensajes personalizados. Oportunidad de mejora menor, no bloqueante). |
| AUTH-05 | REQ-AUTH-03 | Logout | **Logout exitoso:** Hacer clic en "Cerrar sesión". | Sesión invalidada y redirección al login. | ✅ Aprobado | Sesión activa confirmada (`GET /dashboard` → 200) → `POST /logout` → 302. |
| AUTH-06 | REQ-AUTH-01 | Sesión | **URL protegida sin autenticación:** Navegar directamente a `/students` sin estar logueado. | Redirección automática a la página de login. | ✅ Aprobado | `GET /students` sin cookies → 302, `Location: /login`. |
| AUTH-07 | REQ-AUTH-03 | Sesión | **Acceso posterior al logout:** Intentar acceder al dashboard usando el botón "Atrás" tras el logout. | Redirección al login (la sesión debe estar destruida). | ✅ Aprobado | Misma cookie de sesión reutilizada tras `POST /logout` → `GET /dashboard` → 302, `Location: /login` (sesión realmente invalidada en servidor, no solo en el navegador). |
| AUTH-08 | REQ-AUTH-03 | Sesión | **Expiración de sesión:** Permanecer inactivo hasta superar el tiempo máximo de sesión y luego intentar una acción. | Redirección al login pidiendo re-autenticación. | ⏳ No ejecutado | El tiempo de vida de sesión configurado es de 120 minutos (`config/session.php:35`, `SESSION_LIFETIME`). No se ejecutó en este ciclo por no ser práctico esperar 2 horas en vivo; queda pendiente de una verificación programada aparte (o de reducir `SESSION_LIFETIME` temporalmente en un entorno de prueba dedicado). |

### 3.2 Pruebas de Autorización y Control de Acceso (RBAC)

| ID | Requisito | Funcionalidad | Descripción de la Prueba | Resultado Esperado | Estado | Evidencia real |
|---|---|---|---|---|---|---|
| AUTZ-01 | REQ-AUTH-04 | Gestión de Usuarios | Ingresar a la creación de usuarios con rol `profesor` o `tutor`. | Error 403 (Unauthorized) o redirección con mensaje de error. | ✅ Aprobado | `GET /users/create` con sesión de prof.vargas (profesor) → 302, `Location: /` (bloqueado por middleware `rol:admin`). |
| AUTZ-02 | REQ-AUTH-04 | Exportaciones | Descargar el reporte PDF/Excel general con un rol `estudiante`. | Acceso denegado (403). | ✅ Aprobado (probado con rol padre, mismo middleware) | `GET /students/export-pdf-all` con sesión de juan.quispe (padre) → 302, `Location: /` (middleware `rol:admin,academic,profesor`, padre no incluido). No se dispone de un usuario `estudiante` sembrado en los datos reales; se usó el rol padre por compartir el mismo middleware de la ruta. |
| AUTZ-03 | REQ-AUTH-04 | Configuración Académica | Acceder a la gestión de Cursos, Paralelos y Asignaturas con rol `profesor`. | Acceso denegado (403), ya que es exclusivo de administradores/académicos. | ✅ Aprobado | `GET /courses` con sesión de profesor → 302, `Location: /` (middleware `rol:admin,academic`). |
| AUTZ-04 | REQ-AUTH-04 | Asignaciones Docentes | Intentar que un `profesor` asigne una materia a otro profesor. | Acceso denegado (403). | ✅ Aprobado | `POST /teaching-assignments` (con CSRF real) autenticado como profesor → 302, `Location: /` (bloqueado antes de llegar al controlador). |
| AUTZ-05 | REQ-AUTH-05 | Familiares (Tutor) | **Restricción Tutor:** Un padre de familia intenta ver el perfil de un estudiante que NO es su hijo a través de manipulación de URL (`/students/{id}`). | Acceso denegado (403) ("No tienes permiso para acceder a este estudiante"). | ✅ Aprobado | juan.quispe (padre del estudiante id=1) → `GET /students/2` (hijo de otro padre) → **403**. |
| AUTZ-06 | REQ-AUTH-05 | Familiares (Tutor) | Un padre de familia visualiza a su propio hijo. | Acceso permitido en modo solo lectura. | ✅ Aprobado | juan.quispe → `GET /students/1` (su propio hijo) → **200**. |
| AUTZ-07 | REQ-AUTH-06 | Notas/Asistencia (Profesor) | **Restricción Profesor:** Un profesor intenta calificar a un estudiante en un paralelo/materia donde NO tiene asignación docente activa. | Acceso denegado (403). | ✅ Aprobado | prof.vargas (asignado solo a paralelo 1) → `POST /grades/save` sobre estudiante del paralelo 2 → **403 "No autorizado"**. |
| AUTZ-08 | REQ-AUTH-06 | Notas/Asistencia (Profesor) | Un profesor califica a un estudiante que SÍ está en su asignación docente activa. | Calificación registrada correctamente. | ✅ Aprobado | prof.vargas → `POST /grades/save` sobre estudiante de su propio paralelo 1 → **200**, `{"success":true,"grade_id":27}` (registro real creado, luego eliminado en la limpieza de datos de prueba). |
| AUTZ-09 | REQ-AUTH-04 | Gestión de Familiares | Un profesor intenta agregar un nuevo familiar a un estudiante. | Acceso denegado (403), función exclusiva de administradores. | ✅ Aprobado | `POST /family-members` (CSRF real) autenticado como profesor → 302, `Location: /` (bloqueado por middleware `rol:admin,academic`, antes del controlador). |
| AUTZ-10 | REQ-AUTH-04 | Historial de Notas | Un tutor intenta borrar o alterar el historial de notas usando un endpoint subyacente. | Acceso denegado (403). | ✅ Aprobado | juan.quispe (padre) → `POST /grades/27` con `_method=PUT` (ruta real `grades.update`, restringida a `rol:profesor`) → 302, `Location: /` (bloqueado antes del controlador). |
| AUTZ-11 | REQ-AUTH-04 | Actividades Robot | Un profesor intenta lanzar una actividad YURA a un paralelo donde no está asignado. | Error 403 o validación estricta que lo impide. | ✅ Aprobado | prof.flores (asignado solo a paralelo 2) → `POST /robot-activities` con `parallel_id=1` (asignado a otro profesor) → **403 "No autorizado"**. |

### 3.3 Pruebas de API y Tokens Sanctum

| ID | Requisito | Funcionalidad | Descripción de la Prueba | Resultado Esperado | Estado | Evidencia real |
|---|---|---|---|---|---|---|
| API-01 | REQ-AUTH-07 | Endpoints API | Enviar petición a `/api/v1/robot/activity` con un token Sanctum **válido**. | Petición procesada y datos guardados (HTTP 200/201). | ✅ Aprobado | Token real obtenido vía `POST /api/login` (robot@yura.com) → `POST /api/v1/robot/activity` → **201**, `{"success":true,"message":"Resultado de YURA registrado correctamente.",...,"id":28}` (registro real creado, luego eliminado en la limpieza). |
| API-02 | REQ-AUTH-07 | Endpoints API | Enviar petición a `/api/v1/robot/activity` con un token Sanctum **inválido** o corrupto. | Acceso denegado (HTTP 401 Unauthenticated). | ✅ Aprobado | `Authorization: Bearer tokenFalsoCorrupto123456789` → **401**, `{"message":"Unauthenticated."}`. |
| API-03 | REQ-AUTH-07 | Endpoints API | Enviar petición a `/api/v1/robot/activity` **sin** Bearer token en la cabecera. | Acceso denegado (HTTP 401 Unauthenticated). | ✅ Aprobado | Sin cabecera `Authorization` → **401**, `{"message":"Unauthenticated."}`. |
| API-04 | REQ-AUTH-07 | Endpoints API | Enviar petición con un token que fue **revocado** (logout previo o borrado de DB). | Acceso denegado (HTTP 401 Unauthenticated). | ✅ Aprobado | Token del robot revocado vía `POST /api/logout` (200, "Sesión cerrada correctamente") → reintento del mismo token en `/api/v1/robot/activity` → **401**, `{"message":"Unauthenticated."}`. |
| API-05 | REQ-AUTH-04 | Operaciones Críticas API | Usar el token del Robot para acceder al endpoint de gestión de usuarios (`/api/users`). | Acceso denegado (HTTP 403) al no tener el rol requerido. | ✅ Aprobado | Token válido del robot → `GET /api/users` → **403**, `{"message":"No autorizado."}` (bloqueado por `abort_unless($request->user()->isAdmin())` en `Api/UserController::index()`). Nota: al estar `APP_DEBUG` activo en este entorno, la respuesta 403 incluyó un stack trace completo con rutas absolutas del servidor; verificar que `APP_DEBUG=false` en producción antes del despliegue real. |

---
**Resumen de ejecución real (2026-09-21):** 23 de 24 casos ejecutados con evidencia HTTP real, **23/23 aprobados** (0 fallos, 0 vulnerabilidades encontradas en este ciclo). 1 caso (AUTH-08) no se ejecutó por impracticabilidad de esperar 120 minutos en vivo. Todos los datos de prueba creados durante la ejecución (2 registros en `grades`, tokens Sanctum temporales) fueron revertidos al finalizar.

*Nota original: Los resultados de estas pruebas se encontraban en estado "Pendiente" hasta su ejecución formal en los entornos correspondientes. Esta ejecución formal ya se realizó y sus resultados reales quedaron documentados arriba.*
