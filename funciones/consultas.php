<?php
//Estado de Cursado de un alumno por Plan
function estadoPlan($conexion, $idAlumno, $idPlan, $cicloLectivo)
{
    $consulta = "SELECT * FROM materiaterciario inner join curso 
on materiaterciario.idCurso = curso.idCurso
left join calificacionesterciario on (calificacionesterciario.idAlumno = ? and 
materiaterciario.idUnicoMateria = 
(select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = calificacionesterciario.idMateria))
where materiaterciario.idPlan = ? and materiaterciario.idCicloLectivo =
(select idciclolectivo from ciclolectivo where anio = ?) and curso.cursoPrincipal = 1";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iii", $idAlumno, $idPlan, $cicloLectivo);
    $stmt->execute();
    $estadoP = $stmt->get_result();

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
/*function buscarMaterias($conexion, $idAlumno, $idPlan)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso,curso.idDivision as idDivision 
from calificacionesterciario inner join materiaterciario 
on calificacionesterciario.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
where calificacionesterciario.idAlumno = ? and materiaterciario.idPlan = ?
order by curso.idcursopredeterminado, materiaterciario.ubicacion desc";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idAlumno, $idPlan);
    $stmt->execute();
    $calif = $stmt->get_result();

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
        where idInscripcion = ?";
                $stmtExamen = $conexion->prepare($consultaExamen);
                $stmtExamen->bind_param("i", $idInscripcionExamen);
                $stmtExamen->execute();
                $ex = $stmtExamen->get_result();
                $examen = $ex->fetch_assoc()["calificacion"];
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
}*/

//Calificaciones de un alumno por Plan

function buscarMaterias($conexion, $idAlumno, $idPlan)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso, curso.idDivision as idDivision 
    FROM calificacionesterciario 
    INNER JOIN materiaterciario ON calificacionesterciario.idMateria = materiaterciario.idMateria 
    INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso 
    INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado 
    WHERE calificacionesterciario.idAlumno = ? AND materiaterciario.idPlan = ? 
    ORDER BY curso.idcursopredeterminado, materiaterciario.ubicacion DESC";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idAlumno, $idPlan);
    $stmt->execute();
    $calif = $stmt->get_result();

    $listadoCalificaciones = array();
    $i = 0;

    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {
            $idInscripcionExamen = $data['idInscripcionExamen'];

            // Inicializar calificación final vacía
            $examen = " ";

            if ($idInscripcionExamen == null || $idInscripcionExamen == 0) {
                if (
                    $data['estadoCursado'] == "Aprobación PreSistema" ||
                    $data['estadoCursado'] == "Aprobación por Equivalencia" ||
                    $data['estadoCursado'] == "Aprobación por Pase"
                ) {
                    $examen = $data['examenIntegrador'];
                } else {
                    // Buscar calificaciones desde función auxiliar
                    $examenes = buscarExamenes($conexion, $idAlumno, $data['idMateria']);
                    $fechaMax = null;
                    foreach ($examenes as $ex) {
                        if (!empty($ex['Calificacion']) && ($fechaMax === null || $ex['Fecha'] > $fechaMax)) {
                            $fechaMax = $ex['Fecha'];
                            $examen = $ex['Calificacion'];
                        }
                    }
                }
            } else {
                $consultaExamen = "SELECT calificacion FROM inscripcionexamenes WHERE idInscripcion = ?";
                $stmtExamen = $conexion->prepare($consultaExamen);
                $stmtExamen->bind_param("i", $idInscripcionExamen);
                $stmtExamen->execute();
                $ex = $stmtExamen->get_result();
                $examen = $ex->fetch_assoc()["calificacion"] ?? "";

                // Si aún está vacía, recurrir a función auxiliar
                if (empty($examen)) {
                    $examenes = buscarExamenes($conexion, $idAlumno, $data['idMateria']);
                    $fechaMax = null;
                    foreach ($examenes as $ex) {
                        if (!empty($ex['Calificacion']) && ($fechaMax === null || $ex['Fecha'] > $fechaMax)) {
                            $fechaMax = $ex['Fecha'];
                            $examen = $ex['Calificacion'];
                        }
                    }
                }
            }

            // Armar array de resultados
            $listadoCalificaciones[$i] = [
                'idCalificacion'     => $data['idCalificacion'],
                'idMateria'          => $data['idMateria'],
                'Materia'            => $data['nombreMateria'],
                'Curso'              => $data['nombreCurso'],
                'n1'                 => $data['n1'],
                'n2'                 => $data['n2'],
                'n3'                 => $data['n3'],
                'n4'                 => $data['n4'],
                'n5'                 => $data['n5'],
                'n6'                 => $data['n6'],
                'n7'                 => $data['n7'],
                'n8'                 => $data['n8'],
                'r1'                 => $data['r1'],
                'r2'                 => $data['r2'],
                'r3'                 => $data['r3'],
                'r4'                 => $data['r4'],
                'r5'                 => $data['r5'],
                'r6'                 => $data['r6'],
                'r7'                 => $data['r7'],
                'r8'                 => $data['r8'],
                'Asistencia'         => $data['asistencia'],
                'Estado'             => $data['estadoCursado'],
                'CalificacionFinal'  => $examen,
                'idDivision'         => $data['idDivision'],
            ];
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
where calificacionesterciario.idMateria = ? and calificacionesterciario.idAlumno = ?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idMateria, $idAlumno);
    $stmt->execute();
    $calif = $stmt->get_result();

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
function buscarMateriasAdeuda($conexion, $cicloLectivo, $idAlumno, $idPlan, $idcursopred)
{
    $consulta = "SELECT materiaterciario.*, curso.nombre as nombreCurso FROM materiaterciario 
    inner join curso on materiaterciario.idCurso = curso.idCurso and curso.idcursopredeterminado = ? WHERE materiaterciario.idPlan = ? 
    AND materiaterciario.idCicloLectivo = (select idciclolectivo from ciclolectivo where anio = ?)       
    and idUnicoMateria not in 
    (
    select m1.idUnicoMateria
    from calificacionesterciario c1 inner join 
    materiaterciario m1 on c1.idMateria = m1.idMateria inner join
    matriculacionmateria mt1 on c1.idMateria = mt1.idMateria inner join 
    curso on m1.idCurso = curso.idCurso and curso.idcursopredeterminado = ?
    where m1.idUnicoMateria = materiaterciario.idUnicoMateria
    and c1.idAlumno = ?
    and mt1.idAlumno = ?
    and
    (     
    c1.materiaAprobada = 1 or 
    mt1.estado = 'Aprobación PreSistema' or     
    mt1.estado = 'Aprobación por Equivalencia' or 
    mt1.estado = 'Aprobación por Pase'       
    )
    )
    order by curso.idcursopredeterminado, materiaterciario.ubicacion";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iiiiii", $idcursopred, $idPlan, $cicloLectivo, $idcursopred, $idAlumno, $idAlumno);
    $stmt->execute();
    $mat = $stmt->get_result();

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
    $consulta = "SELECT *, cursospredeterminado.nombre as nombreCP 
                 FROM cursospredeterminado 
                 INNER JOIN curso 
                 ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado 
                 AND curso.idPlanEstudio = ? 
                 GROUP BY cursospredeterminado.nombre, cursospredeterminado.idcursopredeterminado
                 ORDER BY cursospredeterminado.idcursopredeterminado ASC";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("i", $idPlan);
    $stmt->execute();
    $cursosP = $stmt->get_result();

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
    curso.idPlanEstudio = ?
    where curso.idCurso in (select max(m1.idCurso) from matriculacion m1 where m1.idAlumno = ?
                            and m1.idPlanDeEstudio = ?)";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iii", $idPlan, $idAlumno, $idPlan);
    $stmt->execute();
    $cursoM = $stmt->get_result();

    $cursosM = " ";

    if (!empty($cursoM)) {
        while ($data = mysqli_fetch_array($cursoM)) {
            $cursosM = $data['idcursopredeterminado'];
        }
    }
    return $cursosM;
}
//Calificaciones de un alumno por Plan y Curso
/*function buscarMateriasCurso($conexion, $idAlumno, $idPlan, $idCursoPredeterminado)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso 
from calificacionesterciario inner join materiaterciario 
on calificacionesterciario.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
where calificacionesterciario.idAlumno = ? 
and materiaterciario.idPlan = ? 
and cursospredeterminado.idcursopredeterminado = ?
order by curso.idcursopredeterminado, materiaterciario.ubicacion desc";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iii", $idAlumno, $idPlan, $idCursoPredeterminado);
    $stmt->execute();
    $calif = $stmt->get_result();

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
        where idInscripcion = ?";
                $stmtExamen = $conexion->prepare($consultaExamen);
                $stmtExamen->bind_param("i", $idInscripcionExamen);
                $stmtExamen->execute();
                $ex = $stmtExamen->get_result();
                $examen = $ex->fetch_assoc()["calificacion"];
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
}*/

function buscarMateriasCurso($conexion, $idAlumno, $idPlan, $idCursoPredeterminado)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso 
        FROM calificacionesterciario 
        INNER JOIN materiaterciario ON calificacionesterciario.idMateria = materiaterciario.idMateria 
        INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso 
        INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado 
        WHERE calificacionesterciario.idAlumno = ? 
        AND materiaterciario.idPlan = ? 
        AND cursospredeterminado.idcursopredeterminado = ?
        ORDER BY curso.idcursopredeterminado, materiaterciario.ubicacion DESC";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iii", $idAlumno, $idPlan, $idCursoPredeterminado);
    $stmt->execute();
    $calif = $stmt->get_result();

    $listadoCalificaciones = array();
    $i = 0;

    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {
            $idInscripcionExamen = $data['idInscripcionExamen'];
            $examen = " ";

            if ($idInscripcionExamen == null || $idInscripcionExamen == 0) {
                if (
                    $data['estadoCursado'] == "Aprobación PreSistema" ||
                    $data['estadoCursado'] == "Aprobación por Equivalencia" ||
                    $data['estadoCursado'] == "Aprobación por Pase"
                ) {
                    $examen = $data['examenIntegrador'];
                } else {
                    // Buscar examen más reciente desde la función auxiliar
                    $examenes = buscarExamenes($conexion, $idAlumno, $data['idMateria']);
                    $fechaMax = null;
                    foreach ($examenes as $ex) {
                        if (!empty($ex['Calificacion']) && ($fechaMax === null || $ex['Fecha'] > $fechaMax)) {
                            $fechaMax = $ex['Fecha'];
                            $examen = $ex['Calificacion'];
                        }
                    }
                }
            } else {
                $consultaExamen = "SELECT calificacion FROM inscripcionexamenes WHERE idInscripcion = ?";
                $stmtExamen = $conexion->prepare($consultaExamen);
                $stmtExamen->bind_param("i", $idInscripcionExamen);
                $stmtExamen->execute();
                $ex = $stmtExamen->get_result();
                $examen = $ex->fetch_assoc()["calificacion"] ?? "";

                // Si no hay calificación registrada, buscar entre exámenes
                if (empty($examen)) {
                    $examenes = buscarExamenes($conexion, $idAlumno, $data['idMateria']);
                    $fechaMax = null;
                    foreach ($examenes as $ex) {
                        if (!empty($ex['Calificacion']) && ($fechaMax === null || $ex['Fecha'] > $fechaMax)) {
                            $fechaMax = $ex['Fecha'];
                            $examen = $ex['Calificacion'];
                        }
                    }
                }
            }

            $listadoCalificaciones[$i] = [
                'idMateria'         => $data['idMateria'],
                'Materia'           => $data['nombreMateria'],
                'Curso'             => $data['nombreCurso'],
                'n1'                => $data['n1'],
                'n2'                => $data['n2'],
                'n3'                => $data['n3'],
                'n4'                => $data['n4'],
                'n5'                => $data['n5'],
                'n6'                => $data['n6'],
                'n7'                => $data['n7'],
                'n8'                => $data['n8'],
                'r1'                => $data['r1'],
                'r2'                => $data['r2'],
                'r3'                => $data['r3'],
                'r4'                => $data['r4'],
                'r5'                => $data['r5'],
                'r6'                => $data['r6'],
                'r7'                => $data['r7'],
                'r8'                => $data['r8'],
                'Asistencia'        => $data['asistencia'],
                'Estado'            => $data['estadoCursado'],
                'CalificacionFinal' => $examen
            ];

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
where inscripcionexamenes.idAlumno = ? and materiaterciario.idUnicoMateria = 
(Select idUnicoMateria from materiaterciario m1 where m1.idMateria = ?)";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idAlumno, $idMateria);
    $stmt->execute();
    $exam = $stmt->get_result();

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
                  WHERE mt.idAlumno = ? 
                  AND mt.idMateria IN (
                    SELECT m1.idMateria 
                    FROM materiaterciario m1 
                    WHERE m1.idUnicoMateria IN (
                      SELECT m2.idUnicoMateria 
                      FROM materiaterciario m2 
                      WHERE m2.idMateria = ?
                    )
                  )
                  AND mt.estado IN ('Aprobación PreSistema', 'Aprobación Por Equivalencia', 'Aprobación Por Pase')";

    $stmt2 = $conexion->prepare($consulta2);
    $stmt2->bind_param("ii", $idAlumno, $idMateria);
    $stmt2->execute();
    $matriculacion = $stmt2->get_result();

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
where matriculacion.idAlumno = ?
group by idPlan";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $plan = $stmt->get_result();

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
where inscripcionexamenes_web.idAlumno = ? and materiaterciario.idPlan = ?
and inscripcionexamenes_web.idcicloLectivo = ? order by inscripcionexamenes_web.fechhora_inscri desc";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iii", $idAlumno, $idPlan, $idCicloLectivo);
    $stmt->execute();
    $sol = $stmt->get_result();

    $listadoSolicitudesExamenes = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesExamenes[$i]['idInscripcionWeb'] = $data['id_Inscripcion_web'];
            $listadoSolicitudesExamenes[$i]['Materia'] = $data['nombreMateria'];
            $listadoSolicitudesExamenes[$i]['Fecha'] = $data['fecha'];
            $listadoSolicitudesExamenes[$i]['Hora'] = $data['hora'];
            switch ($data['estado']) {
                case 1:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Pendiente";
                    break;
                case 2:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
                    break;
                case 3:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Rechazada";
                    break;
                case 4:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Cancelada";
                    break;
                case 5:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
                    break;
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
where inscripcionexamenes_web.idAlumno = ? 
and materiaterciario.idUnicoMateria = 
(Select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = ?)
and inscripcionexamenes_web.idcicloLectivo = ?
and fechasexamenes.idTurno = ?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iiii", $idAlumno, $idMateria, $idCicloLectivo, $idTurno);
    $stmt->execute();
    $sol = $stmt->get_result();

    $listadoSolicitudesExamenes = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesExamenes[$i]['idInscripcionWeb'] = $data['id_Inscripcion_web'];
            $listadoSolicitudesExamenes[$i]['Materia'] = $data['nombreMateria'];
            $listadoSolicitudesExamenes[$i]['Fecha'] = $data['fecha'];
            $listadoSolicitudesExamenes[$i]['Hora'] = $data['hora'];
            switch ($data['estado']) {
                case 1:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Pendiente";
                    break;
                case 2:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
                    break;
                case 3:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Rechazada";
                    break;
                case 4:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Cancelada";
                    break;
                case 5:
                    $listadoSolicitudesExamenes[$i]['Estado'] = "Aprobada";
                    break;
            }
            $listadoSolicitudesExamenes[$i]['Observaciones'] = $data['observaciones'];
            $i++;
        }
    }
    return $listadoSolicitudesExamenes;
}

//Listado de fechas a examen por materia y turno
function buscarFechasExamenTurno($conexion, $idMateria, $idCicloLectivo, $idTurno, $idDivision)
{// esta consulta  busca las fechas de examen por idUnicoMateria porque puede rendir en otro año donde
    // la materia tiene otro id y luego filtra por el idDivision para que sea el mismo curso
    $consulta = "SELECT * from fechasexamenes inner join materiaterciario
on fechasexamenes.idMateria = materiaterciario.idMateria inner join curso
on materiaterciario.idCurso = curso.idCurso inner join cursospredeterminado
on curso.idcursopredeterminado = cursospredeterminado.idcursopredeterminado
where materiaterciario.idMateria in 
(select m.idMateria from materiaterciario m where m.idUnicoMateria = 
(select m1.idUnicoMateria from materiaterciario m1 where m1.idMateria = ?))
and fechasexamenes.idCicloLectivo = ?
and fechasexamenes.idTurno = ? AND curso.idDivision=?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iiii", $idMateria, $idCicloLectivo, $idTurno, $idDivision);
    $stmt->execute();
    $fec = $stmt->get_result();

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
    (?, ?, ?, ?, 0, 1, ?)";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iiiis", $idAlumno, $idMateria, $idCicloLectivo, $idFechaExamen, $currentDate);
    $stmt->execute();
}

//Cancelar solicitud a examen
function cancelarExamen($conexion, $idInscripcionWeb)
{
    $consulta = "update inscripcionexamenes_web
    set estado = '4' where id_Inscripcion_web = ?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("i", $idInscripcionWeb);
    $stmt->execute();
}

//INSERTAR INTENCION DE EXAMEN (ALUMNOS QUE SOLO DEBEN FINALES)
function insertarCursadoFinalizado($conexion, $idAlumno, $idPlan, $idCicloLectivo, $intencion)
{
    // Consulta para verificar si ya existe un registro
    $verifQuery = "SELECT * FROM cursadofinalizado WHERE idAlumno = ? AND idPlan = ? AND idCicloLectivo = ?";

    $stmt = $conexion->prepare($verifQuery);
    $stmt->bind_param("iii", $idAlumno, $idPlan, $idCicloLectivo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificamos si hay registros
    if (mysqli_num_rows($resultado) == 0) {
        // Solo se ejecuta el INSERT si no hay registros
        $consulta = "INSERT INTO cursadofinalizado (idAlumno, idPlan, idCicloLectivo, intencionExamen) VALUES 
        (?, ?, ?, ?)";

        $stmt = $conexion->prepare($consulta);
        $stmt->bind_param("iiis", $idAlumno, $idPlan, $idCicloLectivo, $intencion);
        $stmt->execute();
    } else {
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
    //$idCiclolectivo = null; // Valor por defecto por si no encuentra nada

   /* if (empty($anio)) {
        error_log("buscarIdCiclo: parámetro \$anio vacío");
        return null;
    }*/

    $consulta = "SELECT idCiclolectivo FROM ciclolectivo WHERE anio = ?";

    if ($stmt = $conexion->prepare($consulta)) {
        $stmt->bind_param("s", $anio);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado) {
            if ($fila = $resultado->fetch_assoc()) {
                $idCiclolectivo = $fila['idCiclolectivo'];
            } else {
            //    error_log("buscarIdCiclo: No se encontró idCiclolectivo para anio=$anio");
            }
        } else {
          //  error_log("buscarIdCiclo: Error en get_result()");
        }

        $stmt->close();
    } else {
        //error_log("buscarIdCiclo: Error en prepare(): " . $conexion->error);
    }

    return $idCiclolectivo;
}


//buscar el anio por idciclo
function buscarnombreCiclo($conexion, $idciclo)
{
    $consulta = "select anio from ciclolectivo where idCiclolectivo= ?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("i", $idciclo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        // Obtener el ciclo lectivo
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $anio = $fila['anio']; // Almacenar el idCiclolectivo en una variable
        }
    }

    return $anio;
}

function selectCursadoFinalizadoByIdPlan($conexion,$idAlumno){
    $consulta = "SELECT p.nombre as nombre,c.anio as anio,cur.intencionExamen as intencionExamen from cursadofinalizado cur INNER JOIN
plandeestudio p ON p.idPlan=cur.idPlan INNER JOIN
ciclolectivo c ON c.idciclolectivo=cur.idCicloLectivo
 where cur.idAlumno=? ";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $fec = $stmt->get_result();

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
    $consulta="update cursadofinalizado set intencionExamen=? where idAlumno=? and idPlan=? and idCicloLectivo=?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("siii", $intencionExamen, $idAlumno, $idPlan, $idCicloLectivo);
    $stmt->execute();
}

function buscarIdPlan($conexion, $plan)
{
    $consulta="select idPlan from plandeestudio where nombre=?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("s", $plan);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        // Obtener el idplan
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $idPlan = $fila['idPlan']; // Almacenar el idplan en una variable
        }
    }

    return $idPlan;
}

function buscarNombrePlan($conexion, $idPlan)
{
    $consulta="select nombre from plandeestudio where idPlan=?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("i", $idPlan);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        // Obtener el nombre
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $plan = $fila['nombre']; // Almacenar el nombreplan en una variable
        }
    }

    return $plan;
}
//Listado solicitudes a materia de un alumno por Plan
function buscarSolicitudesMateria($conexion, $idAlumno, $idPlan, $idCicloLectivo)
{
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria 
                FROM matriculacionmateria_web 
                INNER JOIN materiaterciario 
                ON matriculacionmateria_web.idMateria = materiaterciario.idMateria
                WHERE matriculacionmateria_web.idAlumno = ? 
                AND materiaterciario.idPlan = ? 
                AND matriculacionmateria_web.idcicloLectivo = ? 
                ORDER BY fechhora_inscri DESC";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iii", $idAlumno, $idPlan, $idCicloLectivo);
    mysqli_stmt_execute($stmt);
    $sol = mysqli_stmt_get_result($stmt);

    $listadoSolicitudesMateria = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesMateria[$i]['idMatriculacionWeb'] = $data['id_matriculacion_web'];
            $listadoSolicitudesMateria[$i]['Materia'] = $data['nombreMateria'];            
            $listadoSolicitudesMateria[$i]['Fecha'] = $data['fechhora_inscri'];
            $estado = $data['estado'];
            switch ($estado) {
                case '1':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Pendiente";
                    break;
                case '2':
                case '5':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Aprobada";
                    break;
                case '3':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Rechazada";
                    break;
                case '4':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Cancelada";
                    break;
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
    $consulta = "SELECT *, materiaterciario.nombre as nombreMateria 
                FROM matriculacionmateria_web 
                INNER JOIN materiaterciario 
                ON matriculacionmateria_web.idMateria = materiaterciario.idMateria
                WHERE matriculacionmateria_web.idAlumno = ? 
                AND materiaterciario.idUnicoMateria = 
                (SELECT m1.idUnicoMateria FROM materiaterciario m1 WHERE m1.idMateria = ?)
                AND matriculacionmateria_web.idcicloLectivo = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iii", $idAlumno, $idMateria, $idCicloLectivo);
    mysqli_stmt_execute($stmt);
    $sol = mysqli_stmt_get_result($stmt);

    $listadoSolicitudesMateria = array();
    $i = 0;
    if (!empty($sol)) {
        while ($data = mysqli_fetch_array($sol)) {
            $listadoSolicitudesMateria[$i]['idMatriculacionWeb'] = $data['id_matriculacion_web'];
            $listadoSolicitudesMateria[$i]['Materia'] = $data['nombreMateria'];
            $estado = $data['estado'];
            switch ($estado) {
                case '1':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Pendiente";
                    break;
                case '2':
                case '5':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Aprobada";
                    break;
                case '3':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Rechazada";
                    break;
                case '4':
                    $listadoSolicitudesMateria[$i]['Estado'] = "Cancelada";
                    break;
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

    $consulta = "INSERT INTO matriculacionmateria_web
                (idAlumno, idMateria, idCicloLectivo, condicion, estado, fechhora_inscri)
                VALUES (?, ?, ?, 'Regular', 1, ?)";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iiis", $idAlumno, $idMateria, $idCicloLectivo, $currentDate);
    mysqli_stmt_execute($stmt);
}

//Cancelar solicitud a examen
function cancelarCursado($conexion, $idMatriculacionWeb)
{
    $consulta = "UPDATE matriculacionmateria_web
                SET estado = '4'
                WHERE id_matriculacion_web = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $idMatriculacionWeb);
    mysqli_stmt_execute($stmt);
}

//levantar ciclos lectivos
function levantarCiclosLectivos($conexion)
{
    $consulta = "SELECT idciclolectivo, anio
                FROM ciclolectivo
                ORDER BY anio DESC";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_execute($stmt);
    $ciclos = mysqli_stmt_get_result($stmt);

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
function obtenerMateriasxProfesor($conexion, $legajo, $idCicloLectivo, $idPlan)
{
    $consulta = "SELECT m.idMateria, m.nombre, c.nombre as nombreCurso
                FROM materiaterciario m 
                INNER JOIN profesorxmateria p 
                ON m.idMateria = p.idMateria 
                INNER JOIN curso c ON m.idCurso = c.idCurso
                WHERE p.idPersonal = ? 
                AND m.idCicloLectivo = ? 
                AND m.idPlan = ?
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
                ) ORDER BY m.nombre, nombreCurso";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iii", $legajo, $idCicloLectivo, $idPlan);
    mysqli_stmt_execute($stmt);
    $materias = mysqli_stmt_get_result($stmt);

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
    $consulta = "SELECT p.idPlan, p.nombre 
                FROM profesorxmateria pm 
                INNER JOIN materiaterciario m 
                ON m.idMateria = pm.idMateria 
                INNER JOIN plandeestudio p 
                ON m.idPlan = p.idPlan 
                WHERE pm.idPersonal = ? 
                GROUP BY p.nombre";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $legajo);
    mysqli_stmt_execute($stmt);
    $planes = mysqli_stmt_get_result($stmt);

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
    $consulta = 'SELECT c.*,p.apellido,p.nombre,m.estado 
                FROM persona p 
                INNER JOIN alumnosterciario a 
                ON p.idPersona = a.idPersona 
                INNER JOIN matriculacionmateria m 
                ON a.idAlumno = m.idAlumno 
                INNER JOIN calificacionesterciario c 
                ON m.idMateria = c.idMateria 
                AND c.idAlumno = a.idAlumno
                WHERE m.idMateria = ? 
                ORDER BY p.apellido, p.nombre';

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $idMateria);
    mysqli_stmt_execute($stmt);
    $querycalif = mysqli_stmt_get_result($stmt);   

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
    $consulta = 'SELECT p.nombre,p.apellido, asis.' . $dia . ', a.idAlumno 
                FROM persona p 
                INNER JOIN alumnosterciario a 
                ON p.idPersona = a.idPersona 
                INNER JOIN asistenciaterciario asis 
                ON a.idAlumno = asis.idAlumno 
                WHERE asis.idMateria = ? 
                AND asis.mes = ? 
                AND asis.idCicloLectivo = ? 
                ORDER BY p.apellido, p.nombre';

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iii", $idMateria, $mes, $idCicloLectivo);
    mysqli_stmt_execute($stmt);
    $queryasist = mysqli_stmt_get_result($stmt);

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
    $consulta = "UPDATE calificacionesterciario SET $columna = ? WHERE idCalificacion = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "si", $valor, $idCalif);
    $resultado = mysqli_stmt_execute($stmt);

    if (!$resultado) {
        $respuesta = "Error: " . mysqli_error($conexion);
    } else {
        $respuesta = "actualizado";
    }
    return $respuesta;
}

//funcion para actualizar asistencia
function actualizarAsistxDocentes($conexion, $idAlumno, $idCicloLectivo, $mes, $dia, $valor, $idMateria){
    $dia = mysqli_real_escape_string($conexion, $dia);
    $consulta = "UPDATE asistenciaterciario SET $dia = ? WHERE idAlumno = ? AND idCicloLectivo = ? AND mes = ? AND idMateria = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "siiii", $valor, $idAlumno, $idCicloLectivo, $mes, $idMateria);
    $resultado = mysqli_stmt_execute($stmt);

    if (!$resultado) {
        $respuesta = "Error: " . mysqli_error($conexion);
    } else {
        $respuesta = "Datos actualizados correctamente";
    }
    return $respuesta;
}
//funcion para obtener asistencias por materia para pdf
function obtenerAsistenciaMateriaPDF($conexion, $columnas, $idMateria, $mes, $idCicloLectivo){
    $consulta = 'SELECT p.nombre,p.apellido,' . $columnas . ' 
                FROM persona p 
                INNER JOIN alumnosterciario a 
                ON p.idPersona = a.idPersona 
                INNER JOIN asistenciaterciario asis 
                ON a.idAlumno = asis.idAlumno 
                WHERE asis.idMateria = ? 
                AND asis.mes = ? 
                AND asis.idCicloLectivo = ? 
                ORDER BY p.apellido, p.nombre';

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iii", $idMateria, $mes, $idCicloLectivo);
    mysqli_stmt_execute($stmt);
    $queryasist = mysqli_stmt_get_result($stmt);

    $numColumnas = count(explode(',', $columnas));

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
function obtenerFechasExamenProfesor($conexion, $idPersonal1, $idCicloLectivo, $idTurno, $idPlan) {
    $consulta = "SELECT m.nombre,c.nombre AS nombreCurso,f.idMateria, f.idFechaExamen,f.fecha,f.hora
                FROM fechasexamenes f 
                INNER JOIN materiaterciario m 
                ON m.idMateria = f.idMateria 
                INNER JOIN curso c 
                ON c.idCurso = m.idCurso 
                INNER JOIN plandeestudio p 
                ON c.idPlanEstudio = p.idPlan
                WHERE f.idTurno = ? 
                AND f.idCicloLectivo = ? 
                AND f.p1 = ? 
                AND c.idPlanEstudio = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iiii", $idTurno, $idCicloLectivo, $idPersonal1, $idPlan);
    mysqli_stmt_execute($stmt);
    $fec = mysqli_stmt_get_result($stmt);

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
function buscarNombreTurno($conexion, $idTurno){
    $consulta = "SELECT nombre FROM turnosexamenes WHERE idTurno = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $idTurno);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if ($resultado) {
        // Obtener el nombre
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $turno = $fila['nombre']; // Almacenar el nombre turno en una variable
        }
    }

    return $turno;
}

//obtener acta
function obtenerActa($conexion, $idFechaExamen){
    $consulta = "SELECT i.idAlumno, i.oral, i.escrito, i.calificacion, i.libro, i.folio,
                i.idCondicion, p.apellido, p.nombre 
                FROM inscripcionexamenes i 
                INNER JOIN alumnosterciario a 
                ON i.idAlumno = a.idAlumno 
                INNER JOIN persona p 
                ON a.idPersona = p.idPersona 
                WHERE i.idFechaExamen = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $idFechaExamen);
    mysqli_stmt_execute($stmt);
    $acta = mysqli_stmt_get_result($stmt);

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
    $consulta = "UPDATE inscripcionexamenes SET $setUpdate WHERE idFechaExamen = ? AND idAlumno = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "is", $idFechaExamen, $idAlumno);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_affected_rows($stmt);

    if (!$resultado) {
        $respuesta = "Error: " . mysqli_error($conexion);
    } else {
        $respuesta = "actualizado";
    }
    return $respuesta;
}
//actualizar abandono cursado
function actualizarAbandonoCursado($conexion, $idAlumno, $idMateria, $estado){
    $consulta = "UPDATE matriculacionmateria SET estado = ? WHERE idAlumno = ? AND idMateria = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "sii", $estado, $idAlumno, $idMateria);
    $resultado = mysqli_stmt_execute($stmt);

    if (!$resultado) {
        $respuesta = "Error: " . mysqli_error($conexion);
    } else {
        $respuesta = "actualizado";
    }
    return $respuesta;
}

function materiasAlumnoCurso($conn, $idAlumno, $idPlan, $idCursoPredeterminado)
{
    // Preparar la consulta con parámetros
    $consulta = "SELECT *, 
                        materiaterciario.nombre AS nombreMateria, 
                        curso.nombre AS nombreCurso, 
                        curso.idDivision AS idDivision 
                 FROM calificacionesterciario 
                 INNER JOIN materiaterciario ON calificacionesterciario.idMateria = materiaterciario.idMateria 
                 INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso 
                 INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado 
                 WHERE calificacionesterciario.idAlumno = ? 
                   AND materiaterciario.idPlan = ? 
                   AND cursospredeterminado.idcursopredeterminado = ? 
                 ORDER BY curso.idcursopredeterminado, materiaterciario.ubicacion DESC";

    // Preparar el statement
    $stmt = $conn->prepare($consulta);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    // Enlazar los parámetros
    $stmt->bind_param("iii", $idAlumno, $idPlan, $idCursoPredeterminado);
    $stmt->execute();

    // Obtener resultados
    $resultado = $stmt->get_result();
    $materias = array();

    // Armar array con los datos
    while ($fila = $resultado->fetch_assoc()) {
        $materias[] = array(
            'idMateria'        => $fila['idMateria'],
            'Materia'          => $fila['nombreMateria'],
            'Curso'            => $fila['nombreCurso'],
            'Estado'           => $fila['Estado'],
            'CalificacionFinal'=> $fila['CalificacionFinal'],
            'idDivision'       => $fila['idDivision']
        );
    }

    return $materias;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////
///////FIN CONSULTAS CREADAS PARA PLATAFORMA - INICIO CONSULTAS SISTEMA ////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

//buscar todos los planes
function buscarTodosPlanes($conexion) {
      $consulta = "SELECT * FROM plandeestudio";
    $planes = mysqli_query($conexion, $consulta);
  
    $resultados = [];
    while ($fila = mysqli_fetch_assoc($planes)) {
        $resultados[] = $fila;
    }
    return $resultados;
}
function buscarCursosPlanCiclo($conexion, $idPlan, $idCiclo) {
    $consulta = "SELECT * FROM curso WHERE idPlanEstudio = ? and idciclo=?";
    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idPlan, $idCiclo);
    $stmt->execute();
    $result = $stmt->get_result();
    $cursos = array();
    while ($data = $result->fetch_assoc()) {
        $cursos[] = $data;
    }
    return $cursos;
}

function materiasPlanCurso($conexion, $idPlan, $idCurso) {
    $consulta = "SELECT materiaterciario.nombre as nombreMateria, curso.nombre as nombreCurso 
    FROM materiaterciario 
    INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso 
    INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado 
    WHERE materiaterciario.idPlan = ? 
    AND curso.idCurso = ? 
    ORDER BY curso.idcursopredeterminado, materiaterciario.ubicacion DESC";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idPlan, $idCurso);
    $stmt->execute();
    $result = $stmt->get_result();

    $materias = array();
    while ($data = $result->fetch_assoc()) {
        $materias[] = array(
            'nombreMateria' => $data['nombreMateria'],
            'nombreCurso' => $data['nombreCurso']
        );
    }
    return $materias;
}


?>
