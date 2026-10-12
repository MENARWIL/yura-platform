# Manual de sincronización: Yura Cerebro y Laravel

> **Aviso:** este documento conserva instrucciones históricas del contrato
> v1.1 y ha sido sustituido para despliegue/configuración por
> [el manual de demo Oracle Cloud y Raspberry Pi](./manual-demo-yura-oracle-raspberry.md)
> y por el [contrato vigente v1.2](./yura-activity-contract-v1.md). No usar sus
> pasos antiguos de activación como guía para una instalación nueva.

Este manual describe cómo preparar, activar y probar el flujo entre el módulo
**Actividades Yura** del docente y el cerebro Python de Yura que se ejecuta en
la Raspberry Pi.

## 1. Arquitectura y responsabilidad de cada proyecto

- **Laravel** es la fuente oficial de las actividades, los grupos, los
  participantes y las calificaciones.
- El docente crea la actividad desde **Actividades Yura**, eligiendo contenido,
  participantes y destino académico (trimestre y casilla HACER).
- El cerebro consulta Laravel cuando está disponible, recibe la actividad y
  utiliza la dinámica de voz y evaluación del robot.
- El cerebro conserva localmente los resultados pendientes en un outbox SQLite
  hasta que Laravel confirme su recepción.
- Laravel valida el intento recibido y registra la nota grupal en la casilla
  HACER correspondiente para cada integrante del grupo.
- El cerebro no debe guardar calificaciones académicas paralelas.

El contrato entre ambos proyectos es la versión **1.1**. Cada ejecución se
identifica con `activity_id` y `attempt_id`; un reintento de una actividad usa
un `attempt_id` distinto para que una respuesta atrasada no reemplace el
resultado actual.

## 2. Mantener el cerebro como proyecto separado

La instalación activa del cerebro está en la i3. La carpeta `cerebro_yura`
dentro de este workspace Laravel es una copia de referencia para revisar y
preparar cambios; no es la instalación que ejecuta el robot.

Conviene mantener los dos proyectos separados al publicarlos:

1. El repositorio de **Laravel** contiene la aplicación web, API, migraciones,
   contrato y este manual.
2. El repositorio del **cerebro** contiene el código Python, interfaz local,
   configuración de audio y pruebas del robot.
3. La Raspberry Pi clona el repositorio propio del cerebro; no necesita clonar
   Laravel dentro de la carpeta del robot.

La copia de referencia que está dentro de este workspace tiene un repositorio
Git anidado, commits locales y cambios sin confirmar de voz, entrenamiento,
interfaz y arranque. Si se quiere reducir el repositorio Laravel antes de
publicarlo, se puede excluir o retirar **solo esta copia de referencia** tras
confirmar que los cambios necesarios ya están preservados en el repositorio del
cerebro. Eso no debe borrar ni modificar el proyecto activo de la i3.

No agregues accidentalmente el repositorio anidado como si fuera contenido
normal de Laravel. Configura explícitamente si será una copia ignorada, un
submódulo intencional o si se quitará del workspace Laravel; no uses `git
clean`, `reset` ni comandos de borrado amplios para resolverlo.

Para evitar subir datos locales del robot, el repositorio del cerebro debe
ignorar como mínimo:

- `.env.local` y cualquier archivo con credenciales;
- `.venv/`, cachés de Python y archivos `*.pyc`;
- bases SQLite locales, como `*.db`;
- grabaciones de voz, audio privado y cachés regenerables;
- modelos de IA que no deban distribuirse en Git.

El repositorio Laravel tampoco debe contener tokens, claves de servicio ni
copias de `.env.local`.

## 3. Requisitos antes de conectar

Antes de habilitar el polling, confirmar:

- La API de Laravel está desplegada y accesible desde la Raspberry Pi.
- Existe un usuario de servicio activo con rol `robot` y un token Sanctum válido.
- La autenticación de Sanctum funciona para las rutas de actividades.
- Laravel tiene aplicadas sus migraciones, incluida
  `create_robot_activity_attempts_table`.
- El cerebro de la Raspberry tiene el código que implementa el contrato v1.1,
  la cola de entrada, la ejecución real de la actividad y el outbox por
  `(activity_id, attempt_id)`.
- El usuario que ejecuta `yura.service` puede acceder al micrófono y al
  dispositivo de salida de audio.
- El outbox y la base local del cerebro tienen permisos de escritura para ese
  usuario.

La configuración existente en una i3 no se copia automáticamente a la
Raspberry Pi. La Raspberry requiere su propio `.env.local`, preparado de forma
segura.

## 4. Configuración local de la Raspberry Pi

Trabajar en la carpeta del cerebro de la Raspberry. Crear `.env.local` a partir
de `.env.example` solo si todavía no existe; no sobrescribir una configuración
existente:

```bash
cd "/ruta/al/cerebro_yura"
test -f .env.local || cp .env.example .env.local
chmod 600 .env.local
nano .env.local
```

Completar localmente las variables necesarias:

```dotenv
YURA_LARAVEL_URL=https://HOST_DE_LARAVEL
YURA_LARAVEL_TOKEN=TOKEN_SANCTUM_DE_LA_CUENTA_ROBOT
YURA_LARAVEL_SYNC_ENABLED=0
```

Reglas para la configuración:

- `YURA_LARAVEL_URL` es la raíz accesible de Laravel y no lleva `/api` al final.
- En producción usar HTTPS. HTTP solo es aceptable durante una prueba en una
  red local controlada.
- `YURA_LARAVEL_TOKEN` es el token Sanctum del usuario de servicio con rol
  `robot`. Nunca ponerlo en el código, Git, capturas o logs.
- Mantener `YURA_LARAVEL_SYNC_ENABLED=0` mientras se configura y valida el
  equipo. La bandera debe activarse únicamente para una prueba coordinada.
- No pegar ni compartir el contenido completo de `.env.local`.

El mecanismo de carga debe ejecutarse antes de crear el cliente Laravel. El
servicio debe arrancar desde la carpeta del cerebro para que el cargador
encuentre el `.env.local` correcto. Verificar en el código instalado que
`YURA_LARAVEL_SYNC_ENABLED` tenga el comportamiento esperado antes de activar.

## 5. Cómo activar y detener la sincronización

El siguiente procedimiento es para ejecutar en una terminal de la Raspberry
Pi, usando el usuario que posee el servicio de Yura.

Primero comprobar el nombre y estado del servicio:

```bash
systemctl --user list-units --type=service --all | grep -i yura
```

Confirmar en Laravel que la única actividad pendiente que el robot podría
recibir es la actividad de prueba acordada. El endpoint de cola reclama una
actividad y la cambia a `in_progress`; no usarlo como una prueba inocua.

Para activar, cambiar únicamente la bandera en `.env.local`:

```dotenv
YURA_LARAVEL_SYNC_ENABLED=1
```

Luego reiniciar el servicio del cerebro y confirmar que quedó activo:

```bash
systemctl --user restart yura.service
systemctl --user status yura.service --no-pager
```

Para observar los logs durante la prueba:

```bash
journalctl --user -u yura.service -f
```

Los logs pueden contener información de ejecución. No compartirlos sin revisar
que no contengan secretos, transcripciones ni datos personales.

Para detener el polling, volver a dejar la bandera en `0` y reiniciar el servicio:

```dotenv
YURA_LARAVEL_SYNC_ENABLED=0
```

```bash
systemctl --user restart yura.service
```

Al apagar el polling, los resultados que no se hayan confirmado deben
permanecer en el outbox para una entrega posterior.

## 6. Flujo esperado del contrato v1.1

1. El cerebro reclama una actividad desde:
   `GET /api/v1/robot/activities/next`.
2. Laravel responde `204 No Content` cuando no hay actividades pendientes.
3. Si entrega actividad, incluye `contract_version: "1.1"` y un objeto
   `activity` con `activity_id`, `attempt_id`, `attempt_number`, contenido,
   grupos, participantes, ejecución y puntuación.
4. El robot valida los datos y ejecuta la actividad con su flujo real de voz y
   reconocimiento.
5. El resultado incluye la misma pareja `activity_id` y `attempt_id`, estado,
   tiempos y `group_results`. Un fallo debe incluir `failure_reason` y una lista
   `group_results` vacía.
6. El cerebro escribe el resultado en el outbox local antes de enviarlo a:
   `POST /api/v1/robot/activities/{activity_id}/results`.
7. El cerebro marca el registro como entregado únicamente cuando Laravel
   confirma éxito con HTTP `200` o `201`.
8. Laravel valida grupos, participantes, puntajes e intento; para resultados
   válidos registra las calificaciones de los alumnos correspondientes en la
   casilla HACER seleccionada.

Una actividad `failed` no produce notas. Una actividad `partial` puede producir
notas para los grupos incluidos y validados en `group_results`; verificar el
detalle antes de decidir un reintento.

### Reintentos e idempotencia

- Un reintento debe conservar la misma actividad, participantes y destino
  académico.
- Cada nuevo intento tiene otro `attempt_id` y se registra como intento nuevo.
- El outbox distingue intentos por `(activity_id, attempt_id)`.
- Un resultado de un intento viejo puede ser rechazado con HTTP `409`; no debe
  marcarse entregado como si fuese el resultado nuevo ni cambiar una nota
  existente.
- Errores de red o servidor dejan el resultado pendiente para reintentar.
- Rechazos de cliente como `401`, `403`, `404`, `409` o `422` deben conservar
  el payload para revisión, sin borrarlo ni reintentarlo infinitamente.

## 7. Prueba controlada de extremo a extremo

Realizar esta prueba solo cuando el código del cerebro instalado esté integrado
y sus pruebas locales hayan pasado:

1. En Laravel, dejar intencionalmente pendiente una actividad de prueba y
   verificar que no haya otra actividad anterior que pueda reclamar el robot.
2. Confirmar que los alumnos, asignatura, trimestre y casilla HACER son los
   acordados. La ejecución puede registrar calificaciones reales.
3. Activar `YURA_LARAVEL_SYNC_ENABLED=1` y reiniciar `yura.service`.
4. Verificar en Laravel que la actividad pase de `pending` a `in_progress` y
   que aparezca un intento nuevo.
5. Confirmar que el robot anuncia la actividad y escucha a los participantes.
6. Verificar al final que el intento aparezca como `completed`, `partial` o
   `failed`, y revisar el motivo detallado si falla.
7. Si el resultado es completado o parcial, revisar las calificaciones en el
   cuaderno pedagógico. No crear una actividad duplicada para solucionar un
   error de ejecución.
8. Desactivar la bandera cuando termine la prueba si no se desea polling
   continuo.

No publicar capturas con datos de estudiantes, tokens o URLs privadas.

## 8. Diagnóstico de `no_audio`

El motivo `no_audio` significa que la respuesta final quedó vacía. Por sí solo
no determina si:

- no se capturaron muestras del micrófono;
- terminó el tiempo de espera antes de detectar voz;
- se canceló la escucha;
- se capturó audio, pero el reconocedor no produjo texto.

Antes de reintentar una actividad fallida por `no_audio`:

1. Probar el micrófono localmente con una frase de un adulto, sin guardar el
   audio ni la transcripción.
2. Confirmar que el proceso de Yura selecciona el dispositivo de entrada
   esperado y que el usuario del servicio tiene acceso.
3. Revisar el estado de captura, cancelación, timeout y reconocimiento en logs
   locales, sin compartir voces ni información privada.
4. No cambiar umbrales o tiempos a ciegas. Identificar primero si la falla fue
   de hardware, timeout, cancelación o ASR.
5. Cuando la captura y el reconocimiento estén verificados, reintentar la misma
   actividad desde su detalle en Laravel.

## 9. Comprobaciones útiles en la Raspberry

Estado del servicio:

```bash
systemctl --user status yura.service --no-pager
```

Logs recientes, filtrados por actividad y audio:

```bash
journalctl --user -u yura.service --since "10 minutes ago" --no-pager \
  | grep -Ei 'laravel|actividad|audio|microfono|captur|whisper|vosk|timeout|cancel|error|fallo'
```

No mostrar el entorno del proceso con `env`, `printenv` ni comandos que impriman
el archivo `.env.local`: podrían revelar el token.

## 10. Criterio para declarar la sincronización operativa

La integración solo debe declararse operativa cuando una prueba controlada
confirme todo este recorrido:

- Laravel entrega la actividad docente correcta al cerebro.
- El robot la ejecuta con captura y reconocimiento reales.
- El resultado conserva el `activity_id` y el `attempt_id` reclamados.
- Laravel acepta el resultado y actualiza el estado de la actividad.
- Las calificaciones aparecen en las casillas HACER de los alumnos previstos.

Pruebas unitarias locales, una bandera activa o la existencia de URL/token no
demuestran por sí solas que la conexión real funcione.
