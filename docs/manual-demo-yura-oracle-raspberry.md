# Manual de demo remota Yura: Oracle Cloud y Raspberry Pi

## Objetivo y límites

Este manual prepara una demo remota de Laravel en Oracle Cloud y describe la
conexión posterior del cerebro Yura desde una Raspberry Pi 5. No despliega
nada ni cambia el servicio que actualmente se está probando en la i3.

La arquitectura acordada no necesita MCP ni acceso del cerebro a la base de
datos escolar:

- Laravel conserva actividades, grupos, alumnos, selección de respondientes y
  notas. Envía al cerebro el plan mínimo de la actividad.
- El cerebro recibe el plan, consulta su diccionario SQLite local, ejecuta las
  preguntas, calcula los resultados y los envía a Laravel.
- El cerebro consulta el estado del mismo intento para obedecer cancelaciones.
- El contrato vigente es [Yura Activity Contract v1.2](./yura-activity-contract-v1.md).

Oracle Always Free y Cloudflare Quick Tunnels pueden servir para una demo, pero
no constituyen una garantía de disponibilidad ni convierten la instalación en
un hosting administrado. Revisar los precios y límites vigentes en la consola
de Oracle antes de crear recursos. No añadir recursos de pago ni cambiar la
cuenta a modalidad de pago sin aprobación explícita del dueño.

## Decisiones y requisitos previos

El dueño del proyecto debe:

1. Crear y controlar la cuenta Oracle, el MFA y la facturación. No compartir
   contraseña, códigos MFA ni clave privada SSH con Copilot o pegarlos en un
   chat.
2. Crear una VM Ubuntu ARM64 de la familia A1 si está disponible en la región
   hogar. Empezar con un tamaño pequeño dentro de los límites Always Free que
   muestre la consola. La disponibilidad de capacidad depende de la región.
3. Elegir solo datos anónimos y ficticios para la demo. No cargar expedientes,
   nombres reales, notas reales, audios ni otra información personal de alumnos.
4. Designar quién conserva y revoca la clave de despliegue y el token de Yura.
   Los secretos se comparten directamente entre personas por un canal seguro,
   nunca en prompts, tickets, Git ni registros de terminal compartidos.

## Bloqueo que debe resolverse antes de desplegar este workspace

El árbol de trabajo inspeccionado no es una versión lista para publicar: Git
reporta eliminados `package.json` y `vite.config.js`, además de muchos cambios,
eliminaciones y archivos sin seguimiento ajenos a esta guía. No desplegar ni
publicar el árbol completo actual.

Antes de instalar:

1. Preparar una rama/commit de release revisado que contenga solo los archivos
   aprobados.
2. Confirmar en ese commit que los manifiestos de Composer están presentes y,
   si la aplicación requiere compilar Vite, también `package.json`,
   `package-lock.json` y `vite.config.js` (o artefactos de frontend generados
   y revisados).
3. Confirmar que el diccionario SQLite que usará el cerebro se distribuye por
   separado y que su ruta final está definida. No agregarlo al repositorio web
   si no es necesario.
4. Revisar `git status --short`, `git diff --check` y las pruebas focalizadas de
   actividades antes de publicar. No incluir `.env`, tokens, claves, bases de
   datos con información personal, logs ni audios.

Si cualquiera de estos puntos no se puede verificar, detener el despliegue y
pedir al dueño que prepare el release. No restaurar ni borrar cambios locales
para “limpiar” el árbol.

## Fase 1: aprovisionar la VM de Oracle

1. En OCI, elegir la región hogar y crear una VM Ubuntu Server ARM64/Ampere A1
   dentro de las cuotas Always Free visibles en la cuenta. No seleccionar una
   forma de pago por falta de capacidad sin aprobación.
2. Descargar y guardar la clave SSH privada únicamente en el equipo del dueño.
   Crear acceso con clave pública; deshabilitar el acceso SSH por contraseña.
3. Restringir el ingreso SSH (puerto 22) a las IP de administración conocidas.
   Con el túnel rápido de la fase 3 no hace falta abrir 80/443 en el firewall
   de OCI: `cloudflared` establece conexiones salientes. No abrir MySQL ni
   SQLite a Internet.
4. Configurar alertas de presupuesto/gasto en Oracle y revisar periódicamente
   la página de costos. Una alerta no es un límite de gasto. Confirmar en la
   consola si una IP pública u otro recurso elegido incurre en cargos.
5. Mantener actualizaciones de seguridad del sistema. Guardar copias de
   respaldo de la base SQLite y de los archivos necesarios fuera de la VM, y
   probar que pueden restaurarse. El almacenamiento local de la VM no es un
   respaldo.

## Fase 2: instalar Laravel para una demo

Ejecutar esta fase solo desde un release revisado y fijado a un commit, no desde
una rama que cambie mientras se instala.

1. Instalar Nginx, PHP-FPM y las extensiones que exijan `composer.json` y
   `composer.lock`, Composer y SQLite. Instalar Node/npm solo si el release
   incluye manifiestos frontend válidos y necesita compilar assets.
2. Clonar el repositorio Laravel en `/var/www/yura` y verificar que el commit
   coincide con el release aprobado.
3. Instalar dependencias de producción:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   ```

   Ejecutar `npm ci` y `npm run build` solo si los archivos de lock/manifiesto
   requeridos están presentes. Si no, detenerse; no improvisar manifiestos ni
   copiar assets de una versión distinta.
4. Configurar Nginx para servir únicamente el directorio `public/`. Ejecutar
   PHP-FPM con un usuario sin privilegios y conceder escritura solo a los
   directorios Laravel necesarios (`storage/` y `bootstrap/cache/`).
5. Crear una base SQLite en almacenamiento persistente de la VM, por ejemplo
   `/var/lib/yura/database.sqlite`, con propietario/permisos limitados al
   servicio web. No ubicarla bajo `public/`.
6. Crear `.env` en el servidor, fuera de Git, con configuración equivalente a:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://URL_ACTUAL_DE_LA_DEMO
   DB_CONNECTION=sqlite
   DB_DATABASE=/var/lib/yura/database.sqlite
   SESSION_SECURE_COOKIE=true
   ```

   Completar también una `APP_KEY` nueva, correo/sesiones/logs y cualquier otro
   valor requerido por la versión desplegada. No copiar `.env.example` sin
   revisar sus valores: es una plantilla de desarrollo y puede activar
   depuración. Aplicar permisos restrictivos al `.env`.
7. Revisar migraciones y respaldar antes de ejecutarlas. En una base nueva,
   ejecutar únicamente `php artisan migrate --force`; no ejecutar seeders de
   forma general.
8. **No ejecutar `php artisan db:seed` ni `RobotUserSeeder` ni
   `SchoolPilotSeeder` en el hosting.** En este repositorio se observaron
   credenciales de demo conocidas (`password123` y `robot-password-123`), y el
   seeder de robot elimina tokens existentes e imprime el token nuevo en la
   consola. Esos seeders no son un mecanismo seguro de bootstrap.
9. Crear la cuenta administradora y la cuenta de servicio `role=robot` mediante
   un procedimiento puntual, revisado contra el modelo y el esquema de este
   release. Generar credenciales aleatorias; crear el token Sanctum en una
   sesión privada del dueño y guardarlo directamente en un gestor de secretos
   y en la Pi. No imprimirlo en logs, CI, capturas, chat o historial compartido.
   Si no existe un procedimiento seguro compatible con el release, detenerse
   y solicitarlo; no editar seeders en el servidor.
10. Crear solo cuentas, cursos y alumnos ficticios para la demo desde el
    procedimiento administrativo aprobado. Antes de permitir el acceso web,
    comprobar que login requiere autenticación y que las rutas privadas/API
    rechazan visitantes no autenticados.

## Fase 3: publicar HTTPS temporal para la demo

La opción gratuita sin comprar dominio es un **Cloudflare Quick Tunnel**.
Cloudflare lo describe como temporal y para pruebas/desarrollo: no necesita
dominio ni cuenta, pero el hostname cambia cada vez que se crea el túnel, deja
de funcionar cuando se detiene el proceso y no tiene garantía de disponibilidad.
No usarlo como URL definitiva.

1. Instalar `cloudflared` siguiendo la documentación oficial de Cloudflare.
2. Configurar Nginx para escuchar solo en `127.0.0.1:8080`.
3. Probar el servicio en la VM y después iniciar el túnel:

   ```bash
   cloudflared tunnel --url http://localhost:8080
   ```

4. Copiar la URL `https://....trycloudflare.com` que informa `cloudflared`.
   Abrirla desde otra red y probar la página de login y los assets.
5. Para la demo, ejecutar el túnel bajo un servicio supervisado y sin privilegios;
   conservar la URL del log del servicio. Cada recreación del Quick Tunnel puede
   dar otra URL: si cambia, actualizar `APP_URL`, volver a limpiar/generar la
   configuración cacheada de Laravel y actualizar la URL en la Pi antes de
   reactivar su sincronización.
6. Verificar que Laravel reconoce correctamente HTTPS detrás del proxy: los
   redirects y enlaces deben permanecer en `https://`. Configurar el proxy
   confiable de Laravel de forma limitada a la conexión local de `cloudflared`;
   nunca confiar indiscriminadamente en cualquier proxy enviado por Internet.
7. No añadir protección Cloudflare por PIN/correo al Quick Tunnel de esta
   integración: la documentación indica que ese modo no admite clientes API no
   interactivos. La web debe exigir el login de Laravel y la API debe exigir
   Sanctum. Compartir la URL solo con el colega; recordar que cualquiera que la
   conozca puede alcanzar la página de login.

La URL pública de la web y la URL base de la API son la misma raíz HTTPS. Para
una URL estable hace falta pasar después a un dominio propio y un túnel
Cloudflare nombrado, u otra solución con hostname estable; dominio, recursos e
impuestos podrían tener costo. Eso queda fuera de la demo $0.

## Fase 4: comprobar Laravel antes de conectar Yura

1. Confirmar la página de login por HTTPS, el certificado del navegador y una
   sesión administrativa.
2. Confirmar que una petición no autenticada a rutas privadas devuelve rechazo.
3. Desde un cliente HTTP seguro, probar el token de servicio contra
   `GET /api/user`; debe identificar la cuenta de rol `robot`. No copiar el
   token a la línea de comando, historial o documentación compartida.
4. Revisar `/api/v1/robot/activities/next` solo con conocimiento del contrato:
   **no es un health check**. Reclamar esa ruta cambia el estado de una
   actividad pendiente y crea/retoma un intento. No llamarla durante las
   verificaciones generales.
5. No simular una actividad real hasta que el dueño autorice una prueba
   coordinada con datos ficticios y confirme el plan de recuperación.

## Fase 5: preparar la Raspberry Pi 5 (posterior a aprobación)

Esta fase es para el colega cuando el dueño confirme el resultado de la prueba
en la i3 y autorice el despliegue. No se debe ejecutar antes.

1. Obtener el **repositorio oficial independiente del cerebro** y el commit
   aprobado. La carpeta `cerebro_yura/` de este repositorio Laravel es una copia
   de referencia; no asumir que sustituye al repositorio/instalación real de la
   i3 o de la Raspberry.
2. Verificar el sistema operativo, arquitectura, dependencias, servicio systemd,
   modelos de voz y rutas del micrófono siguiendo el README del cerebro oficial.
   No reemplazar ni reiniciar el servicio actual de la i3.
3. Instalar la base SQLite del diccionario en la Pi y fijar una ruta existente
   y legible para el servicio, por ejemplo:

   ```dotenv
   YURA_DICTIONARY_PATH=/ruta/real/diccionario_quechua_sqlite_29-07-2026.db
   ```

   Confirmar con el proceso del servicio que el archivo existe y se puede leer;
   no basta con que exista en el directorio del usuario.
4. Crear el archivo local de configuración del cerebro (`.env.local` según el
   proyecto oficial), con permisos solo para el usuario del servicio. Durante
   la preparación, mantener vacíos URL/token; usar la bandera de sincronización
   solo si está implementada en el commit oficial aprobado:

   ```dotenv
   YURA_LARAVEL_URL=
   YURA_LARAVEL_TOKEN=
   YURA_LARAVEL_SYNC_ENABLED=0
   ```

   La copia de referencia de `cerebro_yura/` en este repositorio no acredita
   por sí sola qué variables reconoce el cerebro instalado. Antes de activar
   nada, comprobar los nombres en el repositorio oficial y commit de la Pi. Si
   no existe una bandera de desactivación, mantener el servicio detenido o sin
   URL/token hasta la autorización de prueba. `YURA_LARAVEL_URL`, cuando se
   configure, es la raíz sin `/api` al final; el token debe pertenecer a la
   cuenta con rol `robot`. No guardar secretos en el repositorio ni enviarlos a
   Copilot.
5. Si el commit oficial confirma una bandera de sincronización, mantenerla en
   `0` y reiniciar solo el servicio Yura de la Pi cuando el dueño lo autorice.
   Si no hay una bandera así, dejar el servicio detenido y URL/token vacíos
   hasta que se autorice la activación. Confirmar que la configuración y el
   diccionario se leen sin errores. Hacer una prueba local de micrófono/ASR
   separada: la prueba anterior obtuvo ceros y el reconocimiento de audio aún
   no está certificado. No atribuirlo a la RAM o al hardware sin medirlo.
6. Solo después de verificar Laravel, URL HTTPS, token, audio y datos ficticios,
   el dueño debe colocar URL/token de producción en `.env.local` y activar la
   sincronización según el mecanismo real del commit oficial. Si ese commit
   reconoce `YURA_LARAVEL_SYNC_ENABLED`, cambiarlo a `1`. Reiniciar el servicio
   una vez; observar los logs locales sin publicarlos.
7. Crear una sola actividad pequeña, con un grupo y pocos respondientes
   ficticios. Verificar secuencialmente: actividad reclamada, nombre anunciado,
   pregunta de la categoría exacta, captura/transcripción de voz, puntaje,
   recepción del resultado en Laravel y nota en la casilla HACER correcta.
   Comprobar también que cancelar desde la web detiene el intento sin notas
   parciales. No crear otra actividad hasta cerrar o cancelar la primera.
8. Apagar sincronización y revocar el token si la prueba revela un error. No
   borrar el outbox: los resultados pendientes deben conservarse para
   diagnóstico. No pegar logs o transcripciones con datos personales en un
   chat.

## Recuperación, mantenimiento y fin de la demo

- Si el Quick Tunnel cambia de URL, actualizar la configuración de Laravel y
  Pi; mantener desactivada la conexión del cerebro mediante el mecanismo
  soportado por el commit oficial hasta validar otra vez la URL.
- Si Laravel deja de responder, no reclamar repetidamente actividades desde
  herramientas manuales y no borrar la base/outbox.
- Hacer respaldo de SQLite antes de actualizar; desplegar un commit fijo y
  aplicar migraciones solo tras revisar compatibilidad.
- Al finalizar la demo, desactivar el polling, detener el servicio de túnel,
  revocar el token Sanctum, exportar/eliminar la base demo según lo acordado y
  borrar recursos OCI que ya no se necesitan. Revisar la factura antes y
  después.

## Enlaces oficiales

- [Oracle Cloud Free Tier](https://www.oracle.com/cloud/free/)
- [Cloudflare Quick Tunnels](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/)
- [Cloudflare Tunnel: instalación](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/)

## Prompt de ejecución para Cloud o para el colega

```text
Prepara únicamente la VM Ubuntu ARM64 de Oracle Cloud para la demo Yura
siguiendo docs/manual-demo-yura-oracle-raspberry.md. No despliegues la
Raspberry ni reinicies la i3.

Antes de instalar, inspecciona el commit/release aprobado y confirma que el
árbol está limpio y tiene los manifiestos Composer/frontend requeridos. El
workspace actual tiene muchos cambios y eliminaciones locales; NO publiques ni
despliegues todo ese árbol. Si falta un manifiesto, no inventes/reconstruyas uno:
detente y pide al dueño un release válido.

Usa solo recursos que la consola confirme como Always Free; no actualices la
cuenta a pago ni crees recursos con precio no confirmado. Restringe SSH a IPs
autorizadas. No abras puertos de base de datos.

Configura Laravel en producción, APP_DEBUG=false y SQLite persistente. No
ejecutes db:seed, SchoolPilotSeeder ni RobotUserSeeder. No uses datos reales.
Detente antes de crear credenciales o tokens: el dueño debe aprovisionar y
guardar secretos de forma privada. No solicites ni imprimas claves SSH, APP_KEY,
contraseñas o tokens en Copilot/chat/logs.

Publica temporalmente con Cloudflare Quick Tunnel solo después de que el dueño
lo apruebe. Recuerda que el hostname cambia al recrearlo y no es una URL
permanente. Comprueba HTTPS/proxy y login; no uses GET
/api/v1/robot/activities/next como health check porque reclama actividades.
No actives la sincronización ni ejecutes una actividad real sin autorización
explícita y una prueba coordinada con datos ficticios.

Al terminar, informa commit desplegado, recursos creados y su costo visible,
URL temporal (no secretos), pruebas ejecutadas, tareas pendientes y cualquier
bloqueo. No hagas commit ni borres/reviertas cambios locales.
```
