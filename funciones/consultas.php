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
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso,curso.idDivision as idDivision 
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
            $listadoCalificaciones[$i]['n6'] = $data['n6'];
            $listadoCalificaciones[$i]['n7'] = $data['n7'];
            $listadoCalificaciones[$i]['n8'] = $data['n8'];
            $listadoCalificaciones[$i]['r1'] = $data['r1'];
            $listadoCalificaciones[$i]['r2'] = $data['r2'];
            $listadoCalificaciones[$i]['r3'] = $data['r3'];
            $listadoCalificaciones[$i]['r4'] = $data['r4'];
            $listadoCalificaciones[$i]['r5'] = $data['r5'];
            $listadoCalificaciones[$i]['r6'] = $data['r6'];
            $listadoCalificaciones[$i]['r7'] = $data['r7'];
            $listadoCalificaciones[$i]['r8'] = $data['r8'];
            $listadoCalificaciones[$i]['Asistencia'] = $data['asistencia'];
            $listadoCalificaciones[$i]['Estado'] = $data['estadoCursado'];
            $listadoCalificaciones[$i]['CalificacionFinal'] = $examen;
            $listadoCalificaciones[$i]['idDivision'] = $data['idDivision'];
            $i++;
        }
    }
    return $listadoCalificaciones;
}

//Datos cursado de materia de un alumno
function cursadoMateria($conexion, $idMateria, $idAlumno)
{
    $consulta = "SELECT calificacionesterciario.* 
from calificacionesterciario 
where calificacionesterciario.idMateria = $idMateria and calificacionesterciario.idAlumno = $idAlumno";

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
            $cursadoMateria[$i]['n6'] = $data['n6'];
            $cursadoMateria[$i]['n7'] = $data['n7'];
            $cursadoMateria[$i]['n8'] = $data['n8'];
            $cursadoMateria[$i]['r1'] = $data['r1'];
            $cursadoMateria[$i]['r2'] = $data['r2'];
            $cursadoMateria[$i]['r3'] = $data['r3'];
            $cursadoMateria[$i]['r4'] = $data['r4'];
            $cursadoMateria[$i]['r5'] = $data['r5'];
            $cursadoMateria[$i]['r6'] = $data['r6'];
            $cursadoMateria[$i]['r7'] = $data['r7'];
            $cursadoMateria[$i]['r8'] = $data['r8'];
            $cursadoMateria[$i]['Asistencia'] = $data['asistencia'];
            $cursadoMateria[$i]['Estado'] = $data['estadoCursado'];
            $i++;
        }
    }
    return $cursadoMateria;
}

//Materias que adeuda de un alumno por Plan y Curso
function buscarMateriasAdeuda($conexion, $cicloLectivo, $idAlumno, $idPlan,$idcursopred)
{
    $consulta = "SELECT materiaterciario.*, curso.nombre as nombreCurso FROM materiaterciario 
    inner join curso on materiaterciario.idCurso = curso.idCurso and curso.idcursopredeterminado = $idcursopred WHERE materiaterciario.idPlan = $idPlan 
    AND materiaterciario.idCicloLectivo = (select idciclolectivo from ciclolectivo where anio = $cicloLectivo)       
    and idUnicoMateria not in 
    (
    select m1.idUnicoMateria
    from calificacionesterciario c1 inner join 
    materiaterciario m1 on c1.idMateria = m1.idMateria inner join
    matriculacionmateria mt1 on c1.idMateria = mt1.idMateria inner join 
    curso on m1.idCurso = curso.idCurso and curso.idcursopredeterminado = $idcursopred
    where m1.idUnicoMateria = materiaterciario.idUnicoMateria
    and c1.idAlumno = $idAlumno
    and mt1.idAlumno = $idAlumno
    and
    (     
    c1.materiaAprobada = 1 or 
    mt1.estado = 'Aprobación PreSistema' or     
    mt1.estado = 'Aprobación por Equivalencia' or 
    mt1.estado = 'Aprobación por Pase'       
    )
    )
    order by curso.idcursopredeterminado, materiaterciario.ubicacion";

    $mat = mysqli_query($conexion, $consulta);

    $listadoMaterias = array();
    $i = 0;
    if (!empty($mat)) {
        while ($data = mysqli_fetch_array($mat)) {           

            $listadoMaterias[$i]['idMateria'] = $data['idMateria'];
            $listadoMaterias[$i]['Materia'] = $data['nombre'];  
            $listadoMaterias[$i]['Curso'] = $data['nombreCurso'];           
            $i++;
        }
    }
    return $listadoMaterias;
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
            $listadoCalificaciones[$i]['n6'] = $data['n6'];
            $listadoCalificaciones[$i]['n7'] = $data['n7'];
            $listadoCalificaciones[$i]['n8'] = $data['n8'];
            $listadoCalificaciones[$i]['r1'] = $data['r1'];
            $listadoCalificaciones[$i]['r2'] = $data['r2'];
            $listadoCalificaciones[$i]['r3'] = $data['r3'];
            $listadoCalificaciones[$i]['r4'] = $data['r4'];
            $listadoCalificaciones[$i]['r5'] = $data['r5'];
            $listadoCalificaciones[$i]['r6'] = $data['r6'];
            $listadoCalificaciones[$i]['r7'] = $data['r7'];
            $listadoCalificaciones[$i]['r8'] = $data['r8'];
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

    // Agregar la segunda consulta
    $consulta2 = "SELECT mt.fechaMatriculacion AS fecha, c.examenIntegrador AS calificacion 
                  FROM matriculacionmateria mt
                  INNER JOIN calificacionesterciario c 
                  ON mt.idAlumno = c.idAlumno AND mt.idMateria = c.idMateria
                  WHERE mt.idAlumno = $idAlumno 
                  AND mt.idMateria IN (
                    SELECT m1.idMateria 
                    FROM materiaterciario m1 
                    WHERE m1.idUnicoMateria IN (
                      SELECT m2.idUnicoMateria 
                      FROM materiaterciario m2 
                      WHERE m2.idMateria = $idMateria
                    )
                  )
                  AND mt.estado IN ('Aprobación PreSistema', 'Aprobación Por Equivalencia', 'Aprobación Por Pase')";

    $matriculacion = mysqli_query($conexion, $consulta2);

    if (!empty($matriculacion)) {
        while ($data = mysqli_fetch_array($matriculacion)) {
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
and inscripcionexamenes_web.idcicloLectivo = $idCicloLectivo order by inscripcionexamenes_web.fechhora_inscri desc";

    $sol = mysqli_query($conexion, $consulta);

    $listadoSolicitudesExamenes = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesExamenes[$i]['idInscripcionWeb'] = $data['id_Inscripcion_web'];
            $listadoSolicitudesExamenes[$i]['Materia'] = $data['nombreMateria'];
            $listadoSolicitudesExamenes[$i]['Fecha'] = $data['fecha'];
            $listadoSolicitudesExamenes[$i]['Hora'] = $data['hora'];
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
            $listadoSolicitudesExamenes[$i]['Hora'] = $data['hora'];
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
function buscarFechasExamenTurno($conexion, $idMateria, $idCicloLectivo, $idTurno, $idDivision)
{
    $consulta = "SELECT * from fechasexamenes inner join materiaterciario
on fechasexamenes.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on curso.idcursopredeterminado = cursospredeterminado.idcursopredeterminado
where materiaterciario.idUnicoMateria = 
(Select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = $idMateria)
and fechasexamenes.idCicloLectivo = $idCicloLectivo
and fechasexamenes.idTurno = $idTurno AND curso.idDivision=$idDivision";

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

//INSERTAR INTENCION DE EXAMEN (ALUMNOS QUE SOLO DEBEN FINALES)
function insertarCursadoFinalizado($conexion, $idAlumno, $idPlan, $idCicloLectivo, $intencion)
{
    // Consulta para verificar si ya existe un registro
    $verifQuery = "SELECT * FROM cursadofinalizado WHERE idAlumno = $idAlumno AND idPlan = $idPlan AND idCicloLectivo = $idCicloLectivo";
    $resultado = mysqli_query($conexion, $verifQuery);
    
    // Verificamos si hay registros
    if (mysqli_num_rows($resultado) == 0) {
        // Solo se ejecuta el INSERT si no hay registros
        $consulta = "INSERT INTO cursadofinalizado (idAlumno, idPlan, idCicloLectivo, intencionExamen) VALUES 
        ($idAlumno, $idPlan, $idCicloLectivo, '$intencion')";
        
        // Ejecutamos la consulta de inserción
        mysqli_query($conexion, $consulta);
    }
    else{

        echo "<script type='text/javascript'>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('mensajeModal').innerText = mensaje;
                $('#messageModal').modal('show');
            });
          </script>";
       
    }
}

//BUSCAR IDCICLO
function buscarIdCiclo($conexion, $anio)
{
$consulta="select idCiclolectivo from ciclolectivo where anio= $anio";
$resultado=mysqli_query($conexion, $consulta);
if ($resultado) {
    // Obtener el ciclo lectivo
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $idCiclolectivo = $fila['idCiclolectivo']; // Almacenar el idCiclolectivo en una variable
    } }

return $idCiclolectivo;
}
//buscar el anio por idciclo
function buscarnombreCiclo($conexion, $idciclo)
{
$consulta="select anio from ciclolectivo where idCiclolectivo= $idciclo";
$resultado=mysqli_query($conexion, $consulta);
if ($resultado) {
    // Obtener el ciclo lectivo
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $anio = $fila['anio']; // Almacenar el idCiclolectivo en una variable
    } }

return $anio;
}

function selectCursadoFinalizadoByIdPlan($conexion,$idAlumno){
    $consulta = "SELECT p.nombre as nombre,c.anio as anio,cur.intencionExamen as intencionExamen from cursadofinalizado cur INNER JOIN
plandeestudio p ON p.idPlan=cur.idPlan INNER JOIN
ciclolectivo c ON c.idciclolectivo=cur.idCicloLectivo
 where cur.idAlumno=$idAlumno ";
    
        $fec = mysqli_query($conexion, $consulta);
    
        $listadoCursadoFinalizado = array();
        $i = 0;
        if (!empty($fec)) {
            while ($data = mysqli_fetch_array($fec)) {
                $listadoCursadoFinalizado[$i]['plan'] = $data['nombre'];
                $listadoCursadoFinalizado[$i]['anio'] = $data['anio'];
                $listadoCursadoFinalizado[$i]['intencionExamen'] = $data['intencionExamen'];
                $i++;
            }
        }
        return $listadoCursadoFinalizado;
}
function updateCursadoFinalizado($conexion,$idAlumno,$idPlan,$idCicloLectivo,$intencionExamen){
    $consulta="update cursadofinalizado set intencionExamen='$intencionExamen' where idAlumno=$idAlumno and idPlan=$idPlan and idCicloLectivo=$idCicloLectivo";
    mysqli_query($conexion, $consulta);

}

function buscarIdPlan($conexion, $plan)
{
$consulta="select idPlan from plandeestudio where nombre= '$plan'";
$resultado=mysqli_query($conexion, $consulta);
if ($resultado) {
    // Obtener el idplan
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $idPlan = $fila['idPlan']; // Almacenar el idplan en una variable
    } }

return $idPlan;
}
function buscarNombrePlan($conexion, $idPlan)
{
$consulta="select nombre from plandeestudio where idPlan= '$idPlan'";
$resultado=mysqli_query($conexion, $consulta);
if ($resultado) {
    // Obtener el nombre
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $plan = $fila['nombre']; // Almacenar el nombreplan en una variable
    } }

return $plan;
}
//Listado solicitudes a materia de un alumno por Plan
function buscarSolicitudesMateria($conexion, $idAlumno, $idPlan, $idCicloLectivo)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria from matriculacionmateria_web inner join materiaterciario
on matriculacionmateria_web.idMateria = materiaterciario.idMateria
where matriculacionmateria_web.idAlumno = $idAlumno and materiaterciario.idPlan = $idPlan
and matriculacionmateria_web.idcicloLectivo = $idCicloLectivo order by fechhora_inscri desc";

    $sol = mysqli_query($conexion, $consulta);

    $listadoSolicitudesMateria = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesMateria[$i]['idMatriculacionWeb'] = $data['id_matriculacion_web'];
            $listadoSolicitudesMateria[$i]['Materia'] = $data['nombreMateria'];            
            $listadoSolicitudesMateria[$i]['Fecha'] = $data['fechhora_inscri'];
            if ($data['estado'] == '1') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Pendiente";
            }
            if ($data['estado'] == '2') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Aprobada";
            }
            if ($data['estado'] == '3') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Rechazada";
            }
            if ($data['estado'] == '4') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Cancelada";
            }
            if ($data['estado'] == '5') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Aprobada";
            }
            $listadoSolicitudesMateria[$i]['Observaciones'] = $data['observaciones'];
            $i++;
        }
    }
    return $listadoSolicitudesMateria;
}

//Exite solicitud a materia
function existeSolicitudMateria($conexion, $idAlumno, $idMateria, $idCicloLectivo)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria from matriculacionmateria_web inner join materiaterciario
on matriculacionmateria_web.idMateria = materiaterciario.idMateria
where matriculacionmateria_web.idAlumno = $idAlumno 
and materiaterciario.idUnicoMateria = 
(Select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = $idMateria)
and matriculacionmateria_web.idcicloLectivo = $idCicloLectivo";

    $sol = mysqli_query($conexion, $consulta);

    $listadoSolicitudesMateria = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesMateria[$i]['idMatriculacionWeb'] = $data['id_matriculacion_web'];
            $listadoSolicitudesMateria[$i]['Materia'] = $data['nombreMateria'];
            if ($data['estado'] == '1') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Pendiente";
            }
            if ($data['estado'] == '2') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Aprobada";
            }
            if ($data['estado'] == '3') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Rechazada";
            }
            if ($data['estado'] == '4') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Cancelada";
            }
            if ($data['estado'] == '5') {
                $listadoSolicitudesMateria[$i]['Estado'] = "Aprobada";
            }
            $listadoSolicitudesMateria[$i]['Observaciones'] = $data['observaciones'];
            $i++;
        }
    }
    return $listadoSolicitudesMateria;
}

//Generar solicitud a cursado
function solicitarCursado($conexion, $idAlumno, $idMateria, $idCicloLectivo)
{
    $timestamp = time();
    $currentDate = gmdate('Y-m-d H:i:s', $timestamp);

    $consulta = "insert into matriculacionmateria_web
    (idAlumno, idMateria, idCicloLectivo, condicion, estado, fechhora_inscri) values
    ($idAlumno, $idMateria, $idCicloLectivo, 'Regular', 1,'$currentDate')";

    $est = mysqli_query($conexion, $consulta);
}

//Cancelar solicitud a examen
function cancelarCursado($conexion, $idMatriculacionWeb)
{
    $consulta = "update matriculacionmateria_web
    set estado = '4' where id_matriculacion_web = $idMatriculacionWeb";

    mysqli_query($conexion, $consulta);
}

//levantar ciclos lectivos
function levantarCiclosLectivos($conexion){

    $consulta = "SELECT idciclolectivo, anio FROM ciclolectivo ORDER BY anio DESC";
    $ciclos = mysqli_query(mysql: $conexion, query: $consulta);
    $listadoCiclos = array();
    $i = 0;
    if (!empty($ciclos)) {
        while ($data = mysqli_fetch_array($ciclos)) {
            $listadoCiclos[$i]['idCicloLectivo'] = $data['idciclolectivo'];
            $listadoCiclos[$i]['anio'] = $data['anio'];
            $i++;
        }
    }
    return $listadoCiclos;
}

//obtener materiasxprofesor
function obtenerMateriasxProfesor($conexion, $legajo,$idCicloLectivo,$idPlan)
{
    $consulta = "SELECT m.nombre, p.idMateria,c.nombre as nombreCurso
    FROM materiaterciario m 
    INNER JOIN profesorxmateria p 
    ON m.idMateria = p.idMateria 
	 INNER JOIN curso c ON m.idCurso=c.idCurso
    WHERE p.idPersonal = $legajo 
    AND m.idCicloLectivo = $idCicloLectivo 
    AND m.idPlan = $idPlan
    AND (
      p.tipo = 'Equipo Docente' 
      OR (
        p.tipo = 'Titular' 
        AND (SELECT COUNT(*) FROM profesorxmateria p2 WHERE p2.idMateria = p.idMateria AND p2.tipo = 'Suplente') = 0 
        AND (SELECT COUNT(*) FROM profesorxmateria p2 WHERE p2.idMateria = p.idMateria AND p2.tipo = 'Operador') = 0
      ) 
      OR (
        p.tipo = 'Suplente' 
        AND (SELECT COUNT(*) FROM profesorxmateria p2 WHERE p2.idMateria = p.idMateria AND p2.tipo = 'Operador') = 0
      ) 
      OR p.tipo = 'Operador'
    ) ORDER BY m.nombre,nombreCurso";

    $materias = mysqli_query($conexion, $consulta);

    $listadoMaterias = array();
    $i = 0;
    if (!empty($materias)) {
        while ($data = mysqli_fetch_array($materias)) {
            $listadoMaterias[$i]['idMateria'] = $data['idMateria'];
            $listadoMaterias[$i]['Materia'] = $data['nombre'];
            $listadoMaterias[$i]['Curso'] = $data['nombreCurso'];
            
            $i++;
        }
    }
    return $listadoMaterias;
}
//obtener planes de un profe segun profesorxmateria
function buscarPlanesProfesorMateria($conexion, $legajo) 
{
    $consulta = "SELECT p.idPlan, p.nombre FROM profesorxmateria pm INNER JOIN materiaterciario m 
ON m.idMateria=pm.idMateria INNER JOIN plandeestudio p ON m.idPlan = p.idPlan 
    WHERE pm.idPersonal =$legajo GROUP BY p.nombre";

    $planes = mysqli_query($conexion, $consulta);

    $listadoPlanes = array();
    $i = 0;
    if (!empty($planes)) {
        while ($data = mysqli_fetch_array($planes)) {
            $listadoPlanes[$i]['idPlan'] = $data['idPlan'];
            $listadoPlanes[$i]['nombrePlan'] = $data['nombre'];
            $i++;
        }
    }
    return $listadoPlanes;  
}

//obtener registros decalificaciones de todos los alumnos de una materia
function obtenerCalificacionesMateria($conexion, $idMateria){
    $consulta = 'SELECT c.*,p.apellido,p.nombre,m.estado FROM persona p INNER JOIN alumnosterciario a ON
p.idPersona=a.idPersona  inner JOIN matriculacionmateria m ON a.idAlumno=m.idAlumno INNER JOIN
calificacionesterciario c ON m.idMateria=c.idMateria AND c.idAlumno=a.idAlumno
WHERE m.idMateria = '.$idMateria.'  order by p.apellido,p.nombre';
    $querycalif = mysqli_query($conexion, $consulta);   
    $listadoMateria = array();
    $i = 0;
    if (!empty($querycalif)) {
        while ($data = mysqli_fetch_array($querycalif)) {
            $listadoMateria[$i]['idCalificacion'] = $data['idCalificacion'];
            $listadoMateria[$i]['idAlumno'] = $data['idAlumno'];
            $listadoMateria[$i]['apellido'] = $data['apellido'];
            $listadoMateria[$i]['nombre'] = $data['nombre'];
            $listadoMateria[$i]['n1'] = $data['n1'];
            $listadoMateria[$i]['n2'] = $data['n2'];
            $listadoMateria[$i]['n3'] = $data['n3'];
            $listadoMateria[$i]['n4'] = $data['n4'];
            $listadoMateria[$i]['n5'] = $data['n5'];
            $listadoMateria[$i]['n6'] = $data['n6'];
            $listadoMateria[$i]['n7'] = $data['n7'];
            $listadoMateria[$i]['n8'] = $data['n8'];
            $listadoMateria[$i]['r1'] = $data['r1'];
            $listadoMateria[$i]['r2'] = $data['r2'];
            $listadoMateria[$i]['r3'] = $data['r3'];
            $listadoMateria[$i]['r4'] = $data['r4'];
            $listadoMateria[$i]['r5'] = $data['r5'];
            $listadoMateria[$i]['r6'] = $data['r6'];
            $listadoMateria[$i]['r7'] = $data['r7'];
            $listadoMateria[$i]['r8'] = $data['r8'];
            $listadoMateria[$i]['asistencia'] = $data['asistencia'];
            $listadoMateria[$i]['estadoCursado'] = $data['estadoCursado'];
            $listadoMateria[$i]['estado'] = $data['estado'];

            $i++;
        }
    }
    return $listadoMateria;
}


//obtener asistencia materia
function obtenerAsistenciaMateria($conexion, $idMateria, $mes, $dia, $idCicloLectivo){
    $consulta = 'SELECT p.nombre,p.apellido, asis.' . $dia . ', a.idAlumno FROM persona p INNER JOIN
    alumnosterciario a ON p.idPersona=a.idPersona INNER JOIN 
    asistenciaterciario asis ON a.idAlumno=asis.idAlumno WHERE asis.idMateria='.$idMateria.'
    AND asis.mes='.$mes.' AND asis.idCicloLectivo='.$idCicloLectivo.' order by p.apellido,p.nombre'; 

    $queryasist = mysqli_query($conexion, $consulta);   
    $lista = array();
    $i = 0;
    if (!empty($queryasist)) {
        while ($data = mysqli_fetch_array($queryasist)) {
            $lista[$i]['idAlumno'] = $data['idAlumno'];
            $lista[$i]['apellido'] = $data['apellido'];
            $lista[$i]['nombre'] = $data['nombre'];
            $lista[$i]['dia'] = $data[$dia];
            $i++;
}
}
    return $lista;
}

//actualizar calificaciones docente
function actualizarCalifDocente($conexion, $idCalif, $columna, $valor){
  
  $consulta = "UPDATE calificacionesterciario SET $columna = '$valor' WHERE idCalificacion = $idCalif";
  
  $resultado = mysqli_query($conexion, $consulta);
  if (!$resultado) {
    $respuesta= "Error: " . mysqli_error($conexion);
  } else {
    $respuesta = "actualizado";
  }
  return $respuesta;
}
//funcion para actualizar asistencia
function actualizarAsistxDocentes($conexion,$idAlumno,$idCicloLectivo,$mes,$dia,$valor){
     $consulta = "UPDATE asistenciaterciario SET $dia = '$valor' WHERE idAlumno = $idAlumno and idCicloLectivo=$idCicloLectivo and mes=$mes";
  
  $resultado = mysqli_query($conexion, $consulta);
  if (!$resultado) {
    $respuesta= "Error: " . mysqli_error($conexion);
  } else {
    $respuesta ="Datos actualizados correctamente";
  }
  return $respuesta;

}
//funcion para obtener asistencias por materia para pdf
function obtenerAsistenciaMateriaPDF($conexion, $columnas, $idMateria, $mes, $idCicloLectivo){
    $consulta = 'SELECT p.nombre,p.apellido,' . $columnas . ' FROM persona p INNER JOIN
    alumnosterciario a ON p.idPersona=a.idPersona INNER JOIN 
    asistenciaterciario asis ON a.idAlumno=asis.idAlumno WHERE asis.idMateria='.$idMateria.'
    AND asis.mes='.$mes.' AND asis.idCicloLectivo='.$idCicloLectivo.' order by p.apellido,p.nombre';
    $numColumnas = count(explode(',', $columnas));

    $queryasist = mysqli_query($conexion, $consulta);
    $lista = array();
    $i = 0;
    if (!empty($queryasist)) {
        while ($data = mysqli_fetch_array($queryasist)) {
            $lista[$i]['apellido'] = $data['apellido'];
            $lista[$i]['nombre'] = $data['nombre'];
            for ($j = 0; $j < $numColumnas; $j++) {
                $lista[$i]['d' . ($j + 1)] = $data[$j + 2];
            }
            $i++;
        }
    }
    return $lista;
}
//obtener fechas de examen por profesor
function obtenerFechasExamenProfesor($conexion, $idPersonal1, $idCicloLectivo,$idTurno,$idPlan) {
    $consulta = "SELECT m.nombre,c.nombre AS nombreCurso,f.idMateria, f.idFechaExamen,f.fecha,f.hora
FROM fechasexamenes f INNER JOIN materiaterciario m ON
m.idMateria=f.idMateria INNER JOIN curso c ON
c.idCurso=m.idCurso INNER JOIN plandeestudio p ON
c.idPlanEstudio=p.idPlan
WHERE f.idTurno=$idTurno AND f.idCicloLectivo=$idCicloLectivo AND f.p1=$idPersonal1 AND c.idPlanEstudio=$idPlan";

    $fec = mysqli_query($conexion, $consulta);
    $listadoFechasExamenes = array();
    $i = 0;
    if (!empty($fec)) {
        while ($data = mysqli_fetch_array($fec)) {
            $listadoFechasExamenes[$i]['idFechaExamen'] = $data['idFechaExamen'];
            $listadoFechasExamenes[$i]['nombreMateria'] = $data['nombre'];
            $listadoFechasExamenes[$i]['Curso'] = $data['nombreCurso'];
            $listadoFechasExamenes[$i]['Fecha'] = $data['fecha'];
            $listadoFechasExamenes[$i]['Hora'] = $data['hora'];
            $listadoFechasExamenes[$i]['idMateria'] = $data['idMateria'];            
            $i++;
        }
    }
    return $listadoFechasExamenes;
}
//buscar turno de examen
function buscarNombreTurno($conexion,$idTurno){
    $consulta="select nombre from turnosexamenes where idTurno= '$idTurno'";
$resultado=mysqli_query($conexion, $consulta);
if ($resultado) {
    // Obtener el nombre
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $turno = $fila['nombre']; // Almacenar el nombre turno en una variable
    } }

return $turno ;
}
//obtener acta
function obtenerActa($conexion, $idFechaExamen){
    $consulta = "SELECT i.idAlumno, i.oral, i.escrito,i.calificacion,i.libro,i.folio,
i.idCondicion,p.apellido,p.nombre FROM inscripcionexamenes i INNER JOIN
alumnosterciario a ON i.idAlumno=a.idAlumno INNER JOIN persona p
ON a.idPersona=p.idPersona WHERE i.idFechaExamen=$idFechaExamen";

    $acta = mysqli_query($conexion, $consulta);
    $listadoActa = array();
    $i = 0;
    if (!empty($acta)) {
        while ($data = mysqli_fetch_array($acta)) {
            $listadoActa[$i]['idAlumno'] = $data['idAlumno'];
            $listadoActa[$i]['apellido'] = $data['apellido'];
            $listadoActa[$i]['nombre'] = $data['nombre'];
            $listadoActa[$i]['oral'] = $data['oral'];
            $listadoActa[$i]['escrito'] = $data['escrito'];
            $listadoActa[$i]['calificacion'] = $data['calificacion'];
            $listadoActa[$i]['libro'] = $data['libro'];
            $listadoActa[$i]['folio'] = $data['folio'];
            $listadoActa[$i]['condicion'] = $data['idCondicion'];
            $i++;
        }
    }
    return $listadoActa;
    
}
//actualizar acta
function actualizarActa($conexion, $idFechaExamen, $idAlumno, $setUpdate){
    $consulta = "UPDATE inscripcionexamenes SET $setUpdate WHERE idFechaExamen=$idFechaExamen and idAlumno=$idAlumno";
    $resultado = mysqli_query($conexion, $consulta);
    if (!$resultado) {
        $respuesta= "Error: " . mysqli_error($conexion);
      } else {
        $respuesta = "actualizado";
      }
      return $respuesta;
}
//actualizar abandono cursado
function actualizarAbandonoCursado($conexion, $idAlumno, $idMateria,$estado){
    $consulta = "UPDATE matriculacionmateria SET estado='$estado' WHERE idAlumno=$idAlumno and idMateria=$idMateria";
    $resultado = mysqli_query($conexion, $consulta);
    if (!$resultado) {
        $respuesta= "Error: " . mysqli_error($conexion);
      } else {
        $respuesta = "actualizado";
      }
      return $respuesta;
}