# Casos de Prueba de Validación - Plataforma YURA

## 1. Análisis de Campos por Módulo

### 1.1 Usuarios
* **name**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 255
  * Formato: `/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u` (letras y espacios simples)
  * Valores permitidos/no permitidos: Permite letras, no permite múltiples espacios seguidos, números ni caracteres especiales.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **email**
  * Obligatorio: Sí
  * Tipo de dato: String (Formato email)
  * Longitud: Mínima: requiere verificacion, Máxima: 255
  * Formato: Formato de correo electrónico estándar
  * Valores permitidos/no permitidos: Solo correos válidos
  * Duplicidad: Único en la tabla `users`
  * Relaciones: Ninguna
  * Reglas de negocio: No se puede repetir entre usuarios.
* **password**
  * Obligatorio: Sí (al crear) / Opcional (al actualizar)
  * Tipo de dato: String
  * Longitud: Mínima: 8, Máxima: requiere verificacion
  * Formato: Al menos una letra mayúscula, una letra minúscula, un número y un símbolo.
  * Valores permitidos/no permitidos: Todos los caracteres que cumplan la regla.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **rol**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: requiere verificacion
  * Formato: requiere verificacion
  * Valores permitidos: `padre`, `madre`, `tutor`, `profesor`, `admin`, `estudiante` (o `teacher` internamente)
  * Duplicidad: Permite duplicados
  * Relaciones: Condiciona si otros campos son obligatorios.
  * Reglas de negocio: Define el tipo de usuario y permisos en el sistema.
* **telefono**
  * Obligatorio: Opcional (obligatorio para profesores)
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 20
  * Formato: `/^[0-9+() -]+$/`
  * Valores permitidos/no permitidos: Solo números, espacios, +, guiones y paréntesis. No letras.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **main_subject_id**
  * Obligatorio: Sí (solo si el rol es profesor)
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `subjects`
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `subjects`
  * Reglas de negocio: Un profesor debe tener una asignatura principal.
* **main_course_id**
  * Obligatorio: Opcional
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `courses`
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `courses`
  * Reglas de negocio: Solo se asigna si el rol es profesor, si no se envía como `null`.
* **foto**
  * Obligatorio: Opcional
  * Tipo de dato: File (Imagen)
  * Longitud: Máxima: 2048 KB
  * Formato: Mimes permitidos: `jpg`, `jpeg`, `png`, `webp`
  * Valores permitidos/no permitidos: Archivos de imagen en los formatos indicados.
  * Duplicidad: N/A
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion

### 1.2 Estudiantes
* **name**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 255
  * Formato: `/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/`
  * Valores permitidos/no permitidos: Letras y espacios. No números ni símbolos.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **age**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0, Máxima: 120
  * Formato: Numérico entero
  * Valores permitidos/no permitidos: Solo valores entre 0 y 120.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **gender**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: requiere verificacion
  * Formato: requiere verificacion
  * Valores permitidos: `Masculino`, `Femenino`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **level**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: requiere verificacion
  * Formato: requiere verificacion
  * Valores permitidos: `básico`, `intermedio`, `avanzado`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **registration_date**
  * Obligatorio: Sí (al crear) / Opcional (al actualizar)
  * Tipo de dato: Date
  * Longitud: requiere verificacion
  * Formato: Fecha
  * Valores permitidos/no permitidos: Antes o igual a "today" (hoy).
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: No permite fechas futuras.
* **status**
  * Obligatorio: Sí (al actualizar)
  * Tipo de dato: String
  * Longitud: requiere verificacion
  * Formato: requiere verificacion
  * Valores permitidos: `activo`, `inactivo`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **course_id**
  * Obligatorio: Opcional (pero se valida en conjunto con el paralelo)
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `courses`
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `courses`
  * Reglas de negocio: El `course_id` debe coincidir con el `course_id` del paralelo seleccionado.
* **parallel_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `parallels`
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `parallels`
  * Reglas de negocio: El paralelo no debe haber alcanzado su capacidad máxima de estudiantes (`parallel->max_students`).
* **parent_user_id (Responsable principal)**
  * Obligatorio: Opcional
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `users` con rol `padre`, `madre` o `tutor` y estado `activo` (o `active`).
  * Duplicidad: Permite duplicados (varios estudiantes pueden tener el mismo responsable)
  * Relaciones: Relación en tabla pivote `student_family_members`.
  * Reglas de negocio: El usuario debe tener el rol adecuado y estar activo.
* **secondary_parent_user_id (Responsable secundario)**
  * Obligatorio: Opcional
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `users` con rol `padre`, `madre` o `tutor` y estado `activo`. NO puede ser igual a `parent_user_id`.
  * Duplicidad: Permite duplicados
  * Relaciones: Relación en tabla pivote `student_family_members`.
  * Reglas de negocio: Debe ser un usuario diferente al responsable principal.
* **teacher_user_id**
  * Obligatorio: Opcional
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0
  * Formato: Numérico
  * Valores permitidos: IDs existentes en la tabla `users` con rol `profesor` o `teacher` y estado `activo`.
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `users`.
  * Reglas de negocio: El profesor debe estar activo.

### 1.3 Cursos
* **name**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 255
  * Formato: requiere verificacion
  * Valores permitidos/no permitidos: Cualquier texto.
  * Duplicidad: Único en la tabla `courses`
  * Relaciones: Ninguna directa.
  * Reglas de negocio: No pueden haber dos cursos con el mismo nombre.
* **nivel_educativo**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: requiere verificacion
  * Formato: requiere verificacion
  * Valores permitidos: `primaria_comunitaria_vocacional`, `secundaria_comunitaria_productiva`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion

### 1.4 Paralelos
* **name**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 50
  * Formato: requiere verificacion
  * Valores permitidos/no permitidos: Cualquier texto válido de hasta 50 caracteres.
  * Duplicidad: Único en la combinación de `course_id` + `name`.
  * Relaciones: Pertenece a un `course_id`.
  * Reglas de negocio: "Ya existe un paralelo con ese nombre en el curso seleccionado."
* **course_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `courses`
  * Duplicidad: Permite duplicados (un curso puede tener varios paralelos)
  * Relaciones: Llave foránea a `courses`
  * Reglas de negocio: requiere verificacion
* **max_students**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: Mínima: 1, Máxima: requiere verificacion
  * Formato: Numérico entero
  * Valores permitidos/no permitidos: Valores numéricos >= 1
  * Duplicidad: Permite duplicados
  * Relaciones: Limita la cantidad de estudiantes asignados a este paralelo.
  * Reglas de negocio: Limita las asignaciones de estudiantes.

### 1.5 Asignaturas
* **name**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 255
  * Formato: `/^[\p{L}\p{N}]+(?:[ \t-]+[\p{L}\p{N}]+)*$/u` (Letras, números, espacios simples y guiones)
  * Valores permitidos/no permitidos: No permite caracteres especiales distintos a guiones.
  * Duplicidad: Único en la tabla `subjects`
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **code**
  * Obligatorio: Opcional
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 30
  * Formato: requiere verificacion
  * Valores permitidos/no permitidos: requiere verificacion
  * Duplicidad: Único en la tabla `subjects` (si se envía)
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **sort_order**
  * Obligatorio: Opcional
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 0, Máxima: 999
  * Formato: Numérico
  * Valores permitidos/no permitidos: Entre 0 y 999.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **active**
  * Obligatorio: Opcional
  * Tipo de dato: Boolean
  * Longitud: requiere verificacion
  * Formato: Booleano
  * Valores permitidos: `true`, `false`, `1`, `0`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion

### 1.6 Asignaciones Docentes
* **teacher_user_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs en `users` donde rol sea `profesor`/`teacher` y estado `activo`.
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `users`
  * Reglas de negocio: El usuario debe ser un profesor activo.
* **subject_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `subjects`
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `subjects`
  * Reglas de negocio: Conjuntamente con `parallel_id`, debe ser único.
* **parallel_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `parallels`
  * Duplicidad: Permite duplicados
  * Relaciones: Llave foránea a `parallels`
  * Reglas de negocio: No se puede asignar más de un profesor a la misma asignatura en un mismo paralelo ("Este paralelo ya tiene una asignación para esa asignatura").

### 1.7 Notas
* **student_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `students`
  * Relaciones: Llave foránea a `students`
  * Reglas de negocio: El profesor debe tener asignado el curso/estudiante.
* **subject_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `subjects`
  * Relaciones: Llave foránea a `subjects`
  * Reglas de negocio: El profesor debe estar asignado a esta materia para el estudiante.
* **score**
  * Obligatorio: Sí
  * Tipo de dato: Numeric / Decimal
  * Rango (Longitud): Mínima: 0, Máxima: 100 (5 si es autoevaluación)
  * Formato: Numérico
  * Valores permitidos/no permitidos: Valores entre 0 y 100.
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: Si el `type` es `autoeval`, el valor máximo permitido es 5. De lo contrario, máximo 100.
* **type**
  * Obligatorio: Sí (en cuaderno pedagógico), Opcional (en actualización individual)
  * Tipo de dato: String
  * Longitud: Mínima: requiere verificacion, Máxima: 50
  * Formato: requiere verificacion
  * Valores permitidos: `activity`, `exam`, `robot`, `autoeval`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: Condiciona el puntaje máximo (autoeval max 5).
* **activity_number**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Rango (Longitud): Mínima: 1, Máxima: 5
  * Formato: Numérico
  * Valores permitidos: 1 a 5
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion
* **quarter**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: `1`, `2`, `3`
  * Duplicidad: Permite duplicados
  * Relaciones: Ninguna
  * Reglas de negocio: requiere verificacion

### 1.8 Asistencia
* **student_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `students`
  * Reglas de negocio: El profesor debe tener asignado el curso/estudiante.
* **subject_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Longitud: requiere verificacion
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `subjects`
  * Reglas de negocio: El profesor debe estar asignado a esta materia.
* **status (attendance / attendance.*)**
  * Obligatorio: Sí / Opcional (dependiendo del endpoint)
  * Tipo de dato: String
  * Longitud: requiere verificacion
  * Formato: requiere verificacion
  * Valores permitidos: `Presente`, `Falta`, `Atraso` (en algunos endpoints no está la validación estricta y *requiere verificacion*)
  * Reglas de negocio: requiere verificacion
* **date**
  * Obligatorio: Sí (en la actualización AJAX)
  * Tipo de dato: Date
  * Longitud: requiere verificacion
  * Formato: Fecha válida
  * Reglas de negocio: requiere verificacion

### 1.9 Familiares (Relación Estudiante - Familiar)
* **student_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `students`
* **user_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Formato: Numérico
  * Valores permitidos: IDs existentes en `users` donde rol sea `padre`, `madre` o `tutor` y estado activo.
* **relation_type**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Valores permitidos: `padre`, `madre`, `tutor`
* **is_primary**
  * Obligatorio: Opcional
  * Tipo de dato: Boolean
  * Reglas de negocio: Si es `true`, los demás responsables de ese estudiante pasan a `false` de forma automática.

### 1.10 Actividades Robot
* **parallel_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Valores permitidos: IDs existentes en `parallels`
  * Reglas de negocio: El paralelo debe tener estudiantes asignados. El profesor debe tener una asignación activa en este paralelo y asignatura.
* **subject_id**
  * Obligatorio: Sí
  * Tipo de dato: Integer
  * Valores permitidos: IDs existentes en `subjects`
* **category**
  * Obligatorio: Sí
  * Tipo de dato: String
  * Valores permitidos: `Alimentos`, `Colores`, `Elementos de Casa` (o `Casa`), `Elementos Naturales` (o `Naturaleza`), `Familia`, `Herramientas`, `Lugares`. *(Nota: la nomenclatura varía ligeramente entre métodos, requiere verificacion).*
* **description**
  * Obligatorio: Opcional
  * Tipo de dato: String
  * Longitud: Máxima: 500

---

## 2. Generación de Casos de Prueba

### 2.1 Pruebas Positivas
| ID | Entidad | Campo | Prueba (Valor/Condición) | Resultado Esperado |
|---|---|---|---|---|
| P-01 | Usuarios | name | "Juan Pérez" | Registro exitoso. |
| P-02 | Usuarios | email | "admin@yura.com" | Registro exitoso (si no existe). |
| P-03 | Usuarios | password | "Secreto123!" | Contraseña aceptada y usuario creado. |
| P-04 | Usuarios | telefono | "+591 712-3456" | Guardado correctamente. |
| P-05 | Estudiantes | age | "0" (Límite inferior) | Registro exitoso. |
| P-06 | Estudiantes | age | "120" (Límite superior) | Registro exitoso. |
| P-07 | Estudiantes | parallel_id | Asignar paralelo con capacidad 10 y 9 inscritos. | Registro exitoso. |
| P-08 | Estudiantes | secondary_parent_user_id | Un padre diferente al responsable principal. | Creado con ambos tutores. |
| P-09 | Cursos | nivel_educativo | "primaria_comunitaria_vocacional" | Curso creado. |
| P-10 | Asignaturas | name | "Matemáticas 1" | Guardado correctamente. |
| P-11 | Asignaturas | sort_order | 0 (Límite inferior) | Guardado correctamente. |
| P-12 | Asignaturas | sort_order | 999 (Límite superior) | Guardado correctamente. |
| P-13 | Asignaciones | parallel_id / subject_id | Asignación docente nueva para un paralelo/materia sin asignar. | Asignación exitosa. |
| P-14 | Notas | score | "0" (Límite inferior) | Nota guardada. |
| P-15 | Notas | score | "100" (Límite superior SER/SABER/HACER) | Nota guardada. |
| P-16 | Notas | score | "5" (Límite superior autoeval) | Nota guardada. |
| P-17 | Asistencia | status | "Atraso" | Registro actualizado/creado exitosamente. |
| P-18 | Familiares | is_primary | `true` en un responsable secundario | Este responsable pasa a ser primario y el otro a secundario. |
| P-19 | Robot | parallel_id | Paralelo con al menos 1 estudiante y profesor asignado. | Actividad de robot creada en estado "pending". |

### 2.2 Pruebas Negativas
| ID | Entidad | Campo | Prueba (Valor/Condición) | Resultado Esperado |
|---|---|---|---|---|
| N-01 | Usuarios | name | "" (Vacío) | Error: Campo obligatorio. |
| N-02 | Usuarios | name | "Juan123" (Número) | Error: Formato inválido (`regex`). |
| N-03 | Usuarios | name | "Juan@#!" (Caracteres especiales) | Error: Formato inválido. |
| N-04 | Usuarios | email | "juan.com" (Formato incorrecto) | Error: Debe ser un correo electrónico válido. |
| N-05 | Usuarios | email | Correo ya existente | Error: El valor ya ha sido registrado (`unique`). |
| N-06 | Usuarios | password | "secreto123" (Falta mayúscula y símbolo) | Error: Contraseña no cumple reglas de seguridad. |
| N-07 | Usuarios | telefono | "abc" (Letras) | Error: Formato inválido. |
| N-08 | Usuarios | main_subject_id | Vacío cuando el rol es `profesor` | Error: El campo es obligatorio para profesores. |
| N-09 | Estudiantes | age | "-1" (Fuera de rango) | Error: Valor mínimo es 0. |
| N-10 | Estudiantes | age | "121" (Fuera de rango) | Error: Valor máximo es 120. |
| N-11 | Estudiantes | registration_date | Fecha del día de mañana | Error: La fecha debe ser anterior o igual a hoy. |
| N-12 | Estudiantes | course_id / parallel_id| Seleccionar paralelo que no pertenece al `course_id` | Error: "El paralelo seleccionado no pertenece al curso indicado." |
| N-13 | Estudiantes | parallel_id | Paralelo en su límite máximo de capacidad | Error: "Este paralelo ha alcanzado su capacidad máxima de estudiantes". |
| N-14 | Estudiantes | parent_user_id | ID de usuario con rol "admin" | Error: El usuario debe tener rol de padre, madre o tutor. |
| N-15 | Estudiantes | secondary_parent_user_id | Igual que `parent_user_id` | Error: Debe ser diferente al responsable principal. |
| N-16 | Estudiantes | score | "101" (Fuera de rango) | Error: No puede superar 100. |
| N-17 | Paralelos | name | Nombre duplicado en el mismo curso | Error: "Ya existe un paralelo con ese nombre en el curso seleccionado." |
| N-18 | Asignaturas | name | "Matemáticas@*" | Error: Formato inválido. |
| N-19 | Asignaturas | sort_order | "-1" (Fuera de rango) | Error: Mínimo 0. |
| N-20 | Asignaturas | sort_order | "1000" (Fuera de rango) | Error: Máximo 999. |
| N-21 | Asignaciones | parallel_id / subject_id | Duplicar una combinación de profesor para un paralelo y materia que ya existe | Error: "Este paralelo ya tiene una asignación para esa asignatura." |
| N-22 | Notas | score | "-1" (Fuera de rango inferior) | Error: Mínimo permitido es 0. |
| N-23 | Notas | score | "101" (Fuera de rango superior) | Error: Máximo permitido es 100. |
| N-24 | Notas | score | "6" en type `autoeval` | Error JSON (422): "La nota no puede superar 5 puntos para este campo." |
| N-25 | Notas | type | "unknown_type" | Error: El valor seleccionado es inválido (debe ser activity, exam, robot, autoeval). |
| N-26 | Asistencia | status | "Ausente" (Combinación inválida / No permitida) | Error: El valor seleccionado es inválido (permitidos: Presente, Falta, Atraso). |
| N-27 | Robot | parallel_id | Paralelo sin estudiantes registrados | Error: "No se pudo crear la actividad porque el paralelo no tiene estudiantes registrados." |
| N-28 | Robot | category | "Deportes" (No permitida) | Error: Categoría inválida. |

## 3. Ejecución real — puntos "especialmente" solicitados (notas/asistencia en límites, capacidad de paralelos, tutor principal/secundario, relación curso-paralelo, asignaciones duplicadas)

Ejecutado el 2026-09-21 con peticiones HTTP reales (`curl`) contra `php artisan serve --port=8020` y la base de datos MySQL real (`db_yura_platform`), con sesiones reales de admin y profesor. Todos los datos de prueba creados (notas, asistencia, capacidad temporal de paralelo) fueron revertidos al finalizar.

| Punto | Prueba real ejecutada | Resultado real | Estado |
|---|---|---|---|
| Notas — límite superior válido | `POST /grades/save` type=activity, score=100 | `{"success":true,"grade_id":29}` → 200 | ✅ Aprobado |
| Notas — fuera de rango superior | `POST /grades/save` type=activity, score=100.01 | `{"success":false,"message":"La nota no puede superar 100 puntos para este campo."}` → 422 | ✅ Aprobado |
| Notas — fuera de rango inferior | `POST /grades/save` type=activity, score=-1, con `Accept: application/json` (igual que el frontend real) | `{"message":"The score field must be at least 0.","errors":{"score":[...]}}` → 422. Nota: mensaje en inglés (Laravel por defecto); el resto de la validación de este endpoint usa mensajes de negocio en español — inconsistencia menor de idioma, no funcional. | ✅ Aprobado (con observación menor de idioma) |
| Notas — autoevaluación límite válido | `POST /grades/save` type=autoeval, score=5 | `{"success":true,"grade_id":30}` → 200 | ✅ Aprobado |
| Notas — autoevaluación fuera de rango | `POST /grades/save` type=autoeval, score=5.01 | `{"success":false,"message":"La nota no puede superar 5 puntos para este campo."}` → 422 | ✅ Aprobado |
| **Asistencia — valor de status inventado** | `POST /attendance/save` con `status=ValorInventadoXYZ` | **ANTES:** 200 `{"success":true}`, y el valor `"ValorInventadoXYZ"` quedó grabado literalmente en `attendance.status` en la base de datos real (verificado con consulta SQL directa). **DESPUÉS del fix:** 422 `{"message":"The selected status is invalid.","errors":{"status":[...]}}`; nada se guarda en BD. Reverificado que el camino feliz (`status=Atraso`) sigue funcionando (200, guardado correctamente). | ✅ **Corregido**: se agregó `in:Presente,Falta,Atraso` a la regla de `status` en `GradeController::saveAttendance()` (antes solo `required\|string`), igualándola a `update()`/`batchUpdate()`. Esto también corrige N-26 para este endpoint específico. Verificado con `php artisan test` (29/29 sin regresión) y con prueba HTTP real antes/después. Datos de prueba revertidos. |
| **Capacidad máxima de paralelos** | Paralelo bajado temporalmente a 10/10 (su ocupación real) → `POST /students` con un estudiante adicional | **Primer intento: HTTP 500** (`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'rol'`) — bug real de regresión encontrado y corregido en vivo (ver nota abajo). Tras la corrección, reintento → 302 con mensaje real "Este paralelo ha alcanzado su capacidad máxima de estudiantes"; estudiante NO creado (verificado en BD). | ✅ Aprobado (tras corrección) |
| Tutor principal = tutor secundario | `POST /students` con `parent_user_id=5` y `secondary_parent_user_id=5` (mismo usuario) | 302 (bloqueado por la regla `different:parent_user_id`); 0 filas creadas con ese nombre en `students` (verificado en BD). | ✅ Aprobado |
| Relación curso-paralelo inválida | `POST /students` con `course_id=2` (Primero, sin paralelos) y `parallel_id=1` (pertenece a course_id=1) | 302 con mensaje real "El paralelo seleccionado no pertenece al curso indicado."; 0 filas creadas (verificado en BD). | ✅ Aprobado |
| Asignación docente duplicada | `POST /teaching-assignments` con `subject_id=1`+`parallel_id=1` (ya asignado a otro profesor) | 302 con mensaje real "Este paralelo ya tiene una asignación para esa asignatura."; conteo de `teaching_assignments` se mantuvo en 2 (verificado en BD). | ✅ Aprobado |

### Bug crítico encontrado y corregido durante esta ejecución

Al ejecutar la prueba de capacidad máxima de paralelos, `POST /students` con `parent_user_id` devolvió **HTTP 500** real: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'rol' in 'where clause'`. Causa: las reglas de validación `Rule::exists('users', 'id')->where(...)` en `StudentController::store()`, `StudentController::update()` y `Api\StudentController::store()` todavía hacían `orWhereIn('rol', [...])` (y, en el caso de la API, también `orWhereIn('estado', [...])`) contra columnas que ya no existen en la tabla `users` (eliminadas en migraciones anteriores de esta misma auditoría: `2026_09_17_000004_drop_legacy_rol_column_from_users_table` para `rol`, y la normalización a inglés para `estado`→`status`). Esto significa que **cualquier creación o edición real de un estudiante con responsable o profesor asignado estaba rota (500) en el código previo a esta corrección**, sin que ningún test existente lo detectara. Corregido eliminando las 6 cláusulas muertas (2 archivos) y dejando solo la comparación contra la columna real `role`/`status`; verificado con `php artisan test` (29/29 sin regresión) y con la prueba de capacidad real repetida exitosamente tras el fix.
