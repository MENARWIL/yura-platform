# Reporte Web y Móvil — v1.2

**Proyecto:** YURA — plataforma web (`yura-platform`) y app Android (`yuraapp`)
**Fecha:** 18-10-2026 · **Estado:** Fases 1 a 3 ejecutadas; pendientes de prueba en el celular y con la MySQL real.

---

## 1. Resumen
Se ejecutó el plan v1.1 aprobado. La web quedó con reglas compartidas de administración de usuarios, una API por roles y 102 pruebas automáticas en verde (eran 52). La app Android muestra una interfaz distinta según el rol: familia, profesor (solo lectura) y administrador (con edición de usuarios). La app compila y pasa 43 pruebas unitarias. **Todavía no se probó la versión nueva en el celular ni contra la MySQL real.**

## 2. Web (`feature/api-app-v1`)
| Área | Resultado |
|---|---|
| Fallas F1, F3–F6 (reporte v1.0) | Corregidas, con pruebas que fallaban antes del cambio |
| Reglas de usuarios U1–U6 | Corregidas (ver sección 3) |
| API por roles | `students`, `robot-activities` (profesor, solo lectura) y `admin/*` (solo administrador) |
| Contrato | `docs/api-app-v1.md` con la ampliación v1.1 |
| Pruebas | 102 pruebas, 606 aserciones, todas en verde |
| Revisión externa | Codex revisó dos veces; sus hallazgos de seguridad se corrigieron (sección 4) |

## 3. Reglas de usuarios (web y API comparten `UserAdminService`)
| # | Regla |
|---|---|
| U1 | Nadie puede suspenderse, borrarse ni quitarse el rol de administrador a sí mismo |
| U2 | La API exige las mismas contraseñas fuertes que la web |
| U3 | La lista de usuarios no expone documento de identidad, foto ni tokens |
| U4 | Cada acción de administración queda en la auditoría (quién, cuándo, a quién, qué cambió) |
| U5 | Al suspender, borrar o cambiar el rol o la contraseña de alguien se cierran sus sesiones |
| U6 | El rol `academic` se puede asignar desde el formulario |
| Nueva | Siempre debe quedar al menos un administrador activo; cada operación es atómica (transacción) |
| Nueva | Un usuario eliminado no se edita ni suspende hasta restaurarlo |

Duración de los tokens: familia 30 días, administrador 7 días, robot sin vencimiento.

## 4. Hallazgos de Codex y resolución
| Revisión | Hallazgo | Resolución |
|---|---|---|
| 1 | Responsable principal ignora un vínculo familiar inactivo | Corregido: el vínculo registrado manda, también para el responsable principal |
| 1 | Los tokens vencen para el robot | Corregido: el rol `robot` no vence aunque entre por `/api/login` |
| 1 | `robot_completed` cuenta actividades sin nota | No cambiado: es el criterio que ya usaba el panel web |
| 2 | Dos administradores podían dejarse sin acceso en solicitudes simultáneas | Corregido con bloqueo de filas y comprobación del último administrador |
| 2 | Cambio de rol o contraseña, tokens y auditoría no eran atómicos | Corregido con transacciones; hay una prueba que fuerza el fallo de la auditoría |

## 5. App Android (`yuraapp`)
| Rol | Contenido |
|---|---|
| Familia | Lista de hijos y detalle (sin cambios funcionales) |
| Profesor (solo lectura) | Sus estudiantes con buscador y detalle; sus actividades del robot con filtro por estado. Letra y botones grandes, sin formularios |
| Administrador | Panel; usuarios (buscar, filtrar, ver eliminados, crear, editar, suspender o reactivar, eliminar, restaurar, con confirmación); estudiantes (lista y detalle); bloqueo con huella o PIN al reabrir la app |
| Otros roles | Aviso «Tu cuenta no tiene acceso a esta aplicación» |

- Diseño con la identidad de la web (paleta, franja tricolor, fondo del login).
- 43 pruebas unitarias en verde; compilación limpia.
- El trabajo de las pantallas lo hizo un subagente; el resultado se verificó compilando y ejecutando las pruebas desde cero.

## 6. Verificado y no verificado
| Verificado | No verificado |
|---|---|
| Suite de la web (102) y de la app (43) | La app nueva en un celular real (el celular se desconectó antes de instalarla) |
| Flujo HTTP de extremo a extremo con una base temporal: ingreso de administrador y de profesor, panel, creación con contraseña débil y fuerte, 403 del profesor en rutas de administrador | Todo contra la MySQL real (el `.env` sigue en `sqlite`) |
| Mensajes de validación en español | Huella y PIN en el celular; menú de filtros |
| | Pantallas pequeñas y tabletas |

## 7. Pendientes
1. Reconectar el celular, instalar y probar cada rol (usuarios de la base temporal: administrador, profesor y familia).
2. Probar con la MySQL real: el dueño pone `DB_CONNECTION=mysql` en el `.env`; comprobar las tablas del robot.
3. Unir `feature/api-app-v1` a `develop` (no se ha subido nada a GitHub).
4. Edición básica de estudiantes por el administrador (Fase 4): aún no hecha.
5. Cursos: la app no edita el curso principal del profesor (no hay endpoint de cursos).
6. Play Store: servidor público con HTTPS, política de privacidad, firma y cuenta de desarrollador.
7. Fuente Outfit y logo e ícono de YURA en la app.
