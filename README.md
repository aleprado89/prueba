# Sistema de Informacion Pedagogica para Escuelas (SE System)

Sistema web de gestion educativa para instituciones escolares, desarrollado en PHP puro. Permite la autogestion de alumnos, docentes y secretarios para administrar calificaciones, inscripciones, examenes y reportes academicos.

**Version**: 3.0.2

## Stack Tecnologico

- **Backend**: PHP 7.1+ (procedural, sin frameworks), MySQLi
- **Base de Datos**: MySQL 8.4+
- **Frontend**: HTML5, Bootstrap 5, Bootswatch Materia, jQuery 3.7.1, Chart.js
- **Reportes PDF**: Dompdf
- **Email**: PHPMailer 6 (SMTP)
- **Dependencias**: Composer

## Estructura del Proyecto

```
/
├── alumnos/          # Autogestion de alumnos (16 archivos)
├── docentes/         # Autogestion de docentes (9 archivos)
├── secretaria/       # Sistema administrativo principal (27 archivos)
├── reportes/         # Generacion de reportes PDF (18 archivos)
├── funciones/        # Logica compartida y utilidades
│   ├── consultas.php           # TODAS las consultas a BD (~120 funciones)
│   ├── analisisestado.php      # Calculo de estados de cursado
│   ├── controlCorrelatividad.php # Control de correlatividades
│   ├── verificarSesion.php     # Control de sesion y autenticacion
│   ├── menu.php / menu_docente.php / menu_secretaria.php
│   ├── parametrosWeb.php       # Parametros dinamicos desde BD
│   └── cerrarsesion.php        # Logout
├── inicio/           # Autenticacion y configuracion
│   ├── conexion.php            # Conexion a BD
│   ├── login.php               # Login alumnos/docentes
│   ├── loginAdmin.php          # Login secretaria
│   ├── loginResult.php         # Procesa login alumno/docente
│   ├── loginResultAdmin.php    # Procesa login admin
│   ├── seleccionar_rol.php     # Seleccion de rol (alumno+docente)
│   └── variablesParticulares.php # Configuracion por institucion
├── css/              # Bootstrap + Bootswatch Materia + estilos.css
├── js/               # jQuery, Bootstrap JS, Chart.js
├── img/              # Logos y membretes
├── vendor/           # Dependencias Composer (Dompdf, PHPMailer)
├── configMail.php    # Configuracion SMTP
└── shema_sistemasescolares.sql  # Esquema de BD
```

## Subsistemas

| Subsistema | Entry Point | Carpeta | Descripcion |
|------------|-------------|---------|-------------|
| Alumnos | `inicio/login.php` | `alumnos/` | Consulta calificaciones, asistencias, solicitudes de inscripcion |
| Docentes | `inicio/login.php` | `docentes/` | Carga calificaciones/asistencias, actas de examen |
| Secretaria | `inicio/loginAdmin.php` | `secretaria/` | Gestion completa: alumnos, docentes, planes, mesas, reportes |

## Instalacion

1. Requisitos: PHP 7.1+, MySQL 8+, Apache (WAMP/XAMPP), extensiones `mysqli`, `mbstring`, `gd`
2. Importar `shema_sistemasescolares.sql` en MySQL
3. Configurar `inicio/conexion.php` con credenciales de BD
4. Configurar `configMail.php` con datos SMTP
5. Ajustar `inicio/variablesParticulares.php` para la institucion
6. Ejecutar `composer install` para dependencias

## Principios de Desarrollo

1. **Consultas centralizadas**: toda consulta SQL va en `funciones/consultas.php`
2. **Prepared statements**: siempre usar `$stmt->bind_param()`, nunca concatenar SQL
3. **Verificacion de sesion**: incluir `verificarSesion.php` al inicio de cada pagina
4. **Orden de includes**: `verificarSesion.php` → `conexion.php` → `consultas.php` → archivos adicionales
5. **Paginas single-file**: cada pagina es un archivo PHP unico (logica + HTML)
6. **Sanitizacion**: validar y sanitizar todos los inputs

## Documentacion Tecnica para Agentes

Ver **[AGENTS.md](AGENTS.md)** para referencia tecnica detallada: esquema de BD, catalogo de funciones, variables de sesion, endpoints AJAX, reglas de negocio y convenciones de codigo.
