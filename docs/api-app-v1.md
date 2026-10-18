# API de la app móvil — v1

Base: `/api/v1/app`. Autenticación: `Authorization: Bearer <token>` (token de `POST /api/login`, vence a los 30 días).
Solo roles de familia (`padre`, `madre`, `tutor`). Otros roles reciben 403. Usuario suspendido: 403. Token vencido o ausente: 401.

## Login — `POST /api/login`
Límite: 5 intentos por minuto por correo + IP (429). Respuesta: `access_token`, `user {id,name,email,role}`.

## `GET /me`
`{"data":{"id":1,"name":"Juan Quispe","email":"...","role":"padre"}}`

## `GET /children`
Solo estudiantes con vínculo activo y permiso de ver.
```json
{"data":[{"id":7,"name":"Ana Paz","course":"6° Primaria","parallel":"A","teacher":"Prof. Vargas",
          "average":70,"attendance_percent":50,"robot_completed":1}]}
```
`average` y `attendance_percent` pueden ser `null` si no hay datos.

## `GET /children/{student}/summary`
403 si el estudiante no es de la familia o el vínculo está inactivo; 404 si no existe.
```json
{"data":{"id":7,"name":"Ana Paz","course":"6° Primaria","parallel":"A","teacher":"Prof. Vargas",
 "report":{"average":80,"activity_average":80,"exam_average":null,"robot_average":null,
  "activity_count":2,"exam_count":0,"robot_count":0,
  "quarter_averages":[{"quarter":1,"average":90},{"quarter":2,"average":70},{"quarter":3,"average":null}],
  "subjects":[{"name":"Quechua","average":80,"count":2}],
  "attendance_percent":50,"attendance_present":1,"attendance_total":2,
  "robot_total":0,"robot_completed":0}}}
```
Solo cuentan las notas con `status = completed`. Los números salen de `App\Services\StudentReport`, el mismo servicio del panel web.

---

# Ampliación v1.1 — profesor y administrador

Misma base `/api/v1/app` y mismo `Authorization: Bearer`. La app elige su interfaz según `user.role` del login.
Rol no permitido en una ruta: **403** `{"message": "..."}`. Validación: **422** `{"message":"...","errors":{"campo":["..."]}}`.
Tokens: familia 30 días, **administrador 7 días**, robot sin vencimiento.

**Listas paginadas:** query `page` (1..) y `per_page` (20 por defecto, máx. 50). Respuesta:
`{"data":[...],"meta":{"page":1,"last_page":3,"total":55}}`

## Estudiantes (administrador y profesor, solo lectura)
- `GET /students?q=` — el administrador ve todos; el profesor solo los suyos. `q` busca por nombre.
  Item: `{"id","name","course","parallel","teacher","average","attendance_percent","robot_completed"}` (igual que `/children`).
- `GET /students/{id}/summary` — mismo JSON que `/children/{id}/summary`. Profesor: 403 si no es su estudiante.

## Actividades del robot (solo profesor, solo lectura)
- `GET /robot-activities?status=` — `status`: `pending|in_progress|completed|partial|failed|cancelled`. Solo las suyas.
  Item: `{"id","status","category","quarter","activity_number","parallel","subject","attempts","groups","created_at","completed_at"}`

## Administrador (solo rol `admin`; cualquier otro rol: 403)
- `GET /admin/overview` →
  `{"data":{"students":n,"teachers":n,"families":n,"users_active":n,"users_suspended":n,"recent_students":[{"id","name","course","parallel"}]}}`
- `GET /admin/users?q=&role=&status=active|inactive&trashed=1` — `q` busca en nombre y correo; `trashed=1` lista los eliminados.
  Item: `{"id","name","first_name","last_name","second_last_name","email","role","status","telefono","main_course_id","deleted_at","is_self"}`
  (`is_self` = es la cuenta que consulta; la app oculta las acciones sobre ella).
- `GET /admin/users/{id}` → `{"data": item}`
- `POST /admin/users` → 201 `{"data": item}`. Campos: `first_name`, `last_name`, `second_last_name`, `email`, `password`, `rol`, `telefono?`, `main_course_id?`.
  `rol` ∈ `padre,madre,tutor,profesor,admin,estudiante,academic`. Contraseña: mínimo 8, con mayúscula, minúscula, número y símbolo.
- `PUT /admin/users/{id}` → `{"data": item}`. Mismos campos; `password` opcional. Cambiar `rol` o `password` cierra las sesiones de esa persona.
- `PATCH /admin/users/{id}/status` → `{"data": item}` (suspende o reactiva). Sobre uno mismo: 422.
- `DELETE /admin/users/{id}` → 204 (borrado recuperable). Sobre uno mismo: 422.
- `POST /admin/users/{id}/restore` → `{"data": item}`

Toda acción de escritura del administrador queda en la auditoría (`audit_logs`).
El borrado definitivo, la foto de perfil, cursos y paralelos se hacen solo en la web.
