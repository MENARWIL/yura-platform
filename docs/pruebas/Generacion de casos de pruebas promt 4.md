# Especificación de Casos de Prueba — Proyecto YURA
## Plataforma Web (Laravel 12) y API (MySQL/Sanctum)

**Tipo de documento:** Especificación de casos de prueba, ejecutable manualmente.
**Documento base:** Este catálogo complementa a `docs/pruebas/plande pruebas promt 3.md` (Plan de Pruebas) y usa como fuente de requisitos `docs/pruebas/identificacion-requisitos-verificables-yura.md` (RF-001 a RF-008, RNF-001 a RNF-004, RN-001 a RN-003).

**Importante:**
- Este documento **no contiene resultados de ejecución**. Los campos "Resultado obtenido" se dejan vacíos, "Estado" se fija en `PENDIENTE` y "Evidencia" en `Pendiente de adjuntar` en todos los casos, tal como fue solicitado.
- Cada caso indica su relación con los requisitos RF/RNF/RN **solo cuando corresponde**. La mayoría de RF-001, RF-002, RF-003, RF-005, RF-006, RF-008, RNF-001, RNF-002, RNF-003 y RN-003 describen hardware del robot, motor ASR o una aplicación móvil que **no existen en este repositorio** (confirmado en `docs/pruebas/Informe_YURA_Auditoria_Pruebas.docx`, sección 5 y 11.2). Para esos casos se indica `N/A — fuera de alcance de la plataforma web` en vez de forzar una relación inexistente. La matriz de trazabilidad de la sección final resume esta cobertura.
- Las reglas de validación, rutas, controladores y nombres de campo citados en cada caso corresponden a los verificados en el código del repositorio (`routes/web.php`, `routes/api.php`, `app/Http/Controllers/`, `app/Models/`, `database/migrations/`) durante la auditoría previa del proyecto. No se han inventado reglas de negocio adicionales a las documentadas.

---

## Leyenda

| Campo | Significado |
|---|---|
| **Prioridad** | Crítica / Alta / Media / Baja, según la estrategia de riesgo del Plan de Pruebas (sección 8) |
| **Estado** | Todos los casos de este documento están en `PENDIENTE` (no ejecutados) |
| **Requisito relacionado** | RF-xxx / RNF-xxx / RN-xxx del documento temático, o `N/A` con la razón |

---

## Índice de módulos

1. [Autenticación](#1-autenticación) (CP-001 a CP-010)
2. [Usuarios](#2-usuarios) (CP-011 a CP-020)
3. [Estudiantes](#3-estudiantes) (CP-021 a CP-034)
4. [Familiares](#4-familiares) (CP-035 a CP-041)
5. [Cursos](#5-cursos) (CP-042 a CP-046)
6. [Paralelos](#6-paralelos) (CP-047 a CP-051)
7. [Asignaturas](#7-asignaturas) (CP-052 a CP-057)
8. [Asignaciones docentes](#8-asignaciones-docentes) (CP-058 a CP-063)
9. [Notas (cuaderno pedagógico)](#9-notas-cuaderno-pedagógico) (CP-064 a CP-075)
10. [Asistencia](#10-asistencia) (CP-076 a CP-082)
11. [Dashboards](#11-dashboards) (CP-083 a CP-087)
12. [Actividades del robot](#12-actividades-del-robot) (CP-088 a CP-096)
13. [API](#13-api) (CP-097 a CP-105)
14. [Exportaciones](#14-exportaciones) (CP-106 a CP-114)
15. [Historial](#15-historial) (CP-115 a CP-119)
16. [Matriz de trazabilidad de requisitos](#16-matriz-de-trazabilidad-de-requisitos)

---

## 1. Autenticación

### CP-001 — Login web exitoso con credenciales válidas

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — no hay RF específico de login web en el documento temático; caso derivado del encargo académico (`consigna/TrabajoLaravel.pdf`, "Sistema de Autenticación") |
| Módulo | Autenticación |
| Objetivo | Verificar que un usuario con credenciales correctas y cuenta activa pueda iniciar sesión por el formulario web |
| Prioridad | Crítica |
| Precondiciones | Existe un usuario con rol `admin`, estado activo, contraseña conocida (p. ej. cuenta sembrada por `SchoolPilotSeeder`) |
| Datos de entrada | email = admin@yura.com; password = password123 |
| Pasos | 1) Navegar a `/login`. 2) Ingresar el email. 3) Ingresar la contraseña. 4) Pulsar "Iniciar sesión". |
| Resultado esperado | El sistema redirige a `/dashboard` y muestra el panel correspondiente al rol `admin`. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Archivo: `app/Http/Controllers/AuthController.php` |

### CP-002 — Login web con contraseña incorrecta

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — caso de seguridad general de autenticación |
| Módulo | Autenticación |
| Objetivo | Verificar que el sistema rechace el acceso con una contraseña incorrecta |
| Prioridad | Crítica |
| Precondiciones | Existe el usuario admin@yura.com |
| Datos de entrada | email = admin@yura.com; password = clave_incorrecta |
| Pasos | 1) Navegar a `/login`. 2) Ingresar el email correcto. 3) Ingresar una contraseña incorrecta. 4) Pulsar "Iniciar sesión". |
| Resultado esperado | El sistema no inicia sesión, permanece en `/login` y muestra un mensaje de error de credenciales inválidas. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso negativo |

### CP-003 — Login web con campos vacíos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Autenticación |
| Objetivo | Verificar que el formulario de login exija email y contraseña |
| Prioridad | Alta |
| Precondiciones | Ninguna |
| Datos de entrada | email = (vacío); password = (vacío) |
| Pasos | 1) Navegar a `/login`. 2) Dejar ambos campos vacíos. 3) Pulsar "Iniciar sesión". |
| Resultado esperado | El sistema no envía la petición o responde con errores de validación en ambos campos ("required"); no se concede acceso. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campos obligatorios. Ver `resources/views/auth/login.blade.php` (líneas 29 y 44, atributo `required`) |

### CP-004 — Login web con email inexistente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Autenticación |
| Objetivo | Verificar que el sistema no revele si un correo existe o no, y rechace el acceso |
| Prioridad | Media |
| Precondiciones | Ninguna |
| Datos de entrada | email = noexiste@yura.com; password = password123 |
| Pasos | 1) Navegar a `/login`. 2) Ingresar un correo no registrado. 3) Ingresar cualquier contraseña. 4) Pulsar "Iniciar sesión". |
| Resultado esperado | Acceso rechazado con un mensaje de error genérico de credenciales inválidas (sin indicar si el correo existe). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso negativo / valor inválido |

### CP-005 — Login web con usuario desactivado

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (restricción de acceso), de forma parcial: el requisito original habla de restricción por rol, este caso extiende el concepto a restricción por estado de cuenta |
| Módulo | Autenticación |
| Objetivo | Verificar que un usuario cuya cuenta fue desactivada por un administrador no pueda iniciar sesión por el panel web |
| Prioridad | Crítica |
| Precondiciones | Existe un usuario con `estado`/`status` = inactivo (por ejemplo, desactivado previamente vía `UserController::toggleStatus`) |
| Datos de entrada | email del usuario inactivo; contraseña correcta de esa cuenta |
| Pasos | 1) Navegar a `/login`. 2) Ingresar el email del usuario inactivo. 3) Ingresar la contraseña correcta. 4) Pulsar "Iniciar sesión". |
| Resultado esperado | El sistema **debe** rechazar el acceso (sesión no debe iniciarse) y mostrar un mensaje indicando que la cuenta está inactiva. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso vinculado a un riesgo ya documentado en `docs/pruebas/Informe_YURA_Auditoria_Pruebas.docx` (Error 3, prioridad ALTA): el login de la API sí valida el estado (`Api/AuthController.php:28-34`), y este caso verifica si el login web (`AuthController.php:19-34`) hace lo mismo. Este caso debe ejecutarse y su resultado registrarse sin asumir el desenlace. |

### CP-006 — Cierre de sesión (logout)

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Autenticación |
| Objetivo | Verificar que la opción de cerrar sesión invalide la sesión activa |
| Prioridad | Alta |
| Precondiciones | Usuario con sesión iniciada |
| Datos de entrada | Ninguno |
| Pasos | 1) Con sesión iniciada, pulsar "Cerrar sesión" en el menú superior. 2) Intentar volver a acceder a `/dashboard` con el botón "Atrás" del navegador. |
| Resultado esperado | La sesión se cierra, el sistema redirige a `/login` o a la página pública, y el acceso posterior a `/dashboard` sin volver a autenticarse es rechazado (redirección a `/login`). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de navegación / persistencia de sesión |

### CP-007 — Acceso a ruta protegida sin sesión iniciada

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (restricción de acceso), de forma parcial |
| Módulo | Autenticación |
| Objetivo | Verificar que las rutas protegidas por el middleware `auth` redirijan al login cuando no hay sesión |
| Prioridad | Crítica |
| Precondiciones | Ningún usuario con sesión iniciada en el navegador (sesión limpia) |
| Datos de entrada | URL directa a `/students`, `/grades`, `/users`, etc. |
| Pasos | 1) Sin iniciar sesión, escribir directamente la URL `/students` en el navegador. 2) Repetir con `/dashboard` y `/users`. |
| Resultado esperado | En todos los casos el sistema redirige a `/login` sin mostrar contenido protegido. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de navegación / seguridad |

### CP-008 — Login API exitoso (Sanctum)

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — no hay RF específico, pero es prerrequisito de RF-005/RF-006 (fuera de alcance) y de la integración robot |
| Módulo | Autenticación / API |
| Objetivo | Verificar que `POST /api/login` emita un token Sanctum válido con credenciales correctas |
| Prioridad | Crítica |
| Precondiciones | Existe un usuario activo con credenciales conocidas |
| Datos de entrada | JSON: `{"email":"admin@yura.com","password":"password123"}` |
| Pasos | 1) Enviar `POST /api/login` con el cuerpo JSON indicado y cabecera `Accept: application/json`. |
| Resultado esperado | Respuesta HTTP 200 con un cuerpo JSON que incluye `access_token` y los datos del usuario. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/Api/AuthController.php` |

### CP-009 — Login API con credenciales inválidas

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Autenticación / API |
| Objetivo | Verificar que la API rechace credenciales incorrectas con el código HTTP adecuado |
| Prioridad | Crítica |
| Precondiciones | Ninguna |
| Datos de entrada | JSON: `{"email":"admin@yura.com","password":"incorrecta"}` |
| Pasos | 1) Enviar `POST /api/login` con el cuerpo JSON indicado. |
| Resultado esperado | Respuesta HTTP 401 con un mensaje de error tipo "Las credenciales son incorrectas.", sin token emitido. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso negativo de API |

### CP-010 — Login API con usuario inactivo

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (restricción de acceso), de forma parcial |
| Módulo | Autenticación / API |
| Objetivo | Verificar que la API rechace el login de una cuenta desactivada |
| Prioridad | Crítica |
| Precondiciones | Existe un usuario con `estado`/`status` = inactivo y credenciales conocidas |
| Datos de entrada | JSON con email y password correctos de una cuenta inactiva |
| Pasos | 1) Enviar `POST /api/login` con las credenciales de la cuenta inactiva. |
| Resultado esperado | Respuesta HTTP 403 (o equivalente), sin emisión de token. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Comparar con CP-005 (equivalente en web); ambos casos permiten confirmar si existe la inconsistencia de comportamiento web/API documentada en el Plan de Pruebas, sección 14 |

---

## 2. Usuarios

### CP-011 — Creación de usuario docente con datos válidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — corresponde al requisito del encargo académico "Gestionar el CRUD de usuarios" (rol Administrador) |
| Módulo | Usuarios |
| Objetivo | Verificar que un administrador pueda registrar un nuevo docente con datos válidos |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | Nombre = "Ana Pérez"; email = ana.perez@school.bo; rol = profesor; contraseña = Password123; confirmación = Password123 |
| Pasos | 1) Ir a "Profesores" → "Nuevo docente". 2) Completar el formulario con los datos indicados. 3) Guardar. |
| Resultado esperado | El usuario se crea correctamente, aparece en el listado de docentes y puede iniciar sesión con la contraseña definida. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/UserController.php` (método `storeTeacher`) |

### CP-012 — Creación de usuario con email duplicado

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Usuarios |
| Objetivo | Verificar que el sistema rechace el registro de un usuario con un correo ya existente |
| Prioridad | Alta |
| Precondiciones | Existe un usuario con email admin@yura.com |
| Datos de entrada | email = admin@yura.com (duplicado); resto de campos válidos |
| Pasos | 1) Ir a "Nuevo docente" (o "Nuevo usuario"). 2) Completar el formulario usando el correo ya existente. 3) Guardar. |
| Resultado esperado | El sistema rechaza el registro y muestra un mensaje de validación indicando que el correo ya está en uso (no una excepción de servidor). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado. El Plan de Pruebas (sección 14) señala como observación que la unicidad de email depende del constraint de base de datos; este caso verifica si además existe un mensaje de validación amigable |

### CP-013 — Creación de usuario con campos obligatorios vacíos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Usuarios |
| Objetivo | Verificar que el formulario exija nombre, email, rol y contraseña |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | Todos los campos vacíos |
| Pasos | 1) Ir a "Nuevo docente". 2) Dejar todos los campos vacíos. 3) Intentar guardar. |
| Resultado esperado | El sistema no crea el usuario y muestra errores de validación en los campos obligatorios. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campos obligatorios |

### CP-014 — Creación de usuario con email de formato inválido

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Usuarios |
| Objetivo | Verificar la validación de formato de correo electrónico |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | email = "correo-sin-arroba" |
| Pasos | 1) Ir a "Nuevo docente". 2) Ingresar un email con formato inválido. 3) Completar el resto de campos válidamente. 4) Guardar. |
| Resultado esperado | El sistema rechaza el envío con un error de validación de formato de correo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato inválido |

### CP-015 — Creación de usuario con rol no permitido

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (control de acceso por rol), de forma parcial |
| Módulo | Usuarios |
| Objetivo | Verificar que solo se puedan asignar los roles válidos del sistema |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | rol = "superadmin" (valor no soportado), enviado manipulando la petición (p. ej. con herramientas de desarrollador o un cliente HTTP) |
| Pasos | 1) Interceptar o construir manualmente una petición de creación de usuario con un valor de rol no contemplado. 2) Enviarla. |
| Resultado esperado | El sistema rechaza la petición por validación (`in:` de roles permitidos) y no crea el usuario. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de operación incorrecta / dato inválido. Referencia: validación `in:padre,madre,tutor,profesor,admin,estudiante` documentada para `Api\UserController` |

### CP-016 — Edición de datos de un usuario existente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Usuarios |
| Objetivo | Verificar que un administrador pueda editar los datos de un usuario existente |
| Prioridad | Media |
| Precondiciones | Existe un usuario docente de prueba |
| Datos de entrada | Nuevo nombre = "Ana Pérez Gutiérrez" |
| Pasos | 1) Ir al listado de usuarios/docentes. 2) Seleccionar "Editar" sobre el usuario de prueba. 3) Modificar el nombre. 4) Guardar. |
| Resultado esperado | El cambio se refleja en el listado y al reabrir el registro. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-017 — Desactivación (toggle de estado) de un usuario

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Usuarios |
| Objetivo | Verificar que un administrador pueda desactivar la cuenta de un usuario |
| Prioridad | Alta |
| Precondiciones | Existe un usuario activo de prueba |
| Datos de entrada | Ninguno (acción de botón) |
| Pasos | 1) Ir al listado de usuarios. 2) Pulsar la acción de desactivar sobre el usuario de prueba. |
| Resultado esperado | El estado del usuario cambia a inactivo en el listado; el usuario queda disponible como precondición para CP-005/CP-010. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `UserController::toggleStatus` |

### CP-018 — Eliminación física (hard delete) de un usuario

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — corresponde al requisito del encargo académico "Hard Delete" |
| Módulo | Usuarios |
| Objetivo | Verificar que el borrado físico elimine el registro completamente de la base de datos |
| Prioridad | Media |
| Precondiciones | Existe un usuario de prueba sin relaciones críticas activas |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de usuarios. 2) Seleccionar "Eliminar definitivamente" sobre el usuario de prueba. 3) Confirmar la acción. |
| Resultado esperado | El usuario desaparece del listado y no puede recuperarse; una consulta directa a la base de datos de prueba confirma que el registro ya no existe. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `UserController::forceDestroy`; confirmado por `CrudAndMediaTest` ("force-delete de usuario") |

### CP-019 — Usuario con rol no administrador intenta acceder a la gestión de usuarios

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (control de acceso por rol) |
| Módulo | Usuarios |
| Objetivo | Verificar que solo el rol `admin` pueda acceder al módulo de usuarios |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con un rol distinto de `admin` (p. ej. `profesor` o `padre`) |
| Datos de entrada | URL directa `/users` |
| Pasos | 1) Iniciar sesión con una cuenta de rol `profesor`. 2) Escribir directamente la URL `/users`. |
| Resultado esperado | El sistema deniega el acceso (403 o redirección), sin mostrar el listado de usuarios. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

### CP-020 — Subida de foto de perfil de usuario

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — corresponde al requisito del encargo académico "Subida de Archivos" |
| Módulo | Usuarios |
| Objetivo | Verificar que se pueda subir una fotografía de perfil válida y que se muestre en el listado |
| Prioridad | Baja |
| Precondiciones | Sesión iniciada con rol `admin`; existe un usuario de prueba |
| Datos de entrada | Archivo de imagen válido (JPG/PNG, tamaño razonable, p. ej. < 2MB) |
| Pasos | 1) Editar el usuario de prueba. 2) Adjuntar la imagen en el campo de foto. 3) Guardar. 4) Ir al listado de usuarios. |
| Resultado esperado | La foto se guarda correctamente y se muestra en el listado con un atributo `alt` descriptivo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Confirmado como implementado por `CrudAndMediaTest` ("subida de fotos") |

---

## 3. Estudiantes

### CP-021 — Registro de estudiante con datos válidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — CRUD principal del proyecto según el encargo académico |
| Módulo | Estudiantes |
| Objetivo | Verificar que se pueda registrar un estudiante nuevo con todos los datos requeridos |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con rol `admin` o `academic`; existen al menos un curso y un paralelo creados |
| Datos de entrada | Nombre = "Estudiante de Prueba"; curso y paralelo válidos; tutor asociado válido (opcional según formulario) |
| Pasos | 1) Ir a "Estudiantes" → "Nuevo estudiante". 2) Completar el formulario con los datos indicados. 3) Guardar. |
| Resultado esperado | El estudiante se crea y aparece en el listado, con los datos correctamente asociados a su curso y paralelo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `resources/views/students/create.blade.php`; `app/Http/Controllers/StudentController.php` |

### CP-022 — Registro de estudiante con nombre vacío

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Estudiantes |
| Objetivo | Verificar que el campo nombre sea obligatorio |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | Nombre = (vacío); resto de campos válidos |
| Pasos | 1) Ir a "Nuevo estudiante". 2) Dejar el nombre vacío. 3) Completar el resto de campos. 4) Guardar. |
| Resultado esperado | El sistema rechaza el registro y muestra un error de validación en el campo nombre. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-023 — Registro de estudiante con nombre que contiene caracteres no permitidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Estudiantes |
| Objetivo | Verificar la regla de formato (regex) aplicada al nombre del estudiante |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | Nombre = "Est123!@#" |
| Pasos | 1) Ir a "Nuevo estudiante". 2) Ingresar un nombre con números y símbolos. 3) Completar el resto de campos. 4) Guardar. |
| Resultado esperado | El sistema rechaza el registro por no cumplir el formato esperado de nombre. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato inválido. Referencia: "regex de nombre" documentada en la auditoría de `StudentController` |

### CP-024 — Registro de estudiante con curso/paralelo inexistente

| Campo | Detalle |
|---|---|
| Requisito relacionado | RN-001 (relación estudiante–curso/nivel), de forma parcial |
| Módulo | Estudiantes |
| Objetivo | Verificar que no se pueda asignar un estudiante a un curso o paralelo que no existe |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | ID de curso o paralelo inexistente (manipulando la petición, ya que el formulario normalmente solo lista valores válidos) |
| Pasos | 1) Construir o interceptar una petición de creación de estudiante con un `course_id`/`parallel_id` inexistente. 2) Enviarla. |
| Resultado esperado | El sistema rechaza la petición por la regla `exists:` sobre curso/paralelo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de operación incorrecta / valor inválido |

### CP-025 — Edición de un estudiante existente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Estudiantes |
| Objetivo | Verificar que los datos de un estudiante puedan actualizarse correctamente |
| Prioridad | Alta |
| Precondiciones | Existe un estudiante de prueba |
| Datos de entrada | Cambiar el paralelo asignado a otro paralelo válido |
| Pasos | 1) Ir al listado de estudiantes. 2) Editar el estudiante de prueba. 3) Cambiar el paralelo. 4) Guardar. |
| Resultado esperado | El cambio se refleja en el listado y en la ficha del estudiante. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-026 — Eliminación lógica (soft delete) de un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — requisito del encargo académico "Soft Delete" |
| Módulo | Estudiantes |
| Objetivo | Verificar que la eliminación lógica oculte al estudiante del listado activo sin borrar el registro |
| Prioridad | Alta |
| Precondiciones | Existe un estudiante de prueba |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de estudiantes. 2) Eliminar (lógicamente) al estudiante de prueba. 3) Verificar el listado activo. |
| Resultado esperado | El estudiante desaparece del listado activo; el registro permanece en la base de datos con `deleted_at` establecido. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-027 — Restauración de un estudiante eliminado lógicamente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Estudiantes |
| Objetivo | Verificar que un estudiante eliminado lógicamente pueda restaurarse |
| Prioridad | Media |
| Precondiciones | Existe un estudiante eliminado lógicamente (resultado de CP-026) |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de estudiantes eliminados. 2) Seleccionar "Restaurar" sobre el estudiante de prueba. |
| Resultado esperado | El estudiante vuelve a aparecer en el listado activo con sus datos intactos. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-028 — Eliminación física (hard delete) de un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — requisito del encargo académico "Hard Delete" |
| Módulo | Estudiantes |
| Objetivo | Verificar que el borrado físico elimine completamente el registro y sus datos asociados según corresponda |
| Prioridad | Media |
| Precondiciones | Existe un estudiante de prueba eliminado lógicamente |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de estudiantes eliminados. 2) Seleccionar "Eliminar definitivamente". 3) Confirmar. |
| Resultado esperado | El estudiante ya no existe en la base de datos (verificable por consulta directa en el entorno de prueba); no puede restaurarse. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `StudentController::forceDestroy` |

### CP-029 — Un profesor visualiza solo a sus estudiantes asignados

| Campo | Detalle |
|---|---|
| Requisito relacionado | RN-001 (el estudiante solo interactúa dentro de su curso/nivel asignado), de forma parcial — aplicado aquí al alcance de visualización del profesor |
| Módulo | Estudiantes |
| Objetivo | Verificar que el listado de estudiantes para un profesor esté limitado a los estudiantes de sus paralelos asignados |
| Prioridad | Alta |
| Precondiciones | Existen al menos dos profesores con asignaciones docentes distintas, cada uno con estudiantes en paralelos diferentes |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como el profesor A. 2) Ir a "Estudiantes". |
| Resultado esperado | El listado muestra únicamente a los estudiantes de los paralelos asignados al profesor A, no a los del profesor B. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol. Confirmado como implementado por la auditoría previa (`StudentController::authorizeAccess`) |

### CP-030 — Un padre/tutor visualiza el listado de estudiantes

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (modo de lectura pasiva y restricción de roles) |
| Módulo | Estudiantes |
| Objetivo | Verificar si el listado de estudiantes visible para un padre/tutor está limitado a sus propios hijos/pupilos |
| Prioridad | Crítica |
| Precondiciones | Existen al menos dos familias con tutores y estudiantes distintos |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión con una cuenta de rol `padre`/`madre`/`tutor`. 2) Ir a "Estudiantes" (si el ítem de menú está disponible) o navegar directamente a `/students`. |
| Resultado esperado | El listado **debe** mostrar únicamente a los estudiantes relacionados con ese tutor, no al resto de estudiantes de la institución. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso vinculado a un hallazgo ya documentado (`Informe_YURA_Auditoria_Pruebas.docx`, Error 4, prioridad ALTA: `StudentController::index` solo filtra por rol `profesor`). Este caso debe ejecutarse y registrarse sin asumir el resultado. |

### CP-031 — Un padre/tutor intenta editar un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (modo de lectura pasiva para padres) |
| Módulo | Estudiantes |
| Objetivo | Verificar que un padre/tutor no pueda editar los datos de un estudiante |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor`; existe un estudiante relacionado con esa cuenta |
| Datos de entrada | URL directa a `/students/{id}/edit` |
| Pasos | 1) Iniciar sesión como padre/tutor. 2) Intentar acceder directamente a la URL de edición de un estudiante relacionado. |
| Resultado esperado | El sistema deniega el acceso (403) a la edición, incluso si el estudiante está relacionado con esa cuenta. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol. Referencia: `authorizeAccess()` bloquea `manage=true` para familiares |

### CP-032 — Un estudiante intenta editar su propio perfil

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Estudiantes |
| Objetivo | Verificar que el rol `estudiante` tenga acceso de solo lectura a su propio perfil |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `estudiante` |
| Datos de entrada | URL directa a `/students/{id}/edit` (su propio ID) |
| Pasos | 1) Iniciar sesión como estudiante. 2) Ir a la vista de su propio perfil. 3) Intentar acceder a la URL de edición. |
| Resultado esperado | El sistema permite ver el perfil propio pero rechaza el acceso a la edición (403). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

### CP-033 — Subida de fotografía de estudiante con archivo no válido

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — requisito del encargo académico "Validar tipo y tamaño de archivo" |
| Módulo | Estudiantes |
| Objetivo | Verificar que el sistema rechace archivos que no sean imágenes válidas o que excedan el tamaño permitido |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`; existe un estudiante de prueba |
| Datos de entrada | Archivo no permitido (p. ej. un `.exe` o `.pdf` renombrado como imagen, o una imagen que exceda el límite de tamaño configurado) |
| Pasos | 1) Editar el estudiante de prueba. 2) Adjuntar el archivo no válido en el campo de foto. 3) Guardar. |
| Resultado esperado | El sistema rechaza el archivo y muestra un mensaje de validación de tipo/tamaño. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso no verificado en auditorías previas (ver Plan de Pruebas, sección 15, "pruebas pendientes"); prioridad de este caso: confirmar si existe esta validación en el código |

### CP-034 — Búsqueda/filtro de estudiantes por curso y paralelo

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Estudiantes |
| Objetivo | Verificar que el filtro de búsqueda del listado de estudiantes funcione correctamente |
| Prioridad | Baja |
| Precondiciones | Existen estudiantes en al menos dos cursos/paralelos distintos |
| Datos de entrada | Filtro por un curso y paralelo específicos |
| Pasos | 1) Ir a "Estudiantes". 2) Aplicar el filtro de curso y paralelo. |
| Resultado esperado | El listado muestra únicamente a los estudiantes del curso/paralelo filtrado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `resources/views/students/index.blade.php` (formulario de filtros) |

---

## 4. Familiares

### CP-035 — Vinculación de un familiar a un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Familiares |
| Objetivo | Verificar que se pueda vincular un familiar/tutor a un estudiante |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `admin`/`academic`; existen un estudiante y un usuario con rol `padre`/`madre`/`tutor` |
| Datos de entrada | Estudiante de prueba; tutor de prueba; marca de "tutor principal" |
| Pasos | 1) Ir a "Familiares". 2) Vincular el tutor de prueba al estudiante de prueba. 3) Guardar. |
| Resultado esperado | La relación se crea en `student_family_members` y aparece en el listado de familiares del estudiante. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/FamilyController.php`; tabla `student_family_members` |

### CP-036 — Vinculación duplicada del mismo familiar al mismo estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Familiares |
| Objetivo | Verificar el comportamiento del sistema ante un intento de duplicar una relación familiar ya existente |
| Prioridad | Media |
| Precondiciones | Ya existe la relación creada en CP-035 |
| Datos de entrada | Mismo estudiante y mismo tutor de CP-035 |
| Pasos | 1) Repetir el procedimiento de vinculación con el mismo estudiante y el mismo tutor. |
| Resultado esperado | El sistema rechaza la duplicación o actualiza la relación existente sin crear un registro duplicado (comportamiento a confirmar, no asumido). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado; no se documentó explícitamente una regla de unicidad para esta relación en la auditoría previa, por lo que este caso debe verificar el comportamiento real sin asumirlo |

### CP-037 — Edición de la relación familiar (modal)

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Familiares |
| Objetivo | Verificar que se pueda editar una relación familiar existente (p. ej. cambiar quién es el tutor principal) |
| Prioridad | Media |
| Precondiciones | Existe la relación de CP-035 |
| Datos de entrada | Cambiar el indicador de tutor principal |
| Pasos | 1) Ir a "Familiares". 2) Abrir el modal de edición de la relación de prueba. 3) Modificar el campo correspondiente. 4) Guardar. |
| Resultado esperado | El cambio se refleja correctamente en el listado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `resources/views/family/index.blade.php` (modal de edición, líneas 117-143) |

### CP-038 — Eliminación de una relación familiar

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Familiares |
| Objetivo | Verificar que se pueda eliminar una relación familiar, incluyendo la confirmación previa |
| Prioridad | Media |
| Precondiciones | Existe la relación de CP-035 |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir a "Familiares". 2) Pulsar "Eliminar" sobre la relación de prueba. 3) Confirmar en el cuadro de diálogo. |
| Resultado esperado | La relación se elimina y ya no aparece en el listado; el estudiante y el usuario tutor no se eliminan. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Confirmación JS documentada (`onsubmit="return confirm(...)"`, `family/index.blade.php:88`) |

### CP-039 — Un tutor visualiza únicamente a sus propios hijos/pupilos vinculados

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (restricción de roles), RN-001 (acotación al alcance del estudiante) de forma parcial |
| Módulo | Familiares |
| Objetivo | Verificar que un tutor vea, en su panel, únicamente a los estudiantes con los que tiene una relación familiar registrada |
| Prioridad | Crítica |
| Precondiciones | Existen al menos dos tutores con hijos/pupilos distintos |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como el tutor A. 2) Revisar el panel/dashboard. |
| Resultado esperado | Solo se muestra información de los estudiantes vinculados al tutor A. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Relacionado con CP-030; caso de permisos por rol |

### CP-040 — Registro de familiar con campos obligatorios vacíos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Familiares |
| Objetivo | Verificar que el formulario de vinculación exija estudiante y tutor |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic` |
| Datos de entrada | Estudiante y/o tutor sin seleccionar |
| Pasos | 1) Ir a "Familiares". 2) Intentar guardar sin seleccionar estudiante o tutor. |
| Resultado esperado | El sistema rechaza el envío con errores de validación en los campos faltantes. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campos obligatorios |

### CP-041 — Búsqueda de familiares/tutores

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Familiares |
| Objetivo | Verificar el buscador del listado de familiares |
| Prioridad | Baja |
| Precondiciones | Existen varios tutores registrados |
| Datos de entrada | Texto de búsqueda parcial de un nombre de tutor existente |
| Pasos | 1) Ir a "Familiares". 2) Usar el campo de búsqueda con el texto indicado. |
| Resultado esperado | El listado se filtra mostrando solo coincidencias relevantes. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `family/index.blade.php:22-49` |

---

## 5. Cursos

### CP-042 — Creación de un curso con datos válidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-004 (equivalente parcial: el proyecto no implementa "unidades temáticas" con ese nombre; se interpreta como la estructura académica que el docente/administrador configura) |
| Módulo | Cursos |
| Objetivo | Verificar que se pueda crear un curso nuevo |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic` |
| Datos de entrada | Nombre = "Sexto de Primaria" |
| Pasos | 1) Ir a "Cursos" → "Nuevo curso". 2) Ingresar el nombre. 3) Guardar. |
| Resultado esperado | El curso se crea y aparece en el listado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/CourseController.php` |

### CP-043 — Creación de un curso con nombre duplicado

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Cursos |
| Objetivo | Verificar que no se puedan crear dos cursos con el mismo nombre |
| Prioridad | Alta |
| Precondiciones | Ya existe un curso llamado "Sexto de Primaria" (CP-042) |
| Datos de entrada | Nombre = "Sexto de Primaria" (duplicado) |
| Pasos | 1) Ir a "Nuevo curso". 2) Ingresar el mismo nombre ya existente. 3) Guardar. |
| Resultado esperado | El sistema rechaza la creación por la regla `unique:courses,name` y muestra un mensaje de validación. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado; regla confirmada en la auditoría previa del código |

### CP-044 — Creación de curso con nombre vacío

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Cursos |
| Objetivo | Verificar que el nombre del curso sea obligatorio |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic` |
| Datos de entrada | Nombre = (vacío) |
| Pasos | 1) Ir a "Nuevo curso". 2) Dejar el nombre vacío. 3) Guardar. |
| Resultado esperado | El sistema rechaza el envío con error de validación. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-045 — Edición de un curso existente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Cursos |
| Objetivo | Verificar que se pueda editar el nombre de un curso |
| Prioridad | Baja |
| Precondiciones | Existe el curso de CP-042 |
| Datos de entrada | Nuevo nombre válido y no duplicado |
| Pasos | 1) Ir al listado de cursos. 2) Editar el curso de prueba. 3) Cambiar el nombre. 4) Guardar. |
| Resultado esperado | El cambio se refleja en el listado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-046 — Eliminación de un curso con paralelos/estudiantes asociados

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Cursos |
| Objetivo | Verificar el comportamiento del sistema al intentar eliminar un curso que tiene paralelos o estudiantes asociados |
| Prioridad | Alta |
| Precondiciones | Existe un curso con al menos un paralelo y un estudiante asociados |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de cursos. 2) Intentar eliminar un curso con datos asociados. |
| Resultado esperado | El sistema debe impedir la eliminación o gestionar la integridad referencial de forma explícita (mensaje de advertencia); no debe dejar datos huérfanos ni fallar con un error no controlado. Comportamiento exacto a confirmar en la ejecución. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de integridad referencial / operación incorrecta; no se documentó explícitamente esta regla en la auditoría previa, por lo que debe verificarse sin asumir el resultado |

---

## 6. Paralelos

### CP-047 — Creación de un paralelo con datos válidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-004 (equivalente parcial) |
| Módulo | Paralelos |
| Objetivo | Verificar que se pueda crear un paralelo asociado a un curso |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic`; existe el curso "Sexto de Primaria" |
| Datos de entrada | Nombre = "Paralelo A"; curso = "Sexto de Primaria" |
| Pasos | 1) Ir a "Paralelos" → "Nuevo paralelo". 2) Completar los datos. 3) Guardar. |
| Resultado esperado | El paralelo se crea correctamente asociado al curso seleccionado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/ParallelController.php` |

### CP-048 — Creación de paralelo sin seleccionar curso

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Paralelos |
| Objetivo | Verificar que el curso sea un campo obligatorio al crear un paralelo |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic` |
| Datos de entrada | Nombre = "Paralelo B"; curso = (sin seleccionar) |
| Pasos | 1) Ir a "Nuevo paralelo". 2) Ingresar el nombre sin seleccionar curso. 3) Guardar. |
| Resultado esperado | El sistema rechaza el envío por falta del campo obligatorio curso. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-049 — Creación de paralelo duplicado para el mismo curso

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Paralelos |
| Objetivo | Verificar el comportamiento ante un paralelo con el mismo nombre en el mismo curso |
| Prioridad | Media |
| Precondiciones | Existe el "Paralelo A" del curso "Sexto de Primaria" (CP-047) |
| Datos de entrada | Nombre = "Paralelo A"; mismo curso |
| Pasos | 1) Ir a "Nuevo paralelo". 2) Repetir el mismo nombre y curso de CP-047. 3) Guardar. |
| Resultado esperado | El sistema debe rechazar la duplicación o gestionarla explícitamente; comportamiento exacto a confirmar en la ejecución, no documentado previamente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado, sin regla de unicidad confirmada previamente en el código auditado |

### CP-050 — Edición de un paralelo existente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Paralelos |
| Objetivo | Verificar que se pueda editar un paralelo existente |
| Prioridad | Baja |
| Precondiciones | Existe el "Paralelo A" |
| Datos de entrada | Nuevo nombre válido |
| Pasos | 1) Ir al listado de paralelos. 2) Editar el paralelo de prueba. 3) Guardar el cambio. |
| Resultado esperado | El cambio se refleja correctamente en el listado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-051 — Eliminación de un paralelo con estudiantes asociados

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Paralelos |
| Objetivo | Verificar el comportamiento del sistema al eliminar un paralelo con estudiantes asociados |
| Prioridad | Alta |
| Precondiciones | Existe un paralelo con al menos un estudiante asignado |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de paralelos. 2) Intentar eliminar un paralelo con estudiantes asociados. |
| Resultado esperado | El sistema debe impedir la eliminación o gestionar explícitamente la integridad referencial, sin dejar estudiantes con referencias inválidas. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de integridad referencial, comportamiento a confirmar |

---

## 7. Asignaturas

### CP-052 — Creación de una asignatura con datos válidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-004 (equivalente parcial) |
| Módulo | Asignaturas |
| Objetivo | Verificar que se pueda crear una asignatura asociada a un paralelo |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic`; existe el "Paralelo A" |
| Datos de entrada | Nombre = "Quechua"; paralelo = "Paralelo A" |
| Pasos | 1) Ir a "Asignaturas" → "Nueva asignatura". 2) Completar los datos. 3) Guardar. |
| Resultado esperado | La asignatura se crea correctamente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/SubjectController.php` |

### CP-053 — Creación de asignatura duplicada en el mismo paralelo

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaturas |
| Objetivo | Verificar que no se pueda crear una asignatura con el mismo nombre en el mismo paralelo |
| Prioridad | Alta |
| Precondiciones | Ya existe la asignatura "Quechua" en "Paralelo A" (CP-052) |
| Datos de entrada | Nombre = "Quechua"; mismo paralelo |
| Pasos | 1) Ir a "Nueva asignatura". 2) Repetir nombre y paralelo de CP-052. 3) Guardar. |
| Resultado esperado | El sistema rechaza la creación por la regla de unicidad por paralelo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado. Referencia: prueba automatizada existente `AcademicSubjectsTest` ("asignatura única por paralelo") confirma que esta regla está implementada; este caso la verifica manualmente |

### CP-054 — Creación de la misma asignatura en paralelos distintos

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaturas |
| Objetivo | Verificar que sí se permita repetir el nombre de una asignatura en paralelos distintos |
| Prioridad | Media |
| Precondiciones | Existe la asignatura "Quechua" en "Paralelo A"; existe "Paralelo B" |
| Datos de entrada | Nombre = "Quechua"; paralelo = "Paralelo B" |
| Pasos | 1) Ir a "Nueva asignatura". 2) Ingresar el mismo nombre pero con "Paralelo B". 3) Guardar. |
| Resultado esperado | La asignatura se crea correctamente (la unicidad es por paralelo, no global). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso positivo, complementario a CP-053 |

### CP-055 — Creación de asignatura con nombre vacío

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaturas |
| Objetivo | Verificar que el nombre de la asignatura sea obligatorio |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic` |
| Datos de entrada | Nombre = (vacío) |
| Pasos | 1) Ir a "Nueva asignatura". 2) Dejar el nombre vacío. 3) Guardar. |
| Resultado esperado | El sistema rechaza el envío con error de validación. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-056 — Edición de una asignatura existente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaturas |
| Objetivo | Verificar que se pueda editar una asignatura |
| Prioridad | Baja |
| Precondiciones | Existe la asignatura "Quechua" |
| Datos de entrada | Nuevo nombre válido y no duplicado en el mismo paralelo |
| Pasos | 1) Ir al listado de asignaturas. 2) Editar la asignatura de prueba. 3) Guardar. |
| Resultado esperado | El cambio se refleja correctamente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-057 — Eliminación de una asignatura con asignaciones docentes o notas asociadas

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaturas |
| Objetivo | Verificar el comportamiento del sistema al eliminar una asignatura que ya tiene notas o asignaciones docentes registradas |
| Prioridad | Alta |
| Precondiciones | Existe una asignatura con al menos una asignación docente y una nota registrada |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de asignaturas. 2) Intentar eliminar la asignatura de prueba. |
| Resultado esperado | El sistema debe impedir la eliminación o gestionar explícitamente la integridad referencial, sin dejar notas huérfanas. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de integridad referencial, comportamiento a confirmar |

---

## 8. Asignaciones docentes

### CP-058 — Creación de una asignación docente válida

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaciones docentes |
| Objetivo | Verificar que se pueda asignar un profesor a una asignatura y paralelo |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `admin`/`academic`; existen un profesor, una asignatura y un paralelo |
| Datos de entrada | Profesor de prueba; asignatura "Quechua"; paralelo "Paralelo A" |
| Pasos | 1) Ir a "Asignaciones docentes" → "Nueva asignación". 2) Completar los datos. 3) Guardar. |
| Resultado esperado | La asignación se crea correctamente y el profesor queda habilitado para calificar en ese paralelo/asignatura. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/TeachingAssignmentController.php` |

### CP-059 — Creación de una asignación docente duplicada

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaciones docentes |
| Objetivo | Verificar el comportamiento ante una asignación duplicada (mismo profesor, asignatura y paralelo) |
| Prioridad | Media |
| Precondiciones | Ya existe la asignación de CP-058 |
| Datos de entrada | Mismos valores de CP-058 |
| Pasos | 1) Repetir el procedimiento de CP-058 con los mismos datos. |
| Resultado esperado | El sistema debe rechazar la duplicación o gestionarla explícitamente; comportamiento a confirmar. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado |

### CP-060 — Creación de asignación docente sin seleccionar profesor

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaciones docentes |
| Objetivo | Verificar que el profesor sea un campo obligatorio |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic` |
| Datos de entrada | Profesor = (sin seleccionar); resto de campos válidos |
| Pasos | 1) Ir a "Nueva asignación". 2) Dejar el profesor sin seleccionar. 3) Guardar. |
| Resultado esperado | El sistema rechaza el envío por falta del campo obligatorio. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-061 — Edición de una asignación docente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaciones docentes |
| Objetivo | Verificar que se pueda reasignar una asignación a otro profesor |
| Prioridad | Media |
| Precondiciones | Existe la asignación de CP-058; existe un segundo profesor de prueba |
| Datos de entrada | Nuevo profesor |
| Pasos | 1) Ir al listado de asignaciones. 2) Editar la asignación de prueba. 3) Cambiar el profesor. 4) Guardar. |
| Resultado esperado | El cambio se refleja correctamente; el profesor anterior pierde la capacidad de calificar en ese paralelo/asignatura (a confirmar en la ejecución). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-062 — Eliminación de una asignación docente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asignaciones docentes |
| Objetivo | Verificar que al eliminar una asignación, el profesor pierda el acceso para calificar en ese paralelo/asignatura |
| Prioridad | Alta |
| Precondiciones | Existe la asignación de CP-058 |
| Datos de entrada | Ninguno |
| Pasos | 1) Eliminar la asignación de prueba. 2) Iniciar sesión como el profesor afectado. 3) Intentar calificar en el paralelo/asignatura desasignado. |
| Resultado esperado | El profesor ya no puede registrar notas en ese paralelo/asignatura (bloqueado por `hasTeachingAssignment()`). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol / integridad de datos, relacionado con el módulo de Notas (sección 9) |

### CP-063 — Un profesor sin asignaciones intenta ver "Asignaciones docentes"

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Asignaciones docentes |
| Objetivo | Verificar que solo administración/gestión académica pueda gestionar asignaciones docentes |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `profesor` |
| Datos de entrada | URL directa `/teaching-assignments` |
| Pasos | 1) Iniciar sesión como profesor. 2) Intentar acceder a `/teaching-assignments`. |
| Resultado esperado | El sistema deniega el acceso al módulo de gestión de asignaciones docentes. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

---

## 9. Notas (cuaderno pedagógico)

### CP-064 — Registro de una nota válida por un profesor asignado

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — módulo central de seguimiento académico, precondición de RF-007 (registro de intentos) en su forma equivalente |
| Módulo | Notas |
| Objetivo | Verificar que un profesor con asignación vigente pueda registrar una nota de un estudiante |
| Prioridad | Crítica |
| Precondiciones | Existe la asignación docente de CP-058; existe un estudiante en ese paralelo |
| Datos de entrada | Nota = 85 (dentro del rango 0-100) |
| Pasos | 1) Iniciar sesión como el profesor asignado. 2) Ir a "Cuaderno Pedagógico". 3) Ingresar la nota para el estudiante y la asignatura correspondiente. 4) Guardar. |
| Resultado esperado | La nota se guarda correctamente y se refleja en el cuaderno pedagógico y en la ficha del estudiante. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/GradeController.php` |

### CP-065 — Registro de una nota fuera del rango permitido (valor límite superior)

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar el límite superior de la validación de notas (máximo 100) |
| Prioridad | Alta |
| Precondiciones | Mismas que CP-064 |
| Datos de entrada | Nota = 101 |
| Pasos | 1) Ingresar 101 como nota de un estudiante. 2) Guardar. |
| Resultado esperado | El sistema rechaza el valor por exceder el máximo permitido (`max:100`). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de valor límite |

### CP-066 — Registro de una nota en el límite superior exacto permitido

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar que el valor límite superior exacto (100) sea aceptado |
| Prioridad | Media |
| Precondiciones | Mismas que CP-064 |
| Datos de entrada | Nota = 100 |
| Pasos | 1) Ingresar 100 como nota de un estudiante. 2) Guardar. |
| Resultado esperado | El sistema acepta el valor y lo guarda correctamente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de valor límite (positivo) |

### CP-067 — Registro de una nota negativa

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar el límite inferior de la validación de notas (mínimo 0) |
| Prioridad | Alta |
| Precondiciones | Mismas que CP-064 |
| Datos de entrada | Nota = -5 |
| Pasos | 1) Ingresar -5 como nota. 2) Guardar. |
| Resultado esperado | El sistema rechaza el valor por ser menor al mínimo permitido (`min:0`). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de valor límite |

### CP-068 — Registro de una nota en el límite inferior exacto permitido

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar que el valor límite inferior exacto (0) sea aceptado |
| Prioridad | Media |
| Precondiciones | Mismas que CP-064 |
| Datos de entrada | Nota = 0 |
| Pasos | 1) Ingresar 0 como nota. 2) Guardar. |
| Resultado esperado | El sistema acepta el valor. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de valor límite (positivo) |

### CP-069 — Registro de una nota con valor no numérico

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar que el campo de nota rechace valores no numéricos |
| Prioridad | Media |
| Precondiciones | Mismas que CP-064 |
| Datos de entrada | Nota = "ochenta" (texto) |
| Pasos | 1) Intentar ingresar texto en el campo de nota (o construir la petición manualmente si el campo del formulario no lo permite). 2) Enviar. |
| Resultado esperado | El sistema rechaza el valor por la regla `numeric`. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato inválido |

### CP-070 — Un profesor sin asignación intenta calificar en un paralelo/asignatura ajenos

| Campo | Detalle |
|---|---|
| Requisito relacionado | RN-001 (acotación del alcance por curso/nivel), de forma parcial |
| Módulo | Notas |
| Objetivo | Verificar que un profesor no pueda registrar notas en un paralelo/asignatura donde no tiene asignación docente vigente |
| Prioridad | Crítica |
| Precondiciones | Existe un profesor B sin asignación en el "Paralelo A"/"Quechua" |
| Datos de entrada | Petición de registro de nota para un estudiante de "Paralelo A" enviada como profesor B |
| Pasos | 1) Iniciar sesión como el profesor B. 2) Intentar registrar una nota para un estudiante del "Paralelo A" (construyendo la petición si la interfaz no lo permite directamente). |
| Resultado esperado | El sistema rechaza la operación (`hasTeachingAssignment()` devuelve falso). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol / operación incorrecta |

### CP-071 — Registro de nota para un estudiante inexistente

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar la validación `exists:students,id` |
| Prioridad | Media |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | `student_id` inexistente (petición construida manualmente) |
| Pasos | 1) Enviar una petición de registro de nota con un `student_id` que no existe en la base de datos. |
| Resultado esperado | El sistema rechaza la operación por la regla `exists:students,id`. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato inválido |

### CP-072 — Edición de una nota ya registrada

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar que se pueda editar una nota previamente registrada |
| Prioridad | Alta |
| Precondiciones | Existe la nota registrada en CP-064 |
| Datos de entrada | Nuevo valor de nota = 90 |
| Pasos | 1) Ir al cuaderno pedagógico. 2) Editar la nota de CP-064. 3) Guardar. |
| Resultado esperado | La nota se actualiza correctamente y el valor anterior queda registrado en el historial (ver módulo Historial, sección 15). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `GradeHistory`; caso de persistencia |

### CP-073 — Actualización masiva de notas (batchUpdate)

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Notas |
| Objetivo | Verificar que la actualización masiva de notas del cuaderno pedagógico guarde correctamente todos los valores ingresados |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada como profesor con asignación vigente; varios estudiantes en el paralelo |
| Datos de entrada | Notas válidas para varios estudiantes en una sola operación de guardado |
| Pasos | 1) Ir al cuaderno pedagógico. 2) Ingresar notas para varios estudiantes en la misma pantalla. 3) Guardar todo. |
| Resultado esperado | Todas las notas ingresadas se guardan correctamente, sin pérdida de datos parcial. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `GradeController::batchUpdate` |

### CP-074 — Un padre/tutor intenta registrar o modificar una nota

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (modo de lectura pasiva: "sin permitir modificar notas ni datos académicos") |
| Módulo | Notas |
| Objetivo | Verificar que un padre/tutor no pueda modificar notas bajo ninguna circunstancia |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor` |
| Datos de entrada | Petición directa de actualización de nota (construida manualmente, ya que la interfaz no debería ofrecer esta opción) |
| Pasos | 1) Iniciar sesión como padre/tutor. 2) Intentar enviar una petición de actualización de nota para un estudiante vinculado. |
| Resultado esperado | El sistema rechaza la operación (403) y no se modifica ningún dato académico. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso crítico de seguridad, directamente exigido por RNF-004 |

### CP-075 — Visualización del cuaderno pedagógico por un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Notas |
| Objetivo | Verificar que un estudiante solo pueda ver sus propias notas, no las de otros compañeros |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `estudiante` |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como estudiante. 2) Ir a la vista de notas/resultados. |
| Resultado esperado | Solo se muestran las notas del propio estudiante. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

---

## 10. Asistencia

### CP-076 — Registro de asistencia con estado "presente"

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asistencia |
| Objetivo | Verificar el registro correcto de asistencia con un valor de estado válido |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | Estudiante de prueba; fecha actual; estado = "presente" |
| Pasos | 1) Ir al módulo de asistencia dentro del cuaderno pedagógico. 2) Marcar al estudiante como "presente". 3) Guardar. |
| Resultado esperado | El registro se guarda correctamente en la tabla `attendance`. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `GradeController::saveAttendance`; columna `status` (varchar 20) |

### CP-077 — Registro de asistencia con cada valor válido del catálogo

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asistencia |
| Objetivo | Verificar que los cuatro estados documentados sean aceptados: presente, falta, licencia, retraso |
| Prioridad | Media |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | Estado = "falta"; luego repetir con "licencia" y "retraso" en registros distintos |
| Pasos | 1) Registrar asistencia con estado "falta" para un estudiante/fecha. 2) Repetir con "licencia" en otra fecha. 3) Repetir con "retraso" en otra fecha. |
| Resultado esperado | Los tres registros se guardan correctamente con su estado respectivo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Valores documentados en el comentario de la migración `create_attendance_table.php` |

### CP-078 — Registro de asistencia con un valor de estado no reconocido

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asistencia |
| Objetivo | Verificar que el sistema rechace un valor de estado fuera del catálogo esperado |
| Prioridad | Media |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | Estado = "ausente_justificado" (valor no documentado) enviado directamente en una petición |
| Pasos | 1) Construir una petición de registro de asistencia con un valor de estado no reconocido. 2) Enviarla. |
| Resultado esperado | El sistema rechaza el valor o lo normaliza según una regla explícita; comportamiento a confirmar, ya que no se documentó una validación `in:` específica para este campo en la auditoría previa. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato inválido; verificar si existe validación `in:` sobre el campo `status` de asistencia |

### CP-079 — Registro de asistencia duplicada para el mismo estudiante y fecha

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asistencia |
| Objetivo | Verificar el comportamiento ante un intento de registrar dos veces la asistencia del mismo estudiante en la misma fecha |
| Prioridad | Alta |
| Precondiciones | Ya existe un registro de asistencia para el estudiante de prueba en la fecha actual (CP-076) |
| Datos de entrada | Mismo estudiante, misma fecha, estado distinto (p. ej. "falta" en lugar de "presente") |
| Pasos | 1) Intentar registrar nuevamente la asistencia del mismo estudiante en la misma fecha con un estado distinto. |
| Resultado esperado | El sistema debe actualizar el registro existente (no duplicarlo) o rechazar la operación de forma explícita; comportamiento a confirmar, no asumido. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de dato duplicado / persistencia |

### CP-080 — Registro de asistencia sin seleccionar estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Asistencia |
| Objetivo | Verificar que el estudiante sea un campo obligatorio al registrar asistencia |
| Prioridad | Media |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | `student_id` = (vacío o inexistente) |
| Pasos | 1) Intentar guardar un registro de asistencia sin estudiante válido. |
| Resultado esperado | El sistema rechaza el envío. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-081 — Un profesor sin asignación intenta registrar asistencia en un paralelo ajeno

| Campo | Detalle |
|---|---|
| Requisito relacionado | RN-001 (acotación por curso/nivel), de forma parcial |
| Módulo | Asistencia |
| Objetivo | Verificar que el registro de asistencia respete la misma restricción de asignación docente que las notas |
| Prioridad | Alta |
| Precondiciones | Profesor B sin asignación en el paralelo de prueba |
| Datos de entrada | Petición de registro de asistencia para un estudiante de un paralelo no asignado al profesor B |
| Pasos | 1) Iniciar sesión como profesor B. 2) Intentar registrar asistencia para un estudiante fuera de su asignación. |
| Resultado esperado | El sistema rechaza la operación. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

### CP-082 — Visualización de asistencia por un padre/tutor

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Asistencia |
| Objetivo | Verificar que un padre/tutor pueda consultar (solo lectura) la asistencia de su hijo/pupilo, sin poder modificarla |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor`; existe asistencia registrada para su hijo/pupilo |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como tutor. 2) Consultar la asistencia de su hijo/pupilo en el dashboard. 3) Intentar modificarla (si la interfaz lo permitiera) o construir la petición directamente. |
| Resultado esperado | Puede visualizar la asistencia; cualquier intento de modificación es rechazado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

---

## 11. Dashboards

### CP-083 — Dashboard del rol administrador

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Dashboards |
| Objetivo | Verificar que el dashboard del administrador muestre información general del sistema |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como admin. 2) Observar el contenido del dashboard. |
| Resultado esperado | Se muestran indicadores generales (p. ej. totales de estudiantes, docentes, cursos) y accesos a los módulos administrativos. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `resources/views/dashboard.blade.php` |

### CP-084 — Dashboard del rol profesor

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Dashboards |
| Objetivo | Verificar que el dashboard del profesor muestre información relevante a sus asignaciones |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `profesor` con asignaciones vigentes |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como profesor. 2) Observar el contenido del dashboard. |
| Resultado esperado | Se muestra información relacionada a sus paralelos/asignaturas asignados, sin datos de otros profesores. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de navegación / permisos por rol |

### CP-085 — Dashboard del rol tutor (padre/madre)

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Dashboards |
| Objetivo | Verificar que el dashboard del tutor muestre únicamente la información de sus hijos/pupilos vinculados (tarjetas de resumen, promedio, asistencia) |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor` |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como tutor. 2) Observar el contenido del dashboard. |
| Resultado esperado | Se muestran únicamente tarjetas/reportes de los estudiantes vinculados a esa cuenta. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `dashboard.blade.php` (rama `@if(Auth::user()->isTutor())`, líneas 33-143) |

### CP-086 — Ruta `/tutor/dashboard`

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Dashboards |
| Objetivo | Verificar el comportamiento de la ruta dedicada `/tutor/dashboard` |
| Prioridad | Baja |
| Precondiciones | Sesión iniciada con rol `tutor` |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como tutor. 2) Navegar directamente a `/tutor/dashboard`. |
| Resultado esperado | El sistema redirige a `/dashboard` (comportamiento documentado del closure de la ruta) y muestra la misma información que CP-085. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de navegación; verificar además si la vista `resources/views/tutor/dashboard.blade.php` queda inutilizada, como fue señalado en auditorías previas |

### CP-087 — Dashboard del rol estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Dashboards |
| Objetivo | Verificar que el dashboard del estudiante muestre únicamente su propia información académica |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `estudiante` |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como estudiante. 2) Observar el contenido del dashboard. |
| Resultado esperado | Solo se muestra información del propio estudiante (notas, asistencia). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

---

## 12. Actividades del robot

### CP-088 — Registro de actividad del robot vía el modal del cuaderno pedagógico

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-007 (equivalente parcial: no existe la tabla `phonetic_evaluations`; el registro real se guarda como `Grade`) |
| Módulo | Actividades del robot |
| Objetivo | Verificar que un profesor pueda lanzar/registrar una actividad del robot desde el modal del cuaderno pedagógico |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | Asignatura, categoría y paralelo válidos (según campos del modal) |
| Pasos | 1) Ir al cuaderno pedagógico. 2) Abrir el modal "Lanzar Actividad al Robot". 3) Completar los campos. 4) Guardar. |
| Resultado esperado | Se crea el registro de actividad (como `Grade` pendiente) para los estudiantes del paralelo seleccionado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `grades/index.blade.php:190-232`; `GradeController::storeRobotActivity` |

### CP-089 — Registro de actividad del robot vía la página completa

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-007 (equivalente parcial) |
| Módulo | Actividades del robot |
| Objetivo | Verificar el flujo alternativo de registro de actividad mediante la página completa `robot/create.blade.php` |
| Prioridad | Media |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | Mismos campos que CP-088, incluyendo el campo "Paralelo" marcado como obligatorio en esta vista |
| Pasos | 1) Navegar directamente a `/robot-activities/create` (sin enlace visible en el menú). 2) Completar el formulario. 3) Guardar. |
| Resultado esperado | Se crea el registro de actividad de forma equivalente al modal (CP-088). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de navegación; esta vista no tiene enlace de entrada en el menú (hallazgo ya documentado); verificar además si el campo "Paralelo" (obligatorio aquí, no en el modal) produce una diferencia real de comportamiento entre ambos flujos |

### CP-090 — Registro de actividad del robot sin seleccionar asignatura

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Actividades del robot |
| Objetivo | Verificar que la asignatura sea un campo obligatorio en ambos flujos (modal y página completa) |
| Prioridad | Media |
| Precondiciones | Sesión iniciada como profesor con asignación vigente |
| Datos de entrada | Asignatura = (sin seleccionar) |
| Pasos | 1) Repetir CP-088 y CP-089 dejando la asignatura sin seleccionar. |
| Resultado esperado | El sistema rechaza el envío en ambos flujos. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio |

### CP-091 — Registro de actividad del robot vía API con datos válidos

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-007 (equivalente parcial) |
| Módulo | Actividades del robot / API |
| Objetivo | Verificar que el endpoint `POST /api/v1/robot/activity` registre correctamente un resultado de actividad |
| Prioridad | Crítica |
| Precondiciones | Token Sanctum válido de una cuenta con rol `robot` (o el rol habilitado para esta integración) |
| Datos de entrada | JSON con `activity_id`, `student_id`, `subject_id`, `score`, `activity_type` válidos |
| Pasos | 1) Enviar `POST /api/v1/robot/activity` con el token en la cabecera `Authorization: Bearer <token>` y el cuerpo JSON indicado. |
| Resultado esperado | Respuesta HTTP 200/201 y creación/actualización del registro `Grade` correspondiente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/Api/RobotActivityController.php`; ver también CP-096 sobre la ambigüedad documentada en RN-002 |

### CP-092 — Registro de actividad del robot vía API sin token

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Actividades del robot / API |
| Objetivo | Verificar que el endpoint rechace peticiones sin autenticación |
| Prioridad | Crítica |
| Precondiciones | Ninguna |
| Datos de entrada | Mismo cuerpo JSON de CP-091, sin cabecera de autorización |
| Pasos | 1) Enviar `POST /api/v1/robot/activity` sin token. |
| Resultado esperado | Respuesta HTTP 401 Unauthenticated. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso negativo de seguridad |

### CP-093 — Registro de actividad del robot vía API con un token de un rol no autorizado

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (restricción estricta de roles) |
| Módulo | Actividades del robot / API |
| Objetivo | Verificar si el endpoint acepta indebidamente peticiones de un usuario autenticado que no sea `robot` ni `profesor` |
| Prioridad | Crítica |
| Precondiciones | Token Sanctum válido de una cuenta con rol `padre` o `estudiante` |
| Datos de entrada | Mismo cuerpo JSON de CP-091, con token de un rol no docente/no robot |
| Pasos | 1) Enviar `POST /api/v1/robot/activity` con el token de una cuenta de rol `padre`. |
| Resultado esperado | El sistema **debe** rechazar la operación (403) por no corresponder al rol autorizado para esta integración. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso vinculado a un hallazgo ya documentado (`Informe_YURA_Auditoria_Pruebas.docx`, Error 5, prioridad ALTA: el endpoint solo exige `auth:sanctum`, sin verificación de rol). Este caso debe ejecutarse y su resultado registrarse sin asumir el desenlace. |

### CP-094 — Idempotencia del registro de actividad del robot (mismo `activity_id`)

| Campo | Detalle |
|---|---|
| Requisito relacionado | RF-007 (equivalente parcial) |
| Módulo | Actividades del robot / API |
| Objetivo | Verificar que reenviar el mismo `activity_id` para el mismo estudiante/asignatura actualice el registro existente en vez de duplicarlo |
| Prioridad | Alta |
| Precondiciones | Ya existe el registro creado en CP-091 |
| Datos de entrada | Mismo `activity_id`, `student_id`, `subject_id`; `score` distinto |
| Pasos | 1) Enviar nuevamente `POST /api/v1/robot/activity` con el mismo `activity_id` y un `score` distinto. |
| Resultado esperado | El registro existente se actualiza (no se crea un segundo `Grade` duplicado), según el comportamiento `firstOrNew` documentado. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia / no duplicación |

### CP-095 — Registro de actividad del robot con `score` fuera de rango

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Actividades del robot / API |
| Objetivo | Verificar que el endpoint valide el rango del puntaje recibido |
| Prioridad | Media |
| Precondiciones | Token Sanctum válido |
| Datos de entrada | `score` = 150 (fuera de rango esperado) |
| Pasos | 1) Enviar `POST /api/v1/robot/activity` con un `score` fuera del rango esperado. |
| Resultado esperado | El sistema rechaza la petición por validación de rango. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de valor límite / dato inválido |

### CP-096 — Verificación de que el puntaje del robot no reemplaza indebidamente una nota oficial (RN-002)

| Campo | Detalle |
|---|---|
| Requisito relacionado | RN-002 ("las puntuaciones acumuladas en los juegos del robot son referenciales para el seguimiento y no se computan directamente como notas oficiales del cuaderno pedagógico") |
| Módulo | Actividades del robot |
| Objetivo | Verificar si el resultado registrado vía la API del robot se refleja como una nota oficial en el cuaderno pedagógico, y contrastar ese comportamiento contra la regla de negocio RN-002 |
| Prioridad | Alta |
| Precondiciones | Existe una nota oficial previa del estudiante en la asignatura correspondiente; se dispone de acceso para registrar una actividad del robot para ese mismo estudiante/asignatura |
| Datos de entrada | Resultado de actividad del robot enviado vía CP-091 |
| Pasos | 1) Registrar la nota oficial de un estudiante manualmente (vía cuaderno pedagógico). 2) Registrar una actividad del robot para el mismo estudiante/asignatura vía API. 3) Comparar el cuaderno pedagógico antes y después. |
| Resultado esperado | A definir por el equipo del proyecto: este caso documenta una **ambigüedad ya identificada** en `docs/pruebas/identificacion-requisitos-verificables-yura.md` (RN-002), ya que la implementación actual registra los resultados del robot directamente como `Grade` (`Api/RobotActivityController::store` usa `Grade::firstOrNew`), lo que podría contradecir la idea de que sean solo "referenciales". Este caso no asume cuál de los dos comportamientos es el correcto; solo lo registra objetivamente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de verificación de regla de negocio ambigua; requiere definición del equipo del proyecto antes de poder calificarse como aprobado/erróneo |

---

## 13. API

### CP-097 — Listado de estudiantes vía API sin token

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | API |
| Objetivo | Verificar que `GET /api/students` rechace peticiones no autenticadas |
| Prioridad | Crítica |
| Precondiciones | Ninguna |
| Datos de entrada | Ninguno |
| Pasos | 1) Enviar `GET /api/students` sin cabecera de autorización. |
| Resultado esperado | Respuesta HTTP 401. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `app/Http/Controllers/Api/StudentController.php` |

### CP-098 — Listado de estudiantes vía API con token de administrador

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | API |
| Objetivo | Verificar que un administrador obtenga el listado completo de estudiantes vía API |
| Prioridad | Alta |
| Precondiciones | Token Sanctum válido de una cuenta `admin` |
| Datos de entrada | Ninguno |
| Pasos | 1) Enviar `GET /api/students` con el token de administrador. |
| Resultado esperado | Respuesta HTTP 200 con el listado completo de estudiantes en formato JSON. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso positivo |

### CP-099 — Listado de estudiantes vía API con token de un profesor

| Campo | Detalle |
|---|---|
| Requisito relacionado | RN-001 (acotación por curso/nivel), RNF-004 |
| Módulo | API |
| Objetivo | Verificar que la API limite el listado de estudiantes a los del profesor autenticado |
| Prioridad | Alta |
| Precondiciones | Token Sanctum válido de un profesor con asignaciones vigentes |
| Datos de entrada | Ninguno |
| Pasos | 1) Enviar `GET /api/students` con el token del profesor de prueba. |
| Resultado esperado | Respuesta HTTP 200 con únicamente los estudiantes de los paralelos asignados a ese profesor. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol, ya documentado como implementado en `Api\StudentController::index` |

### CP-100 — Listado de estudiantes vía API con token de un padre/tutor

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | API |
| Objetivo | Verificar que la API limite el listado de estudiantes a los hijos/pupilos del tutor autenticado |
| Prioridad | Crítica |
| Precondiciones | Token Sanctum válido de un tutor de prueba |
| Datos de entrada | Ninguno |
| Pasos | 1) Enviar `GET /api/students` con el token del tutor de prueba. |
| Resultado esperado | Respuesta HTTP 200 con únicamente los estudiantes vinculados a ese tutor (pivote `familiares`). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

### CP-101 — Creación de estudiante vía API (`POST /api/students`)

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | API |
| Objetivo | Verificar el contrato de creación de estudiantes vía API |
| Prioridad | Media |
| Precondiciones | Token Sanctum válido de una cuenta con permiso para crear estudiantes |
| Datos de entrada | JSON con los campos obligatorios de estudiante |
| Pasos | 1) Enviar `POST /api/students` con el cuerpo JSON válido y el token correspondiente. |
| Resultado esperado | Respuesta HTTP 201 (o 200) con el estudiante creado en formato JSON. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso positivo de API |

### CP-102 — Creación de estudiante vía API con campos faltantes

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | API |
| Objetivo | Verificar que la API responda con errores de validación estructurados (no un error de servidor) |
| Prioridad | Media |
| Precondiciones | Token Sanctum válido |
| Datos de entrada | JSON incompleto (sin nombre) |
| Pasos | 1) Enviar `POST /api/students` con un cuerpo JSON sin el campo nombre. |
| Resultado esperado | Respuesta HTTP 422 con el detalle de los errores de validación por campo. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de campo obligatorio / API |

### CP-103 — Consulta de un estudiante específico vía API (`GET /api/students/{id}`) por un tutor no relacionado

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | API |
| Objetivo | Verificar que un tutor no pueda consultar el detalle de un estudiante que no le pertenece, mediante acceso directo por ID |
| Prioridad | Crítica |
| Precondiciones | Token Sanctum válido de un tutor A; existe un estudiante vinculado únicamente al tutor B |
| Datos de entrada | ID de un estudiante no vinculado al tutor A |
| Pasos | 1) Enviar `GET /api/students/{id}` con el token del tutor A, usando el ID de un estudiante del tutor B. |
| Resultado esperado | Respuesta HTTP 403 (o 404), sin exponer los datos del estudiante. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Referencia: `User::canViewStudent()` |

### CP-104 — Formato y códigos de estado JSON consistentes en toda la API

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | API |
| Objetivo | Verificar que todas las respuestas de la API sean JSON válido, incluso ante error |
| Prioridad | Baja |
| Precondiciones | Ninguna |
| Datos de entrada | Distintas peticiones válidas e inválidas a los endpoints de `routes/api.php` |
| Pasos | 1) Enviar varias peticiones (válidas e inválidas) a distintos endpoints de la API. 2) Revisar la cabecera `Content-Type` y el cuerpo de cada respuesta. |
| Resultado esperado | Todas las respuestas tienen `Content-Type: application/json` y un cuerpo JSON válido (comportamiento esperado por el middleware `ForceJsonResponse`). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de contrato de API |

### CP-105 — Uso de un token revocado o expirado

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | API |
| Objetivo | Verificar que un token de Sanctum ya revocado no permita el acceso |
| Prioridad | Alta |
| Precondiciones | Se dispone de un token válido que luego es revocado (p. ej. mediante logout o eliminación manual del token en la base de datos de prueba) |
| Datos de entrada | Token revocado |
| Pasos | 1) Obtener un token válido (CP-008). 2) Revocarlo. 3) Enviar una petición a un endpoint protegido usando ese mismo token. |
| Resultado esperado | Respuesta HTTP 401. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de seguridad; el mecanismo exacto para revocar un token (endpoint de logout de API, si existe) debe confirmarse durante la ejecución |

---

## 14. Exportaciones

### CP-106 — Exportación individual de un estudiante a PDF

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — requisito del encargo académico "Generar reportes PDF y Excel" (rol Administrador) |
| Módulo | Exportaciones |
| Objetivo | Verificar que se pueda exportar la ficha de un estudiante a PDF con datos correctos |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin`/`academic`/`profesor` (según corresponda); existe un estudiante de prueba con notas |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir a la ficha del estudiante de prueba. 2) Pulsar "Exportar PDF". |
| Resultado esperado | Se descarga un archivo PDF con los datos correctos del estudiante (nombre, notas, asistencia según corresponda al reporte). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `StudentController::exportPdf`; Dompdf |

### CP-107 — Exportación individual de un estudiante a Excel

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Exportaciones |
| Objetivo | Verificar que se pueda exportar la ficha de un estudiante a Excel con datos correctos |
| Prioridad | Media |
| Precondiciones | Mismas que CP-106 |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir a la ficha del estudiante de prueba. 2) Pulsar "Exportar Excel". |
| Resultado esperado | Se descarga un archivo Excel (.xlsx) con los datos correctos del estudiante. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `StudentController::exportExcel`; Maatwebsite/Excel. Requiere que las extensiones PHP `gd`/`zip` estén habilitadas en el entorno de prueba (ver Plan de Pruebas, sección 9) |

### CP-108 — Exportación masiva (todos los estudiantes) a PDF por un administrador

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A — requisito del encargo académico (rol Administrador: "Generar reportes PDF y Excel") |
| Módulo | Exportaciones |
| Objetivo | Verificar que un administrador pueda exportar el PDF con todos los estudiantes |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `admin` |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir al listado de estudiantes. 2) Pulsar "Exportar todos a PDF". |
| Resultado esperado | Se descarga un PDF con el listado completo de estudiantes. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | `StudentController::exportPdfAll`; `routes/web.php:36` |

### CP-109 — Exportación masiva (todos los estudiantes) a PDF por un padre/tutor

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 (restricción estricta de roles) |
| Módulo | Exportaciones |
| Objetivo | Verificar si un padre/tutor puede exportar el PDF con **todos** los estudiantes de la institución, no solo los suyos |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor` |
| Datos de entrada | URL/acción directa de exportación masiva (`students.export.pdf.all`) |
| Pasos | 1) Iniciar sesión como padre/tutor. 2) Acceder directamente a la ruta de exportación masiva de PDF (con o sin botón visible en la interfaz). |
| Resultado esperado | El sistema **debe** rechazar la operación (403) para este rol. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso vinculado al hallazgo CRÍTICO ya documentado (`Informe_YURA_Auditoria_Pruebas.docx`, Error 1: `routes/web.php:36-37` y `StudentController.php:550-562` no verifican rol). Este es el caso de mayor prioridad de todo el documento y debe ejecutarse y registrarse sin asumir el resultado. |

### CP-110 — Exportación masiva (todos los estudiantes) a Excel por un padre/tutor

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Exportaciones |
| Objetivo | Verificar si un padre/tutor puede exportar el Excel con **todos** los estudiantes de la institución |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor` |
| Datos de entrada | URL/acción directa de exportación masiva (`students.export.excel.all`) |
| Pasos | 1) Iniciar sesión como padre/tutor. 2) Acceder directamente a la ruta de exportación masiva de Excel. |
| Resultado esperado | El sistema **debe** rechazar la operación (403) para este rol. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso equivalente a CP-109, para el formato Excel; mismo hallazgo crítico documentado |

### CP-111 — Exportación masiva por un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Exportaciones |
| Objetivo | Verificar si un estudiante puede exportar el PDF/Excel con todos los estudiantes de la institución |
| Prioridad | Crítica |
| Precondiciones | Sesión iniciada con rol `estudiante` |
| Datos de entrada | URL/acción directa de exportación masiva |
| Pasos | 1) Iniciar sesión como estudiante. 2) Acceder directamente a la ruta de exportación masiva (PDF y Excel). |
| Resultado esperado | El sistema **debe** rechazar la operación para este rol. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Extiende la cobertura de CP-109/CP-110 a un rol adicional |

### CP-112 — Exportación con entorno sin extensiones `gd`/`zip`

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Exportaciones |
| Objetivo | Verificar el comportamiento del sistema ante un entorno con las extensiones PHP `gd`/`zip` deshabilitadas |
| Prioridad | Alta |
| Precondiciones | Entorno de prueba con las extensiones `gd`/`zip` deshabilitadas (condición ya documentada como observada en el entorno auditado) |
| Datos de entrada | Ninguno |
| Pasos | 1) En un entorno con `gd`/`zip` deshabilitadas, intentar exportar un estudiante a Excel. |
| Resultado esperado | El sistema debería mostrar un mensaje de error controlado al usuario, no una pantalla de error no controlada (comportamiento a confirmar). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso derivado de un riesgo ya documentado en el Plan de Pruebas (sección 13, "Riesgo de fallo silencioso en exportaciones") |

### CP-113 — Contenido de la exportación PDF individual coincide con los datos del sistema

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Exportaciones |
| Objetivo | Verificar la exactitud de los datos exportados frente a los datos reales del estudiante |
| Prioridad | Media |
| Precondiciones | Estudiante de prueba con notas y asistencia conocidas |
| Datos de entrada | Ninguno |
| Pasos | 1) Registrar notas y asistencia conocidas para el estudiante de prueba. 2) Exportar su PDF. 3) Comparar el contenido del PDF contra los datos registrados en el sistema. |
| Resultado esperado | Los datos del PDF coinciden exactamente con los datos almacenados. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia / exactitud de datos |

### CP-114 — Tiempo de generación de una exportación masiva

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Exportaciones |
| Objetivo | Medir el tiempo de respuesta de la exportación masiva con un volumen de datos representativo |
| Prioridad | Baja |
| Precondiciones | Base de datos de prueba con un número representativo de estudiantes (p. ej. los generados por `SchoolPilotSeeder`) |
| Datos de entrada | Ninguno |
| Pasos | 1) Como administrador, solicitar la exportación masiva a PDF. 2) Medir el tiempo transcurrido desde la solicitud hasta la descarga completa. |
| Resultado esperado | Registrar el tiempo medido (sin un umbral predefinido en la documentación del proyecto para este caso; a definir por el equipo si se requiere un SLA). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar (incluir el tiempo medido en segundos) |
| Observaciones | Caso de rendimiento |

---

## 15. Historial

### CP-115 — Visualización del historial de notas de un estudiante

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Historial |
| Objetivo | Verificar que se pueda consultar el historial de cambios de notas de un estudiante |
| Prioridad | Media |
| Precondiciones | Existe al menos una edición de nota previa (resultado de CP-072) |
| Datos de entrada | Ninguno |
| Pasos | 1) Ir a la ficha del estudiante de prueba. 2) Acceder a "Historial de notas". |
| Resultado esperado | Se muestra el valor anterior y el valor actual de la nota, con fecha del cambio. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Ruta `grades.history`; tabla `grade_history` |

### CP-116 — El historial refleja correctamente múltiples ediciones consecutivas

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Historial |
| Objetivo | Verificar que cada edición de una nota genere un registro independiente en el historial |
| Prioridad | Media |
| Precondiciones | Nota de prueba ya creada |
| Datos de entrada | Editar la misma nota tres veces con valores distintos (p. ej. 70 → 80 → 90) |
| Pasos | 1) Editar la nota de prueba tres veces consecutivas con valores distintos. 2) Consultar el historial. |
| Resultado esperado | El historial muestra las tres transiciones de valor, en el orden correcto. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de persistencia |

### CP-117 — Un padre/tutor consulta el historial de notas de su hijo/pupilo

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Historial |
| Objetivo | Verificar que un tutor pueda ver (solo lectura) el historial de notas de su hijo/pupilo |
| Prioridad | Media |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor`; existe historial de notas para su hijo/pupilo |
| Datos de entrada | Ninguno |
| Pasos | 1) Iniciar sesión como tutor. 2) Consultar el historial de notas de su hijo/pupilo. |
| Resultado esperado | Puede visualizar el historial correspondiente a su hijo/pupilo únicamente. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol |

### CP-118 — Un padre/tutor intenta consultar el historial de un estudiante no relacionado

| Campo | Detalle |
|---|---|
| Requisito relacionado | RNF-004 |
| Módulo | Historial |
| Objetivo | Verificar que un tutor no pueda acceder al historial de notas de un estudiante ajeno mediante acceso directo por URL |
| Prioridad | Alta |
| Precondiciones | Sesión iniciada con rol `padre`/`madre`/`tutor`; existe un estudiante no vinculado a esa cuenta |
| Datos de entrada | URL directa `/grades/{id}/history` con el ID de un estudiante no relacionado |
| Pasos | 1) Iniciar sesión como tutor. 2) Acceder directamente a la URL del historial de un estudiante ajeno. |
| Resultado esperado | El sistema rechaza el acceso (403). |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de permisos por rol / navegación |

### CP-119 — Historial de notas no permite modificación directa

| Campo | Detalle |
|---|---|
| Requisito relacionado | N/A |
| Módulo | Historial |
| Objetivo | Verificar que la vista de historial sea de solo lectura para todos los roles |
| Prioridad | Baja |
| Precondiciones | Cualquier rol con acceso de lectura al historial |
| Datos de entrada | Ninguno |
| Pasos | 1) Acceder a la vista de historial de notas. 2) Verificar que no existan controles de edición sobre los registros históricos. |
| Resultado esperado | La vista no ofrece ninguna opción de edición o eliminación de registros históricos. |
| Resultado obtenido | |
| Estado | PENDIENTE |
| Evidencia | Pendiente de adjuntar |
| Observaciones | Caso de integridad de datos |

---

## 16. Matriz de trazabilidad de requisitos

Esta matriz resume qué casos de prueba cubren cada requisito del documento `docs/pruebas/identificacion-requisitos-verificables-yura.md`. Se indica explícitamente cuando un requisito está fuera del alcance de la plataforma web (ver `plande pruebas promt 3.md`, sección 4), en lugar de forzar una relación inexistente.

| Requisito | Descripción breve | Cobertura en este documento |
|---|---|---|
| RF-001 | Captura de comandos de voz por botón/pantalla del robot | Fuera de alcance de la plataforma web — no existe implementación de hardware/captura de audio en este repositorio |
| RF-002 | Motor ASR procesa audio y devuelve transcripción | Fuera de alcance de la plataforma web |
| RF-003 | Motor ASR devuelve porcentaje de precisión fonética | Fuera de alcance de la plataforma web |
| RF-004 | Docente crea unidades temáticas, palabras clave y retos | Cobertura parcial/equivalente: CP-042, CP-047, CP-052 (creación de cursos, paralelos y asignaturas como estructura académica configurable por el docente/administración) |
| RF-005 | App móvil muestra gráficos de avance a apoderados | Fuera de alcance — no existe aplicación móvil en este repositorio |
| RF-006 | App móvil notifica en tiempo real | Fuera de alcance — no existe aplicación móvil en este repositorio |
| RF-007 | Registrar cada intento en `phonetic_evaluations` con marca de tiempo | Cobertura parcial/equivalente: CP-088, CP-089, CP-091, CP-094 (el registro real se hace como `Grade` vía web o API, no existe la tabla `phonetic_evaluations`) |
| RF-008 | Activar actuadores/luces/bocinas como recompensa | Fuera de alcance — no existe hardware en este repositorio |
| RNF-001 | Operación local sin depender de internet | Fuera de alcance de este catálogo — corresponde a una prueba de infraestructura de red, no a un caso de interfaz/API ejecutable manualmente en este documento; ver Plan de Pruebas, sección 15 |
| RNF-002 | Usabilidad infantil (11-12 años) | Fuera de alcance de este catálogo — requiere pruebas de usabilidad presencial, no cubiertas aquí; ver Plan de Pruebas, sección 15 |
| RNF-003 | Latencia ASR/recompensa ≤ 2.5s | Fuera de alcance de este catálogo (depende del hardware/ASR); CP-114 mide tiempos de un proceso distinto (exportación), no de este requisito |
| RNF-004 | Restricción estricta de roles y modo de lectura pasiva para padres | Cobertura amplia: CP-005, CP-010, CP-019, CP-030, CP-031, CP-032, CP-039, CP-074, CP-075, CP-082, CP-085, CP-087, CP-092, CP-093, CP-097, CP-100, CP-103, CP-109, CP-110, CP-111, CP-117, CP-118 |
| RN-001 | Estudiante solo interactúa con retos de su curso/nivel | Cobertura parcial/equivalente: CP-024, CP-029, CP-070, CP-081, CP-099 (aplicada como acotación de acceso por curso/paralelo y asignación docente, no existe un concepto explícito de "reto" en el código) |
| RN-002 | Puntuaciones del robot son referenciales, no notas oficiales | CP-096 (caso que documenta la ambigüedad ya identificada entre esta regla y la implementación real, sin asumir cuál es correcta) |
| RN-003 | Robot no genera conversación libre; vocabulario configurado previamente | Fuera de alcance de la plataforma web — depende del firmware/lógica del robot, no implementado en este repositorio |

---

## Resumen del catálogo

- **Total de casos de prueba definidos:** 119 (CP-001 a CP-119).
- **Todos los casos** tienen `Resultado obtenido` vacío, `Estado = PENDIENTE` y `Evidencia = Pendiente de adjuntar`, conforme a lo solicitado.
- Este documento **no declara ninguna prueba como ejecutada** ni afirma el estado real de ninguna funcionalidad del sistema. Los casos marcados como vinculados a hallazgos de auditorías previas (por ejemplo, CP-005, CP-030, CP-093, CP-109, CP-110) citan esos antecedentes únicamente como contexto de por qué el caso tiene prioridad alta o crítica, no como un resultado ya confirmado por este documento.
- La ejecución de estos casos, el llenado de "Resultado obtenido"/"Estado"/"Evidencia" y el registro de defectos derivados corresponden a una fase posterior, no cubierta por este documento.
