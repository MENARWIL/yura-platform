# Guía de Instalación para el Equipo - YURA Platform

¡Hola equipo! Sigan estos pasos para tener el proyecto funcionando en sus computadoras igual que en la de Wilson.

## Requisitos Previos
Tener instalado en su PC:
1. **XAMPP** (con PHP 8.2+ y MySQL)
2. **Composer** ([descargar aquí](https://getcomposer.org/))
3. **Node.js** ([descargar aquí](https://nodejs.org/))

---

## Pasos para la Instalación

### 1. Preparar la Carpeta
Copien la carpeta `yura-platform` en su directorio de trabajo (ej: `C:\xampp\htdocs\web3\yura-platform`).

### 2. Crear la Base de Datos
1. Abran su navegador en `localhost/phpmyadmin`.
2. Creen una nueva base de datos llamada EXACTAMENTE: `yura_platform`.

### 3. Configurar el Entorno (.env)
Abran la carpeta en VS Code y busquen el archivo `.env`. Asegúrense de que estas líneas coincidan con su configuración de XAMPP:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yura_platform
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Ejecutar Comandos de Instalación
Abran una terminal dentro de la carpeta del proyecto y ejecuten estos comandos en orden:

```bash
# 1. Instalar dependencias de PHP
composer install

# 2. Instalar dependencias de diseño
npm install
npm run build

# 3. Generar la clave de seguridad
php artisan key:generate

# 4. CREAR TABLAS Y USUARIOS DE PRUEBA (IMPORTANTE)
php artisan migrate --seed
```

---

## Usuarios para la Presentación
Una vez que el último comando termine, ya pueden entrar a `http://localhost:8000` (o donde lo sirvan con `php artisan serve`) con estos correos:

| Rol | Correo | Contraseña |
| --- | --- | --- |
| **Administrador** | admin@yura.com | password123 |
| **Profesor** | profesor@yura.com | password123 |
| **Padre** | padre@yura.com | password123 |

---

## Notas de Personalización
Para cambiar el nombre del integrante que aparece en la plataforma:
1. Abran el archivo `resources/views/layouts/app.blade.php`.
2. Busquen las líneas 29 y 132.
3. Reemplacen "Wilson Mendieta Arnez" por su nombre completo.

**Nota sobre CRUDs**: Se han eliminado las restricciones de roles. Cualquier usuario puede ver y editar todos los estudiantes para facilitar las pruebas del equipo.

---
*Desarrollado para YURA Platform - 2026*
