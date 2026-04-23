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
│   ├── verificarAccesoReporte.php # Autorizacion por rol en reportes PDF
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
├── index.php         # Redireccion a inicio/login.php
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

## Tests automatizados

- Documentación: **[docs/testing.md](docs/testing.md)** (variables de entorno, PHPUnit, HTTP opcional, smoke PDF).
- Checklist manual y matriz de riesgo: **[docs/QA-checklist.md](docs/QA-checklist.md)**.
- Base de datos de prueba: scripts `scripts/reset-test-db.ps1` (Windows) o `scripts/reset-test-db.sh` (Linux/macOS); seed en `tests/fixtures/seed_minimal.sql`.
- Ejecución rápida: `composer test` o `vendor\bin\phpunit` con `SESYSTEM_TEST_MODE=1` y MySQL accesible (ver `docs/testing.md`).
- **Todas las pruebas (incl. HTTP):** `composer run test:all` (levanta un `php -S` temporal; credenciales BD: ver `docs/testing.md`).
- E2E opcional (Playwright): **[e2e/README.md](e2e/README.md)**.

## Principios de Desarrollo

1. **Consultas centralizadas**: toda consulta SQL va en `funciones/consultas.php`
2. **Prepared statements**: siempre usar `$stmt->bind_param()`, nunca concatenar SQL
3. **Verificacion de sesion**: incluir `verificarSesion.php` al inicio de cada pagina
4. **Orden de includes**: `verificarSesion.php` → `conexion.php` → `consultas.php` → archivos adicionales
5. **Paginas single-file**: cada pagina es un archivo PHP unico (logica + HTML)
6. **Sanitizacion**: validar y sanitizar todos los inputs

## Convencion Frontend (CSS/JS)

- **Bootstrap CSS**: usar solo `css/material/bootstrap.min.css` (no duplicar con `css/bootstrap.min.css`).
- **CSS comun**: incluir `css/estilos.css` despues del tema Material.
- **Scripts base**: usar `js/jquery-3.7.1.js` + `js/bootstrap.bundle.js` (evitar `bootstrap.min.js`, `popper.min.js`, `jquery*.min.js` en paginas nuevas).
- **Footer comun**: incluir `../funciones/footer.html` al final del `body` en paginas de `alumnos/`, `docentes/` y `secretaria/`.
- **Menus**: `funciones/menu.php`, `funciones/menu_docente.php`, `funciones/menu_secretaria.php` se incluyen dentro del `body` y no deben volver a insertar hojas de estilo globales.

La consulta de claves de acceso web (`secretaria/verClaves.php`) esta restringida a usuarios de secretaria; la URL antigua en `docentes/` redirige alli.

**Contraseñas**: `funciones/password_web.php` centraliza `password_hash` / verificacion compatible con registros antiguos en texto plano. En el primer login valido se puede migrar el campo a hash. Usuarios administrativos (`usuarios.clave`) y cambios desde `cambiarClave.php` guardan hash. Las paginas bajo `secretaria/` incluyen `requerirSecretaria.php` para que solo personal con `sec_nombreUsuario` acceda (no alumnos/docentes con sesion abierta).

## Documentacion Tecnica para Agentes

Ver **[AGENTS.md](AGENTS.md)** para referencia tecnica detallada: esquema de BD, catalogo de funciones, variables de sesion, endpoints AJAX, reglas de negocio y convenciones de codigo.
