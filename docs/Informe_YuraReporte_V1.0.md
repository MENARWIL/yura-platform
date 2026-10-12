# INFORME TÉCNICO Y ARQUITECTÓNICO: YURA REPORTE V1.0
**Plataforma Educativa Institucional y de Gestión Académica YURA**  
*Auditoría de Software, Diagnóstico de Seguridad, Arquitectura de Información y Plan de Orquestación*  
**Versión:** 1.0  
**Fecha de Emisión:** 01 de Octubre de 2026  
**Entorno Operativo:** Windows / XAMPP / PHP 8.2+ / MySQL 8.0 / Laravel 12  
**Código de Proyecto:** YURA-PLT-2026-V1.0  

---

## 1. RESUMEN EJECUTIVO

El presente informe formaliza la auditoría técnica exhaustiva y el levantamiento arquitectónico inicial de la **Plataforma Educativa YURA**, desarrollada originalmente como soporte pedagógico del proyecto *"Robot Interactivo con Inteligencia Artificial ASR para el Apoyo en la Práctica del Idioma Quechua en Estudiantes de Sexto de Primaria en la U. E. Boliviano Holandés"*, orientada actualmente a su consolidación como una **Plataforma Web Educativa Institucional** alineada a las mejores prácticas de los portales públicos educativos de Bolivia.

La evaluación técnica integral evidencia un sistema base sólido sobre **Laravel 12 y MySQL 8.0**, con **29 pruebas automatizadas aprobadas (Pest/PHPUnit)**, pero con oportunidades críticas de saneamiento en seguridad, normalización de datos, accesibilidad universal y la ausencia de los módulos fundamentales de un portal público: catálogo de trámites ciudadanos en línea, repositorio oficial de normativa y resoluciones, buscador unificado y auditoría inmutable de operaciones.

---

## 2. FICHA TÉCNICA DEL ECOSISTEMA DE SOFTWARE

| Parámetro | Detalle Técnico | Evaluación |
|---|---|---|
| **Lenguaje Base** | PHP 8.2.12 (CLI / CGI) | ✅ CORRECTO |
| **Framework Backend** | Laravel Framework 12.0.x (`laravel/framework: ^12.0`) | ✅ CORRECTO |
| **Autenticación API** | Laravel Sanctum 4.0 (`laravel/sanctum: ^4.0`) | ✅ CORRECTO |
| **Base de Datos** | MySQL 8.0.46 Community Server (InnoDB, utf8mb4) | ✅ CORRECTO |
| **Librería de Reportes PDF** | Barryvdh DomPDF 3.1 (`barryvdh/laravel-dompdf: ^3.1`) | ✅ CORRECTO |
| **Librería de Exportación Excel** | Maatwebsite Excel 3.1 (`maatwebsite/excel: ^3.1`) | ✅ CORRECTO |
| **Motor de Testing** | Pest PHP 3.x / PHPUnit 11.5 | ✅ CORRECTO (29 tests PASS) |
| **Frontend Compilado** | Vite 7.0.7 con Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`) | ⚠️ NECESITA MEJORA |
| **Frontend Administrativo** | AdminLTE 3 / Bootstrap 4 / FontAwesome 6 (Vendor local) | ⚠️ NECESITA MEJORA |
| **Internacionalización** | Laravel Localization (`es` Castellano / `qu` Quechua) | ✅ CORRECTO |

---

## 3. DIAGRAMAS ARQUITECTÓNICOS DEL SISTEMA

### 3.1 Diagrama de Arquitectura Global de la Plataforma

```mermaid
graph TD
    subgraph Capa_Presentacion ["Capa de Presentación y Clientes"]
        Citizen[Ciudadano / Visitante]
        Parent[Padres / Tutores Familiares]
        Teacher[Personal Docente]
        AdminUser[Administrador / Gestor SIE]
        RobotClient[Robot Físico YURA / App Móvil]
    end

    subgraph Capa_Enrutamiento ["Enrutamiento y Seguridad de Entrada"]
        WebGate["Rutas Web (routes/web.php)<br/>Sesiones + CSRF + SetLocale"]
        ApiGate["Rutas API (routes/api.php)<br/>Laravel Sanctum Bearer Token"]
        RoleMiddleware["Middleware de Roles y Políticas<br/>(CheckRole: admin, academic, profesor, tutor)"]
    end

    subgraph Capa_Controladores ["Controladores de Negocio"]
        PublicCtrl["Controladores Públicos<br/>Portal, Trámites, Normativa"]
        AcademicCtrl["Controladores Académicos<br/>Student, Grade, Attendance"]
        AdminCtrl["Controladores de Administración<br/>User, Course, Subject, Audit"]
        ApiCtrl["Controladores API REST<br/>RobotActivity, StudentApi, AuthApi"]
    end

    subgraph Capa_Servicios ["Capa de Lógica y Servicios"]
        ProcedureService["Servicio de Trámites y Seguimiento"]
        PedagogicalService["Servicio de Cuaderno Pedagógico"]
        AuditService["Servicio de Auditoría Inmutable"]
        MediaService["Servicio de Archivos Seguros"]
    end

    subgraph Capa_Persistencia ["Base de Datos y Almacenamiento"]
        MySQLDB[(MySQL 8.0 - db_yura_platform<br/>19 tablas activas)]
        StorageDir["Storage Local Protegido<br/>(Expedientes, Fotos, PDF)"]
    end

    Citizen -->|HTTP GET / POST| WebGate
    Parent -->|HTTP Sesión| WebGate
    Teacher -->|HTTP Sesión| WebGate
    AdminUser -->|HTTP Sesión| WebGate
    RobotClient -->|JSON REST HTTPS| ApiGate

    WebGate --> RoleMiddleware
    ApiGate --> RoleMiddleware

    RoleMiddleware --> PublicCtrl
    RoleMiddleware --> AcademicCtrl
    RoleMiddleware --> AdminCtrl
    RoleMiddleware --> ApiCtrl

    PublicCtrl --> ProcedureService
    PublicCtrl --> MediaService
    AcademicCtrl --> PedagogicalService
    AdminCtrl --> AuditService
    ApiCtrl --> PedagogicalService
    ApiCtrl --> AuditService

    ProcedureService --> MySQLDB
    PedagogicalService --> MySQLDB
    AuditService --> MySQLDB
    MediaService --> StorageDir
```

---

### 3.2 Diagrama Entidad-Relación (Modelo de Datos Normalizado)

```mermaid
erDiagram
    users ||--o{ students : "es docente de / es padre de"
    users ||--o{ teaching_assignments : "dicta"
    users ||--o{ audit_logs : "ejecuta accion"
    courses ||--o{ parallels : "subdivide en"
    courses ||--o{ students : "matricula a"
    parallels ||--o{ students : "asigna a"
    students ||--o{ student_family_members : "posee vinculo con"
    users ||--o{ student_family_members : "es apoderado en"
    students ||--o{ grades : "evaluado en"
    subjects ||--o{ grades : "calificada en"
    students ||--o{ attendance : "registra asistencia en"
    subjects ||--o{ teaching_assignments : "asignada en"
    parallels ||--o{ teaching_assignments : "pertenece a"
    
    users ||--o{ procedures : "solicita tramite"
    procedure_types ||--o{ procedures : "clasifica"
    procedures ||--o{ procedure_histories : "registra estados"
    procedures ||--o{ procedure_attachments : "adjunta requisitos"
    official_documents ||--o{ audit_logs : "auditado en"

    users {
        bigint id PK
        string name
        string email
        string password
        string role
        string ci
        string status
        timestamp created_at
    }

    students {
        bigint id PK
        string first_name
        string last_name
        string second_last_name
        bigint course_id FK
        bigint parallel_id FK
        bigint parent_user_id FK
        bigint teacher_user_id FK
        string status
    }

    procedures {
        bigint id PK
        string tracking_code UK
        bigint procedure_type_id FK
        bigint applicant_user_id FK
        string applicant_name
        string applicant_ci
        string status
        text observations
        timestamp created_at
    }

    procedure_histories {
        bigint id PK
        bigint procedure_id FK
        string status
        text note
        bigint changed_by_user_id FK
        timestamp created_at
    }

    official_documents {
        bigint id PK
        string title
        string code UK
        string category
        int year
        string file_path
        boolean active
    }

    audit_logs {
        bigint id PK
        bigint user_id FK
        string action
        string entity
        bigint entity_id
        string ip_address
        string status
        timestamp created_at
    }
```

---

### 3.3 Diagrama del Ciclo de Vida y Estados de Trámites

```mermaid
stateDiagram-v2
    [*] --> BORRADOR : Ciudadano inicia formulario
    BORRADOR --> ENVIADO : Adjunta requisitos y confirma envio
    ENVIADO --> EN_REVISION : Funcionario de ventanilla recepciona
    
    state EN_REVISION {
        [*] --> VerificandoRequisitos
        VerificandoRequisitos --> ValidandoIdentidad
        ValidandoIdentidad --> [*]
    }

    EN_REVISION --> OBSERVADO : Requisitos incompletos / ilegibles
    OBSERVADO --> ENVIADO : Ciudadano subsana observaciones
    
    EN_REVISION --> APROBADO : Requisitos cumplen normativa
    EN_REVISION --> RECHAZADO : Improcedente con resolucion motivada
    
    APROBADO --> FINALIZADO : Emision de certificacion y entrega
    RECHAZADO --> FINALIZADO : Cierre de expediente
    FINALIZADO --> [*]
```

---

### 3.4 Diagrama de Secuencia: Flujo End-to-End del Trámite Educativo

```mermaid
sequenceDiagram
    autonumber
    actor Ciudadano as Visitante / Ciudadano
    participant Portal as Portal Público Web
    participant Auth as Módulo Autenticación
    participant Tramites as Motor de Trámites
    participant DB as Base de Datos MySQL
    actor Admin as Funcionario / Administrador

    Ciudadano->>Portal: 1. Consulta catálogo de trámites y requisitos
    Portal-->>Ciudadano: Muestra ficha técnica y requisitos
    Ciudadano->>Auth: 2. Registra cuenta ciudadana con CI y correo
    Auth->>DB: Guarda usuario activo rol 'padre' / 'ciudadano'
    Auth-->>Ciudadano: Sesión iniciada con token de seguridad
    
    Ciudadano->>Tramites: 3. Completa solicitud y sube documentos adjuntos
    Tramites->>DB: Crea registro en 'procedures' y genera TRM-2026-XXXX
    Tramites->>DB: Registra estado inicial 'ENVIADO' en 'procedure_histories'
    Tramites-->>Ciudadano: Emite Comprobante Digital con Código Único
    
    Admin->>Portal: 4. Ingresa al Panel Administrativo de Trámites
    Portal->>DB: Consulta trámites en bandeja 'ENVIADO'
    Admin->>Tramites: 5. Abre expediente, verifica adjuntos y valida datos
    Admin->>Tramites: 6. Cambia estado a 'APROBADO' con resolución
    Tramites->>DB: Actualiza status y añade hito en historial con auditoría
    
    Ciudadano->>Portal: 7. Ingresa código TRM-2026-XXXX en buscador público
    Portal->>DB: Consulta trazabilidad de la solicitud
    Portal-->>Ciudadano: Muestra estado 'APROBADO' y fecha de retiro
```

---

### 3.5 Diagrama de Orquestación de los 6 Subagentes Especializados

```mermaid
graph TD
    Orchestrator["ORQUESTADOR PRINCIPAL<br/>(Tutor Senior & Arquitecto Jefe)"]

    subgraph Equipo_Especializado ["Equipo de 6 Subagentes Concurrentes"]
        Sub1["SUBAGENTE 1<br/>Auditor & Analista Funcional<br/>(Diagnóstico, tablas de hallazgos, dependencias)"]
        Sub2["SUBAGENTE 2<br/>UX/UI & Accesibilidad Educativa<br/>(Design System, WCAG AA, paleta y estados)"]
        Sub3["SUBAGENTE 3<br/>Frontend Engineer<br/>(Vistas Blade, buscador global, filtros)"]
        Sub4["SUBAGENTE 4<br/>Backend & Database Architect<br/>(Modelos, migraciones, procedimientos, servicios)"]
        Sub5["SUBAGENTE 5<br/>Ciberseguridad & QA Tester<br/>(OWASP, sanitización, audit_logs, rate limiting)"]
        Sub6["SUBAGENTE 6<br/>Integración, DevOps & Verificación<br/>(Contratos API, suite Pest, regresión E2E)"]
    end

    Orchestrator -->|Supervisa Contratos| Sub1
    Orchestrator -->|Establece Guía de Estilos| Sub2
    Orchestrator -->|Asigna Vistas Blade| Sub3
    Orchestrator -->|Define Esquema BD| Sub4
    Orchestrator -->|Exige Políticas OWASP| Sub5
    Orchestrator -->|Valida Integración Final| Sub6

    Sub1 -.->|Reporta Riesgos| Orchestrator
    Sub2 -.->|Entrega Componentes| Sub3
    Sub4 -.->|Provee Endpoints| Sub3
    Sub5 -.->|Valida Endpoints| Sub4
    Sub6 -.->|Ejecuta Tests de Todos| Orchestrator
```

---

### 3.6 Diagrama de Ruta de Implementación por Prioridades

```mermaid
graph LR
    subgraph P0_Seguridad ["Fase P0: Crítico (Inmediato)"]
        P0_1["Eliminación de scripts residuales en raíz"]
        P0_2["Corrección de colisión students.course"]
        P0_3["Aislamiento de credenciales"]
    end

    subgraph P1_Core ["Fase P1: Núcleo Backend & BD"]
        P1_1["Migraciones: Trámites, Normativa, Auditoría"]
        P1_2["Servicios y Controladores de Trámites"]
        P1_3["Habilitación de Registro Ciudadano"]
    end

    subgraph P2_Frontend ["Fase P2: Portal y Accesibilidad"]
        P2_1["Layout Institucional Público WCAG AA"]
        P2_2["Página de Inicio con Servicios Educativos"]
        P2_3["Buscador de Normativa y Seguimiento"]
    end

    subgraph P3_Certificacion ["Fase P3: QA & Certificación"]
        P3_1["Pruebas E2E de Trámite Completo"]
        P3_2["Verificación 100% Regresión (29 Tests)"]
        P3_3["Informe Final y Despliegue"]
    end

    P0_Seguridad --> P1_Core
    P1_Core --> P2_Frontend
    P2_Frontend --> P3_Certificacion
```

---

## 4. INVENTARIO DE BASE DE DATOS Y VOLUMEN DE REGISTROS

La base de datos `db_yura_platform` en MySQL 8.0 cuenta con **19 tablas operativas**:

| N° | Nombre de Tabla | Cantidad de Filas | Clasificación y Función |
|---|---|---|---|
| 1 | `users` | 25 registros | Cuentas de acceso (Admin, Académico, Profesores, Tutores, Robot) |
| 2 | `students` | 20 registros | Expediente de alumnos, asignación de aula y puntajes base |
| 3 | `student_family_members` | 20 registros | Relaciones de parentesco, permisos de visualización y tutoría |
| 4 | `grades` | 22 registros | Calificaciones trimestrales, actividades y notas de robot |
| 5 | `grade_history` | 4 registros | Trazabilidad y auditoría de ediciones de notas |
| 6 | `attendance` | 141 registros | Asistencia diaria por alumno, materia y fecha |
| 7 | `courses` | 2 registros | Niveles educativos (Sexto de Primaria, etc.) |
| 8 | `parallels` | 3 registros | Paralelos (A, B, C) con capacidad máxima de alumnos |
| 9 | `subjects` | 1 registro | Asignatura curricular (Lengua Quechua y Originaria) |
| 10 | `teaching_assignments` | 2 registros | Asignación de docentes a materias y paralelos |
| 11 | `personal_access_tokens` | 5 registros | Tokens de autenticación API Sanctum |
| 12 | `sessions` | 54 registros | Sesiones HTTP activas en base de datos |
| 13 | `cache` | 4 registros | Llaves de caché en base de datos |
| 14 | `cache_locks` | 0 registros | Bloqueos concurrentes de caché |
| 15 | `jobs` | 0 registros | Trabajos en cola asíncrona |
| 16 | `failed_jobs` | 0 registros | Registro de fallos en colas |
| 17 | `job_batches` | 0 registros | Lotes de procesamiento de colas |
| 18 | `migrations` | 43 registros | Histórico de migraciones ejecutadas |
| 19 | `password_reset_tokens` | 0 registros | Recuperación de claves |

---

## 5. HALLAZGOS Y DICTAMEN DEL TUTOR SENIOR

### 5.1 Colisión Estructural en el Modelo `Student` (`students.course`)
* **QUÉ está mal:** En la tabla `students` coexiste la columna de texto `course` con la relación Eloquent `course()` vinculada a `course_id`.
* **POR QUÉ está mal:** Laravel resuelve las propiedades dinámicas de los modelos priorizando las columnas de la tabla. Al invocar `$student->course`, se obtiene una cadena en lugar del modelo `Course`, obligando al código a invocar `course()->first()`.
* **QUÉ riesgo produce:** Multiplica de forma descontrolada las consultas SQL ($N+1$), degradando severamente el tiempo de respuesta conforme crece el padrón estudiantil.
* **CÓMO debe resolverse:** Desacoplar la relación utilizando un alias explícito seguro o normalizar la columna de texto hacia la relación foránea canónica.

### 5.2 Exposición de Scripts Residuales en Raíz
* **QUÉ está mal:** Permanencia de los scripts `temp_demo_check.php` y `test_api.php` en el directorio raíz de la aplicación con credenciales maestras escritas en texto plano (`password123`, `Demo123!`).
* **POR QUÉ está mal:** Violan el principio fundamental de confidencialidad y control de acceso de OWASP (A01 y A02). Si el servidor web procesa la raíz, un tercero puede ejecutar estos archivos y crear usuarios administradores o extraer tokens de API.
* **QUÉ riesgo produce:** Escalada de privilegios y compromiso total de la base de datos.
* **CÓMO debe resolverse:** Archivar o eliminar de inmediato los scripts de depuración de la raíz y proteger el acceso público exclusivamente a la carpeta `public/`.

### 5.3 Ausencia de Módulo Ciudadano de Trámites y Normativa
* **QUÉ está mal:** La página principal actual es una carátula estática de feria escolar, careciendo de portal de servicios ciudadanos, catálogo de trámites y repositorio de resoluciones ministeriales/administrativas.
* **POR QUÉ está mal:** Una plataforma institucional educativa debe permitir la interacción de la comunidad con sus instituciones mediante trámites formales y acceso transparente a la información pública.
* **CÓMO debe resolverse:** Crear los módulos de trámites con código de seguimiento único y repositorio de documentos oficiales con filtros dinámicos.

---

## 6. MATRIZ DE PRUEBAS DE REGRESIÓN APROBADAS (SUITE PEST)

La suite de pruebas automatizadas existente fue ejecutada satisfactoriamente, registrando **100% de efectividad**:

```
PASS  Tests\Unit\ExampleTest ................................... 1 test passed
PASS  Tests\Unit\StudentModelNamingCompatibilityTest ........... 1 test passed
PASS  Tests\Feature\AcademicSubjectsTest ....................... 4 tests passed
PASS  Tests\Feature\CrudAndMediaTest ........................... 2 tests passed
PASS  Tests\Feature\ExampleTest ................................ 1 test passed
PASS  Tests\Feature\FamilyAndStudentAuthTest ................... 10 tests passed
PASS  Tests\Feature\RobotIntegrationTest ....................... 3 tests passed
PASS  Tests\Feature\SecurityRegressionTest ..................... 7 tests passed

TOTAL: 29 pruebas aprobadas (126 aserciones ejecutadas en 34.37s)
```

Ninguna modificación futura podrá degradar este resultado. Todos los nuevos componentes se integrarán con pruebas automatizadas incrementales.

---

## 7. CONTROL DE VERSIONES Y PROGRESIÓN DE INFORMES

Conforme a la instrucción operativa de control de versiones y auditoría:
* **Versión Actual:** `Informe YuraReporte V1.0` (Registro inicial de auditoría y diagramas estructurales).
* **Regla de Progresión:** Cada solicitud de informe sucesivo incrementará la versión en base decimal (`V2.0`, `V3.0`, etc.) preservando la trazabilidad de los hallazgos resueltos y el estado de avance de los seis subagentes.

---
*Fin del Informe Técnico YuraReporte V1.0 - Elaborado por el Orquestador y Auditor Técnico Principal.*
