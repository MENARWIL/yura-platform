# PROMPT 2 - Identificacion de requisitos verificables

## Proyecto

**Robot Interactivo con Inteligencia Artificial ASR para el Apoyo en la Practica del Idioma Quechua en Estudiantes de Sexto de Primaria en la U. E. Boliviano Holandes (Proyecto YURA).**

## Alcance y criterio de analisis

Esta matriz utiliza exclusivamente la informacion proporcionada para el Prompt 2. Los requisitos que dependen de detalles no definidos se marcan como **ambiguos** o **pendientes de especificacion**.

La codificacion utilizada es:

- `RF-001` a `RF-007`: requisitos funcionales.
- `RNF-001` a `RNF-004`: requisitos no funcionales.
- `RN-001` a `RN-003`: reglas de negocio relacionadas.
- `CP-RF-xxx-01` y `CP-RNF-xxx-01`: casos de prueba relacionados.

## Matriz de requisitos funcionales

| ID | Tipo de requisito | Requisito | Modulo | Prioridad | Criterio de aceptacion | Como puede verificarse | Caso(s) de prueba relacionado(s) |
|---|---|---|---|---|---|---|---|
| RF-001 | Funcional | El robot debe capturar comandos de voz en quechua mediante boton o pantalla e iniciar una evaluacion. | Interfaz Fisica del Robot | Alta | Al accionar el boton o control equivalente de la pantalla, el robot inicia la captura de audio y la evaluacion correspondiente. | Prueba funcional en el robot usando ambos mecanismos de inicio. Verificar que la evaluacion no comience sin una accion valida. | CP-RF-001-01, CP-RF-001-02, CP-RF-001-03 |
| RF-002 | Funcional | El motor ASR debe procesar el audio capturado y devolver una transcripcion. | Inteligencia Artificial ASR | Alta | Dado un audio valido en quechua, el motor devuelve una transcripcion asociada al intento. | Prueba de integracion entre captura y motor ASR usando audios de prueba. Verificar respuesta para audio valido, audio vacio y audio no entendible. | CP-RF-002-01, CP-RF-002-02, CP-RF-002-03 |
| RF-003 | Funcional | El motor ASR debe devolver un porcentaje de precision fonetica junto con la transcripcion. | Inteligencia Artificial ASR | Alta | Cada intento procesado devuelve un porcentaje numerico de precision fonetica junto con la transcripcion. | Prueba de salida del motor y validacion del tipo, rango y relacion del porcentaje con el audio evaluado. | CP-RF-003-01, CP-RF-003-02, CP-RF-003-03 |
| RF-004 | Funcional | La plataforma web debe permitir al docente crear unidades tematicas, palabras clave y retos. | Plataforma Web de Gestion Academica | Alta | Un docente autorizado puede registrar cada uno de esos elementos y posteriormente consultarlos o utilizarlos en la configuracion de actividades. | Prueba funcional de formularios web y prueba de persistencia en la base de datos. | CP-RF-004-01, CP-RF-004-02, CP-RF-004-03 |
| RF-005 | Funcional | La aplicacion movil debe mostrar graficos de avance a los apoderados. | Aplicacion Movil de Consulta | Alta | Un apoderado autenticado puede visualizar los graficos de avance correspondientes al estudiante vinculado. | Prueba de aplicacion movil con datos de avance conocidos y comparacion de valores mostrados contra los datos de origen. | CP-RF-005-01, CP-RF-005-02 |
| RF-006 | Funcional | La aplicacion movil debe notificar a los apoderados en tiempo real. | Aplicacion Movil de Consulta | Alta | Cuando ocurre el evento que debe generar una notificacion, el apoderado recibe la notificacion sin una actualizacion manual de la pantalla. | Prueba de integracion movil-servidor/notificaciones midiendo el tiempo entre evento y recepcion. | CP-RF-006-01, CP-RF-006-02 |
| RF-007 | Funcional | El sistema debe registrar cada intento en la tabla `phonetic_evaluations` con marca de tiempo. | Seguimiento, Reportes y Estadisticas | Alta | Por cada intento procesado existe exactamente un registro correspondiente en `phonetic_evaluations` con su marca de tiempo. | Prueba de integracion y consulta directa de base de datos. Comparar cantidad de intentos ejecutados contra registros creados. | CP-RF-007-01, CP-RF-007-02, CP-RF-007-03 |
| RF-008 | Funcional | El robot debe activar actuadores mecanicos, luces y bocinas como recompensa ludica al recibir una respuesta correcta. | Actividades Grupales y Gamificacion | Alta | Cuando el sistema determina que la respuesta es correcta, se activan los actuadores, luces y bocinas configurados como recompensa. | Prueba funcional con respuesta correcta y respuesta incorrecta. Verificar que la recompensa solo se active bajo la condicion definida. | CP-RF-008-01, CP-RF-008-02, CP-RF-008-03 |

## Matriz de requisitos no funcionales

| ID | Tipo de requisito | Requisito | Modulo | Prioridad | Criterio de aceptacion | Como puede verificarse | Caso(s) de prueba relacionado(s) |
|---|---|---|---|---|---|---|---|
| RNF-001 | No funcional - Disponibilidad local | Las funciones principales deben operar en el aula de forma local mediante red LAN sin depender de internet constante. | Interfaz Fisica del Robot, ASR, Plataforma Web, Seguimiento y Reportes | Alta | Con la conexion a internet deshabilitada, las funciones principales definidas para el aula continuan operativas mediante la red LAN local. | Prueba de desconexion de internet manteniendo la LAN activa. Ejecutar captura, procesamiento, evaluacion y registro segun el alcance local definido. | CP-RNF-001-01, CP-RNF-001-02 |
| RNF-002 | No funcional - Usabilidad infantil | La interfaz debe ser intuitiva y ludica para niños de 11 a 12 años. | Interfaz Fisica del Robot | Alta | Un grupo de usuarios del rango indicado puede iniciar y completar el flujo de evaluacion sin asistencia tecnica no prevista. | Prueba de usabilidad observada con estudiantes de 11 a 12 años, registrando errores, abandonos y necesidad de ayuda. | CP-RNF-002-01, CP-RNF-002-02 |
| RNF-003 | No funcional - Rendimiento | La latencia del procesamiento ASR y de la respuesta ludica del robot no debe exceder 2.5 segundos. | ASR y Actividades Grupales/Gamificacion | Alta | El tiempo medido desde el evento definido hasta la transcripcion/respuesta ASR y desde la respuesta correcta hasta la activacion ludica es igual o menor a 2.5 segundos. | Prueba de rendimiento con medicion de timestamps en condiciones normales y con la carga de aula definida. | CP-RNF-003-01, CP-RNF-003-02, CP-RNF-003-03 |
| RNF-004 | No funcional - Seguridad y privacidad | El sistema debe aplicar una restriccion estricta de roles y la APK debe funcionar para los padres en modo de lectura pasiva, sin permitir modificar notas ni datos academicos. | Plataforma Web y Aplicacion Movil de Consulta | Alta | Un padre/apoderado puede consultar informacion autorizada, pero cualquier intento de modificar notas o datos academicos es rechazado y no altera la base de datos. | Pruebas de autorizacion en interfaz, API y solicitudes manipuladas directamente. Verificar respuesta de rechazo y ausencia de cambios en base de datos. | CP-RNF-004-01, CP-RNF-004-02, CP-RNF-004-03 |

## Matriz de reglas de negocio

Las siguientes reglas no son requisitos funcionales independientes, pero deben incorporarse a los casos de prueba y a los criterios de aceptacion.

| ID | Regla de negocio | Modulo | Criterio verificable | Como puede verificarse | Caso(s) relacionado(s) |
|---|---|---|---|---|---|
| RN-001 | Un estudiante solo puede interactuar con retos programados para su curso y nivel actual. | Plataforma Web, Robot y Gamificacion | Si el reto no corresponde al curso o nivel del estudiante, el sistema no permite iniciar o completar esa interaccion. | Crear retos para cursos/niveles distintos y probar el acceso de estudiantes no correspondientes. | CP-RN-001-01, CP-RN-001-02 |
| RN-002 | Las puntuaciones acumuladas en los juegos del robot son referenciales para el seguimiento y no se computan directamente como notas oficiales del cuaderno pedagogico. | Robot, Seguimiento y Plataforma Web | Una puntuacion de juego puede aparecer en el seguimiento, pero no modifica directamente una nota oficial. | Registrar una puntuacion robot y comparar antes y despues las notas oficiales del cuaderno pedagogico. | CP-RN-002-01, CP-RN-002-02 |
| RN-003 | El robot no genera conversacion libre por IA; las respuestas validas se limitan al vocabulario configurado previamente. | ASR y Robot | Una respuesta fuera del vocabulario configurado no se acepta como respuesta valida ni genera una recompensa de respuesta correcta. | Probar palabras configuradas, palabras no configuradas y expresiones libres. | CP-RN-003-01, CP-RN-003-02 |

## Casos de prueba propuestos

### RF-001 - Captura e inicio de evaluacion

- `CP-RF-001-01`: iniciar evaluacion mediante boton.
- `CP-RF-001-02`: iniciar evaluacion mediante pantalla.
- `CP-RF-001-03`: verificar que no se inicie una evaluacion sin accion del usuario.

### RF-002 y RF-003 - Procesamiento ASR

- `CP-RF-002-01`: procesar audio valido en quechua.
- `CP-RF-002-02`: procesar audio vacio o sin voz.
- `CP-RF-002-03`: procesar audio no entendible.
- `CP-RF-003-01`: verificar que se devuelva transcripcion y porcentaje.
- `CP-RF-003-02`: validar que el porcentaje tenga formato y rango definidos.
- `CP-RF-003-03`: comparar resultados con audios cuya precision esperada sea conocida.

### RF-004 - Configuracion docente

- `CP-RF-004-01`: crear una unidad tematica.
- `CP-RF-004-02`: crear palabras clave.
- `CP-RF-004-03`: crear un reto y verificar su disponibilidad para la configuracion correspondiente.

### RF-005 y RF-006 - Consulta movil

- `CP-RF-005-01`: visualizar grafico con datos existentes.
- `CP-RF-005-02`: verificar que el grafico se actualice con nuevos datos.
- `CP-RF-006-01`: recibir una notificacion despues del evento configurado.
- `CP-RF-006-02`: verificar comportamiento sin conectividad disponible.

### RF-007 - Registro de intentos

- `CP-RF-007-01`: ejecutar un intento y verificar un registro en `phonetic_evaluations`.
- `CP-RF-007-02`: ejecutar varios intentos y comparar cantidades.
- `CP-RF-007-03`: verificar que cada registro tenga marca de tiempo valida.

### RF-008 - Recompensa ludica

- `CP-RF-008-01`: respuesta correcta activa actuadores, luces y bocinas.
- `CP-RF-008-02`: respuesta incorrecta no activa la recompensa de respuesta correcta.
- `CP-RF-008-03`: verificar activacion conjunta de los componentes configurados.

### RNF-001 - Operacion local

- `CP-RNF-001-01`: desconectar internet y comprobar las funciones principales mediante LAN.
- `CP-RNF-001-02`: verificar el comportamiento cuando tambien se pierde la LAN.

El segundo caso permite documentar la limitacion del sistema, pero no define un criterio de aceptacion porque no se proporciono el comportamiento esperado sin red local.

### RNF-002 - Usabilidad infantil

- `CP-RNF-002-01`: observar a un estudiante de 11 a 12 anos completando el flujo.
- `CP-RNF-002-02`: registrar errores de comprension, abandonos y solicitudes de ayuda.

### RNF-003 - Tiempo de respuesta

- `CP-RNF-003-01`: medir latencia de procesamiento ASR.
- `CP-RNF-003-02`: medir latencia entre respuesta correcta y recompensa.
- `CP-RNF-003-03`: repetir mediciones bajo la carga normal de aula.

### RNF-004 - Seguridad y privacidad

- `CP-RNF-004-01`: intentar modificar notas desde el perfil de padre/apoderado.
- `CP-RNF-004-02`: intentar modificar datos academicos desde solicitudes manipuladas.
- `CP-RNF-004-03`: verificar que el intento rechazado no modifique la base de datos.

## Ambiguedades y limites de verificabilidad

Los siguientes elementos no pueden verificarse completamente con la informacion disponible:

1. **Porcentaje de precision fonetica:** no se definio la formula, rango, umbral de aprobacion ni conjunto de referencia.
2. **Notificaciones en tiempo real:** no se definio el tiempo maximo aceptable ni el mecanismo de notificacion.
3. **Funciones principales locales:** no se especifico que funciones deben continuar cuando internet no esta disponible.
4. **Interfaz intuitiva y ludica:** no se definieron metricas de usabilidad, numero de usuarios de prueba ni porcentaje aceptable de exito.
5. **Latencia de 2.5 segundos:** no se preciso el punto inicial y final exacto de la medicion ni las condiciones de carga.
6. **Respuesta correcta:** no se definio como se determina, ni el umbral de precision que activa la recompensa.
7. **Vocabulario configurado:** no se indicaron formato, idioma exacto, cantidad de palabras ni reglas de coincidencia.
8. **Graficos de avance:** no se especificaron indicadores, formulas ni frecuencia de actualizacion.
9. **Modo de lectura pasiva:** no se detallo si tambien bloquea operaciones como exportar, descargar o compartir informacion.
10. **Marca de tiempo:** no se especifico zona horaria, formato ni precision requerida.

## Informacion que debe proporcionarse para completar la matriz

Para cerrar los criterios de aceptacion y elaborar casos de prueba ejecutables, se necesita:

### ASR

- Formula del porcentaje de precision fonetica.
- Rango valido del porcentaje.
- Umbral para considerar correcta una respuesta.
- Audios de referencia en quechua.
- Variantes de pronunciacion aceptadas.
- Tiempo exacto que debe medirse.

### Robot y hardware

- Modelo y caracteristicas del mini PC.
- Componentes y controladores de actuadores, luces y bocinas.
- Protocolo entre Python, ESP32 y la plataforma.
- Estados esperados ante respuesta correcta e incorrecta.
- Comportamiento frente a fallas de hardware.

### Operacion local

- Funciones que deben operar sin internet.
- Servicios alojados en LAN.
- Sincronizacion posterior cuando vuelva la conectividad.
- Comportamiento ante perdida de LAN.

### Plataforma web y base de datos

- Estructura de `phonetic_evaluations`.
- Relaciones entre estudiante, curso, nivel, reto y evaluacion.
- Reglas para crear, editar y eliminar unidades, palabras y retos.
- Definicion de los reportes y estadisticas.

### Aplicacion movil

- APK o codigo fuente.
- Flujo de autenticacion.
- Tipo de notificaciones.
- Tiempo maximo para considerar una notificacion recibida en tiempo real.
- Graficos e indicadores que deben mostrarse.
- Operaciones permitidas en modo lectura.

### Usabilidad y rendimiento

- Numero de estudiantes evaluados simultaneamente.
- Carga normal y maxima del aula.
- Dispositivos objetivo.
- Metrica de exito para niños de 11 a 12 anos.
- Tamano de la muestra de usuarios.

## Conclusion

Los requisitos proporcionados pueden transformarse en pruebas funcionales, de integracion, rendimiento, usabilidad y seguridad. Sin embargo, varios criterios aun requieren valores medibles y especificaciones tecnicas, especialmente precision ASR, notificaciones en tiempo real, operacion local, respuesta correcta y comunicacion entre robot, ESP32, plataforma y aplicacion movil.
