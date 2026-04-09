# E2E con Playwright (opcional)

Esta carpeta está reservada para pruebas end-to-end con [Playwright](https://playwright.dev/), alineado con la fase 4 del plan de testing (baja frecuencia / alto costo).

## Cuándo usarla

- Después de que la base de datos de prueba y PHPUnit (integración + smoke PDF) sean estables.
- Ejecución local o job CI **nocturno / pre-release**, no en cada push (salvo que el equipo decida lo contrario).

## Configuración mínima sugerida

Desde la raíz del proyecto (o dentro de `e2e/`):

```bash
npm init -y
npm install --save-dev @playwright/test
npx playwright install
```

Crear `playwright.config.ts` con `baseURL` apuntando al servidor local (por ejemplo `http://127.0.0.1:8080/` si usas `php -S`).

## Flujos mínimos recomendados (5–15)

1. Login alumno o docente (`inicio/login.php`) → menú correspondiente.
2. Login secretaría (`inicio/loginAdmin.php`) → `secretaria/menusecretaria.php`.
3. Una petición que dispare el patrón AJAX (cabecera `X-Requested-With`) con sesión válida.
4. Logout / destrucción de sesión.

## CI separado

Definir un workflow dedicado (por ejemplo `.github/workflows/e2e-playwright.yml`) que:

1. Levante PHP + MySQL (o use servicios existentes).
2. Ejecute `npx playwright test` en horario programado (`schedule`) o `workflow_dispatch`.

No forma parte del job `PHPUnit` por defecto para mantener feedback rápido en PRs.
