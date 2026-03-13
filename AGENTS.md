# AGENTS.md - Referencia Tecnica para Agentes de IA

> Sistema de Informacion Pedagogica para Escuelas - v3.0.2
> PHP 7.1+ / MySQL 8.4 / Bootstrap 5 / jQuery 3.7.1

---

## A. Arquitectura y Flujos

### Subsistemas y Entry Points

```
inicio/login.php ──→ inicio/loginResult.php ──┬──→ alumnos/menualumnos.php
                                               ├──→ docentes/menudocentes.php
                                               └──→ inicio/seleccionar_rol.php (rol dual)

inicio/loginAdmin.php ──→ inicio/loginResultAdmin.php ──→ secretaria/menusecretaria.php
```

### Flujo de Autenticacion

**Alumnos/Docentes** (`login.php` → `loginResult.php`):
- Login por DNI + password
- Tablas: `passwords_alumnos` (alumnos por `idAlumno`), `passwords` (docentes por `legajo`)
- Password default alumnos: su DNI (se crea registro en `passwords_alumnos` si no existe)
- Password default docentes: variable `$CLAVE_DOCENTE_POR_DEFECTO` o campo `colegio.docenteModifica`
- Funciones: `verificarAccesoAlumno()`, `verificarAccesoDocente()`, `verificarAccesoAlumnoYDocente()`
- Si es ambos (alumno y docente): redirige a `seleccionar_rol.php`

**Secretaria** (`loginAdmin.php` → `loginResultAdmin.php`):
- Login por `nombreUsuario` + `clave` (tabla `usuarios`)
- Comparacion de password en texto plano: `$password == $row['clave']`
- Post-login: `session_regenerate_id(true)`, setea variables `sec_*`

### Patron de Includes (orden estandar)

Todo archivo PHP de pagina sigue este orden:

```php
include '../funciones/verificarSesion.php';  // 1. Siempre primero
include '../inicio/conexion.php';            // 2. Conexion BD
include '../funciones/consultas.php';        // 3. Funciones de consulta
// Opcionales segun necesidad:
include '../funciones/parametrosWeb.php';
include '../funciones/analisisestado.php';
include '../funciones/controlCorrelatividad.php';
include '../inicio/variablesParticulares.php';
// Menu segun rol:
include '../funciones/menu.php';             // alumnos
include '../funciones/menu_docente.php';     // docentes
include '../funciones/menu_secretaria.php';  // secretaria
// Al final del body:
include '../funciones/footer.html';
```

Reportes PDF agregan `require_once '../vendor/autoload.php'` antes de otros includes.

---

## B. Mapa Completo de Archivos

### `alumnos/` - Autogestion de Alumnos

| Archivo | Proposito |
|---------|-----------|
| `menualumnos.php` | Menu principal y dashboard del alumno |
| `calificaciones.php` | Vista general de calificaciones |
| `calificaciones_planes.php` | Calificaciones filtradas por plan de estudio |
| `calificaciones_verCalificaciones.php` | Detalle de calificaciones por materia |
| `calificaciones_verAsistencias.php` | Detalle de asistencias por materia |
| `materias_planes.php` | Planes disponibles para inscripcion a materias |
| `materias_materias.php` | Listado de materias para solicitar inscripcion |
| `materias_solicitar.php` | Procesa solicitud de inscripcion a materia |
| `materias_solicitudes_listado.php` | Lista solicitudes de cursado del alumno |
| `examenes_planes.php` | Planes disponibles para inscripcion a examen |
| `examenes_materias.php` | Materias con mesas de examen disponibles |
| `examenes_solicitar.php` | Procesa solicitud de inscripcion a examen |
| `examenes_solicitudes_listado.php` | Lista solicitudes de examen del alumno |
| `actuaDatosAlu.php` | Actualizar datos personales |
| `cambiarClave.php` | Cambiar password |
| `aluDebeFinal.php` | Lista de materias que el alumno debe como final |

### `docentes/` - Autogestion de Docentes

| Archivo | Proposito |
|---------|-----------|
| `menudocentes.php` | Menu principal del docente |
| `materiaxdocente.php` | Materias asignadas al docente |
| `carga_calif.php` | Carga de calificaciones por materia |
| `carga_asist.php` | Carga de asistencias por materia |
| `cargaActa.php` | Gestion de actas volantes de examen |
| `mesasExamenProf.php` | Mesas de examen asignadas al docente |
| `actuaDatosDoc.php` | Actualizar datos personales |
| `cambiarClave.php` | Cambiar password |
| `verClaves.php` | Ver/gestionar claves |

### `secretaria/` - Sistema Administrativo

| Archivo | Proposito |
|---------|-----------|
| `menusecretaria.php` | Menu principal de secretaria |
| `buscarAlumno.php` | Buscador de alumnos (hub: redirige segun `?origin=`) |
| `buscarPersonal.php` | Buscador de personal |
| `legajoAlu.php` | Legajo completo del alumno (datos, documentacion) |
| `legajoPersonal.php` | Legajo del personal |
| `matriculacion.php` | Matriculacion de alumno a plan/curso |
| `inscripcionMateria.php` | Inscripcion manual a materia |
| `inscripcionExamenAlumno.php` | Inscripcion individual a examen |
| `inscripcionExamenMasivo.php` | Inscripcion masiva a examen |
| `equivalencias.php` | Equivalencias y pases entre planes |
| `registrosPresistema.php` | Registros de regularidad/aprobacion anteriores al sistema |
| `carga_calif_secretaria.php` | Carga de calificaciones por materia |
| `carga_califxalumno_secretaria.php` | Carga de calificaciones por alumno |
| `carga_asist_secretaria.php` | Carga de asistencias |
| `mesasExamen.php` | ABM de mesas de examen |
| `actas.php` | Gestion de actas (notas de mesa) |
| `solicitudesExamen.php` | Recepcion de solicitudes web de examen |
| `solicitudesCursado.php` | Recepcion de solicitudes web de cursado |
| `asignarDocenteMateria.php` | Asignar docente a materia |
| `equiparDocenteMateria.php` | Equipar docente a materia |
| `materiaxsecretario.php` | Materias por secretario |
| `listadoPorCurso.php` | Listados filtrados por curso |
| `listadoPorMateria.php` | Listados filtrados por materia |
| `listadoPersonal.php` | Listado de personal |
| `parametrosCursos.php` | ABM de cursos |
| `parametrosPlanesEstudio.php` | ABM de planes de estudio |
| `editParametrosWeb.php` | Editar parametros web (fechas inscripcion, turnos) |
| `usuarios.php` | ABM de usuarios administrativos y permisos |

### `reportes/` - Generacion de PDF

Todos generan PDF via Dompdf. Patron de nombre: `[tipo][formato]PDF.php`

| Archivo | Contenido |
|---------|-----------|
| `actaVolantePDF.php` | Acta volante de examen |
| `aluRegularPDF.php` | Certificado de alumno regular |
| `asistenciaAluPDF.php` | Asistencia del alumno |
| `asistenciaDocPDF.php` | Asistencia para docente |
| `calificacionesDocPDF.php` | Calificaciones para docente |
| `PDFcalificacionesAlumno.php` | Calificaciones del alumno |
| `PDFaluDebeFinal.php` | Alumnos que deben final |
| `listadoCurso[Cuadriculado/Dni/Renglon]PDF.php` | Listado por curso (3 formatos) |
| `listadoMateria[Cuadriculado/Dni/Renglon]PDF.php` | Listado por materia (3 formatos) |
| `listadoPersonal[Cuadriculado/Dni/Renglon]PDF.php` | Listado de personal (3 formatos) |
| `solicitudesCursPDF.php` | Solicitudes de cursado |
| `solicitudesExamPDF.php` | Solicitudes de examen |

### `funciones/` - Logica Compartida

| Archivo | Proposito |
|---------|-----------|
| `consultas.php` | **TODAS** las consultas a BD (~120 funciones, ~5760 lineas) |
| `analisisestado.php` | Calculo de estado de cursado (6 funciones, ~3580 lineas) |
| `controlCorrelatividad.php` | Control de correlatividades para inscripciones (12 funciones) |
| `verificarSesion.php` | Verificacion de sesion, redireccion, respuesta 401 AJAX |
| `cerrarsesion.php` | Destruye sesion, limpia cookies, redirige |
| `parametrosWeb.php` | Carga parametros del colegio desde BD (usa `obtenerParametrosColegio()`) |
| `menu.php` | Navbar para alumnos |
| `menu_docente.php` | Navbar para docentes (con AJAX para ciclo lectivo) |
| `menu_secretaria.php` | Navbar para secretaria (con dropdowns por seccion) |
| `footer.html` | Footer comun |
| `sessionControl.js` | Timeout de inactividad (15 min), logout multi-tab |
| `CORRECCION_ESTADOS.php` | Script de mantenimiento para corregir estados |
| `prueba.php` / `pruebaSession.php` | Archivos de desarrollo/testing |

### `inicio/` - Autenticacion y Configuracion

| Archivo | Proposito |
|---------|-----------|
| `conexion.php` | Conexion MySQLi (`localhost`, `se_user`, `sesystem_prueba`, `utf8mb4`) |
| `login.php` | Formulario login alumnos/docentes |
| `loginAdmin.php` | Formulario login secretaria |
| `loginResult.php` | Procesa login alumno/docente, setea sesion |
| `loginResultAdmin.php` | Procesa login admin, setea sesion |
| `seleccionar_rol.php` | Selector de rol para usuarios duales |
| `variablesParticulares.php` | Configuracion por institucion (logos, flags, documentacion) |

### Raiz

| Archivo | Proposito |
|---------|-----------|
| `configMail.php` | Configuracion SMTP (PHPMailer) |
| `forgot-password.php` | Recuperacion de password por email |
| `autoload.inc.php` | Carga autoloader de Composer |
| `shema_sistemasescolares.sql` | Esquema completo de la BD |
| `VERSION` | Version del sistema (3.0.2) |

---

## C. Esquema de Base de Datos

BD: `sesystem_prueba` | MySQL 8.4 | Charset: `utf8mb4` | 67 tablas

### Tablas Core (uso frecuente)

#### `persona` - Datos personales compartidos (alumnos y personal)
```
PK: idPersona (int, AUTO_INCREMENT)
Campos: apellido, nombre, dni, sexo, fechaNac, nacionalidad, lugarNac, provincia,
        ciudad, direccion, barrio, mail, telefono, celular, cuilPre, cuilPost,
        telefonoEmergencia, codigoPostal, FotoCarnet
Flags: registroModificacion, registroNuevo
```

#### `alumnosterciario` - Datos especificos del alumno
```
PK: idAlumno (int, AUTO_INCREMENT)
FK: idPersona → persona.idPersona
Campos: nombrePadre, nombreMadre, ocupacionPadre, ocupacionMadre, vivePadre, viveMadre,
        estudiosPadre, estudiosMadre, telefonoPadre, telefonoMadre, mailPadre, mailMadre,
        tituloIngreso, carreraAnterior, colegioProcedencia, anioIngreso, beca, legajo,
        alta, baja, nroMatri, observaciones, egresado, foto, documentacion, trabaja,
        idFamilia, materiasAdeuda, retiroBiblioteca, mailInstitucional
Flags: registroModificacion, registroNuevo
```

#### `personal` - Datos del personal docente/administrativo
```
PK: legajo (int, AUTO_INCREMENT)
FK: idPersona → persona.idPersona
Campos: estadoCivil, tipoCargo, cargo, titulo, legJunta, legEscuela, escalafD, escalafE,
        numReg, apto, certArt28, incapac, actual (1=activo), nivel, fechaBaja,
        tipoTitulo, mailInst
Flags: registroModificacion, registroNuevo
```

#### `materiaterciario` - Materias del nivel terciario
```
PK: idMateria (int, AUTO_INCREMENT)
Campos: idUnicoMateria (identificador unico cross-ciclo), nombre, idPlan, idCicloLectivo,
        idCurso, ubicacion, idTipoMateria, horasCatedra, idtipocursado,
        calificacionRegular, calificacionPromocion, calificacionTrabajo,
        asistenciaRegular, asistenciaPromocion, calificacionExamen,
        asistenciaRegularRed, asistenciaPromocionRed,
        fechaInicio, fechaFin, cantidadTurnosLibre, cantidadTurnosRegular, hsSinAp
```
**Nota**: `idUnicoMateria` identifica la misma materia a traves de distintos ciclos lectivos. `idMateria` es unico por ciclo.

#### `calificacionesterciario` - Calificaciones por alumno y materia
```
PK: idCalificacion (int, AUTO_INCREMENT)
FK: idAlumno → alumnosterciario.idAlumno, idMateria → materiaterciario.idMateria
Notas: n1..n8 (calificaciones parciales), f1..f8 (fechas), r1..r8 (recuperatorios)
       asistencia (porcentaje o texto), sinAsistencia, examenIntegrador, coloquio,
       estadoCursadoNumero (0-15), estadoCursado (texto), materiaAprobada (0/1),
       idInscripcionExamen (vinculo con examen aprobado), tmp_apenom (cache nombre)
Flags: registroModificacion, registroNuevo
```

#### `asistenciaterciario` - Asistencia diaria por materia
```
PK: idAsistenciaTerciario (int, AUTO_INCREMENT)
FK: idAlumno → alumnosterciario, idMateria → materiaterciario, idCicloLectivo → ciclolectivo
Campos: mes, d1..d31 (varchar, un campo por dia del mes), tmp_apenom
```

#### `curso` - Cursos (instancia de cursoPredeterminado para un ciclo/plan)
```
PK: idCurso (int, AUTO_INCREMENT)
FK: idNivel → nivel, idciclo → ciclolectivo, idcursopredeterminado → cursospredeterminado
Campos: nombre, idPlanEstudio, idTurnoCurso, cursoPrincipal (1=principal), idDivision
```

#### `plandeestudio` - Planes de estudio
```
PK: idPlan (int, AUTO_INCREMENT)
Campos: idNivel, numero, nombre, cursado, resolucion
```

#### `matriculacion` - Matriculacion de alumno a plan/curso
```
PK: idMatriculacion (int, AUTO_INCREMENT)
FK: idCurso → curso, idNivel → nivel
Campos: idAlumno, fechaMatriculacion, anio, estado, idPlanDeEstudio,
        pagoMatricula, certificadoSalud, fechaBajaMatriculacion
```

#### `matriculacionmateria` - Inscripcion de alumno a materia
```
PK: idMatriculacionMateria (int, AUTO_INCREMENT)
FK: idAlumno → alumnosterciario
Campos: idNivel, idMateria, fechaMatriculacion, fechaBajaMatriculacion,
        estado (texto: 'Activo', 'Regularidad PreSistema', 'Aprobación PreSistema',
                'Aprobación por Equivalencia', 'Aprobación por Pase', etc.),
        idCicloLectivo
```

#### `inscripcionexamenes` - Inscripciones a mesa de examen
```
PK: idInscripcion (int, AUTO_INCREMENT)
Campos: idAlumno, idMateria, idCicloLectivo, idFechaExamen, oral, escrito,
        calificacion, libro, folio, idCondicion, observaciones
```

#### `fechasexamenes` - Mesas de examen
```
PK: idFechaExamen (int, AUTO_INCREMENT)
Campos: idMateria, idTurno, idCicloLectivo, fecha, hora,
        p1..p7 (idPersona de docentes: titular + 6 vocales)
```

#### `inscripcionexamenes_web` - Solicitudes web de examen (pendientes de aprobacion)
```
PK: id_Inscripcion_web (int, AUTO_INCREMENT)
Campos: idAlumno, idMateria, idCicloLectivo, idFechaExamen, idCondicion,
        estado (-1=pendiente, 1=pendiente confirmada, 2=aceptada, 3=rechazada),
        observaciones, fechhora_inscri, fechhora_proces
```

#### `matriculacionmateria_web` - Solicitudes web de cursado
```
PK: id_matriculacion_web (int, AUTO_INCREMENT)
Campos: idAlumno, idMateria, idCicloLectivo, condicion,
        estado (-1/1=pendiente, 2=aceptada, 3=rechazada),
        observaciones, fechhora_inscri, fechhora_proces
```

#### `usuarios` - Usuarios administrativos (secretaria)
```
PK: idusuarios (int, AUTO_INCREMENT)
Campos: nombreUsuario, clave (texto plano), tipoPermiso (varchar),
        idnivel (6=terciario)
Nota: tipoPermiso=7 da acceso a gestion de usuarios
```

#### `passwords` - Passwords de docentes
```
PK: id (int, AUTO_INCREMENT)
FK: legajo → personal.legajo
Campos: idPersona, password, pp_user, webparam
```

#### `passwords_alumnos` - Passwords de alumnos
```
PK: id (int, AUTO_INCREMENT)
Campos: idAlumno, password, pp_user
```

#### `colegio` - Configuracion del colegio
```
Sin PK explicita (registro unico por codnivel)
Campos: nombreColegio, direccion, numCuenta, cuit, localidad, repLegal, nivel, codcol,
        anioautoweb, anio_carga_notas, iDturnoautoweb,
        inscExamDesde/Hasta, inscCursDesde/Hasta (datetime - ventanas de inscripcion),
        inscExamLectDesde, inscCursLectDesde,
        cargaActaVolDesde/Hasta/Turno,
        codnivel (filtro: '6' para terciario), passLegajoWeb,
        docenteModifica (flag), licencia
```

#### `ciclolectivo` - Ciclos lectivos (anios)
```
PK: idciclolectivo (int, AUTO_INCREMENT)
Campos: anio
```

### Tablas de Soporte

| Tabla | Proposito | Campos clave |
|-------|-----------|--------------|
| `alumno` | Alumnos nivel no-terciario | idAlumno, idPersona |
| `condicion` | Condiciones de examen | idCondicion, condicion (Regular, Libre, etc.) |
| `condicionescursado` | Condiciones de cursado | idCondicion, condicion |
| `correlatividadesterciario` | Correlatividades entre materias | idUnicoMateria, idUnicoMatCorrelativa, condicionCorrelatividad (0=regular, 1=aprobado), tipoInscripcion (0=cursado, 1=examen), grupal |
| `cursospredeterminado` | Plantillas de curso (ej: 1er Anio, 2do Anio) | idcursopredeterminado, idNivel, nombre |
| `division` | Divisiones (A, B, etc.) | idDivision, nombre |
| `turnoscursos` | Turnos de cursado | idTurnoCurso, nombre |
| `turnosexamenes` | Turnos de examen | idTurno, nombre |
| `nivel` | Niveles educativos (6=terciario) | idNivel, nombre |
| `profesorxmateria` | Asignacion docente-materia | idProfXMat, idMateria, idPersonal, tipo |
| `certificacion` | Cargos y certificaciones del personal | idcertificacion, idpersonal, cargo, titularSuplente |
| `licencias` | Licencias del personal | idlicencias, idPersonal, fechaInicio, fechaFin |
| `descripcioncalificaciones` | Descripciones de columnas de calificacion por materia | idMateria, n1..n8, r1..r8, examenIntegrador |
| `resoluciones` | Resoluciones de equivalencias/pases | idResolucion, tipoResolucion, nombre, procedencia, idReferencia |
| `tipomateria` | Tipos de materia | idTipoMateria, nombre |
| `tipocursadoxmateria` | Tipos de cursado | idtipocursadoxmateria, tipocursado, cursado |
| `documentacionalumnos` | Documentos digitales de alumnos | id, documento, idAlumno, nombre, tipoArchivo |
| `historialcarreras` | Historial de carreras del alumno | idHistorialCarrera, idPlan, idAlumno, libro, folio |
| `cursadofinalizado` | Registro de cursado finalizado | idAlumno, idPlan, idCicloLectivo, intencionExamen |
| `log` | Log de acciones | idlog, fecha, idAlumno, usuario, descripcion |
| `formularios` | Formularios del sistema (para permisos) | idformulario, formulario |
| `llaves` | Permisos especificos por formulario | idllave, llave, idformulario |
| `llavesgenerales` | Tipos de acceso (Total/Lectura/Especifica) | idllavegral, llavesgenerales |
| `llavesxform` | Permisos asignados a usuarios | idusuario, idformulario, idllave, idllavegral |

### Tablas Legacy / No usadas activamente en terciario

`alumno`, `asistencia`, `calificaciones`, `materia`, `cursoxmateria`, `correlatividades`, `calculopromedios` (nivel no-terciario), `cuotas`, `cuotasgeneradas`, `cupones`, `planpagos`, `referenciacuotas`, `importexcurso`, `creditos`, `informemorosos`, `correo`, `familia`, `respaldo`, `habilitartrigger`, `consultas`, `horarios`, `horariosmaterias`, `fechascalendario`, `cursossecuencia`, `planabreviado`, `usuariospredeterminado`.

---

## D. Catalogo de Funciones

### `funciones/consultas.php` (~120 funciones)

#### Parametros y Configuracion
| Funcion | Descripcion |
|---------|-------------|
| `obtenerParametrosColegio($conn, $codNivel=6)` | Obtiene config del colegio (fechas inscripcion, turnos, anio) |
| `levantarCiclosLectivos($conexion)` | Lista todos los ciclos lectivos |
| `obtenerCiclosLectivos($conexion)` | Obtiene ciclos lectivos ordenados |
| `obtenerUltimoCicloLectivo($conexion)` | Ultimo ciclo lectivo por anio |
| `buscarIdCiclo($conexion, $anio)` | Busca idCicloLectivo por anio |
| `buscarnombreCiclo($conexion, $idciclo)` | Nombre del ciclo por ID |
| `obtenerTurnosCursos($conexion)` | Lista turnos de cursos |
| `obtenerDivisiones($conexion)` | Lista divisiones |
| `obtenerCondicionesCursado($conexion)` | Lista condiciones de cursado |
| `obtenerCondicionesExamen($conexion)` | Lista condiciones de examen |
| `obtenerTodosTurnos($conn)` | Lista turnos de examen |

#### Planes de Estudio
| Funcion | Descripcion |
|---------|-------------|
| `buscarTodosPlanes($conexion)` | Lista todos los planes |
| `buscarPlanes($conexion, $idAlumno)` | Planes en que esta matriculado un alumno |
| `buscarIdPlan($conexion, $plan)` | Busca idPlan por numero de plan |
| `buscarNombrePlan($conexion, $idPlan)` | Nombre del plan por ID |
| `obtenerPlanesDeEstudio($conexion)` | Todos los planes |
| `obtenerPlanesDeEstudioPorNivel($conexion, $idNivel=6)` | Planes filtrados por nivel |
| `crearPlanDeEstudio($conexion, $numero, $nombre, $cursado, $resolucion, $idNivel)` | Crear plan |
| `actualizarPlanDeEstudio($conexion, $idPlan, ...)` | Actualizar plan |
| `eliminarPlanDeEstudio($conexion, $idPlan)` | Eliminar plan (verifica dependencias) |
| `planDeEstudioTieneCursos($conexion, $idPlan)` | Verifica si plan tiene cursos asociados |

#### Cursos
| Funcion | Descripcion |
|---------|-------------|
| `obtenerCursosPredeterminadosPorNivel($conexion, $idNivel=6)` | Plantillas de cursos |
| `obtenerCursosPorNivel($conexion, $idNivel=6, $idCiclo=0)` | Cursos por nivel y ciclo |
| `buscarCursosPlanCiclo($conexion, $idPlan, $idCiclo)` | Cursos de un plan en un ciclo |
| `buscarCursoPredeterminado($conexion, $idPlan)` | Cursos predeterminados de un plan |
| `buscarCursoMatriculado($conexion, $idPlan, $idAlumno)` | Curso donde esta matriculado el alumno |
| `generarNombreCurso($conexion, $idCursoPred, $idDiv, $idTurno)` | Genera nombre de curso compuesto |
| `existeCursoPrincipalReferente($conexion, $idPlan, $idCursoPred, $excluir)` | Verifica existencia curso principal |
| `crearCurso($conexion, ...)` | Crea curso |
| `actualizarCurso($conexion, ...)` | Actualiza curso |
| `eliminarCurso($conexion, $idCurso, &$error)` | Elimina curso (verifica dependencias) |
| `cursoTieneMaterias($conexion, $idCurso)` | Verifica si curso tiene materias |
| `getCursosPorPlan($conn, $idPlan)` | Cursos de un plan |

#### Materias
| Funcion | Descripcion |
|---------|-------------|
| `buscarMaterias($conexion, $idAlumno, $idPlan)` | Materias del curriculo con estado de cursado |
| `buscarMateriasCurso($conexion, $idAlumno, $idPlan, $idCursoPred)` | Materias de un curso especifico |
| `buscarMateriasAdeuda($conexion, $ciclo, $idAlumno, $idPlan, $idCurso)` | Materias que adeuda |
| `materiasPlanCurso($conexion, $idPlan, $idCurso)` | Materias de plan/curso |
| `obtenerTodasLasMaterias($conn, $idCiclo, $idPlan, $idCurso=null)` | Todas las materias con filtros |
| `obtenerMateriasDeAlumnoEnCiclo($conexion, $idAlumno, $idCiclo)` | Materias del alumno en un ciclo |
| `obtenerNombreMateria($conexion, $idMateria)` | Nombre de materia por ID |
| `getMateriasPorCurso($conn, $idCurso)` | Materias de un curso |
| `materiasAlumnoCurso($conn, $idAlumno, $idPlan, $idCursoPred)` | Materias con estado para alumno |

#### Calificaciones
| Funcion | Descripcion |
|---------|-------------|
| `estadoPlan($conexion, $idAlumno, $idPlan, $ciclo)` | Estado de cursado por plan |
| `cursadoMateria($conexion, $idMateria, $idAlumno)` | Estado de cursado de una materia |
| `obtenerCalificacionesMateria($conexion, $idMateria)` | Calificaciones de todos los alumnos en materia |
| `obtenerCalificacionesMateriaPDF($conexion, $idMateria)` | Idem, formato PDF |
| `actualizarCalifDocente($conexion, $idCalif, $columna, $valor)` | Actualiza calificacion (docente) |
| `actualizarCalifSecretaria($conexion, $idCalif, $columna, $valor)` | Actualiza calificacion (secretaria, whitelist) |
| `obtenerIdCalificacion($conexion, $idAlumno, $idMateria)` | ID calificacion por alumno/materia |
| `obtenerMateriasConCalificacionesPorAlumno($conexion, $idAlumno)` | Calificaciones completas del alumno |
| `obtenerPlanesDeAlumnoConCalificaciones($conexion, $idAlumno)` | Planes donde el alumno tiene calificaciones |
| `inicializarCalificacionMateria($conexion, $idAlumno, $idMateria)` | Crea registro vacio de calificacion |
| `eliminarCalificacionMateria($conexion, $idAlumno, $idMateria)` | Elimina registro de calificacion |

#### Asistencia
| Funcion | Descripcion |
|---------|-------------|
| `obtenerAsistenciaMateria($conexion, $idMateria, $mes, $dia, $idCiclo)` | Asistencia de materia (docente) |
| `obtenerAsistenciaMateriaSecretaria($conexion, $idMateria, $mes, $dia, $idCiclo)` | Asistencia (secretaria) |
| `obtenerAsistenciaMateriaPDF($conexion, $columnas, $idMateria, $mes, $idCiclo)` | Asistencia formato PDF |
| `actualizarAsistxDocentes($conexion, $idAlumno, $idCiclo, $mes, $dia, $valor, $idMateria)` | Actualiza asistencia |
| `obtenerMesesConAsistenciaMateria($conn, $idAlumno, $idMateria, $idCiclo)` | Meses con registros |
| `obtenerAsistenciaRegistroMateriaMes($conn, $idAlumno, $idMateria, $mes, $idCiclo)` | Registro de un mes |
| `obtenerAsistenciaDeMateriaParaAlumno($conexion, $idAlumno, $idMateria, $mes, $idCiclo)` | Asistencia alumno/materia |
| `inicializarAsistenciaMateria($conexion, $idAlumno, $idMateria, $idCiclo)` | Crea registros vacios (12 meses) |
| `eliminarAsistenciaMateria($conexion, $idAlumno, $idMateria, $idCiclo)` | Elimina registros de asistencia |

#### Examenes
| Funcion | Descripcion |
|---------|-------------|
| `buscarExamenes($conexion, $idAlumno, $idMateria)` | Historial de examenes del alumno |
| `buscarFechasExamenTurno($conexion, $idMateria, $idCiclo, $idTurno, $idDiv)` | Fechas de examen disponibles |
| `obtenerFechasExamenProfesor($conexion, $idPersonal, $idCiclo, $idTurno, $idPlan)` | Mesas del profesor |
| `buscarNombreTurno($conexion, $idTurno)` | Nombre del turno |
| `obtenerActa($conexion, $idFechaExamen)` | Acta de examen (alumnos inscriptos) |
| `obtenerDetalleActaCompleto($conn, $idFechaExamen)` | Acta completa (cabecera + alumnos) |
| `actualizarNotaInscripcion($conn, $idInscripcion, $campo, $valor)` | Actualiza nota de examen |
| `actualizarDatoActa($conn, $idInscripcion, $campo, $valor)` | Actualiza dato de acta (whitelist) |
| `obtenerIdMateriaPorFechaExamen($conn, $idFechaExamen)` | ID materia de una fecha de examen |
| `buscarMesasExamen($conn, $idCiclo, $idTurno, $idMateria)` | Busca mesas por filtros |
| `filtrarMesasExamen($conn, $idCiclo, $idTurno, $idPlan, $idCurso, $idMateria)` | Filtra mesas con filtros dinamicos |
| `crearMesaExamen($conn, $idMateria, $idTurno, $idCiclo, $fecha, $hora)` | Crea mesa de examen |
| `actualizarMesaExamen($conn, $idFechaExamen, $fecha, $hora)` | Actualiza fecha/hora mesa |
| `asignarDocentesMesa($conn, $idFechaExamen, $p1...$p7)` | Asigna 7 docentes a mesa |
| `eliminarInscripcionesPorMesa($conn, $idFechaExamen)` | Elimina inscripciones de una mesa |
| `eliminarFechaExamen($conn, $idFechaExamen)` | Elimina mesa |

#### Inscripciones (Web y Manual)
| Funcion | Descripcion |
|---------|-------------|
| `solicitarExamen($conexion, $idAlumno, $idMateria, $idCiclo, $idFechaExamen)` | Solicitud web examen |
| `cancelarExamen($conexion, $idInscripcionWeb)` | Cancela solicitud web examen |
| `existeSolicitudExamen($conexion, $idAlumno, $idMateria, $idCiclo, $idTurno)` | Verifica si existe solicitud |
| `buscarSolicitudesExamen($conexion, $idAlumno, $idPlan, $idCiclo, $idTurno)` | Lista solicitudes examen |
| `solicitarCursado($conexion, $idAlumno, $idMateria, $idCiclo, $condicion)` | Solicitud web cursado |
| `cancelarCursado($conexion, $idMatriculacionWeb)` | Cancela solicitud web cursado |
| `existeSolicitudMateria($conexion, $idAlumno, $idMateria, $idCiclo)` | Verifica si existe solicitud |
| `estaMatriculadoEnMateria($conn, $idAlumno, $idMateria, $idCiclo)` | Verifica matriculacion |
| `buscarSolicitudesMateria($conexion, $idAlumno, $idPlan, $idCiclo)` | Lista solicitudes cursado |
| `inscribirAlumnoExamen($conexion, ..., $idTurno)` | Inscripcion manual a examen (verifica duplicados) |
| `obtenerInscripcionesTurno($conexion, $idAlumno, $idTurno, $idCiclo)` | Inscripciones de un turno |
| `eliminarInscripcionExamen($conexion, $idInscripcion)` | Elimina inscripcion examen |
| `inscribirAlumnosMasivo($conn, $listaAlumnos, $idFechaExamen, $condicion)` | Inscripcion masiva |
| `buscarAlumnosAptosPorCondicion($conn, $idUnicoMateria, $idCurso, $condicion)` | Alumnos aptos para examen |
| `obtenerIdEstadoCursada($conn, $idAlumno, $idUnicoMateria)` | Estado de cursada numerico |
| `obtenerCondicionExamen($conn, $idAlumno, $idUnicoMateria)` | Condicion de examen por estado |
| `verificarInscripcionExistente($conn, $idAlumno, $idFechaExamen)` | Verifica duplicado |

#### Solicitudes Web (Administracion)
| Funcion | Descripcion |
|---------|-------------|
| `buscarSolicitudesExamenWeb($conn, $idCiclo, $idTurno, $idPlan, ...)` | Busca solicitudes web examen |
| `obtenerFechasAlternativas($conn, $idMateria, $idCiclo, $idTurno)` | Fechas alternativas para mesa |
| `obtenerDatosSolicitudWeb($conn, $idInscripcionWeb)` | Datos de solicitud para procesar |
| `actualizarEstadoSolicitudWeb($conn, $idInscWeb, $estado, $idCondicion, $obs)` | Acepta/rechaza solicitud examen |
| `buscarSolicitudesCursadoWeb($conn, $idCiclo, $idPlan, ...)` | Busca solicitudes web cursado |
| `obtenerSolicitudCursadoWebPorId($conn, $idMatriculacionWeb)` | Detalle solicitud cursado |
| `actualizarEstadoSolicitudCursadoWeb($conn, $idMatriculacionWeb, $estado, $obs)` | Acepta/rechaza solicitud cursado |

#### Matriculacion
| Funcion | Descripcion |
|---------|-------------|
| `insertarMatriculacionPlan($conexion, $data)` | Matricula alumno en plan/curso |
| `obtenerMatriculacionesPlanAlumno($conexion, $idAlumno)` | Lista matriculaciones de plan |
| `actualizarMatriculacionPlan($conexion, $idMatriculacion, $data)` | Actualiza matriculacion |
| `eliminarMatriculacionPlan($conexion, $idMatriculacion)` | Elimina matriculacion |
| `insertarMatriculacionMateria($conexion, $data)` | Inscribe a materia (con init asistencia/calificacion) |
| `obtenerMatriculacionesMateriaAlumno($conexion, $idAlumno, $idPlan, $idCurso)` | Lista inscripciones |
| `actualizarMatriculacionMateria($conexion, $idMatriculacionMateria, $data)` | Actualiza inscripcion |
| `eliminarMatriculacionMateria($conexion, $idMatriculacionMateria)` | Elimina inscripcion |
| `obtenerDetallesMatriculacionMateria($conexion, $idMatriculacionMateria)` | Detalles para eliminacion |
| `obtenerPlanesMatriculadosPorAnio($conexion, $idAlumno, $anio)` | Planes matriculados por anio |
| `selectCursadoFinalizadoByIdPlan($conexion, $idAlumno)` | Cursados finalizados |
| `insertarCursadoFinalizado($conexion, ...)` | Registra cursado finalizado |
| `updateCursadoFinalizado($conexion, ...)` | Actualiza cursado finalizado |
| `actualizarAbandonoCursado($conexion, $idAlumno, $idMateria, $estado)` | Marca abandono |

#### Alumnos y Personal (CRUD)
| Funcion | Descripcion |
|---------|-------------|
| `buscarAlumnos($conexion, $apellido, $nombre)` | Busca alumnos por nombre/apellido |
| `obtenerDatosAlumno($conexion, $idAlumno)` | Datos completos del alumno |
| `obtenerDatosBasicosAlumno($conexion, $idAlumno)` | Apellido, nombre, DNI |
| `insertPersona($conexion, $data)` | Inserta persona |
| `updatePersona($conexion, $idPersona, $data)` | Actualiza persona |
| `insertAlumnoTerciario($conexion, $idPersona, $data)` | Inserta alumno terciario |
| `updateAlumnoTerciario($conexion, $idPersona, $data)` | Actualiza alumno terciario |
| `dniExiste($conexion, $dni, $excludeIdPersona)` | Verifica DNI duplicado |
| `buscarPersonal($conexion, $apellido, $nombre)` | Busca personal |
| `obtenerDatosPersonal($conexion, $legajo)` | Datos del personal |
| `insertPersonal($conexion, $idPersona, $data)` | Inserta personal |
| `updatePersonal($conexion, $legajo, $data)` | Actualiza personal |
| `obtenerPersonalActivoLegajo($conexion)` | Personal activo |
| `obtenerPersonalParaListado($conexion, $soloActivos)` | Personal para listados |
| `obtenerAlumnosPorCurso($conexion, $idCurso, $idCiclo)` | Alumnos de un curso |
| `obtenerAlumnosPorMateria($conexion, $idMateria)` | Alumnos de una materia |
| `obtenerNombreDocente($conn, $legajo)` | Nombre docente por idPersona |
| `obtenerDocentesActivos($conn)` | Lista docentes activos |

#### Docente-Materia
| Funcion | Descripcion |
|---------|-------------|
| `obtenerMateriasxProfesor($conexion, $legajo, $idCiclo, $idPlan)` | Materias del profesor |
| `buscarPlanesProfesorMateria($conexion, $legajo)` | Planes donde el profesor tiene materias |
| `obtenerAsignacionesProfesorMateria($conexion, $idMateria)` | Docentes asignados a materia |
| `insertProfesorMateria($conexion, $idMateria, $idPersonal, $tipo)` | Asigna docente |
| `updateProfesorMateria($conexion, $idProfXMat, $idPersonal, $tipo)` | Actualiza asignacion |
| `deleteProfesorMateria($conexion, $idProfXMat)` | Elimina asignacion |

#### Presistema y Equivalencias
| Funcion | Descripcion |
|---------|-------------|
| `crearInscripcionExamenPresistema($conn, ...)` | Crea registro examen presistema |
| `insertarPresistemaRegular($conn, ...)` | Inserta regularidad presistema (3 tablas) |
| `insertarPresistemaAprobado($conn, ...)` | Inserta aprobacion presistema (3 tablas) |
| `obtenerRegistrosPresistema($conexion, $idAlumno)` | Lista presistemas del alumno |
| `obtenerDetallesPresistema($conn, $idMatriculacionMateria)` | Detalles para edicion |
| `eliminarPresistema($conn, $idMatriculacionMateria)` | Elimina presistema (3 tablas, transaccion) |
| `actualizarPresistemaRegular($conn, ...)` | Actualiza regular |
| `actualizarPresistemaAprobado($conn, ...)` | Actualiza aprobado |
| `convertirPresistemaRegularAAprobado($conn, ...)` | Convierte regular a aprobado |
| `checkMatriculacionMateriaExiste($conexion, $idAlumno, $idMateria)` | Verifica duplicado por idUnicoMateria |
| `insertarEquivalencia($conn, $data)` | Inserta equivalencia/pase (3 tablas) |
| `obtenerEquivalenciasAlumno($conexion, $idAlumno)` | Lista equivalencias |
| `obtenerDetallesEquivalencia($conn, $idMatriculacionMateria)` | Detalles equivalencia |
| `eliminarEquivalencia($conn, $idMatriculacionMateria)` | Elimina equivalencia (3 tablas) |
| `actualizarEquivalencia($conn, $data)` | Actualiza equivalencia |

#### Usuarios y Permisos
| Funcion | Descripcion |
|---------|-------------|
| `obtenerUsuariosAdmin($conexion)` | Lista usuarios admin |
| `obtenerUsuarioAdminPorId($conexion, $idUsuario)` | Usuario por ID |
| `existeNombreUsuarioAdmin($conexion, $nombre, $excludeId)` | Verifica duplicado |
| `crearUsuarioAdmin($conexion, $nombre, $clave)` | Crea usuario (tipoPermiso=0, idnivel=6) |
| `actualizarUsuarioAdmin($conexion, $idUsuario, $nombre, $clave)` | Actualiza usuario |
| `eliminarUsuarioAdmin($conexion, $idUsuario)` | Elimina usuario + permisos |
| `obtenerFormulariosPermisos($conexion)` | Lista formularios |
| `obtenerLlavesGenerales($conexion)` | Lista tipos acceso (Total/Lectura/Especifica) |
| `obtenerLlavesPorFormulario($conexion, $idFormulario)` | Llaves de un formulario |
| `obtenerPermisosUsuarioFormulario($conexion, $idUsuario, $idForm)` | Permisos de usuario |
| `guardarPermisosUsuarioFormulario($conexion, $idUsuario, $idForm, $idLlaveGral, $idsLlaves)` | Guarda permisos |
| `obtenerResumenPermisosUsuarios($conexion)` | Resumen de permisos para grilla |

### `funciones/analisisestado.php` (6 funciones)

| Funcion | Descripcion |
|---------|-------------|
| `obtenerAsistencia($conexion, $idAlumno, $idMateria, $idCiclo)` | Obtiene registros de asistencia |
| `porcentaje($tabla)` | Calcula porcentaje de asistencia |
| `actualizarAsistencia($conexion, $idAlumno, $idMateria, $valor)` | Actualiza campo asistencia en calificaciones |
| `debeMateria($conexion, $idAlumno, $idMateria)` | Verifica si alumno debe materia como final |
| `iniciarAnalisis($conexion, $idMateria, $idAlumno, $idCalificacion)` | Inicia analisis de estado de cursado |
| `analisis_estado(...)` | Calcula el estado de cursado (codigos 0-15), actualiza BD |

### `funciones/controlCorrelatividad.php` (12 funciones)

| Funcion | Descripcion |
|---------|-------------|
| `controlCorrelatividades($idUnicoMateria, $idAlumno, $tipoInscripcion)` | Control principal: evalua las 4 condiciones |
| `correlatividadRegular($idUnicoMateria, $idAlumno, $tipoInscripcion)` | Verifica correlativas regulares individuales |
| `correlatividadRegularGrupal($idUnicoMateria, $idAlumno, $tipoInscripcion)` | Verifica correlativas regulares grupales |
| `correlatividadAprobado($idUnicoMateria, $idAlumno, $tipoInscripcion)` | Verifica correlativas aprobadas individuales |
| `correlatividadAprobadoGrupal($idUnicoMateria, $idAlumno, $tipoInscripcion)` | Verifica correlativas aprobadas grupales |
| `inscripcionExamenEstado($inscripcion, $estadoNumero, $nombreMateria)` | Evalua si el estado permite inscripcion a examen |
| `inscripcionExamenControl($conexion, $idAlumno, $idUnicoMateria, $inscripcion, $masivo)` | Control completo para inscripcion a examen |
| `selectNombreXIdUnico($idUnicoMateria)` | Nombre de materia por idUnicoMateria |
| `getCorrelativasIndividual($idUnicoMateria, $condicion, $tipoInscripcion)` | Query correlativas individuales |
| `getCorrelativasGrupal($idUnicoMateria, $condicion, $tipoInscripcion)` | Query correlativas grupales |
| `getCorrelativasGrupalDetalle($idUnicoMateria, $condicion, $tipoInscripcion, $grupal)` | Detalle grupal |
| `getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateria)` | Calificaciones para control |

Tabla de correlatividades (`correlatividadesterciario`):
- `condicionCorrelatividad`: 0=Regular, 1=Aprobado
- `tipoInscripcion`: 0=Cursado, 1=Examen
- `grupal`: string que agrupa correlativas que se cumplen en conjunto

---

## E. Variables de Sesion por Rol

### Alumno (prefijo `alu_`)
```
$_SESSION['alu_idAlumno']   // int - PK en alumnosterciario
$_SESSION['alu_nombre']     // string
$_SESSION['alu_apellido']   // string
$_SESSION['alu_dni']        // string
$_SESSION['alu_idPersona']  // int - FK a persona
```

### Docente (prefijo `doc_`)
```
$_SESSION['doc_legajo']     // int - PK en personal
$_SESSION['doc_nombre']     // string
$_SESSION['doc_apellido']   // string
$_SESSION['doc_dni']        // string
$_SESSION['doc_idPersona']  // int - FK a persona
```

### Secretaria (prefijo `sec_`)
```
$_SESSION['sec_id']              // int - PK en usuarios
$_SESSION['sec_nombreUsuario']   // string
$_SESSION['sec_tipoPermiso']     // string - '7' = superadmin (acceso a usuarios.php)
$_SESSION['sec_idnivel']         // int - nivel (6=terciario)
```

### Variables Compartidas
```
$_SESSION['active_user_identifier']  // "sec_N", "doc_N", "alu_N" (seteado por verificarSesion.php)
$_SESSION['redirect_after_logout']   // URL de redireccion post-logout
$_SESSION['logo']                    // Ruta logo institucion (login)
$_SESSION['logoSE']                  // Ruta logo SE System
$_SESSION['membrete']                // Ruta membrete para reportes
$_SESSION['profeModCiclo']           // 1=docentes pueden cambiar ciclo lectivo
$_SESSION['profeModCalifAsis']       // 1=docentes pueden modificar calificaciones/asistencias
$_SESSION['claveDocente']            // Clave default para primer acceso docente
$_SESSION['aluDebeFinal']            // 1=habilita funcionalidad "alumnos que deben final"
$_SESSION['soloLecturaExam']         // Flag lectura de examenes
$_SESSION['cursSoloLectura']         // Flag lectura de cursado
```

---

## F. Endpoints AJAX

### Deteccion AJAX
`verificarSesion.php` detecta AJAX via `$_SERVER['HTTP_X_REQUESTED_WITH'] === 'xmlhttprequest'`.
Si la sesion expiro en una peticion AJAX, retorna `HTTP 401` con JSON: `{"session_expired": true}`.

### Archivos que procesan AJAX (POST, retornan JSON)

| Archivo | Operaciones AJAX |
|---------|-----------------|
| `secretaria/carga_califxalumno_secretaria.php` | Carga/actualiza calificaciones por alumno |
| `secretaria/carga_calif_secretaria.php` | Carga calificaciones por materia |
| `secretaria/carga_asist_secretaria.php` | Carga asistencias |
| `secretaria/inscripcionExamenAlumno.php` | Inscripcion individual a examen |
| `secretaria/inscripcionExamenMasivo.php` | Inscripcion masiva a examen |
| `secretaria/inscripcionMateria.php` | Inscripcion a materia |
| `secretaria/matriculacion.php` | Matriculacion plan/curso |
| `secretaria/mesasExamen.php` | ABM mesas de examen |
| `secretaria/actas.php` | Gestion de actas |
| `secretaria/equivalencias.php` | Equivalencias/pases |
| `secretaria/registrosPresistema.php` | Registros presistema |
| `secretaria/legajoAlu.php` | Legajo del alumno |
| `secretaria/solicitudesExamen.php` | Procesar solicitudes web examen |
| `secretaria/solicitudesCursado.php` | Procesar solicitudes web cursado |
| `secretaria/parametrosCursos.php` | ABM cursos |
| `secretaria/parametrosPlanesEstudio.php` | ABM planes |
| `secretaria/usuarios.php` | ABM usuarios |
| `docentes/carga_calif.php` | Carga calificaciones (docente) |
| `docentes/carga_asist.php` | Carga asistencias (docente) |
| `docentes/cargaActa.php` | Actas (docente) |
| `docentes/materiaxdocente.php` | Materias del docente |
| `docentes/mesasExamenProf.php` | Mesas del docente |
| `alumnos/materias_materias.php` | Materias para inscripcion |

### Patron AJAX Tipico (jQuery)
```javascript
$.ajax({
    url: 'archivo.php',
    type: 'POST',
    data: { accion: 'nombreAccion', param1: valor1 },
    dataType: 'json',
    success: function(response) { /* ... */ },
    error: function(xhr) {
        if (xhr.status === 401) { /* sesion expirada, redirigir */ }
    }
});
```

Los archivos PHP detectan la accion via `$_POST['accion']` y responden con `echo json_encode([...])`.

---

## G. Reglas de Negocio

### Codigos de Estado de Cursado (0-15)

Calculados por `analisis_estado()` en `analisisestado.php`. Se almacenan en `calificacionesterciario.estadoCursadoNumero`.

| Codigo | Estado | Descripcion |
|--------|--------|-------------|
| 0 | Libre | Desaprueba cursado |
| 1 | Regular | Aprueba cursado con asistencia |
| 2 | Coloquio | Requiere coloquio |
| 3 | Libre S/Asist | Libre sin evaluacion de asistencia |
| 4 | Regular S/Asist | Regular sin evaluacion de asistencia |
| 5 | Coloquio S/Asist | Coloquio sin evaluacion de asistencia |
| 6 | Sin Calificaciones | No tiene notas cargadas |
| 7 | Asistencia Requerida | Falta cargar asistencia |
| 8 | Asistencia Insuficiente | No alcanza porcentaje minimo |
| 9 | Sin Asistencia | Sin registros de asistencia |
| 10 | Recursa | Debe recursar la materia |
| 11 | Aprueba | Materia aprobada |
| 12 | Recursa S/Asist | Recursa sin evaluacion asistencia |
| 13 | Aprueba S/Asist | Aprobada sin evaluacion asistencia |
| 14 | Promocional | Promocion directa |
| 15 | Promocional S/Asist | Promocion sin evaluacion asistencia |

**Mapeo para inscripcion a examen:**
- Regular/Aprobo Cursada: estados 1, 4
- Libre/No Regular: estados 0, 3, 10, 12
- Coloquio: estados 2, 5
- Promocional: estados 11, 13, 14, 15
- Error (no inscribible): estados 6, 7, 8, 9

### Correlatividades

Tabla `correlatividadesterciario` define requisitos previos entre materias. Tipos:
- **Regular Individual**: materia correlativa debe estar regular (condicion=0)
- **Regular Grupal**: grupo de correlativas deben cumplirse juntas
- **Aprobado Individual**: materia correlativa debe estar aprobada (condicion=1)
- **Aprobado Grupal**: grupo de aprobadas que deben cumplirse juntas

Se verifican tanto para inscripcion a cursado (`tipoInscripcion=0`) como a examen (`tipoInscripcion=1`).

### Flujo de Inscripciones

**Inscripcion a Cursado (Web):**
1. Alumno solicita desde `materias_solicitar.php` → inserta en `matriculacionmateria_web`
2. Secretaria revisa en `solicitudesCursado.php`
3. Al aceptar: inserta en `matriculacionmateria` + inicializa `calificacionesterciario` + `asistenciaterciario`

**Inscripcion a Examen (Web):**
1. Alumno solicita desde `examenes_solicitar.php` → inserta en `inscripcionexamenes_web`
2. Secretaria revisa en `solicitudesExamen.php`
3. Al aceptar: inserta en `inscripcionexamenes` con condicion y mesa

**Inscripcion Manual:**
- Secretaria puede inscribir directamente sin pasar por solicitud web
- Inscripcion masiva: selecciona alumnos aptos por condicion, inscribe en lote

### Presistema y Equivalencias

Registros anteriores a la implementacion del sistema. Operan sobre 3 tablas en transaccion:
- `matriculacionmateria` (estado: 'Regularidad PreSistema' / 'Aprobación PreSistema')
- `calificacionesterciario`
- `inscripcionexamenes` (solo para aprobados)

Equivalencias/Pases operan sobre:
- `matriculacionmateria` (estado: 'Aprobación por Equivalencia' / 'Aprobación por Pase')
- `calificacionesterciario`
- `resoluciones`

### Sistema de Permisos (Secretaria)

Tres niveles de acceso por formulario:
- `idllavegral = 1`: Acceso Total
- `idllavegral = 2`: Solo Lectura
- `idllavegral = 3`: Acceso Especifico (llaves individuales)

`tipoPermiso = 7` en tabla `usuarios` da acceso a la gestion de usuarios/permisos.

---

## H. Configuracion del Sistema

### `inicio/conexion.php` - Conexion a BD
```php
$servername = "localhost";
$username = "se_user";
$password = "88668866";
$dbname = "sesystem_prueba";
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");
```

### `configMail.php` - SMTP
```php
define('MAIL_HOST', 'smtp.hostinger.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'sistemasescolares@sesystem.com.ar');
define('MAIL_FROM', 'sistemasescolares@sesystem.com.ar');
define('MAIL_FROM_NAME', 'SistemasEscolares');
```

### `inicio/variablesParticulares.php` - Config por Institucion
- `$_SESSION['logo']`, `$_SESSION['logoSE']`, `$_SESSION['membrete']`: rutas de imagenes
- `$_SESSION['profeModCiclo']`: 1=docentes cambian ciclo
- `$_SESSION['profeModCalifAsis']`: 1=docentes modifican calificaciones
- `$_SESSION['claveDocente']`: password default primer acceso
- `$_SESSION['aluDebeFinal']`: 1=habilita seccion "debe final"
- `$req1...$req20`: Labels de documentacion para legajo (Fotocopia DNI, Fotos 4x4, etc.)

### `funciones/parametrosWeb.php` - Parametros Dinamicos
Llama a `obtenerParametrosColegio($conn, 6)` y carga variables globales:
- Fechas de inscripcion a examen y cursado (desde/hasta)
- Turno de examen activo
- Anio del ciclo lectivo web
- Nombre del colegio

---

## I. Convenciones de Desarrollo

### Reglas Estrictas

1. **Toda consulta SQL va en `funciones/consultas.php`**. Nunca ejecutar SQL directamente en archivos de presentacion.
2. **Siempre usar prepared statements** con `$stmt->bind_param()`. Nunca concatenar variables en SQL.
3. **Incluir `verificarSesion.php` primero** en toda pagina. Garantiza sesion activa y redireccion si expira.
4. **Sanitizar inputs**: usar `htmlspecialchars()` para output HTML, `intval()`/cast para enteros, whitelist para columnas.

### Patron de Pagina PHP

```php
<?php
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
// ... includes adicionales ...

// Bloque AJAX: si es POST con 'accion', procesar y retornar JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json');
    $accion = $_POST['accion'];
    // switch/if sobre $accion...
    echo json_encode($resultado);
    exit;
}

// Bloque HTML: si es GET, renderizar pagina
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; // o menu.php / menu_docente.php ?>
<div class="container mt-4">
    <!-- Contenido -->
</div>
<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<?php include '../funciones/footer.html'; ?>
</body>
</html>
```

### Estructura de Funcion en consultas.php

```php
function nombreFuncion($conexion, $param1, $param2) {
    $sql = "SELECT/INSERT/UPDATE/DELETE ...";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar nombreFuncion: " . $conexion->error);
        return []; // o false/null
    }
    $stmt->bind_param("tipos", $param1, $param2);
    $stmt->execute();
    $result = $stmt->get_result();
    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
    $stmt->close();
    return $datos;
}
```

### CSS y Estilos
- Bootstrap 5 base: `css/material/bootstrap.min.css` (Bootswatch Materia)
- Custom: `css/estilos.css`
- Iconos: Bootstrap Icons 1.11.3 (CDN)
- Navbar: clase `navbar-custom` con colores personalizados en `estilos.css`

### JavaScript
- jQuery 3.7.1 para AJAX y manipulacion DOM
- Bootstrap 5 Bundle (incluye Popper) para componentes interactivos
- `sessionControl.js`: timeout inactividad 15 min, logout multi-tab via localStorage

### Nivel Educativo
El sistema opera sobre nivel terciario (`codnivel = 6`, `idNivel = 6`). Las tablas con sufijo `terciario` son las activas: `alumnosterciario`, `materiaterciario`, `calificacionesterciario`, `asistenciaterciario`, `correlatividadesterciario`. Las tablas sin sufijo (`alumno`, `materia`, `calificaciones`, `asistencia`) son del nivel no-terciario y no se usan activamente.
