# Plan final: web y app YURA — v1.1

**Proyecto:** YURA — plataforma web (`yura-platform`) y app Android (`yuraapp`)
**Fecha:** 18-10-2026 · **Estado:** pendiente de aprobación del dueño del proyecto. No se ha empezado ninguna fase nueva.

---

## 0. Punto de partida (ya hecho)
- **Web**, rama `feature/api-app-v1`: 6 fallas de seguridad corregidas, API `v1/app` para familias y servicio de resumen compartido con la web. La suite pasa con 72 pruebas.
- **App** (`yuraapp`): login y pantallas de familia con el diseño de la web. 9 pruebas unitarias; probada en un celular real.
- **Pendiente de instalar:** el arreglo del teclado del login (compila, aún no está en el celular).
- **Sin hacer:** unir la rama a `develop` y probar con la base MySQL real.

## 1. Cierre de lo anterior
1. Instalar y probar el arreglo del teclado.
2. **Prueba con la MySQL real:** el dueño pone `DB_CONNECTION=mysql` en el `.env` (el archivo no se lee). Se comprueban las tablas del robot y se prueba con un padre real.
3. Unir `feature/api-app-v1` a `develop` cuando la prueba pase. No se sube nada a GitHub sin orden expresa.

## 2. Web: reglas compartidas de usuarios (Fase 1)
Primero se escriben pruebas que fallan y luego se corrigen:

| # | Corrección |
|---|---|
| U1 | Nadie puede suspenderse, borrarse ni quitarse el rol de administrador a sí mismo, ni dejar el sistema sin administrador activo |
| U2 | La API crea usuarios con las mismas reglas que la web (contraseña fuerte, tres campos de nombre) |
| U3 | El listado de usuarios se pagina, se busca por nombre, correo y rol, y devuelve solo los campos necesarios |
| U4 | Auditoría real: cada acción de administración queda registrada (quién, cuándo, a quién, qué cambió) |
| U5 | Al suspender, borrar o cambiar el rol o la contraseña de alguien, se cierran sus sesiones |
| U6 | El formulario de usuarios permite asignar el rol `academic` |

Todo vive en un solo servicio (`UserAdminService`) que usan la web y la API. Las pantallas actuales de la web no cambian; solo cambian las reglas.

## 3. Web: API por roles (Fase 2)
| Rol | Rutas `v1/app` | Permisos |
|---|---|---|
| Administrador (director) | `admin/users` (listar, crear, editar, suspender o reactivar, borrar y restaurar); `admin/students` (listar, ver y editar datos básicos) | Editar |
| Profesor | `teacher/students` (solo los suyos), resumen de cada estudiante, estado de las actividades del robot | Solo lectura: cualquier ruta de edición responde 403 |
| Familia | `me`, `children`, `children/{id}/summary` (ya existen) | Solo lectura |

- Pruebas por rol contra cada ruta, incluido que un profesor no vea estudiantes de otro profesor.
- **Tokens:** familia 30 días, administrador 7 días, robot sin vencimiento.
- Solo en la web: borrado definitivo, foto de perfil, cursos, paralelos y exportaciones.

## 4. App: mejoras y pantallas (Fases 3 y 4)
La app muestra pantallas distintas según el rol. El código de edición solo existe para administradores.

| Área | Contenido |
|---|---|
| Administrador | Panel con totales y recientes; usuarios (lista con búsqueda y filtro por rol, crear, editar, suspender o reactivar, borrar y restaurar, todo con confirmación); estudiantes (buscar, ver y editar datos básicos) |
| Profesor | Lista de sus estudiantes y detalle (promedio, trimestres, materias, asistencia, retos de Yura) y estado de las actividades del robot. Sin ningún botón de guardar |
| Familia | Lo actual, más actualizar al deslizar la lista |

**Mejoras para todos los roles:**
- Letra grande, buen contraste y botones grandes, pensando en profesores mayores.
- Pantalla de carga al abrir (hoy aparece vacía un instante).
- Huella o PIN al abrir la app para el administrador.
- Mensajes de error claros en español: sin conexión, sesión vencida, sin permiso.
- Fuente Outfit como en la web, y logo e ícono de YURA.
- Actualizar al deslizar y reintentar cuando falla.

**No incluido por ahora:** trabajo sin conexión (caché), notificaciones y exportar PDF.

## 5. Calidad (siempre)
- Pruebas antes de cada cambio; la suite completa (hoy 72) debe quedar en verde.
- Pruebas en la app para cada pantalla y para los casos de error.
- Revisión externa: Codex revisa la seguridad de las rutas de administrador antes de cerrar la fase.
- Commits por fase, sin tocar `develop`.
- `php artisan migrate` en la MySQL real solo con orden aparte. No se leen claves ni el `.env`. No se automatizan toques en el celular: solo se mira la pantalla.

## 6. Cierre (Fase 5)
- Reporte v1.2 en PDF, con lo hecho, lo que falló y lo que queda.
- Contrato de la API de administrador y de profesor, junto al de familia (`docs/api-app-v1.md`).
- Lista de requisitos para Play Store: dominio con HTTPS, política de privacidad, firma y cuenta de desarrollador.

## 7. Tiempos (aproximados)
| Fase | Esfuerzo |
|---|---|
| 1. Reglas compartidas de usuarios | 2–3 h |
| 2. API por roles y pruebas | 3–4 h |
| 3. App: profesor y administrador | 4–6 h |
| 4. Estudiantes del administrador | 2–3 h |
| 5. Pruebas, revisión de Codex y reporte | 2 h |

Son varias sesiones de trabajo. Cada fase termina con las pruebas en verde y se avisa antes de la siguiente.

## 8. Riesgos y límites
- Las pruebas con la MySQL real solo se pueden hacer si el dueño configura el `.env`.
- Solo se ha probado en un celular; no en pantallas pequeñas ni tabletas.
- La app no está lista para Play Store hasta que haya un servidor público con HTTPS.
- Las reglas nuevas de usuarios cambian un poco la web: un administrador ya no podrá suspenderse a sí mismo.

## 9. Decisiones que se asumen si se aprueba sin cambios
1. Se corrige también la web, con las reglas de la sección 2.
2. Token de administrador de 7 días, con huella o PIN para abrir la app.
3. Solo `admin` administra; `academic` queda fuera.
4. Los profesores son solo lectura.

## 10. Aprobación
| Decisión | Respuesta |
|---|---|
| ¿Aprobado el plan tal como está? | ☐ Sí ☐ Con cambios: ______________ |
| Fecha y firma | |
