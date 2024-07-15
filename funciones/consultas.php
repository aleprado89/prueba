<?php

//Estado de Cursado de un alumno por Plan
function estadoPlan($conexion, $idAlumno, $idPlan, $cicloLectivo)
{
    $consulta = "SELECT * FROM materiaterciario inner join curso 
on materiaterciario.idCurso = curso.idCurso
left join calificacionesterciario on (calificacionesterciario.idAlumno = $idAlumno and 
materiaterciario.idUnicoMateria = 
(select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = calificacionesterciario.idMateria))
where materiaterciario.idPlan = $idPlan and materiaterciario.idCicloLectivo =
(select idciclolectivo from ciclolectivo where anio = $cicloLectivo) and curso.cursoPrincipal = 1";

    $estadoP = mysqli_query($conexion, $consulta);

    $listadoCurricula = array();
    $i = 0;
    if (!empty($estadoP)) {
        while ($data = mysqli_fetch_array($estadoP)) {
            $listadoCurricula[$i]['materiaAprobada'] = $data['materiaAprobada'];
            $listadoCurricula[$i]['estadoCursado'] = $data['estadoCursado'];
            $listadoCurricula[$i]['idCalificacion'] = $data['idCalificacion'];
            $i++;
        }
    }
    return $listadoCurricula;
}

//Calificaciones de un alumno por Plan
function buscarMaterias($conexion, $idAlumno, $idPlan)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso 
from calificacionesterciario inner join materiaterciario 
on calificacionesterciario.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
where calificacionesterciario.idAlumno = $idAlumno and materiaterciario.idPlan = $idPlan
order by curso.idcursopredeterminado, materiaterciario.ubicacion desc";

    $calif = mysqli_query($conexion, $consulta);

    $listadoCalificaciones = array();
    $i = 0;
    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {
            $idInscripcionExamen = $data['idInscripcionExamen'];
            if ($idInscripcionExamen == null || $idInscripcionExamen == 0) {
                if (
                    $data['estadoCursado'] == "Aprobación PreSistema" ||
                    $data['estadoCursado'] == "Aprobación por Equivalencia" ||
                    $data['estadoCursado'] == "Aprobación por Pase"
                ) {
                    $examen = $data['examenIntegrador'];
                } else {
                    $examen = " ";
                }
            } else {
                $consultaExamen = "SELECT calificacion from inscripcionexamenes 
        where idInscripcion = $idInscripcionExamen";
                $ex = mysqli_fetch_array(mysqli_query($conexion, $consultaExamen));
                $examen = $ex["calificacion"];
            }

            $listadoCalificaciones[$i]['idCalificacion'] = $data['idCalificacion'];
            $listadoCalificaciones[$i]['idMateria'] = $data['idMateria'];
            $listadoCalificaciones[$i]['Materia'] = $data['nombreMateria'];
            $listadoCalificaciones[$i]['Curso'] = $data['nombreCurso'];
            $listadoCalificaciones[$i]['n1'] = $data['n1'];
            $listadoCalificaciones[$i]['n2'] = $data['n2'];
            $listadoCalificaciones[$i]['n3'] = $data['n3'];
            $listadoCalificaciones[$i]['n4'] = $data['n4'];
            $listadoCalificaciones[$i]['n5'] = $data['n5'];
            $listadoCalificaciones[$i]['n6'] = $data['n5'];
            $listadoCalificaciones[$i]['n7'] = $data['n5'];
            $listadoCalificaciones[$i]['n8'] = $data['n5'];
            $listadoCalificaciones[$i]['r1'] = $data['n5'];
            $listadoCalificaciones[$i]['r2'] = $data['n5'];
            $listadoCalificaciones[$i]['r3'] = $data['n5'];
            $listadoCalificaciones[$i]['r4'] = $data['n5'];
            $listadoCalificaciones[$i]['r5'] = $data['n5'];
            $listadoCalificaciones[$i]['r6'] = $data['n5'];
            $listadoCalificaciones[$i]['r7'] = $data['n5'];
            $listadoCalificaciones[$i]['r8'] = $data['n5'];
            $listadoCalificaciones[$i]['Asistencia'] = $data['asistencia'];
            $listadoCalificaciones[$i]['Estado'] = $data['estadoCursado'];
            $listadoCalificaciones[$i]['CalificacionFinal'] = $examen;
            $i++;
        }
    }
    return $listadoCalificaciones;
}

//Datos cursado de materia de un alumno
function cursadoMateria($conexion, $idCalificacion)
{
    $consulta = "SELECT calificacionesterciario.* 
from calificacionesterciario 
where calificacionesterciario.idCalificacion = $idCalificacion";

    $calif = mysqli_query($conexion, $consulta);

    $cursadoMateria = array();
    $i = 0;
    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {            

            $cursadoMateria[$i]['idCalificacion'] = $data['idCalificacion'];
            $cursadoMateria[$i]['idMateria'] = $data['idMateria'];
            $cursadoMateria[$i]['n1'] = $data['n1'];
            $cursadoMateria[$i]['n2'] = $data['n2'];
            $cursadoMateria[$i]['n3'] = $data['n3'];
            $cursadoMateria[$i]['n4'] = $data['n4'];
            $cursadoMateria[$i]['n5'] = $data['n5'];
            $cursadoMateria[$i]['n6'] = $data['n5'];
            $cursadoMateria[$i]['n7'] = $data['n5'];
            $cursadoMateria[$i]['n8'] = $data['n5'];
            $cursadoMateria[$i]['r1'] = $data['n5'];
            $cursadoMateria[$i]['r2'] = $data['n5'];
            $cursadoMateria[$i]['r3'] = $data['n5'];
            $cursadoMateria[$i]['r4'] = $data['n5'];
            $cursadoMateria[$i]['r5'] = $data['n5'];
            $cursadoMateria[$i]['r6'] = $data['n5'];
            $cursadoMateria[$i]['r7'] = $data['n5'];
            $cursadoMateria[$i]['r8'] = $data['n5'];
            $cursadoMateria[$i]['Asistencia'] = $data['asistencia'];
            $cursadoMateria[$i]['Estado'] = $data['estadoCursado'];
            $i++;
        }
    }
    return $cursadoMateria;
}

//Listado Cursos Predeterminados por Plan
function buscarCursoPredeterminado($conexion, $idPlan)
{
    $consulta = "SELECT *, cursospredeterminado.nombre as nombreCP FROM `cursospredeterminado` inner join curso on
    cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado and 
    curso.idPlanEstudio = $idPlan group by cursospredeterminado.nombre";

    $cursosP = mysqli_query($conexion, $consulta);

    $listadoCursosP = array();
    $i = 0;
    if (!empty($cursosP)) {
        while ($data = mysqli_fetch_array($cursosP)) {
            $listadoCursosP[$i]['idcursopredeterminado'] = $data['idcursopredeterminado'];
            $listadoCursosP[$i]['nombreCurso'] = $data['nombreCP'];
            $i++;
        }
    }
    return $listadoCursosP;
}

//Buscar último curso Matriculado
function buscarCursoMatriculado($conexion, $idPlan, $idAlumno)
{
    $consulta = "SELECT cursospredeterminado.idcursopredeterminado FROM `cursospredeterminado` inner join curso on
    cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado and 
    curso.idPlanEstudio = $idPlan
    where curso.idCurso in (select max(m1.idCurso) from matriculacion m1 where m1.idAlumno = $idAlumno
                            and m1.idPlanDeEstudio = $idPlan)";

    $cursoM = mysqli_query($conexion, $consulta);

    $cursosM = " ";

    if (!empty($cursoM)) {
        while ($data = mysqli_fetch_array($cursoM)) {
            $cursosM = $data['idcursopredeterminado'];
        }
    }
    return $cursosM;
}

//Calificaciones de un alumno por Plan y Curso
function buscarMateriasCurso($conexion, $idAlumno, $idPlan, $idCursoPredeterminado)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso 
from calificacionesterciario inner join materiaterciario 
on calificacionesterciario.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
where calificacionesterciario.idAlumno = '$idAlumno' 
and materiaterciario.idPlan = '$idPlan' 
and cursospredeterminado.idcursopredeterminado = '$idCursoPredeterminado'
order by curso.idcursopredeterminado, materiaterciario.ubicacion desc";

    $calif = mysqli_query($conexion, $consulta);

    $listadoCalificaciones = array();
    $i = 0;
    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {
            $idInscripcionExamen = $data['idInscripcionExamen'];
            if ($idInscripcionExamen == null || $idInscripcionExamen == 0) {
                if (
                    $data['estadoCursado'] == "Aprobación PreSistema" ||
                    $data['estadoCursado'] == "Aprobación por Equivalencia" ||
                    $data['estadoCursado'] == "Aprobación por Pase"
                ) {
                    $examen = $data['examenIntegrador'];
                } else {
                    $examen = " ";
                }
            } else {
                $consultaExamen = "SELECT calificacion from inscripcionexamenes 
        where idInscripcion = $idInscripcionExamen";
                $ex = mysqli_fetch_array(mysqli_query($conexion, $consultaExamen));
                $examen = $ex["calificacion"];
            }

            $listadoCalificaciones[$i]['idMateria'] = $data['idMateria'];
            $listadoCalificaciones[$i]['Materia'] = $data['nombreMateria'];
            $listadoCalificaciones[$i]['Curso'] = $data['nombreCurso'];
            $listadoCalificaciones[$i]['n1'] = $data['n1'];
            $listadoCalificaciones[$i]['n2'] = $data['n2'];
            $listadoCalificaciones[$i]['n3'] = $data['n3'];
            $listadoCalificaciones[$i]['n4'] = $data['n4'];
            $listadoCalificaciones[$i]['n5'] = $data['n5'];
            $listadoCalificaciones[$i]['n6'] = $data['n5'];
            $listadoCalificaciones[$i]['n7'] = $data['n5'];
            $listadoCalificaciones[$i]['n8'] = $data['n5'];
            $listadoCalificaciones[$i]['r1'] = $data['n5'];
            $listadoCalificaciones[$i]['r2'] = $data['n5'];
            $listadoCalificaciones[$i]['r3'] = $data['n5'];
            $listadoCalificaciones[$i]['r4'] = $data['n5'];
            $listadoCalificaciones[$i]['r5'] = $data['n5'];
            $listadoCalificaciones[$i]['r6'] = $data['n5'];
            $listadoCalificaciones[$i]['r7'] = $data['n5'];
            $listadoCalificaciones[$i]['r8'] = $data['n5'];
            $listadoCalificaciones[$i]['Asistencia'] = $data['asistencia'];
            $listadoCalificaciones[$i]['Estado'] = $data['estadoCursado'];
            $listadoCalificaciones[$i]['CalificacionFinal'] = $examen;
            $i++;
        }
    }
    return $listadoCalificaciones;
}

//Examenes de un alumno por Materia
function buscarExamenes($conexion, $idAlumno, $idMateria)
{
    $consulta = "SELECT * from inscripcionexamenes inner join fechasexamenes
on inscripcionexamenes.idFechaExamen = fechasexamenes.idFechaExamen inner join materiaterciario
on inscripcionexamenes.idMateria = materiaterciario.idMateria
where inscripcionexamenes.idAlumno = $idAlumno and materiaterciario.idUnicoMateria = 
(Select idUnicoMateria from materiaterciario m1 where m1.idMateria = $idMateria)";

    $exam = mysqli_query($conexion, $consulta);

    $listadoExamenes = array();
    $i = 0;
    if (!empty($exam)) {
        while ($data = mysqli_fetch_array($exam)) {
            $listadoExamenes[$i]['Fecha'] = $data['fecha'];
            $listadoExamenes[$i]['Calificacion'] = $data['calificacion'];
            $i++;
        }
    }
    return $listadoExamenes;
}

//Planes de un alumno
function buscarPlanes($conexion, $idAlumno)
{
    $consulta = "SELECT plandeestudio.idPlan, plandeestudio.nombre from matriculacion inner join plandeestudio
on matriculacion.idPlanDeEstudio = plandeestudio.idPlan
where matriculacion.idAlumno = $idAlumno
group by idPlan";

    $plan = mysqli_query($conexion, $consulta);

    $listadoPlanes = array();
    $i = 0;
    if (!empty($plan)) {
        while ($data = mysqli_fetch_array($plan)) {
            $listadoPlanes[$i]['idPlan'] = $data['idPlan'];
            $listadoPlanes[$i]['Plan'] = $data['nombre'];
            $i++;
        }
    }
    return $listadoPlanes;
}

//Listado solicitudes a examen de un alumno por Plan
function buscarSolicitudesExamen($conexion, $idAlumno, $idPlan, $idCicloLectivo)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria from inscripcionexamenes_web inner join fechasexamenes
on inscripcionexamenes_web.idFechaExamen = fechasexamenes.idFechaExamen inner join materiaterciario
on inscripcionexamenes_web.idMateria = materiaterciario.idMateria
where inscripcionexamenes_web.idAlumno = $idAlumno and materiaterciario.idPlan = $idPlan
and inscripcionexamenes_web.idcicloLectivo = $idCicloLectivo";

    $sol = mysqli_query($conexion, $consulta);

    $listadoSolicitudesExamenes = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesExamenes[$i]['idInscripcionWeb'] = $data['id_Inscripcion_web'];
            $listadoSolicitudesExamenes[$i]['Materia'] = $data['nombreMateria'];
            $listadoSolicitudesExamenes[$i]['Fecha'] = $data['fecha'];
            if ($data['estado'] == '1') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Pendiente";
            }
            if ($data['estado'] == '2') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
            }
            if ($data['estado'] == '3') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Rechazada";
            }
            if ($data['estado'] == '4') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Cancelada";
            }
            if ($data['estado'] == '5') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
            }
            $listadoSolicitudesExamenes[$i]['Observaciones'] = $data['observaciones'];
            $i++;
        }
    }
    return $listadoSolicitudesExamenes;
}

//Exite solicitud a examen
function existeSolicitudExamen($conexion, $idAlumno, $idMateria, $idCicloLectivo, $idTurno)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria from inscripcionexamenes_web inner join fechasexamenes
on inscripcionexamenes_web.idFechaExamen = fechasexamenes.idFechaExamen inner join materiaterciario
on inscripcionexamenes_web.idMateria = materiaterciario.idMateria
where inscripcionexamenes_web.idAlumno = $idAlumno 
and materiaterciario.idUnicoMateria = 
(Select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = $idMateria)
and inscripcionexamenes_web.idcicloLectivo = $idCicloLectivo
and fechasexamenes.idTurno = $idTurno";

    $sol = mysqli_query($conexion, $consulta);

    $listadoSolicitudesExamenes = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesExamenes[$i]['idInscripcionWeb'] = $data['id_Inscripcion_web'];
            $listadoSolicitudesExamenes[$i]['Materia'] = $data['nombreMateria'];
            $listadoSolicitudesExamenes[$i]['Fecha'] = $data['fecha'];
            if ($data['estado'] == '1') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Pendiente";
            }
            if ($data['estado'] == '2') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
            }
            if ($data['estado'] == '3') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Rechazada";
            }
            if ($data['estado'] == '4') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Cancelada";
            }
            if ($data['estado'] == '5') {
                $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
            }
            $listadoSolicitudesExamenes[$i]['Observaciones'] = $data['observaciones'];
            $i++;
        }
    }
    return $listadoSolicitudesExamenes;
}

//Listado de fechas a examen por materia y turno
function buscarFechasExamenTurno($conexion, $idMateria, $nombreCurso, $idCicloLectivo, $idTurno)
{
    $consulta = "SELECT * from fechasexamenes inner join materiaterciario
on fechasexamenes.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on curso.idcursopredeterminado = cursospredeterminado.idcursopredeterminado
where materiaterciario.idUnicoMateria = 
(Select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = $idMateria)
and fechasexamenes.idCicloLectivo = $idCicloLectivo 
and fechasexamenes.idTurno = $idTurno";

    $fec = mysqli_query($conexion, $consulta);

    $listadoFechasExamenes = array();
    $i = 0;
    if (!empty($fec)) {
        while ($data = mysqli_fetch_array($fec)) {
            $listadoFechasExamenes[$i]['idFechaExamen'] = $data['idFechaExamen'];
            $listadoFechasExamenes[$i]['Fecha'] = $data['fecha'];
            $listadoFechasExamenes[$i]['Hora'] = $data['hora'];
            $i++;
        }
    }
    return $listadoFechasExamenes;
}

//Generar solicitud a examen
function solicitarExamen($conexion, $idAlumno, $idMateria, $idCicloLectivo, $idFechaExamen)
{
    $timestamp = time();
    $currentDate = gmdate('Y-m-d H:i:s', $timestamp);

    $consulta = "insert into inscripcionexamenes_web
    (idAlumno, idMateria, idCicloLectivo, idFechaExamen, idCondicion, estado, fechhora_inscri) values
    ($idAlumno, $idMateria, $idCicloLectivo, $idFechaExamen, 0, 1,'$currentDate')";

    mysqli_query($conexion, $consulta);
}

//Cancelar solicitud a examen
function cancelarExamen($conexion, $idInscripcionWeb)
{
    $consulta = "update inscripcionexamenes_web
    set estado = '4' where id_Inscripcion_web = $idInscripcionWeb";

    mysqli_query($conexion, $consulta);
}
