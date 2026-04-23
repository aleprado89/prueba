# Formularios y permisos de secretaría (v3)

## Fuente de verdad

- **Nombres y catálogo:** tabla `formularios` en MySQL (sin duplicar descripciones en PHP).
- **Whitelist de pantallas implementadas:** enteros en [`funciones/formulariosSecretaria.php`](../funciones/formulariosSecretaria.php) (`formulariosSecretariaV3Ids()`).
- **Id 14:** obsoleto; usar **70** para inscripción a examen web. La UI de permisos en `usuarios.php` no muestra el 14. Migración opcional: [`scripts/migrate_llavesxform_14_a_70.sql`](../scripts/migrate_llavesxform_14_a_70.sql).

## Tablas

| Tabla | Uso |
|-------|-----|
| `formularios` | `idformulario`, descripción `formulario` |
| `llavesgenerales` | 1 = Acceso total, 2 = Solo lectura, 3 = Según específicas |
| `llavesxform` | Por usuario: `idusuario`, `idformulario`, `idllavegral`, `idllave` (0/NULL salvo modo 3) |
| `llaves` | Permisos finos: `idllave`, texto `llave`, `idformulario`. Solo se usa con `idllavegral = 3`. |

Varias llaves específicas para el mismo formulario = **varias filas** en `llavesxform` con `idllavegral = 3` y distinto `idllave`.

Si un `idformulario` **no** tiene filas en `llaves`, solo aplica modo **1** o **2** (no modo 3).

## Resolución de permiso (implementación)

Funciones en [`funciones/consultas.php`](../funciones/consultas.php):

- `obtenerFormulariosPermisosV3($conn)` — filtra catálogo BD por whitelist y excluye 14.
- `obtenerPermisosUsuarioFormulario` — prioridad: si hay fila con gral **1** → total; si hay gral **3** → modo específico con unión de `idllave`; si solo **2** → lectura.
- `usuarioTieneAccesoFormularioSecretaria($conn, $idFormulario)` — `sec_tipoPermiso === 7` hace bypass (acceso total).
- `usuarioFormularioSecretariaEsSoloLectura`, `usuarioTieneLlaveEspecificaSecretaria`, `secretariaMenuMuestraFormulario` (menú).

## Pantalla por `idformulario`

| ID | PHP principal |
|----|----------------|
| 1 | `matriculacion.php` |
| 2 | `inscripcionMateria.php` |
| 3 | `listadoPorCurso.php`, `listadoPorMateria.php` (no `buscarAlumno` genérico) |
| 5 | `carga_calif_secretaria.php`, `carga_califxalumno_secretaria.php` |
| 6 | `carga_asist_secretaria.php` |
| 7 | `registrosPresistema.php` |
| 8 | Certificado regular: `buscarAlumno.php?origin=certificadoRegular`, `reportes/aluRegularPDF.php` |
| 9 | `equivalencias.php` |
| 10 | `mesasExamen.php` |
| 11 | `inscripcionExamenAlumno.php`, `inscripcionExamenMasivo.php` |
| 12 | `actas.php` |
| 20 | `buscarPersonal.php`, `legajoPersonal.php` |
| 22 | `listadoPersonal.php` |
| 24 | `asignarDocenteMateria.php` |
| 33 | `editParametrosWeb.php`, `parametrosPlanesEstudio.php`, `parametrosCursos.php` |
| 40 | `usuarios.php` (sigue control `tipoPermiso` 7) |
| 41 | `verClaves.php` |
| 70 | `solicitudesExamen.php` |
| 71 | `solicitudesCursado.php` |

Cableado: tras `consultas.php`, `define('ID_FORMULARIO_SECRETARIA', N);` y `require_once '../funciones/requerirPermisoFormulario.php';` (ver páginas ya actualizadas).

## Nuevos módulos

1. Revisar si existe fila en `formularios`.
2. Si no, agregar registro en `formularios` (y en `llaves` solo si habrá modo 3).
3. Añadir el id a `formulariosSecretariaV3Ids()` y mapear la pantalla en este documento.
4. Incluir `requerirPermisoFormulario.php` y actualizar [`menu_secretaria.php`](../funciones/menu_secretaria.php) si corresponde.
