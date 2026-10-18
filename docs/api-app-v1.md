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
