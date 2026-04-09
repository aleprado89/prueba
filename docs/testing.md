# Testing automatizado y entornos

Este proyecto usa **PHPUnit** para integración con MySQL, pruebas HTTP opcionales contra un servidor local y smoke de PDFs.

## Requisitos

- PHP 7.1+ con extensiones `mysqli`, `mbstring`, `dom`, `json`
- Composer
- MySQL 8+ (misma familia que producción)
- Para pruebas HTTP: servidor web apuntando al docroot del proyecto (por ejemplo `php -S 127.0.0.1:8080 -t c:\wamp64\www\prueba`)

## Instalación

```bash
composer install
```

## Base de datos de prueba

No ejecutar tests contra la base de producción.

1. Crear una base vacía, por ejemplo `sesystem_test`.
2. Importar el esquema: `shema_sistemasescolares.sql`.
3. Aplicar datos mínimos: `tests/fixtures/seed_minimal.sql`.

Scripts de ayuda:

- **Windows (PowerShell):** `scripts/reset-test-db.ps1`
- **Linux/macOS:** `scripts/reset-test-db.sh`

Ajustar variables `SESYSTEM_DB_*` o editar el script para tu usuario MySQL.

### WAMP y credenciales habituales

En entornos locales el archivo `inicio/conexion.php` suele usar usuario `se_user` y contraseña (por ejemplo la que ya tengas en WAMP). Para que PHPUnit se conecte a **`sesystem_test`**, ese mismo usuario MySQL debe tener permisos sobre esa base (el script de reset la crea vacía y vuelca esquema + seed).

Ejemplo en PowerShell antes de `phpunit` o `composer test`:

```powershell
$env:SESYSTEM_TEST_MODE='1'
$env:SESYSTEM_DB_USER='se_user'
$env:SESYSTEM_DB_PASSWORD='tu_clave'
$env:SESYSTEM_DB_NAME='sesystem_test'
```

Si usas `root` sin contraseña, define `SESYSTEM_DB_PASSWORD` como cadena vacía en PowerShell: `$env:SESYSTEM_DB_PASSWORD=''`.

## Variables de entorno

| Variable | Descripción |
|----------|-------------|
| `SESYSTEM_TEST_MODE` | `1` activa `inicio/conexion.test.php` (usado por PHPUnit). |
| `SESYSTEM_DB_HOST` | Host MySQL (default `127.0.0.1`). |
| `SESYSTEM_DB_USER` | Usuario (default `se_user`). |
| `SESYSTEM_DB_PASSWORD` | Contraseña. |
| `SESYSTEM_DB_NAME` | Base de datos (default `sesystem_test` en modo test). |
| `TEST_BASE_URL` | URL base **con barra final** para pruebas HTTP, ej. `http://127.0.0.1:8765/`. Si no está definida, esas pruebas se omiten. |
| `TEST_HTTP_PORT` | Solo para `scripts/run-phpunit-with-http.php`: puerto del servidor embebido (default `8765`). |

## Ejecutar PHPUnit

Desde la raíz del proyecto:

```bash
set SESYSTEM_TEST_MODE=1
set SESYSTEM_DB_NAME=sesystem_test
vendor\bin\phpunit
```

En PowerShell:

```powershell
$env:SESYSTEM_TEST_MODE='1'
$env:SESYSTEM_DB_NAME='sesystem_test'
.\vendor\bin\phpunit
```

### Incluir las pruebas HTTP (sin omitirlas)

Opción A — **recomendada**: un solo comando levanta `php -S`, define `TEST_BASE_URL` y ejecuta PHPUnit:

```powershell
$env:SESYSTEM_TEST_MODE='1'
$env:SESYSTEM_DB_NAME='sesystem_test'
# Ajustar usuario/clave si no son los por defecto de conexion.test.php
composer run test:all
```

Equivalente: `php scripts/run-phpunit-with-http.php` (puerto por defecto `8765`; variable opcional `TEST_HTTP_PORT`).

Opción B — servidor manual en otra terminal:

```powershell
php -S 127.0.0.1:8080 -t c:\wamp64\www\prueba
```

```powershell
$env:TEST_BASE_URL='http://127.0.0.1:8080/'
.\vendor\bin\phpunit
```

## Grupos de pruebas

- Sin grupo: pruebas que no requieren BD o que se omiten si no hay conexión.
- `@group integration`: requieren MySQL con esquema + seed.
- `@group http`: requieren `TEST_BASE_URL`.
- `@group pdf`: generación real de PDF vía inclusión de scripts en `reportes/` (requieren BD + sesión simulada).

## E2E con Playwright (opcional)

No forma parte del `composer install` por defecto. Ver `e2e/README.md` para evaluación local o job nocturno.

## Definition of Done (resumen)

- Cambios en lógica compartida (`funciones/consultas.php`, auth, sesión): añadir o actualizar prueba automatizada **o** entrada en `docs/QA-checklist.md` con ID de ticket.
- Regresión conocida: ticket de deuda técnica; no silenciar tests fallidos.

## Política de deuda de tests

- Si un cambio toca reglas de negocio o SQL compartido y no hay prueba nueva, debe existir un ticket explícito (severidad acordada con el equipo).
- Tests intermitentes: corregir, acotar o deshabilitar **con issue** enlazado; no ignorar sin registro.
- Revisión en PR: si el diff afecta `funciones/consultas.php`, `verificarSesion.php` o reportes PDF, revisar si la capa de test correspondiente (integración / HTTP / PDF) necesita actualización.

## CI (GitHub Actions)

El workflow `.github/workflows/phpunit.yml` ejecuta `composer install`, prepara MySQL, importa el esquema (sustituyendo el nombre `sesystem_prueba` por `sesystem_test`), aplica el seed y corre PHPUnit.
