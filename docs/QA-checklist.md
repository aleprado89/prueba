# Matriz de riesgo y checklist manual de QA

Versionado en el repositorio. Complementa las pruebas automatizadas (`docs/testing.md`).

## Matriz de riesgo por rol (prioridad)

| Prioridad | Flujo | Rol | Riesgo si falla | Cobertura automatizada sugerida |
|-----------|--------|-----|-----------------|----------------------------------|
| P1 | Login DNI/password (alumno/docente) y login admin (secretaría) | Todos | Acceso indebido o bloqueo masivo | HTTP + integración `password_web` |
| P1 | Sesión expirada / AJAX 401 JSON | Alumno/docente/secretaría | UX rota, datos inconsistentes | HTTP `X-Requested-With` sin cookie |
| P1 | `requerirSecretaria.php` — solo admin en `secretaria/` | Secretaría | Exposición de datos sensibles | HTTP + revisión de includes |
| P2 | Carga calificaciones/asistencias (docente y secretaría) | Docente/secretaría | Notas incorrectas | Integración DB + AJAX muestra |
| P2 | Inscripciones web (cursado/examen) y aprobación | Alumno/secretaría | Inscripciones erróneas | Integración + checklist |
| P2 | Permisos por formulario (`llavesxform`) | Secretaría | Escalada de privilegios | Manual / futura integración |
| P3 | Reportes PDF (listados, actas, asistencias) | Varios | Informes legales incorrectos | Smoke PDF (`%PDF`) |
| P3 | Correlatividades e `analisis_estado` | Alumno/secretaría | Reglas de negocio incorrectas | Integración focalizada |

## Checklist por rol (humo antes de release)

### Alumno

- [ ] Login con DNI válido; menú carga.
- [ ] Ver calificaciones y asistencias (al menos una materia).
- [ ] Solicitud de inscripción a materia o examen (si ventana abierta) o mensaje coherente si cerrada.
- [ ] Cambio de clave (si aplica) y logout.

### Docente

- [ ] Login; materias asignadas visibles.
- [ ] Abrir carga de calificaciones o asistencias de una materia; guardar un cambio de prueba en entorno de prueba.
- [ ] Logout.

### Secretaría

- [ ] Login admin; menú principal.
- [ ] Buscar un alumno y abrir legajo o datos básicos.
- [ ] Una operación AJAX representativa (por ejemplo listado o carga) sin error 500.
- [ ] Generar un PDF de prueba (listado o reporte acotado).
- [ ] Logout.

### Transversal

- [ ] Recuperación de contraseña (`forgot-password.php`) solo si SMTP está configurado.
- [ ] Navegación con sesión expirada: redirección a login o JSON 401 en AJAX.

## Registro de ejecución

| Fecha | Versión / commit | Ejecutó | Notas |
|-------|------------------|---------|-------|
| | | | |
