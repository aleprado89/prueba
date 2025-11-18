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

//Calificaciones de un alumno por Plan
function buscarMaterias($conexion, $idAlumno, $idPlan)
{
  $consulta = "SELECT *,
    materiaterciario.nombre as nombreMateria,
    curso.nombre as nombreCurso,
    curso.idDivision as idDivision,
    materiaterciario.idCicloLectivo AS idCicloLectivoMateria,
    cl.anio AS anioCiclo
  FROM calificacionesterciario
  INNER JOIN materiaterciario ON calificacionesterciario.idMateria = materiaterciario.idMateria
  INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso
  INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
  INNER JOIN ciclolectivo cl ON materiaterciario.idCicloLectivo = cl.idCiclolectivo
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
      $examen = " ";

      if ($idInscripcionExamen == null || $idInscripcionExamen == 0) {
        if (
          $data['estadoCursado'] == "Aprobación PreSistema" ||
          $data['estadoCursado'] == "Aprobación por Equivalencia" ||
          $data['estadoCursado'] == "Aprobación por Pase"
        ) {
          $examen = $data['examenIntegrador'];
        } else {
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
        'idCalificacion'    => $data['idCalificacion'],
        'idMateria'     => $data['idMateria'],
        'Materia'       => $data['nombreMateria'],
        'Curso'       => $data['nombreCurso'],
        'idCicloLectivoMateria' => $data['idCicloLectivoMateria'],
        'anioCiclo'     => $data['anioCiclo'],
        'n1'          => $data['n1'],
        'n2'          => $data['n2'],
        'n3'          => $data['n3'],
        'n4'          => $data['n4'],
        'n5'          => $data['n5'],
        'n6'          => $data['n6'],
        'n7'          => $data['n7'],
        'n8'          => $data['n8'],
        'r1'          => $data['r1'],
        'r2'          => $data['r2'],
        'r3'          => $data['r3'],
        'r4'          => $data['r4'],
        'r5'          => $data['r5'],
        'r6'          => $data['r6'],
        'r7'          => $data['r7'],
        'r8'          => $data['r8'],
        'Asistencia'    => $data['asistencia'],
        'Estado'      => $data['estadoCursado'],
        'CalificacionFinal' => $examen,
        'idDivision'    => $data['idDivision'],
        'materiaAprobada' => $data['materiaAprobada'] 
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
      $listadoCursosP[$i]['nombreCurso'] = $data['nombreCP']; // Asegúrate de que este campo se use para el nombre
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

function buscarMateriasCurso($conexion, $idAlumno, $idPlan, $idCursoPredeterminado)
{
  $consulta = "SELECT *,
    materiaterciario.nombre as nombreMateria,
    curso.nombre as nombreCurso,
    materiaterciario.idCicloLectivo AS idCicloLectivoMateria, /* AGREGADO */
    cl.anio AS anioCiclo /* AGREGADO */
  FROM calificacionesterciario
  INNER JOIN materiaterciario ON calificacionesterciario.idMateria = materiaterciario.idMateria
  INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso
  INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
  INNER JOIN ciclolectivo cl ON materiaterciario.idCicloLectivo = cl.idCiclolectivo /* AGREGADO */
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
        'idMateria'     => $data['idMateria'],
        'Materia'       => $data['nombreMateria'],
        'Curso'       => $data['nombreCurso'],
        'idCicloLectivoMateria' => $data['idCicloLectivoMateria'], // AGREGADO
        'anioCiclo'     => $data['anioCiclo'], // AGREGADO
        'n1'          => $data['n1'],
        'n2'          => $data['n2'],
        'n3'          => $data['n3'],
        'n4'          => $data['n4'],
        'n5'          => $data['n5'],
        'n6'          => $data['n6'],
        'n7'          => $data['n7'],
        'n8'          => $data['n8'],
        'r1'          => $data['r1'],
        'r2'          => $data['r2'],
        'r3'          => $data['r3'],
        'r4'          => $data['r4'],
        'r5'          => $data['r5'],
        'r6'          => $data['r6'],
        'r7'          => $data['r7'],
        'r8'          => $data['r8'],
        'Asistencia'    => $data['asistencia'],
        'Estado'      => $data['estadoCursado'],
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
function buscarSolicitudesExamen($conexion, $idAlumno, $idPlan, $idCicloLectivo,$idTurno)
{
  $consulta = "SELECT *, materiaterciario.nombre as nombreMateria from inscripcionexamenes_web inner join fechasexamenes
on inscripcionexamenes_web.idFechaExamen = fechasexamenes.idFechaExamen inner join materiaterciario
on inscripcionexamenes_web.idMateria = materiaterciario.idMateria
where inscripcionexamenes_web.idAlumno = ? and materiaterciario.idPlan = ? and fechasexamenes= ?
and inscripcionexamenes_web.idcicloLectivo = ? order by inscripcionexamenes_web.fechhora_inscri desc";

  $stmt = $conexion->prepare($consulta);
  $stmt->bind_param("iiii", $idAlumno, $idPlan, $idCicloLectivo,$idTurno);
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
{// esta consulta busca las fechas de examen por idUnicoMateria porque puede rendir en otro año donde
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
      $listadoFechasExamenes[$i]['idMateria']=$data['idMateria'];
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
  $consulta = "SELECT idCiclolectivo FROM ciclolectivo WHERE anio = ?";

  if ($stmt = $conexion->prepare($consulta)) {
    $stmt->bind_param("s", $anio);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $fila = $resultado->fetch_assoc()) {
        return $fila['idCiclolectivo'];
    }
    $stmt->close();
  }
  return null; // Return null if not found or error
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
        GROUP BY p.idPlan";

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
  $consulta = "SELECT c.*,p.apellido,p.nombre,m.estado 
                FROM persona p 
                INNER JOIN alumnosterciario a 
                ON p.idPersona = a.idPersona 
                INNER JOIN matriculacionmateria m 
                ON a.idAlumno = m.idAlumno 
                and m.estado not in 
                ('PreSistema',
                'Desaprob./Recurs. PreSistema',
                'Libre PreSistema',
                'Regularidad PreSistema',
                'Aprobación PreSistema',
                'Aprobación por Equivalencia',
                'Aprobación por Pase')
                INNER JOIN calificacionesterciario c 
                ON m.idMateria = c.idMateria 
                AND c.idAlumno = a.idAlumno
                WHERE m.idMateria = ? 
                ORDER BY p.apellido, p.nombre";


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
      $listadoMateria[$i]['examenIntegrador'] = $data['examenIntegrador'];

      $i++;
    }
  }
  return $listadoMateria;
}
function obtenerCalificacionesMateriaPDF($conexion, $idMateria){
  $consulta = "SELECT c.*,p.apellido,p.nombre,m.estado 
                FROM persona p 
                INNER JOIN alumnosterciario a 
                ON p.idPersona = a.idPersona 
                INNER JOIN matriculacionmateria m 
                ON a.idAlumno = m.idAlumno 
                and m.estado not in 
                ('PreSistema',
                'Desaprob./Recurs. PreSistema',
                'Libre PreSistema',
                'Regularidad PreSistema',
                'Aprobación PreSistema',
                'Aprobación por Equivalencia',
                'Aprobación por Pase')
                INNER JOIN calificacionesterciario c 
                ON m.idMateria = c.idMateria 
                AND c.idAlumno = a.idAlumno
                WHERE m.idMateria = ? AND m.estado != 'Abandonó Cursado'
                ORDER BY p.apellido, p.nombre";

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
  // Asegurarse de que $dia sea seguro para usar en el nombre de la columna
  $dia_columna_escaped = mysqli_real_escape_string($conexion, $dia);

  $consulta = 'SELECT p.nombre, p.apellido, asis.' . $dia_columna_escaped . ', a.idAlumno, mm.estado
        FROM persona p
        INNER JOIN alumnosterciario a
        ON p.idPersona = a.idPersona
        INNER JOIN asistenciaterciario asis
        ON a.idAlumno = asis.idAlumno
        INNER JOIN matriculacionmateria mm ON a.idAlumno = mm.idAlumno AND asis.idMateria = mm.idMateria
        WHERE asis.idMateria = ?
        AND asis.mes = ?
        AND asis.idCicloLectivo = ?
        ORDER BY p.apellido, p.nombre';

  $stmt = $conexion->prepare($consulta);
  $stmt->bind_param("iii", $idMateria, $mes, $idCicloLectivo);
  $stmt->execute();
  $queryasist = $stmt->get_result();

  $lista = array();
  $i = 0;
  if (!empty($queryasist)) {
    while ($data = mysqli_fetch_array($queryasist)) {
      $lista[$i]['idAlumno'] = $data['idAlumno'];
      $lista[$i]['apellido'] = $data['apellido'];
      $lista[$i]['nombre'] = $data['nombre'];
      $lista[$i]['dia'] = $data[$dia_columna_escaped];
      $lista[$i]['estado'] = $data['estado']; // <-- Añadido: el estado de la matriculación
      $i++;
    }
  }
  return $lista;
}

//actualizar calificaciones docente
function actualizarCalifDocente($conexion, $idCalif, $columna, $valor){
  $consulta = "UPDATE calificacionesterciario SET registroModificacion=1, $columna = ? WHERE idCalificacion = ?";

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
  $consulta = "UPDATE asistenciaterciario SET registroModificacion=1,$dia = ? WHERE idAlumno = ? AND idCicloLectivo = ? AND mes = ? AND idMateria = ?";

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
  $consulta = 'SELECT p.nombre,p.apellido, ' . $columnas . '
        FROM persona p
        INNER JOIN alumnosterciario a
        ON p.idPersona = a.idPersona
        INNER JOIN asistenciaterciario asis
        ON a.idAlumno = asis.idAlumno
        INNER JOIN matriculacionmateria mt
        ON a.idAlumno = mt.idAlumno AND asis.idMateria=mt.idMateria
        WHERE asis.idMateria = ?
        AND asis.mes = ?
        AND asis.idCicloLectivo = ?
        AND mt.estado != "Abandonó Cursado"
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

// *** NUEVA FUNCION PARA OBTENER MESES CON ASISTENCIA PARA EL ALUMNO EN UN CICLO ***
function obtenerMesesConAsistenciaMateria($conn, $idAlumno, $idMateria, $idCicloLectivo) {
    $meses = [];
    $stmt = $conn->prepare("SELECT DISTINCT mes FROM asistenciaterciario WHERE idAlumno = ? AND idMateria = ? AND idCicloLectivo = ? ORDER BY mes ASC");
    if ($stmt) {
        $stmt->bind_param("iii", $idAlumno, $idMateria, $idCicloLectivo);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $meses[] = (int)$row['mes'];
        }
        $stmt->close();
    }
    return $meses;
}
// NUEVA: Obtener el registro completo de asistencia de una materia para un mes y alumno específico.
function obtenerAsistenciaRegistroMateriaMes($conn, $idAlumno, $idMateria, $mes, $idCicloLectivo) {
    $stmt = $conn->prepare("SELECT * FROM asistenciaterciario WHERE idAlumno = ? AND idMateria = ? AND mes = ? AND idCicloLectivo = ?");
    if ($stmt) {
        $stmt->bind_param("iiii", $idAlumno, $idMateria, $mes, $idCicloLectivo);
        $stmt->execute();
        $result = $stmt->get_result();
        $asistencia = $result->fetch_assoc();
        $stmt->close();
        return $asistencia; // Retorna un array asociativo con todas las columnas d1-d31, o null si no hay.
    }
    return null;
}

// *** NUEVA FUNCION PARA OBTENER TODAS LAS MATERIAS DE UN ALUMNO EN UN CICLO ***
function obtenerMateriasDeAlumnoEnCiclo($conexion, $idAlumno, $idCicloLectivo) {
    $consulta = 'SELECT DISTINCT mt.idMateria, mt.nombre as nombreMateria, c.nombre as nombreCurso
                 FROM matriculacionmateria mm
                 INNER JOIN materiaterciario mt ON mm.idMateria = mt.idMateria
                 INNER JOIN curso c ON mt.idCurso = c.idCurso
                 WHERE mm.idAlumno = ? AND mt.idCicloLectivo = ? AND mm.estado != "Abandonó Cursado"
                 ORDER BY mt.nombre ASC';
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "ii", $idAlumno, $idCicloLectivo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $materias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $materias[] = $row;
    }
    return $materias;
}

// *** NUEVA FUNCION PARA OBTENER ASISTENCIA DE UNA MATERIA ESPECIFICA PARA UN ALUMNO EN UN MES ***
function obtenerAsistenciaDeMateriaParaAlumno($conexion, $idAlumno, $idMateria, $mes, $idCicloLectivo) {
    $num_dias = cal_days_in_month(CAL_GREGORIAN, $mes, buscarnombreCiclo($conexion, $idCicloLectivo));
    $columnasAsistencia = [];
    for ($i = 1; $i <= $num_dias; $i++) {
        $columnasAsistencia[] = "asis.d" . $i;
    }
    $columnas_sql = implode(',', $columnasAsistencia);

    $consulta = 'SELECT ' . $columnas_sql . '
                 FROM asistenciaterciario asis
                 WHERE asis.idAlumno = ?
                 AND asis.idMateria = ?
                 AND asis.mes = ?
                 AND asis.idCicloLectivo = ?';
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iiii", $idAlumno, $idMateria, $mes, $idCicloLectivo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = $result->fetch_assoc(); // Solo esperamos una fila para una materia/alumno/mes específicos

    $attendance_data = [];
    if ($data) {
        for ($i = 1; $i <= $num_dias; $i++) {
            $attendance_data['d' . $i] = $data['d' . $i] ?? ''; // Maneja días que quizás no existan
        }
    }
    return $attendance_data;
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
        WHERE i.idFechaExamen = ? order by p.apellido, p.nombre";

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
  mysqli_stmt_execute($stmt);
  $resultado = mysqli_stmt_affected_rows($stmt);

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
              curso.idDivision AS idDivision,
              materiaterciario.idCicloLectivo AS idCicloLectivoMateria, /* AGREGADO */
              cl.anio AS anioCiclo /* AGREGADO */
           FROM calificacionesterciario
           INNER JOIN materiaterciario ON calificacionesterciario.idMateria = materiaterciario.idMateria
           INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso
           INNER JOIN cursospredeterminado ON cursospredeterminado.idcursopredeterminado = curso.idcursopredeterminado
           INNER JOIN ciclolectivo cl ON materiaterciario.idCicloLectivo = cl.idCiclolectivo /* AGREGADO */
           WHERE calificacionesterciario.idAlumno = ?
            AND materiaterciario.idPlan = ?
            AND cursospredeterminado.idcursopredeterminado = ?
            AND  (calificacionesterciario.materiaAprobada is null or calificacionesterciario.materiaAprobada = 0)
            AND CURDATE() > materiaterciario.FechaFin
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
      'idMateria'     => $fila['idMateria'],
      'Materia'       => $fila['nombreMateria'],
      'Curso'       => $fila['nombreCurso'],
      'idCicloLectivoMateria' => $fila['idCicloLectivoMateria'], // AGREGADO
      'anioCiclo'     => $fila['anioCiclo'], // AGREGADO
      'Estado'      => $fila['Estado'],
      'CalificacionFinal'=> $fila['CalificacionFinal'],
      'idDivision'    => $fila['idDivision']
    );
  }

  return $materias;
}
//obtener id calificacion por id alumno y idmateria
function obtenerIdCalificacion($conexion, $idAlumno, $idMateria) {
  $consulta = "SELECT idCalificacion FROM calificacionesterciario WHERE idAlumno = ? AND idMateria = ?";
  $stmt = $conexion->prepare($consulta);

  if (!$stmt) {
    return null;
  }

  $stmt->bind_param("ii", $idAlumno, $idMateria);
  $stmt->execute();

  $idCalificacion = null; // Inicialización para evitar 'undefined variable'
  $stmt->bind_result($idCalificacion);

  if ($stmt->fetch()) {
    $stmt->close();
    return $idCalificacion;
  } else {
    $stmt->close();
    return null;
  }
}
function obtenerIdMateriaPorFechaExamen(mysqli $conn, int $idFechaExamen): int
{
    $sql  = "SELECT idMateria FROM fechasexamenes 
             WHERE idFechaExamen = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idFechaExamen);
    $stmt->execute();
    $stmt->bind_result($idMateria);
    $stmt->fetch();
    $stmt->close();

    return $idMateria ?? 0;   // 0 o lanza excepción si no lo encuentras
}



//////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////
///////FIN CONSULTAS CREADAS PARA PLATAFORMA - INICIO CONSULTAS SISTEMA SECRETARIA ////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

//buscar todos los planes
function buscarTodosPlanes($conexion) {
  $consulta = "SELECT idPlan, nombre FROM plandeestudio ORDER BY nombre ASC"; // Añadido ORDER BY
  $stmt = $conexion->prepare($consulta);
  $stmt->execute();
  $planes = $stmt->get_result();

  $resultados = [];
  while ($fila = $planes->fetch_assoc()) {
    $resultados[] = $fila;
  }
  return $resultados;
}

function buscarCursosPlanCiclo($conexion, $idPlan, $idCiclo) {
  $consulta = "SELECT idCurso, nombre FROM curso WHERE idPlanEstudio = ? and idCiclo=?";
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
    // MODIFICAR LA CONSULTA PARA INCLUIR idUnicoMateria
    $consulta = "SELECT 
                    materiaterciario.nombre as nombreMateria,
                    materiaterciario.idMateria,
                    materiaterciario.idUnicoMateria,  -- <-- AÑADIR ESTA LÍNEA
                    curso.nombre as nombreCurso
                FROM materiaterciario
                INNER JOIN curso ON materiaterciario.idCurso = curso.idCurso
                WHERE materiaterciario.idPlan = ?
                AND curso.idCurso = ?
                ORDER BY materiaterciario.ubicacion DESC";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idPlan, $idCurso);
    $stmt->execute();
    $result = $stmt->get_result();

    $materias = array();
    while ($data = $result->fetch_assoc()) {
        $materias[] = array(
            'nombreMateria' => $data['nombreMateria'],
            'nombreCurso' => $data['nombreCurso'],
            'idMateria' => $data['idMateria'],
            'idUnicoMateria' => $data['idUnicoMateria'] // <-- AÑADIR ESTA LÍNEA
        );
    }
    return $materias;
}

/**
  * Obtiene todas las materias asignadas a un ciclo lectivo y plan de estudio.
  * Esta función es para uso de secretaría/administración donde no se filtra por docente.
  *
  * @param mysqli $conn Objeto de conexión a la base de datos.
  * @param int $idCicloLectivo ID del ciclo lectivo.
  * @param int $idPlan ID del plan de estudio.
  * @return array Un array de arrays, cada uno representando una materia con su curso.
  */
function obtenerTodasLasMaterias($conn, $idCicloLectivo, $idPlan, $idCurso = null) { // Añade $idCurso como parámetro opcional
  $sql = "
    SELECT
      mt.idMateria,
      mt.nombre AS Materia,
      c.nombre AS Curso,
      mt.idCicloLectivo,
      mt.idPlan
    FROM
      materiaterciario mt
    JOIN
      curso c ON mt.idCurso = c.idCurso
    WHERE
      mt.idCicloLectivo = ? AND mt.idPlan = ?
  ";
  $types = "ii";
  $params = [$idCicloLectivo, $idPlan];

  if ($idCurso !== null && $idCurso !== '') { // Añadir filtro por curso si se proporciona
    $sql .= " AND mt.idCurso = ?";
    $types .= "i";
    $params[] = $idCurso;
  }

  $sql .= " ORDER BY mt.nombre ASC";

  $stmt = $conn->prepare($sql);
  if ($stmt === false) {
    error_log("Error al preparar la consulta (obtenerTodasLasMaterias): " . $conn->error);
    return [];
  }

  // Usar call_user_func_array para bind_param con un array de parámetros dinámico
  call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

  $stmt->execute();
  $result = $stmt->get_result();

  $materias = [];
  while ($row = $result->fetch_assoc()) {
    $materias[] = $row;
  }
  $stmt->close();
  return $materias;
}

// Nueva función para obtener condiciones de cursado para el menú desplegable
function obtenerCondicionesCursado($conexion) {
  // Asumiendo que idCondicion del 1 al 9 son los estados válidos para selección
  $consulta = "SELECT idCondicion, condicion FROM condicionescursado WHERE idCondicion BETWEEN 1 AND 9 ORDER BY idCondicion ASC";
  $stmt = $conexion->prepare($consulta);
  if (!$stmt) {
    error_log("Error al preparar la consulta (obtenerCondicionesCursado): " . $conexion->error);
    return [];
  }
  $stmt->execute();
  $result = $stmt->get_result();

  $condiciones = [];
  while ($row = $result->fetch_assoc()) {
    $condiciones[] = $row;
  }
  $stmt->close();
  return $condiciones;
}

// **NUEVA FUNCIÓN: obtenerAsistenciaMateriaSecretaria**
// Copia de obtenerAsistenciaMateria pero incluye el estado de la matriculacionmateria
function obtenerAsistenciaMateriaSecretaria($conexion, $idMateria, $mes, $dia, $idCicloLectivo){
  // Asegurarse de que $dia sea seguro para usar en el nombre de la columna
  $dia_columna_escaped = mysqli_real_escape_string($conexion, $dia);

  $consulta = 'SELECT p.nombre, p.apellido, asis.' . $dia_columna_escaped . ', a.idAlumno, mm.estado
        FROM persona p
        INNER JOIN alumnosterciario a
        ON p.idPersona = a.idPersona
        INNER JOIN asistenciaterciario asis
        ON a.idAlumno = asis.idAlumno
        LEFT JOIN matriculacionmateria mm ON a.idAlumno = mm.idAlumno AND asis.idMateria = mm.idMateria
        WHERE asis.idMateria = ?
        AND asis.mes = ?
        AND asis.idCicloLectivo = ?
        ORDER BY p.apellido, p.nombre';

  $stmt = $conexion->prepare($consulta);
  $stmt->bind_param("iii", $idMateria, $mes, $idCicloLectivo);
  $stmt->execute();
  $queryasist = $stmt->get_result();

  $lista = array();
  $i = 0;
  if (!empty($queryasist)) {
    while ($data = mysqli_fetch_array($queryasist)) {
      $lista[$i]['idAlumno'] = $data['idAlumno'];
      $lista[$i]['apellido'] = $data['apellido'];
      $lista[$i]['nombre'] = $data['nombre'];
      $lista[$i]['dia'] = $data[$dia_columna_escaped];
      $lista[$i]['estado'] = $data['estado']; // <-- Añadido: el estado de la matriculación
      $i++;
    }
  }
  return $lista;
}

function buscarAlumnos($conexion, $apellido = '', $nombre = '') {
    // MODIFICACIÓN: Se ajusta el ORDER BY para una priorización más precisa.
    $sql = "SELECT p.idPersona, p.apellido, p.nombre, p.dni, a.idAlumno
            FROM persona p
            INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona
            WHERE p.apellido LIKE ? AND p.nombre LIKE ?
            ORDER BY
                CASE
                    WHEN p.apellido LIKE ? THEN 1  -- Prioridad 1 si el apellido COMIENZA con el término
                    ELSE 2                         -- Prioridad 2 para el resto de coincidencias
                END,
                p.apellido, p.nombre"; // Luego, ordenar alfabéticamente

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta (buscarAlumnos): " . $conexion->error);
        return [];
    }

    $paramApellidoLike = '%' . $apellido . '%';
    $paramNombreLike = '%' . $nombre . '%';
    // El parámetro para el 'CASE' debe ser el que busca al inicio
    $paramApellidoStarts = $apellido . '%'; 

    // Se vinculan los 3 parámetros en el orden correcto
    $stmt->bind_param("sss", $paramApellidoLike, $paramNombreLike, $paramApellidoStarts);
    
    $stmt->execute();
    $result = $stmt->get_result();

    $alumnos = [];
    while ($row = $result->fetch_assoc()) {
        $alumnos[] = $row;
    }
    $stmt->close();
    return $alumnos;
}


// NEW/MODIFIED: obtaining all student data
function obtenerDatosAlumno($conexion, $idAlumno) {
    if (!$idAlumno) return null;

    // Updated query to match the revised field mappings and assumed schema
    $sql = "SELECT p.idPersona, p.apellido, p.nombre, p.dni, p.sexo, p.fechaNac AS fechaNacimiento,
                   p.nacionalidad AS nacionalidadNacimiento, p.provincia AS provinciaNacimiento, p.ciudad AS localidadNacimiento,
                   p.mail AS email, p.telefono, p.celular, p.direccion AS domicilio, p.codigoPostal AS cp,
                   p.cuilPre, p.cuilPost, p.telefonoEmergencia, p.FotoCarnet AS fotoURL,
                   a.idAlumno, a.vivePadre, a.viveMadre, a.egresado, a.trabaja, a.retiroBiblioteca,
                   a.observaciones AS observacionesAlumno, a.mailInstitucional, a.documentacion, a.materiasAdeuda, a.idFamilia
            FROM persona p
            INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona
            WHERE a.idAlumno = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing obtenerDatosAlumno: " . $conexion->error);
        return null;
    }
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// NEW: function for inserting persona data
function insertPersona($conexion, $data) {
    $sql = "INSERT INTO persona (apellido, nombre, dni, sexo, fechaNac, nacionalidad, provincia, ciudad, mail, telefono, celular, cuilPre, cuilPost, telefonoEmergencia, direccion, codigoPostal, FotoCarnet)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing insertPersona: " . $conexion->error);
        return false;
    }
    // 17 's' por los 17 campos VARCHAR/DATE/etc. (FechaNac -> s, FotoCarnet -> s)
    // fechaNacimiento puede ser null, pero mysqli_stmt_bind_param lo espera como string o null.
    // PHP 8+ handles null for 's' type correctly, converting to SQL NULL. Older PHP might require explicit NULL.
    // Assuming PHP 8+ compatible or a custom wrapper for null handling.
    $stmt->bind_param("sssssssssssssssss",
        $data['apellido'], $data['nombre'], $data['dni'], $data['sexo'], $data['fechaNacimiento'],
        $data['nacionalidadNacimiento'], $data['provinciaNacimiento'], $data['localidadNacimiento'],
        $data['email'], $data['telefono'], $data['celular'], $data['cuilPre'], $data['cuilPost'],
        $data['telefonoEmergencia'], $data['domicilio'], $data['cp'], $data['fotoURL']
    );
    $success = $stmt->execute();
    $id = $conexion->insert_id;
    $stmt->close();
    return $success ? $id : false;
}

// NEW: function for updating persona data
function updatePersona($conexion, $idPersona, $data) {
    $sql = "UPDATE persona SET apellido=?, nombre=?, dni=?, sexo=?, fechaNac=?, nacionalidad=?, provincia=?, ciudad=?, mail=?, telefono=?, celular=?, cuilPre=?, cuilPost=?, telefonoEmergencia=?, direccion=?, codigoPostal=?, FotoCarnet=?
            WHERE idPersona=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing updatePersona: " . $conexion->error);
        return false;
    }
    // 17 's' para los campos SET, y 1 'i' para el WHERE idPersona. Total 18.
    $stmt->bind_param("sssssssssssssssssi",
        $data['apellido'], $data['nombre'], $data['dni'], $data['sexo'], $data['fechaNacimiento'],
        $data['nacionalidadNacimiento'], $data['provinciaNacimiento'], $data['localidadNacimiento'],
        $data['email'], $data['telefono'], $data['celular'], $data['cuilPre'], $data['cuilPost'],
        $data['telefonoEmergencia'], $data['domicilio'], $data['cp'], $data['fotoURL'],
        $idPersona
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// NEW: function for inserting alumnosterciario data
function insertAlumnoTerciario($conexion, $idPersona, $data) {
    $sql = "INSERT INTO alumnosterciario (idPersona, vivePadre, viveMadre, egresado, trabaja, retiroBiblioteca, observaciones, mailInstitucional, documentacion, materiasAdeuda, idFamilia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing insertAlumnoTerciario: " . $conexion->error);
        return false;
    }
    // 6 'i' (idPersona, vivePadre, viveMadre, egresado, trabaja, retiroBiblioteca)
    // 4 's' (observaciones, mailInstitucional, documentacion, materiasAdeuda)
    // 1 'i' (idFamilia).
    // Total 11 variables. String de tipos: "iiiiisssisi"
    $stmt->bind_param("iiiiiissssi", // String de tipos correcta
        $idPersona, $data['vivePadre'], $data['viveMadre'], $data['egresado'],
        $data['trabaja'], $data['retiroBiblioteca'], $data['observacionesAlumno'], $data['mailInstitucional'],
        $data['documentacion'], $data['materiasAdeuda'], $data['idFamilia']
    );
    $success = $stmt->execute();
    $id = $conexion->insert_id;
    $stmt->close();
    return $success ? $id : false;
}

// NEW: function for updating alumnosterciario data
function updateAlumnoTerciario($conexion, $idPersona, $data) {
    $sql = "UPDATE alumnosterciario SET vivePadre=?, viveMadre=?, egresado=?, trabaja=?, retiroBiblioteca=?, observaciones=?, mailInstitucional=?, documentacion=?, materiasAdeuda=?, idFamilia=?
            WHERE idPersona=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing updateAlumnoTerciario: " . $conexion->error);
        return false;
    }
    
    $stmt->bind_param("iiiiissssii", // String de tipos correcta (5i, 4s, 1i, 1i -> 5+4+1+1 = 11)
        $data['vivePadre'], $data['viveMadre'], $data['egresado'],
        $data['trabaja'], $data['retiroBiblioteca'], $data['observacionesAlumno'], $data['mailInstitucional'],
        $data['documentacion'], $data['materiasAdeuda'], $data['idFamilia'],
        $idPersona
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Existing function for DNI existence check
function dniExiste($conexion, $dni, $excludeIdPersona = null) {
    $sql = "SELECT COUNT(*) AS count FROM persona WHERE dni = ?";
    if ($excludeIdPersona) {
        $sql .= " AND idPersona != ?";
    }
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta (dniExiste): " . $conexion->error);
        return true; // Asumir que existe para evitar inconsistencias si hay error
    }
    if ($excludeIdPersona) {
        $stmt->bind_param("si", $dni, $excludeIdPersona);
    } else {
        $stmt->bind_param("s", $dni);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] > 0;
}

// --- NUEVAS FUNCIONES PARA MATRICULACIÓN DE PLAN/CURSO ---

function insertarMatriculacionPlan($conexion, $data) {
    $sql = "INSERT INTO matriculacion (idNivel, idCurso, idAlumno, fechaMatriculacion, anio, estado, tarde, idPlanDeEstudio, pagoMatricula, pagoMonto, certificadoSalud, fechaBajaMatriculacion, certificadoTrabajo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error en preparar insertarMatriculacionPlan: " . $conexion->error);
        return false;
    }

    // Convertir fechas vacías a NULL
    $fechaMatriculacion = !empty($data['fechaMatriculacion']) ? $data['fechaMatriculacion'] : null;
    $fechaBajaMatriculacion = !empty($data['fechaBajaMatriculacion']) ? $data['fechaBajaMatriculacion'] : null;

    // Convertir pagoMonto a NULL si es 0 o vacío y pagoMatricula es 0
    $pagoMonto = ($data['pagoMatricula'] == 0 || empty($data['pagoMonto'])) ? null : $data['pagoMonto'];
    $estado = empty($fechaBajaMatriculacion) ? 'Activo' : 'De Baja'; // Determina el estado
    
    $idNivel = 6; // Siempre 6 según la especificación
    $anio = date('Y'); // Año actual

    // Tipos de datos: i (int), s (string), d (double), b (blob)
    // Parámetros: idNivel(i), idCurso(i), idAlumno(i), fechaMatriculacion(s), anio(i), estado(s), tarde(i), idPlanDeEstudio(i), pagoMatricula(i), pagoMonto(s), certificadoSalud(i), fechaBajaMatriculacion(s), certificadoTrabajo(i)
    // Total: 6 ints, 4 strings
    $stmt->bind_param("iiisisiiisisi", // String de tipos corregida: 6i, 4s, 3i = 13 (idCurso, idAlumno, anio, tarde, pagoMatricula, certificadoSalud, certificadoTrabajo)
        $idNivel,
        $data['idCurso'],
        $data['idAlumno'],
        $fechaMatriculacion,
        $anio,
        $estado,
        $data['tarde'],
        $data['idPlanDeEstudio'],
        $data['pagoMatricula'], // 0 o 1
        $pagoMonto, // Puede ser null
        $data['certificadoSalud'], // Asumimos que viene como 0 si no hay campo específico o si es el checkbox, será 1 o 0
        $fechaBajaMatriculacion, // Puede ser null
        $data['certificadoTrabajo'] // 0 o 1
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function obtenerMatriculacionesPlanAlumno($conexion, $idAlumno) {
    $sql = "SELECT m.idMatriculacion, pe.nombre AS nombrePlan, c.nombre AS nombreCurso, m.fechaMatriculacion, m.estado, m.pagoMatricula, m.pagoMonto, m.fechaBajaMatriculacion, m.certificadoTrabajo, m.idPlanDeEstudio, m.idCurso
            FROM matriculacion m
            INNER JOIN plandeestudio pe ON m.idPlanDeEstudio = pe.idPlan
            INNER JOIN curso c ON m.idCurso = c.idCurso
            WHERE m.idAlumno = ? ORDER BY m.fechaMatriculacion DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $result = $stmt->get_result();
    $matriculaciones = [];
    while ($row = $result->fetch_assoc()) {
        $matriculaciones[] = $row;
    }
    $stmt->close();
    return $matriculaciones;
}

/*function actualizarMatriculacionPlan($conexion, $idMatriculacion, $data) {
    $sql = "UPDATE matriculacion SET idNivel=?, idCurso=?, idAlumno=?, fechaMatriculacion=?, anio=?, estado=?, tarde=?, idPlanDeEstudio=?, pagoMatricula=?, pagoMonto=?, certificadoSalud=?, fechaBajaMatriculacion=?, certificadoTrabajo=?
            WHERE idMatriculacion=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error en preparar actualizarMatriculacionPlan: " . $conexion->error);
        return false;
    }

    $fechaMatriculacion = !empty($data['fechaMatriculacion']) ? $data['fechaMatriculacion'] : null;
    $fechaBajaMatriculacion = !empty($data['fechaBajaMatriculacion']) ? $data['fechaBajaMatriculacion'] : null;
    $pagoMonto = ($data['pagoMatricula'] == 0 || empty($data['pagoMonto'])) ? null : $data['pagoMonto'];
    $estado = empty($fechaBajaMatriculacion) ? 'Activo' : 'De Baja';
    
    $idNivel = 6;
    $anio = date('Y');

    // parámetros: (idNivel(i), idCurso(i), idAlumno(i), fechaMatriculacion(s), anio(i), estado(s), tarde(i), idPlanDeEstudio(i), pagoMatricula(i), pagoMonto(s), certificadoSalud(i), fechaBajaMatriculacion(s), certificadoTrabajo(i), idMatriculacion(i))
    // Totales: 7 int, 4 string
    $stmt->bind_param("iiisisiiisisi", // String de tipos corregida: (6i, 4s, 3i, 1i)
        $idNivel,
        $data['idCurso'],
        $data['idAlumno'],
        $fechaMatriculacion,
        $anio,
        $estado,
        $data['tarde'],
        $data['idPlanDeEstudio'],
        $data['pagoMatricula'],
        $pagoMonto,
        $data['certificadoSalud'],
        $fechaBajaMatriculacion,
        $data['certificadoTrabajo'],
        $idMatriculacion
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}*/

function eliminarMatriculacionPlan($conexion, $idMatriculacion) {
    $sql = "DELETE FROM matriculacion WHERE idMatriculacion = ?"; // Se podria hacer un soft delete con un campo 'activo'
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idMatriculacion);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}


// --- NUEVAS FUNCIONES PARA MATRICULACIÓN DE MATERIAS ---

function insertarMatriculacionMateria($conexion, $data) {
    $sql = "INSERT INTO matriculacionmateria (idAlumno, idNivel, idMateria, fechaMatriculacion, fechaBajaMatriculacion, estado, idCicloLectivo)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error en preparar insertarMatriculacionMateria: " . $conexion->error);
        return false;
    }

    $fechaMatriculacion = !empty($data['fechaMatriculacionMateria']) ? $data['fechaMatriculacionMateria'] : date('Y-m-d');
    $fechaBajaMatriculacion = !empty($data['fechaBajaMatriculacionMateria']) ? $data['fechaBajaMatriculacionMateria'] : null;
    $idNivel = 6; // Siempre 6

    // **MODIFICACIÓN AQUÍ:** Necesitamos el nombre de la condición, no el ID.
    // Obtenemos el nombre de la condición usando el ID recibido.
    $idEstado = $data['estadoMatriculacionMateria'] ?? null; // Este es el idCondicion
    $estadoNombre = 'Regular'; // Valor por defecto si algo falla o está vacío

    if ($idEstado !== null) {
        $sql_get_condicion = "SELECT condicion FROM condicionescursado WHERE idCondicion = ?";
        $stmt_condicion = $conexion->prepare($sql_get_condicion);
        if ($stmt_condicion) {
            $stmt_condicion->bind_param("i", $idEstado);
            $stmt_condicion->execute();
            $resultado_condicion = $stmt_condicion->get_result();
            if ($fila_condicion = $resultado_condicion->fetch_assoc()) {
                $estadoNombre = $fila_condicion['condicion'];
            }
            $stmt_condicion->close();
        } else {
            error_log("Error al preparar la consulta para obtener condición: " . $conexion->error);
        }
    }

    $anio_matriculacion = date('Y', strtotime($fechaMatriculacion));
    $idCicloLectivo = buscarIdCiclo($conexion, $anio_matriculacion);
    if (is_null($idCicloLectivo)) {
        error_log("No se encontró idCicloLectivo para el año: " . $anio_matriculacion);
        return false;
    }

    // iiisssi (idAlumno, idNivel, idMateria, fechaMatriculacion (string), fechaBajaMatriculacion (string), estado (string), idCicloLectivo (int))
    // Aquí, $estadoNombre es el que se guarda en la columna 'estado'.
    $stmt->bind_param("iiisssi",
        $data['idAlumno'],
        $idNivel,
        $data['idMateria'],
        $fechaMatriculacion,
        $fechaBajaMatriculacion,
        $estadoNombre, // Guardamos el nombre de la condición
        $idCicloLectivo
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}


function obtenerMatriculacionesMateriaAlumno($conexion, $idAlumno, $idPlanFilter = null, $idCursoFilter = null) {
    // La consulta original se usará para cargar la tabla completa al principio
    // Esta nueva función será llamada por el AJAX cuando se apliquen los filtros.
    $sql = "SELECT mm.idMatriculacionMateria, mt.nombre AS nombreMateria, c.nombre AS nombreCurso, pe.nombre AS nombrePlan,
            mm.fechaMatriculacion, mm.estado, mm.fechaBajaMatriculacion, cl.anio AS anioCicloLectivo,
            mm.idMateria, mm.idNivel, mm.idCicloLectivo, pe.idPlan AS idPlanFK, c.idCurso AS idCursoFK
        FROM matriculacionmateria mm
        INNER JOIN materiaterciario mt ON mm.idMateria = mt.idMateria
        INNER JOIN curso c ON mt.idCurso = c.idCurso
        INNER JOIN plandeestudio pe ON mt.idPlan = pe.idPlan
        INNER JOIN ciclolectivo cl ON mm.idCicloLectivo = cl.idCiclolectivo
        WHERE mm.idAlumno = ?";

    $params = [$idAlumno];
    $types = "i";

    if ($idPlanFilter !== null && $idPlanFilter !== '') {
        $sql .= " AND pe.idPlan = ?";
        $params[] = $idPlanFilter;
        $types .= "i";
    }
    if ($idCursoFilter !== null && $idCursoFilter !== '') {
        $sql .= " AND c.idCurso = ?";
        $params[] = $idCursoFilter;
        $types .= "i";
    }

    $sql .= " ORDER BY cl.anio DESC, mm.fechaMatriculacion DESC"; // Ordenar por año y luego por fecha

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerMatriculacionesMateriaAlumno con filtros: " . $conexion->error);
        return [];
    }

    // Usar call_user_func_array para bind_param con un array de parámetros dinámico
    $bind_params = [$types]; // El primer elemento es el string de tipos
    foreach ($params as &$param) { // Usamos '&' para pasar por referencia
        $bind_params[] = &$param;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    $stmt->execute();
    $result = $stmt->get_result();
    $matriculaciones_materia = [];
    while ($row = $result->fetch_assoc()) {
        $matriculaciones_materia[] = $row;
    }
    $stmt->close();
    return $matriculaciones_materia;
}


function actualizarMatriculacionMateria($conexion, $idMatriculacionMateria, $data) {
    $sql = "UPDATE matriculacionmateria SET idAlumno=?, idNivel=?, idMateria=?, fechaMatriculacion=?, fechaBajaMatriculacion=?, estado=?, idCicloLectivo=?
            WHERE idMatriculacionMateria=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error en preparar actualizarMatriculacionMateria: " . $conexion->error);
        return false;
    }

    $fechaMatriculacion = !empty($data['fechaMatriculacionMateria']) ? $data['fechaMatriculacionMateria'] : date('Y-m-d');
    $fechaBajaMatriculacion = !empty($data['fechaBajaMatriculacionMateria']) ? $data['fechaBajaMatriculacionMateria'] : null;
    $idNivel = 6;
    $estado = empty($fechaBajaMatriculacion) ? 'Regular' : 'De Baja';
    
    $anio_matriculacion = date('Y', strtotime($fechaMatriculacion));
    $idCicloLectivo = buscarIdCiclo($conexion, $anio_matriculacion);
    if (is_null($idCicloLectivo)) {
        error_log("No se encontró idCicloLectivo para el año: " . $anio_matriculacion);
        return false;
    }

    // iiisssii (idAlumno, idNivel, idMateria, fechaMatriculacion (string), fechaBajaMatriculacion (string), estado (string), idCicloLectivo (int), idMatriculacionMateria (int))
    $stmt->bind_param("iiisssii",
        $data['idAlumno'],
        $idNivel,
        $data['idMateria'],
        $fechaMatriculacion,
        $fechaBajaMatriculacion,
        $estado,
        $idCicloLectivo,
        $idMatriculacionMateria
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function eliminarMatriculacionMateria($conexion, $idMatriculacionMateria) {
    $sql = "DELETE FROM matriculacionmateria WHERE idMatriculacionMateria = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idMatriculacionMateria);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Funciones auxiliares para dropdowns
function obtenerPlanesDeEstudio($conexion) {
  $sql = "SELECT idPlan, nombre FROM plandeestudio ORDER BY nombre ASC";
  $stmt = $conexion->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();
  $planes = [];
  while($row = $result->fetch_assoc()) {
    $planes[] = $row;
  }
  $stmt->close();
  return $planes;
}
// Nueva función para actualizar matriculación de plan/curso
function actualizarMatriculacionPlan($conexion, $idMatriculacion, $data) {
    $sql = "UPDATE matriculacion SET idNivel=?, idCurso=?, idAlumno=?, fechaMatriculacion=?, anio=?, estado=?, tarde=?, idPlanDeEstudio=?, pagoMatricula=?, pagoMonto=?, certificadoSalud=?, fechaBajaMatriculacion=?, certificadoTrabajo=?
            WHERE idMatriculacion=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error en preparar actualizarMatriculacionPlan: " . $conexion->error);
        return false;
    }

    $fechaMatriculacion = !empty($data['fechaMatriculacion']) ? $data['fechaMatriculacion'] : null;
    $fechaBajaMatriculacion = !empty($data['fechaBajaMatriculacion']) ? $data['fechaBajaMatriculacion'] : null;
    $pagoMonto = ($data['pagoMatricula'] == 0 || empty($data['pagoMonto'])) ? null : $data['pagoMonto'];
    $estado = empty($fechaBajaMatriculacion) ? 'Activo' : 'De Baja';

    $idNivel = 6; // Asumiendo que es fijo
    $anio = date('Y'); // Año actual

    // Parámetros: (idNivel(i), idCurso(i), idAlumno(i), fechaMatriculacion(s), anio(i), estado(s), tarde(i), idPlanDeEstudio(i), pagoMatricula(i), pagoMonto(s), certificadoSalud(i), fechaBajaMatriculacion(s), certificadoTrabajo(i), idMatriculacion(i))
    $stmt->bind_param("iiisisiiisisii",
        $idNivel,
        $data['idCurso'],
        $data['idAlumno'],
        $fechaMatriculacion,
        $anio,
        $estado,
        $data['tarde'],
        $data['idPlanDeEstudio'],
        $data['pagoMatricula'],
        $pagoMonto,
        $data['certificadoSalud'],
        $fechaBajaMatriculacion,
        $data['certificadoTrabajo'],
        $idMatriculacion // El ID para el WHERE
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
// --- NUEVAS FUNCIONES PARA MANEJAR INSCRIPCIÓN Y ELIMINACIÓN DE MATERIAS ---

/**
 * Inserta los registros iniciales en asistenciaterciario para una materia.
 * Crea 12 registros, uno por cada mes.
 */
function inicializarAsistenciaMateria($conexion, $idAlumno, $idMateria, $idCicloLectivo) {
    $sql_asistencia = "INSERT INTO asistenciaterciario (idAlumno, idMateria, mes, idCicloLectivo) VALUES (?, ?, ?, ?)";
    $stmt_asistencia = $conexion->prepare($sql_asistencia);
    if (!$stmt_asistencia) {
        error_log("Error al preparar inicializarAsistenciaMateria: " . $conexion->error);
        return false;
    }

    // Habilitar transacciones para asegurar la atomicidad de las operaciones
    $conexion->begin_transaction();

    $success = true;
    for ($mes = 1; $mes <= 12; $mes++) {
        if (!$stmt_asistencia->bind_param("iiii", $idAlumno, $idMateria, $mes, $idCicloLectivo)) {
            error_log("Error al bind_param en inicializarAsistenciaMateria para mes {$mes}: " . $stmt_asistencia->error);
            $success = false;
            break;
        }
        if (!$stmt_asistencia->execute()) {
            error_log("Error al ejecutar inicializarAsistenciaMateria para mes {$mes}: " . $stmt_asistencia->error);
            $success = false;
            break;
        }
    }
    $stmt_asistencia->close();

    if ($success) {
        $conexion->commit();
        return true;
    } else {
        $conexion->rollback();
        return false;
    }
}

/**
 * Crea el registro inicial en calificacionesterciario para una materia.
 */
function inicializarCalificacionMateria($conexion, $idAlumno, $idMateria) {
    // Asumimos que sinAsistencia se pasa explícitamente como 0
    $sql_calificacion = "INSERT INTO calificacionesterciario (idAlumno, idMateria, sinAsistencia) VALUES (?, ?, 0)";
    $stmt_calificacion = $conexion->prepare($sql_calificacion);
    if (!$stmt_calificacion) {
        error_log("Error al preparar inicializarCalificacionMateria: " . $conexion->error);
        return false;
    }

    if (!$stmt_calificacion->bind_param("ii", $idAlumno, $idMateria)) {
        error_log("Error al bind_param en inicializarCalificacionMateria: " . $stmt_calificacion->error);
        $stmt_calificacion->close();
        return false;
    }
    if (!$stmt_calificacion->execute()) {
        error_log("Error al ejecutar inicializarCalificacionMateria: " . $stmt_calificacion->error);
        $stmt_calificacion->close();
        return false;
    }
    $stmt_calificacion->close();
    return true;
}

/**
 * Elimina todos los registros de asistenciaterciario para un alumno, materia y ciclo lectivo.
 */
function eliminarAsistenciaMateria($conexion, $idAlumno, $idMateria, $idCicloLectivo) {
    $sql = "DELETE FROM asistenciaterciario WHERE idAlumno = ? AND idMateria = ? AND idCicloLectivo = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar eliminarAsistenciaMateria: " . $conexion->error);
        return false;
    }

    if (!$stmt->bind_param("iii", $idAlumno, $idMateria, $idCicloLectivo)) {
        error_log("Error al bind_param en eliminarAsistenciaMateria: " . $stmt->error);
        $stmt->close();
        return false;
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Elimina el registro de calificacionesterciario para un alumno y materia.
 */
function eliminarCalificacionMateria($conexion, $idAlumno, $idMateria) {
    $sql = "DELETE FROM calificacionesterciario WHERE idAlumno = ? AND idMateria = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar eliminarCalificacionMateria: " . $conexion->error);
        return false;
    }

    if (!$stmt->bind_param("ii", $idAlumno, $idMateria)) {
        error_log("Error al bind_param en eliminarCalificacionMateria: " . $stmt->error);
        $stmt->close();
        return false;
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
/**
 * Obtiene detalles de una inscripción de materia para su posterior eliminación.
 */
function obtenerDetallesMatriculacionMateria($conexion, $idMatriculacionMateria) {
    $sql = "SELECT idAlumno, idMateria, idCicloLectivo
            FROM matriculacionmateria
            WHERE idMatriculacionMateria = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerDetallesMatriculacionMateria: " . $conexion->error);
        return null;
    }
    $stmt->bind_param("i", $idMatriculacionMateria);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();
    return $details;
}
/**
 * Obtiene los planes de estudio en los que un alumno está matriculado para un año específico.
 *
 * @param mysqli $conexion La conexión a la base de datos.
 * @param int $idAlumno El ID del alumno.
 * @param int $anio El año para filtrar las matriculaciones.
 * @return array Un array de planes matriculados.
 */
function obtenerPlanesMatriculadosPorAnio($conexion, $idAlumno, $anio) {
    // Primero obtenemos el idCicloLectivo para el año dado
    $idCicloLectivo = buscarIdCiclo($conexion, $anio);
    if (is_null($idCicloLectivo)) {
        return []; // No hay ciclo lectivo para este año, por lo tanto, no hay matriculaciones
    }

    // Ahora buscamos las matriculaciones de plan para ese alumno y ciclo lectivo
    $sql = "SELECT DISTINCT pe.idPlan, pe.nombre
            FROM matriculacion m
            INNER JOIN plandeestudio pe ON m.idPlanDeEstudio = pe.idPlan
            WHERE m.idAlumno = ? AND m.anio = ?
            ORDER BY pe.nombre ASC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerPlanesMatriculadosPorAnio: " . $conexion->error);
        return [];
    }
    // Usamos el año directamente para el bind_param, no el idCicloLectivo aquí.
    // Si la tabla `matriculacion` tiene un campo `anio` que se corresponde con `ciclolectivo.anio`, esto es correcto.
    $stmt->bind_param("ii", $idAlumno, $anio);
    $stmt->execute();
    $result = $stmt->get_result();

    $planes = [];
    while ($row = $result->fetch_assoc()) {
        $planes[] = $row;
    }
    $stmt->close();
    return $planes;
}
function obtenerUltimoCicloLectivo($conexion) {
    $sql = "SELECT idciclolectivo, anio
            FROM ciclolectivo
            ORDER BY anio DESC
            LIMIT 1"; // Obtenemos solo el registro más reciente por año

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerUltimoCicloLectivo: " . $conexion->error);
        return null;
    }

    if (!$stmt->execute()) {
        error_log("Error al ejecutar obtenerUltimoCicloLectivo: " . $stmt->error);
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $ultimoCiclo = $result->fetch_assoc(); // Obtiene la primera (y única) fila
    $stmt->close();

    return $ultimoCiclo; // Retorna el array o null si no hay resultados
}
function obtenerMateriasConCalificacionesPorAlumno($conexion, $idAlumno) {
    $sql = "SELECT
                c.idCalificacion,
                c.idAlumno,
                mt.nombre AS nombreMateria,
                curso.nombre AS nombreCurso,
                mt.idMateria,
                mt.idPlan,
                mt.idCicloLectivo AS idCicloLectivoMateria,
                cl.anio AS anioCiclo,
                c.n1, c.n2, c.n3, c.n4, c.n5, c.n6, c.n7, c.n8,
                c.r1, c.r2, c.r3, c.r4, c.r5, c.r6, c.r7, c.r8,
                c.asistencia,
                c.estadoCursado,
                c.examenIntegrador,
                c.materiaAprobada,
                mm.estado AS estadoInscripcion -- <<< AÑADIDO: El estado de matriculacionmateria
            FROM
                calificacionesterciario c
            INNER JOIN
                materiaterciario mt ON c.idMateria = mt.idMateria
            INNER JOIN
                ciclolectivo cl ON mt.idCicloLectivo = cl.idCiclolectivo
            INNER JOIN 
                curso ON mt.idCurso = curso.idCurso
            -- Usamos LEFT JOIN por si por alguna razón no hubiera una matriculación correspondiente pero sí una calificación
            LEFT JOIN
                matriculacionmateria mm ON c.idAlumno = mm.idAlumno AND c.idMateria = mm.idMateria
            WHERE
                c.idAlumno = ?
            ORDER BY
                curso.idcursopredeterminado DESC, mt.ubicacion ";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerMateriasConCalificacionesPorAlumno: " . $conexion->error);
        return [];
    }
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $result = $stmt->get_result();

    $materias = [];
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }
    $stmt->close();
    return $materias;
}

// Agrega esta función para obtener el nombre de la materia
function obtenerNombreMateria($conexion, $idMateria) {
    $sql = "SELECT nombre FROM materiaterciario WHERE idMateria = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerNombreMateria: " . $conexion->error);
        return "Materia Desconocida";
    }
    $stmt->bind_param("i", $idMateria);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['nombre'];
    }
    $stmt->close();
    return "Materia Desconocida";
}
function obtenerDatosBasicosAlumno($conexion, $idAlumno) {
    $sql = "SELECT p.apellido, p.nombre, p.dni
            FROM persona p
            INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona
            WHERE a.idAlumno = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta en obtenerDatosBasicosAlumno: " . $conexion->error);
        return null;
    }
    $stmt->bind_param("i", $idAlumno);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta en obtenerDatosBasicosAlumno: " . $stmt->error);
        return null;
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("No se encontró alumno con idAlumno: " . $idAlumno);
        return null;
    }
    return $result->fetch_assoc();
}
function actualizarCalifSecretaria($conexion, $idCalif, $columna, $valor) {
    // Lista blanca de columnas permitidas
    $columnasPermitidas = [
        'n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8',
        'r1', 'r2', 'r3', 'r4', 'r5', 'r6', 'r7', 'r8',
        'examenIntegrador'
    ];

    if (!in_array($columna, $columnasPermitidas)) {
        error_log("ACTUALIZAR_CALIF_ERROR: Intento de actualizar una columna no permitida: " . $columna);
        return "error_columna"; // Devuelve un string de error
    }

    $consulta = "UPDATE calificacionesterciario SET registroModificacion=1, `$columna` = ? WHERE idCalificacion = ?";

    $stmt = $conexion->prepare($consulta);
    if (!$stmt) {
        error_log("ACTUALIZAR_CALIF_ERROR: Fallo al preparar la consulta: " . $conexion->error);
        return "error_preparacion"; // Devuelve un string de error
    }

    // bind_param: 's' para el valor (string), 'i' para el id (integer)
    $stmt->bind_param("si", $valor, $idCalif);

    if (!$stmt->execute()) {
        error_log("ACTUALIZAR_CALIF_ERROR: Fallo al ejecutar la consulta: " . $stmt->error);
        $stmt->close();
        return "error_ejecucion"; // Devuelve un string de error
    }

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        return "actualizado"; // ¡LA RESPUESTA DE ÉXITO!
    } else {
        // Si no se afectaron filas, puede ser que el valor fuera el mismo o el ID no existiera.
        // Lo tratamos como un éxito funcional para que la celda se ponga verde.
        $stmt->close();
        return "actualizado"; // O "sin_cambios" si quieres manejarlo diferente en el JS
    }
}
function obtenerPlanesDeAlumnoConCalificaciones($conexion, $idAlumno) {
    $sql = "SELECT DISTINCT
                pe.idPlan,
                pe.nombre AS nombrePlan
            FROM
                calificacionesterciario c
            INNER JOIN
                materiaterciario mt ON c.idMateria = mt.idMateria
            INNER JOIN
                plandeestudio pe ON mt.idPlan = pe.idPlan
            WHERE
                c.idAlumno = ?
            ORDER BY
                pe.nombre ASC";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerPlanesDeAlumnoConCalificaciones: " . $conexion->error);
        return [];
    }
    
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $planes = [];
    while ($row = $result->fetch_assoc()) {
        $planes[] = $row;
    }
    
    $stmt->close();
    return $planes;
}
function obtenerAlumnosPorCurso($conexion, $idCurso, $idCicloLectivo) {
    // Esta consulta asume que la tabla 'matriculacion' es la que vincula a un alumno con un curso para un ciclo lectivo.
    // Asegúrate de que 'm.anio' se corresponda con el ciclo lectivo.
    $sql = "SELECT p.apellido, p.nombre, p.dni
            FROM persona p
            JOIN alumnosterciario a ON p.idPersona = a.idPersona
            JOIN matriculacion m ON a.idAlumno = m.idAlumno
            WHERE m.idCurso = ? 
            AND m.anio = (SELECT anio FROM ciclolectivo WHERE idciclolectivo = ?)
            ORDER BY p.apellido, p.nombre ASC";
            
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerAlumnosPorCurso: " . $conexion->error);
        return [];
    }
    
    $stmt->bind_param("ii", $idCurso, $idCicloLectivo);
    $stmt->execute();
    $result = $stmt->get_result();

    $alumnos = [];
    while ($row = $result->fetch_assoc()) {
        $alumnos[] = $row;
    }
    $stmt->close();
    return $alumnos;
}
function obtenerAlumnosPorMateria($conexion, $idMateria) {
    // La consulta busca en matriculacionmateria los alumnos inscritos en la materia dada
    $sql = "SELECT p.apellido, p.nombre, p.dni
            FROM persona p
            JOIN alumnosterciario a ON p.idPersona = a.idPersona
            JOIN matriculacionmateria m ON a.idAlumno = m.idAlumno
            WHERE m.idMateria = ? 
            ORDER BY p.apellido, p.nombre ASC";
            
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerAlumnosPorMateria: " . $conexion->error);
        return [];
    }
    
    $stmt->bind_param("i", $idMateria);
    $stmt->execute();
    $result = $stmt->get_result();

    $alumnos = [];
    while ($row = $result->fetch_assoc()) {
        $alumnos[] = $row;
    }
    $stmt->close();
    return $alumnos;
}

/**
 * ==========================================================
 * FUNCIONES PARA GESTIÓN DE MESAS DE EXAMEN (mesasExamen.php)
 * ==========================================================
 */

/**
 * Inserta una nueva mesa de examen.
 *
 * @param mysqli $conn Conexión a la DB.
 * @param int $idMateria
 * @param int $idTurno
 * @param int $idCicloLectivo
 * @param string $fecha (YYYY-MM-DD)
 * @param string $hora (HH:MM)
 * @return bool True si fue exitoso, false si falló.
 * @throws Exception Si la preparación o ejecución falla.
 */
function crearMesaExamen($conn, $idMateria, $idTurno, $idCicloLectivo, $fecha, $hora) {
    $sql = "INSERT INTO fechasexamenes (idMateria, idTurno, idCicloLectivo, fecha, hora) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de inserción: " . $conn->error);
    }
    $stmt->bind_param("iiiss", $idMateria, $idTurno, $idCicloLectivo, $fecha, $hora);
    $success = $stmt->execute();
    if (!$success) {
        throw new Exception("Error al ejecutar la inserción: " . $stmt->error);
    }
    $stmt->close();
    return $success;
}

/**
 * Actualiza la fecha y hora de una mesa de examen.
 *
 * @param mysqli $conn Conexión a la DB.
 * @param int $idFechaExamen
 * @param string $fecha (YYYY-MM-DD)
 * @param string $hora (HH:MM)
 * @return bool True si fue exitoso, false si falló.
 * @throws Exception Si la preparación o ejecución falla.
 */
function actualizarMesaExamen($conn, $idFechaExamen, $fecha, $hora) {
    $sql = "UPDATE fechasexamenes SET fecha = ?, hora = ? WHERE idFechaExamen = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
    }
    $stmt->bind_param("ssi", $fecha, $hora, $idFechaExamen);
    $success = $stmt->execute();
    if (!$success) {
        throw new Exception("Error al ejecutar la actualización: " . $stmt->error);
    }
    $stmt->close();
    return $success;
}

/**
 * Asigna los 7 docentes (titular y vocales) a una mesa de examen.
 *
 * @param mysqli $conn Conexión a la DB.
 * @param int $idFechaExamen
 * @param int|null $p1 Legajo del Titular (o null)
 * @param int|null $p2 Legajo del Vocal (o null)
 * @param int|null $p3 Legajo del Vocal (o null)
 * @param int|null $p4 Legajo del Vocal (o null)
 * @param int|null $p5 Legajo del Vocal (o null)
 * @param int|null $p6 Legajo del Vocal (o null)
 * @param int|null $p7 Legajo del Vocal (o null)
 * @return bool True si fue exitoso, false si falló.
 * @throws Exception Si la preparación o ejecución falla.
 */
function asignarDocentesMesa($conn, $idFechaExamen, $p1, $p2, $p3, $p4, $p5, $p6, $p7) {
    $sql = "UPDATE fechasexamenes SET p1 = ?, p2 = ?, p3 = ?, p4 = ?, p5 = ?, p6 = ?, p7 = ? WHERE idFechaExamen = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error al preparar la asignación de docentes: " . $conn->error);
    }
    // 'iiiiiiii' - 7 ints para docentes (que pueden ser null), 1 int para el ID
    $stmt->bind_param("iiiiiiii", $p1, $p2, $p3, $p4, $p5, $p6, $p7, $idFechaExamen);
    $success = $stmt->execute();
    if (!$success) {
        throw new Exception("Error al ejecutar la asignación de docentes: " . $stmt->error);
    }
    $stmt->close();
    return $success;
}

/**
 * Elimina todas las inscripciones de alumnos asociadas a una mesa de examen.
 * (Parte de una transacción)
 *
 * @param mysqli $conn Conexión a la DB.
 * @param int $idFechaExamen
 * @return bool True si fue exitoso, false si falló.
 * @throws Exception Si la preparación o ejecución falla.
 */
function eliminarInscripcionesPorMesa($conn, $idFechaExamen) {
    $sql_insc = "DELETE FROM inscripcionexamenes WHERE idFechaExamen = ?";
    $stmt_insc = $conn->prepare($sql_insc);
    if ($stmt_insc === false) {
        throw new Exception("Error al preparar borrado de inscripciones: " . $conn->error);
    }
    $stmt_insc->bind_param("i", $idFechaExamen);
    $success = $stmt_insc->execute();
    $stmt_insc->close();
    return $success;
}

/**
 * Elimina la mesa de examen (fecha).
 * (Parte de una transacción)
 *
 * @param mysqli $conn Conexión a la DB.
 * @param int $idFechaExamen
 * @return bool True si fue exitoso, false si falló.
 * @throws Exception Si la preparación o ejecución falla.
 */
function eliminarFechaExamen($conn, $idFechaExamen) {
    $sql_fecha = "DELETE FROM fechasexamenes WHERE idFechaExamen = ?";
    $stmt_fecha = $conn->prepare($sql_fecha);
    if ($stmt_fecha === false) {
        throw new Exception("Error al preparar borrado de fecha de examen: " . $conn->error);
    }
    $stmt_fecha->bind_param("i", $idFechaExamen);
    $success = $stmt_fecha->execute();
    $stmt_fecha->close();
    return $success;
}

/**
 * Obtiene todos los ciclos lectivos ordenados por año descendente.
 *
 * @param mysqli $conn Conexión a la DB.
 * @return mysqli_result
 */
function obtenerCiclosLectivos($conn) {
    $sql = "SELECT idciclolectivo, anio FROM ciclolectivo ORDER BY anio DESC";
    return $conn->query($sql);
}

/**
 * Obtiene todos los turnos de examen.
 *
 * @param mysqli $conn Conexión a la DB.
 * @return mysqli_result
 */
function obtenerTurnosExamen($conn) {
    $sql = "SELECT idTurno, nombre FROM turnosexamenes ORDER BY nombre";
    return $conn->query($sql);
}

/**
 * Obtiene todos los planes de estudio.
 *
 * @param mysqli $conn Conexión a la DB.
 * @return mysqli_result
 */
function obtenerPlanesEstudio($conn) {
    $sql = "SELECT idPlan, nombre FROM plandeestudio ORDER BY nombre";
    return $conn->query($sql);
}

/**
 * Filtra las mesas de examen según los criterios proporcionados.
 *
 * *** MODIFICADA: ***
 * Ahora requiere que $idCicloFilter Y $idTurnoFilter tengan valor
 * para empezar a buscar.
 *
 * @param mysqli $conn Conexión a la DB.
 * @param int|null $idCicloFilter
 * @param int|null $idTurnoFilter
 * @param int|null $idPlanFilter
 * @param int|null $idCursoFilter
 * @param int|null $idMateriaFilter
 * @return array Lista de mesas de examen.
 */
function filtrarMesasExamen($conn, $idCicloFilter = null, $idTurnoFilter = null, $idPlanFilter = null, $idCursoFilter = null, $idMateriaFilter = null) {
    $mesas_examen = [];
    
    // *** NUEVA CONDICIÓN MÍNIMA ***
    // Solo buscar si se especificó al menos Ciclo Lectivo Y Turno.
    if (empty($idCicloFilter) || empty($idTurnoFilter)) {
        return $mesas_examen; // Devuelve vacío si no se cumple el mínimo
    }

    $sql_grid = "SELECT f.idFechaExamen, f.fecha, f.hora, 
                        m.nombre as nombreMateria, 
                        c.nombre as nombreCurso, 
                        p.nombre as nombrePlan, 
                        t.nombre as nombreTurno, 
                        cl.anio as anioCiclo,
                        f.p1, f.p2, f.p3, f.p4, f.p5, f.p6, f.p7
                 FROM fechasexamenes f
                 JOIN materiaterciario m ON f.idMateria = m.idMateria
                 JOIN curso c ON m.idCurso = c.idCurso
                 JOIN plandeestudio p ON m.idPlan = p.idPlan
                 JOIN turnosexamenes t ON f.idTurno = t.idTurno
                 JOIN ciclolectivo cl ON f.idCicloLectivo = cl.idciclolectivo
                 WHERE f.idCicloLectivo = ? AND f.idTurno = ?"; // Filtros base
    
    $params = [$idCicloFilter, $idTurnoFilter];
    $types = "ii";

    // Filtros adicionales
    if ($idPlanFilter) { $sql_grid .= " AND m.idPlan = ?"; $params[] = $idPlanFilter; $types .= "i"; }
    if ($idCursoFilter) { $sql_grid .= " AND m.idCurso = ?"; $params[] = $idCursoFilter; $types .= "i"; }
    if ($idMateriaFilter) { $sql_grid .= " AND f.idMateria = ?"; $params[] = $idMateriaFilter; $types .= "i"; }

    $sql_grid .= " ORDER BY f.fecha DESC, m.nombre ASC";

    $stmt_grid = $conn->prepare($sql_grid);
    if ($stmt_grid) {
        $stmt_grid->bind_param($types, ...$params);
        $stmt_grid->execute();
        $result_grid = $stmt_grid->get_result();
        while ($row = $result_grid->fetch_assoc()) {
            $mesas_examen[] = $row;
        }
        $stmt_grid->close();
    }
    return $mesas_examen;
}
/**
 * Obtiene todos los docentes activos (personal.actual = 1).
 *
 * @param mysqli $conn Conexión a la DB.
 * @return array Lista de docentes [legajo, apellido, nombre].
 * @throws Exception Si la consulta falla.
 */
function obtenerDocentesActivos($conn) {
    $docentes = [];
    $sql = "SELECT p.legajo, pe.apellido, pe.nombre 
            FROM personal p 
            JOIN persona pe ON p.idPersona = pe.idPersona 
            WHERE p.actual = 1 
            ORDER BY pe.apellido, pe.nombre";
    
    $result = $conn->query($sql);
    
    if (!$result) {
         throw new Exception("Error al consultar docentes: " . $conn->error);
    }
    
    while ($row = $result->fetch_assoc()) {
        $docentes[] = $row;
    }
    return $docentes;
}

/**
 * ==========================================================
 * FUNCIONES PARA INSCRIPCIÓN INDIVIDUAL A EXAMEN (Secretaría)
 * ==========================================================
 */

/**
 * Inscribe un alumno a una mesa de examen (tabla inscripcionexamenes).
 * Verifica duplicados antes de insertar.
 *
 * @param mysqli $conexion Conexión a la DB.
 * @param int $idAlumno
 * @param int $idMateria
 * @param int $idCicloLectivo
 * @param int $idFechaExamen
 * @param int $idCondicion
 * @return array ['success' => bool, 'message' => string]
 */
function inscribirAlumnoExamen($conexion, $idAlumno, $idMateria, $idCicloLectivo, $idFechaExamen, $idCondicion, $idTurno) {
    
    // 1. Verificar si ya existe esta inscripción EXACTA (misma mesa)
    $sql_check1 = "SELECT idInscripcion FROM inscripcionexamenes 
                   WHERE idAlumno = ? AND idFechaExamen = ? AND idMateria = ?";
    $stmt_check1 = $conexion->prepare($sql_check1);
    
    if (!$stmt_check1) {
        error_log("Error al preparar (check1) inscribirAlumnoExamen: " . $conexion->error);
        return ['success' => false, 'message' => 'Error al preparar la verificación (Mesa).'];
    }
    
    $stmt_check1->bind_param("iii", $idAlumno, $idFechaExamen, $idMateria);
    $stmt_check1->execute();
    $result_check1 = $stmt_check1->get_result();
    
    if ($result_check1->num_rows > 0) {
        $stmt_check1->close();
        return ['success' => false, 'message' => 'El alumno ya se encuentra inscripto en esta mesa de examen.'];
    }
    $stmt_check1->close();

    // 2. (NUEVA VERIFICACIÓN) Verificar si ya está inscripto en el MISMO TURNO para la MISMA MATERIA
    $sql_check2 = "SELECT ie.idInscripcion
                   FROM inscripcionexamenes ie
                   JOIN fechasexamenes fe ON ie.idFechaExamen = fe.idFechaExamen
                   WHERE ie.idAlumno = ?
                     AND ie.idMateria = ?
                     AND fe.idTurno = ?";
    $stmt_check2 = $conexion->prepare($sql_check2);

    if (!$stmt_check2) {
        error_log("Error al preparar (check2) inscribirAlumnoExamen: " . $conexion->error);
        return ['success' => false, 'message' => 'Error al preparar la verificación (Turno).'];
    }

    $stmt_check2->bind_param("iii", $idAlumno, $idMateria, $idTurno);
    $stmt_check2->execute();
    $result_check2 = $stmt_check2->get_result();

    if ($result_check2->num_rows > 0) {
        $stmt_check2->close();
        return ['success' => false, 'message' => 'El alumno ya se encuentra inscripto en otra mesa para esta materia en este mismo turno de examen.'];
    }
    $stmt_check2->close();


    // 3. Si pasó ambas verificaciones, proceder con la inserción
    $sql_insert = "INSERT INTO inscripcionexamenes 
                   (idAlumno, idMateria, idCicloLectivo, idFechaExamen, idCondicion) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conexion->prepare($sql_insert);
    
    if (!$stmt_insert) {
        error_log("Error al preparar (insert) inscribirAlumnoExamen: " . $conexion->error);
        return ['success' => false, 'message' => 'Error al preparar la inscripción.'];
    }
    
    $stmt_insert->bind_param("iiiii", $idAlumno, $idMateria, $idCicloLectivo, $idFechaExamen, $idCondicion);
    
    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        return ['success' => true, 'message' => 'Inscripción realizada con éxito.'];
    } else {
        $error_msg = $stmt_insert->error;
        $stmt_insert->close();
        error_log("Error al ejecutar (insert) inscribirAlumnoExamen: " . $error_msg);
        return ['success' => false, 'message' => 'Error al guardar la inscripción: ' . $error_msg];
    }
}

/**
 * (NUEVA FUNCIÓN) Obtiene las inscripciones a examen de un alumno para un turno y ciclo específicos.
 *
 * @param mysqli $conexion Conexión a la DB.
 * @param int $idAlumno
 * @param int $idTurno
 * @param int $idCicloLectivo
 * @return array Lista de inscripciones.
 */
function obtenerInscripcionesTurno($conexion, $idAlumno, $idTurno, $idCicloLectivo) {
    $inscripciones = [];
    $sql = "SELECT ie.idInscripcion, fe.fecha, fe.hora, m.nombre as nombreMateria, c.condicion
            FROM inscripcionexamenes ie
            JOIN fechasexamenes fe ON ie.idFechaExamen = fe.idFechaExamen
            JOIN materiaterciario m ON ie.idMateria = m.idMateria
            JOIN condicion c ON ie.idCondicion = c.idCondicion
            WHERE ie.idAlumno = ?
              AND fe.idTurno = ?
              AND ie.idCicloLectivo = ?
            ORDER BY fe.fecha, m.nombre";
            
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (obtenerInscripcionesTurno): " . $conexion->error);
        return [];
    }
    
    $stmt->bind_param("iii", $idAlumno, $idTurno, $idCicloLectivo);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $inscripciones[] = $row;
        }
    } else {
        error_log("Error al ejecutar (obtenerInscripcionesTurno): " . $stmt->error);
    }
    
    $stmt->close();
    return $inscripciones;
}
/**
 * (NUEVA FUNCIÓN) Elimina un registro de inscripción a examen.
 *
 * @param mysqli $conexion Conexión a la DB.
 * @param int $idInscripcion ID del registro en la tabla 'inscripcionexamenes'.
 * @return array ['success' => bool, 'message' => string]
 */
function eliminarInscripcionExamen($conexion, $idInscripcion) {
    // Usamos prepared statements para seguridad
    $sql = "DELETE FROM inscripcionexamenes WHERE idInscripcion = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error al preparar (eliminarInscripcionExamen): " . $conexion->error);
        return ['success' => false, 'message' => 'Error al preparar la solicitud de eliminación.'];
    }
    
    $stmt->bind_param("i", $idInscripcion);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return ['success' => true, 'message' => 'Inscripción eliminada con éxito.'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'No se encontró la inscripción para eliminar (ID: ' . $idInscripcion . ').'];
        }
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
        error_log("Error al ejecutar (eliminarInscripcionExamen): " . $error_msg);
        return ['success' => false, 'message' => 'Error al eliminar la inscripción: ' . $error_msg];
    }
}
/**
 * ==========================================================
 * FUNCIONES PARA GESTIÓN DE REGISTROS PRESISTEMA
 * ==========================================================
 */

/**
 * Obtiene las condiciones de examen (tabla 'condicion').
 */
function obtenerCondicionesExamen($conexion) {
    $sql = "SELECT idCondicion, condicion FROM condicion ORDER BY condicion ASC";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerCondicionesExamen: " . $conexion->error);
        return [];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $condiciones = [];
    while ($row = $result->fetch_assoc()) {
        $condiciones[] = $row;
    }
    $stmt->close();
    return $condiciones;
}

/**
 * Crea el registro base en inscripcionexamenes para un aprobado presistema.
 * Devuelve el ID de la inscripción creada.
 */
function crearInscripcionExamenPresistema($conn, $idAlumno, $idMateria, $calificacion, $idCondicionExamen, $libro, $folio) {
    // Obtenemos el PRIMER ciclo lectivo registrado (el más antiguo) como placeholder
    $sql_ciclo = "SELECT idciclolectivo FROM ciclolectivo ORDER BY anio ASC LIMIT 1";
    $idCiclo = $conn->query($sql_ciclo)->fetch_assoc()['idciclolectivo'] ?? 1; // Usar 1 como fallback

    $sql_insert = "INSERT INTO inscripcionexamenes 
                   (idAlumno, idMateria, idCicloLectivo, idFechaExamen, calificacion, libro, folio, idCondicion, registroNuevo, registroModificacion) 
                   VALUES (?, ?, ?, 0, ?, ?, ?, ?, 0, 0)"; // Marcamos como no nuevo y no modificado (es histórico)
    
    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) {
        throw new Exception("Error al preparar crearInscripcionExamenPresistema: " . $conn->error);
    }
    $stmt->bind_param("iiisssi", $idAlumno, $idMateria, $idCiclo, $calificacion, $libro, $folio, $idCondicionExamen);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar crearInscripcionExamenPresistema: " . $stmt->error);
    }
    $idInscripcion = $conn->insert_id;
    $stmt->close();
    return $idInscripcion;
}

/**
 * Inserta un registro de REGULARIDAD presistema.
 * (Debe ser llamado dentro de una transacción)
 */
function insertarPresistemaRegular($conn, $idAlumno, $idMateria, $fechaObtencion, $turnosTranscurridos = 0) {
    // 1. Insertar en matriculacionmateria
    $sql_mm = "INSERT INTO matriculacionmateria 
               (idAlumno, idNivel, idMateria, fechaMatriculacion, estado, idCicloLectivo, registroNuevo, registroModificacion) 
               VALUES (?, 6, ?, ?, 'Regularidad PreSistema', 0, 0, 0)";
    $stmt_mm = $conn->prepare($sql_mm);
    if (!$stmt_mm) throw new Exception("Error al preparar (matriculacionmateria): " . $conn->error);
    $stmt_mm->bind_param("iis", $idAlumno, $idMateria, $fechaObtencion);
    if (!$stmt_mm->execute()) throw new Exception("Error al insertar (matriculacionmateria): " . $stmt_mm->error);
    $stmt_mm->close();

    // 2. Insertar en calificacionesterciario
    // (Aquí 'asistencia' se usa para guardar los turnos transcurridos, es un hack si no hay campo)
    // Vamos a asumir que no guardamos los turnos si no hay campo y lo dejamos en 100%
    // NOTA: La solicitud pedía guardar turnos. No hay un campo claro. Usaremos 'asistencia' como hack.
    $asistencia_hack = $turnosTranscurridos; // O '100%' si no queremos usar el hack

    $sql_ct = "INSERT INTO calificacionesterciario 
               (idAlumno, idMateria, asistencia, sinAsistencia, estadoCursadoNumero, estadoCursado, registroNuevo, registroModificacion) 
               VALUES (?, ?, ?, 0, 1, 'Regularidad PreSistema', 0, 0)";
    $stmt_ct = $conn->prepare($sql_ct);
    if (!$stmt_ct) throw new Exception("Error al preparar (calificacionesterciario): " . $conn->error);
    $stmt_ct->bind_param("iis", $idAlumno, $idMateria, $asistencia_hack);
    if (!$stmt_ct->execute()) throw new Exception("Error al insertar (calificacionesterciario): " . $stmt_ct->error);
    $stmt_ct->close();
}

/**
 * Inserta un registro de APROBACIÓN presistema.
 * (Debe ser llamado dentro de una transacción)
 */
function insertarPresistemaAprobado($conn, $idAlumno, $idMateria, $fechaObtencion, $calificacion, $idCondicionExamen, $libro, $folio) {
    // 1. Crear el registro de examen
    $idInscripcion = crearInscripcionExamenPresistema($conn, $idAlumno, $idMateria, $calificacion, $idCondicionExamen, $libro, $folio);

    // 2. Insertar en matriculacionmateria
    $sql_mm = "INSERT INTO matriculacionmateria 
               (idAlumno, idNivel, idMateria, fechaMatriculacion, estado, idCicloLectivo, registroNuevo, registroModificacion) 
               VALUES (?, 6, ?, ?, 'Aprobación PreSistema', 0, 0, 0)";
    $stmt_mm = $conn->prepare($sql_mm);
    if (!$stmt_mm) throw new Exception("Error al preparar (matriculacionmateria Aprob): " . $conn->error);
    $stmt_mm->bind_param("iis", $idAlumno, $idMateria, $fechaObtencion);
    if (!$stmt_mm->execute()) throw new Exception("Error al insertar (matriculacionmateria Aprob): " . $stmt_mm->error);
    $stmt_mm->close();

    // 3. Insertar en calificacionesterciario
    $sql_ct = "INSERT INTO calificacionesterciario 
               (idAlumno, idMateria, asistencia, examenIntegrador, sinAsistencia, estadoCursadoNumero, estadoCursado, materiaAprobada, idInscripcionExamen, registroNuevo, registroModificacion) 
               VALUES (?, ?, '100%', ?, 0, 11, 'Aprobación PreSistema', 1, ?, 0, 0)";
    $stmt_ct = $conn->prepare($sql_ct);
    if (!$stmt_ct) throw new Exception("Error al preparar (calificacionesterciario Aprob): " . $conn->error);
    $stmt_ct->bind_param("iisi", $idAlumno, $idMateria, $calificacion, $idInscripcion);
    if (!$stmt_ct->execute()) throw new Exception("Error al insertar (calificacionesterciario Aprob): " . $stmt_ct->error);
    $stmt_ct->close();
}

/**
 * Obtiene todos los registros presistema de un alumno para la tabla.
 */
function obtenerRegistrosPresistema($conexion, $idAlumno) {
    $sql = "SELECT 
                mm.idMatriculacionMateria, mm.estado, mm.fechaMatriculacion, 
                mt.nombre AS nombreMateria, mt.idMateria,
                c.nombre AS nombreCurso, 
                pe.nombre AS nombrePlan, 
                ct.examenIntegrador, ct.idCalificacion, ct.asistencia AS turnosTranscurridos,
                ie.calificacion, ie.libro, ie.folio, ie.idCondicion, ie.idInscripcion
            FROM matriculacionmateria mm
            JOIN materiaterciario mt ON mm.idMateria = mt.idMateria
            JOIN curso c ON mt.idCurso = c.idCurso
            JOIN plandeestudio pe ON mt.idPlan = pe.idPlan
            LEFT JOIN calificacionesterciario ct ON mm.idAlumno = ct.idAlumno AND mm.idMateria = ct.idMateria AND (ct.estadoCursado LIKE '%PreSistema%')
            LEFT JOIN inscripcionexamenes ie ON ct.idInscripcionExamen = ie.idInscripcion
            WHERE mm.idAlumno = ? 
            AND (mm.estado = 'Regularidad PreSistema' OR mm.estado = 'Aprobación PreSistema')
            ORDER BY pe.nombre, c.nombre, mt.nombre";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerRegistrosPresistema: " . $conexion->error);
        return [];
    }
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $result = $stmt->get_result();
    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }
    $stmt->close();
    return $registros;
}

/**
 * Obtiene los detalles de un registro presistema para edición/eliminación.
 */
function obtenerDetallesPresistema($conn, $idMatriculacionMateria) {
    $sql = "SELECT 
                mm.idMatriculacionMateria, mm.idAlumno, mm.idMateria,
                ct.idCalificacion, 
                ie.idInscripcion
            FROM matriculacionmateria mm
            LEFT JOIN calificacionesterciario ct ON mm.idAlumno = ct.idAlumno AND mm.idMateria = ct.idMateria AND (ct.estadoCursado LIKE '%PreSistema%')
            LEFT JOIN inscripcionexamenes ie ON ct.idInscripcionExamen = ie.idInscripcion
            WHERE mm.idMatriculacionMateria = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerDetallesPresistema: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $idMatriculacionMateria);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();
    return $details;
}

/**
 * Elimina un registro presistema (de las 3 tablas).
 * (Debe ser llamado dentro de una transacción)
 */
function eliminarPresistema($conn, $idMatriculacionMateria) {
    $details = obtenerDetallesPresistema($conn, $idMatriculacionMateria);
    if (!$details) {
        throw new Exception("Registro no encontrado.");
    }

    if (!empty($details['idInscripcion'])) {
        $stmt_ie = $conn->prepare("DELETE FROM inscripcionexamenes WHERE idInscripcion = ?");
        $stmt_ie->bind_param("i", $details['idInscripcion']);
        if (!$stmt_ie->execute()) throw new Exception("Error al eliminar de inscripcionexamenes.");
        $stmt_ie->close();
    }

    if (!empty($details['idCalificacion'])) {
        $stmt_ct = $conn->prepare("DELETE FROM calificacionesterciario WHERE idCalificacion = ?");
        $stmt_ct->bind_param("i", $details['idCalificacion']);
        if (!$stmt_ct->execute()) throw new Exception("Error al eliminar de calificacionesterciario.");
        $stmt_ct->close();
    }

    $stmt_mm = $conn->prepare("DELETE FROM matriculacionmateria WHERE idMatriculacionMateria = ?");
    $stmt_mm->bind_param("i", $idMatriculacionMateria);
    if (!$stmt_mm->execute()) throw new Exception("Error al eliminar de matriculacionmateria.");
    $stmt_mm->close();
}

/**
 * Actualiza un registro que ES y SIGUE SIENDO 'Regular'.
 */
function actualizarPresistemaRegular($conn, $idMatriculacionMateria, $idCalificacion, $fechaObtencion, $turnosTranscurridos = 0) {
    $sql_mm = "UPDATE matriculacionmateria SET fechaMatriculacion = ? WHERE idMatriculacionMateria = ?";
    $stmt_mm = $conn->prepare($sql_mm);
    $stmt_mm->bind_param("si", $fechaObtencion, $idMatriculacionMateria);
    if (!$stmt_mm->execute()) throw new Exception("Error al actualizar matriculacionmateria.");
    $stmt_mm->close();

    // Usamos el hack de 'asistencia' para los turnos
    $sql_ct = "UPDATE calificacionesterciario SET asistencia = ? WHERE idCalificacion = ?";
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("si", $turnosTranscurridos, $idCalificacion);
    if (!$stmt_ct->execute()) throw new Exception("Error al actualizar calificacionesterciario.");
    $stmt_ct->close();
}

/**
 * Actualiza un registro que ES y SIGUE SIENDO 'Aprobado'.
 */
function actualizarPresistemaAprobado($conn, $idMatriculacionMateria, $idCalificacion, $idInscripcion, $fechaObtencion, $calificacion, $idCondicionExamen, $libro, $folio) {
    $sql_mm = "UPDATE matriculacionmateria SET fechaMatriculacion = ? WHERE idMatriculacionMateria = ?";
    $stmt_mm = $conn->prepare($sql_mm);
    $stmt_mm->bind_param("si", $fechaObtencion, $idMatriculacionMateria);
    if (!$stmt_mm->execute()) throw new Exception("Error al actualizar matriculacionmateria (Aprob).");
    $stmt_mm->close();

    $sql_ct = "UPDATE calificacionesterciario SET examenIntegrador = ? WHERE idCalificacion = ?";
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("si", $calificacion, $idCalificacion);
    if (!$stmt_ct->execute()) throw new Exception("Error al actualizar calificacionesterciario (Aprob).");
    $stmt_ct->close();

    $sql_ie = "UPDATE inscripcionexamenes SET calificacion = ?, libro = ?, folio = ?, idCondicion = ? WHERE idInscripcion = ?";
    $stmt_ie = $conn->prepare($sql_ie);
    $stmt_ie->bind_param("sssii", $calificacion, $libro, $folio, $idCondicionExamen, $idInscripcion);
    if (!$stmt_ie->execute()) throw new Exception("Error al actualizar inscripcionexamenes (Aprob).");
    $stmt_ie->close();
}

/**
 * Convierte un registro de 'Regular' a 'Aprobado'.
 */
function convertirPresistemaRegularAAprobado($conn, $idMatriculacionMateria, $idCalificacion, $idMateria, $idAlumno, $fechaObtencion, $calificacion, $idCondicionExamen, $libro, $folio) {
    // 1. Crear el NUEVO registro de examen
    $idInscripcion = crearInscripcionExamenPresistema($conn, $idAlumno, $idMateria, $calificacion, $idCondicionExamen, $libro, $folio);

    // 2. Actualizar matriculacionmateria
    $sql_mm = "UPDATE matriculacionmateria SET fechaMatriculacion = ?, estado = 'Aprobación PreSistema' WHERE idMatriculacionMateria = ?";
    $stmt_mm = $conn->prepare($sql_mm);
    $stmt_mm->bind_param("si", $fechaObtencion, $idMatriculacionMateria);
    if (!$stmt_mm->execute()) throw new Exception("Error al actualizar matriculacionmateria (Conversión).");
    $stmt_mm->close();

    // 3. Actualizar calificacionesterciario (que ya existía)
    $sql_ct = "UPDATE calificacionesterciario SET 
                    examenIntegrador = ?, 
                    estadoCursadoNumero = 11, 
                    estadoCursado = 'Aprobación PreSistema', 
                    materiaAprobada = 1, 
                    idInscripcionExamen = ?,
                    asistencia = '100%' 
                WHERE idCalificacion = ?";
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("sii", $calificacion, $idInscripcion, $idCalificacion);
    if (!$stmt_ct->execute()) throw new Exception("Error al actualizar calificacionesterciario (Conversión).");
    $stmt_ct->close();
}
/**
 * ==========================================================
 * FUNCIONES PARA GESTIÓN DE EQUIVALENCIAS (equivalencias.php)
 * ==========================================================
 */

/**
 * Verifica si ya existe una matriculación para un alumno en una materia (por idUnicoMateria).
 *
 * @param mysqli $conexion
 * @param int $idAlumno
 * @param int $idMateria (El idMateria PK de la materia que se intenta inscribir)
 * @return bool True si ya existe, false si no.
 */
function checkMatriculacionMateriaExiste($conexion, $idAlumno, $idMateria) {
    // ==========================================================
    // == 🔹 MODIFICACIÓN: Comprobación por idUnicoMateria
    // ==========================================================
    $sql = "SELECT COUNT(mm.idMatriculacionMateria) AS count
            FROM matriculacionmateria mm
            JOIN materiaterciario mt ON mm.idMateria = mt.idMateria
            WHERE mm.idAlumno = ? 
            AND mt.idUnicoMateria = (
                SELECT m_inner.idUnicoMateria 
                FROM materiaterciario m_inner 
                WHERE m_inner.idMateria = ?
            )";
    // ==========================================================
            
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar checkMatriculacionMateriaExiste: " . $conexion->error);
        return true; // Asumir que existe para prevenir duplicados
    }
    $stmt->bind_param("ii", $idAlumno, $idMateria);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] > 0;
}

/**
 * Inserta un registro de Equivalencia o Pase (3 tablas).
 * (Debe ser llamado dentro de una transacción)
 *
 * @param mysqli $conn
 * @param array $data Array asociativo con los datos del formulario.
 * @return bool True si éxito, false si falla.
 * @throws Exception Si algo sale mal.
 */
function insertarEquivalencia($conn, $data) {
    // Extraer datos
    $idAlumno = $data['idAlumno'];
    $idMateria = $data['idMateria'];
    $fecha = $data['fecha'];
    $tipo = $data['tipo']; // "Aprobación por Equivalencia" o "Aprobación por Pase"
    $resolucion = $data['resolucion'];
    $calificacion = $data['calificacion'];
    $procedencia = $data['procedencia'];

    // 1. Insertar en matriculacionmateria
    $sql_mm = "INSERT INTO matriculacionmateria 
               (idAlumno, idNivel, idMateria, fechaMatriculacion, estado, idCicloLectivo) 
               VALUES (?, 6, ?, ?, ?, 0)";
    $stmt_mm = $conn->prepare($sql_mm);
    if (!$stmt_mm) throw new Exception("Error al preparar (matriculacionmateria): " . $conn->error);
    $stmt_mm->bind_param("iiss", $idAlumno, $idMateria, $fecha, $tipo);
    if (!$stmt_mm->execute()) throw new Exception("Error al insertar (matriculacionmateria): " . $stmt_mm->error);
    
    $idMatriculacionMateria = $conn->insert_id;
    $stmt_mm->close();

    // 2. Insertar en calificacionesterciario
    // ==========================================================
    // == 🔹 MODIFICACIÓN: Se añade idInscripcionExamen = NULL
    // ==========================================================
    $sql_ct = "INSERT INTO calificacionesterciario 
               (idAlumno, idMateria, asistencia, sinAsistencia, estadoCursadoNumero, estadoCursado, materiaAprobada, examenIntegrador, idInscripcionExamen) 
               VALUES (?, ?, '100%', 0, 11, ?, 1, ?, NULL)";
    // ==========================================================
               
    $stmt_ct = $conn->prepare($sql_ct);
    if (!$stmt_ct) throw new Exception("Error al preparar (calificacionesterciario): " . $conn->error);
    $stmt_ct->bind_param("iiss", $idAlumno, $idMateria, $tipo, $calificacion);
    if (!$stmt_ct->execute()) throw new Exception("Error al insertar (calificacionesterciario): " . $stmt_ct->error);
    $stmt_ct->close();

    // 3. Insertar en resoluciones
    $nombreResolucion = $resolucion . " " . $calificacion;
    $sql_r = "INSERT INTO resoluciones 
              (tipoResolucion, nombre, procedencia, idReferencia) 
              VALUES ('Matriculación Materia', ?, ?, ?)";
    $stmt_r = $conn->prepare($sql_r);
    if (!$stmt_r) throw new Exception("Error al preparar (resoluciones): " . $conn->error);
    $stmt_r->bind_param("ssi", $nombreResolucion, $procedencia, $idMatriculacionMateria);
    if (!$stmt_r->execute()) throw new Exception("Error al insertar (resoluciones): " . $stmt_r->error);
    $stmt_r->close();

    return true;
}

/**
 * Obtiene todos los registros de equivalencia/pase de un alumno.
 *
 * @param mysqli $conexion
 * @param int $idAlumno
 * @return array
 */
function obtenerEquivalenciasAlumno($conexion, $idAlumno) {
    $sql = "SELECT 
                mm.idMatriculacionMateria, mm.fechaMatriculacion, mm.estado, 
                mt.nombre AS nombreMateria, 
                c.nombre AS nombreCurso, 
                pe.nombre AS nombrePlan, 
                r.nombre AS nombreResolucion, r.procedencia, r.idResolucion, 
                ct.examenIntegrador AS calificacion, ct.idCalificacion,
                mt.idMateria, c.idCurso, pe.idPlan
            FROM matriculacionmateria mm
            JOIN materiaterciario mt ON mm.idMateria = mt.idMateria
            JOIN curso c ON mt.idCurso = c.idCurso
            JOIN plandeestudio pe ON mt.idPlan = pe.idPlan
            LEFT JOIN resoluciones r ON mm.idMatriculacionMateria = r.idReferencia AND r.tipoResolucion = 'Matriculación Materia'
            LEFT JOIN calificacionesterciario ct ON mm.idAlumno = ct.idAlumno AND mm.idMateria = ct.idMateria AND (ct.estadoCursado = 'Aprobación por Equivalencia' OR ct.estadoCursado = 'Aprobación por Pase')
            WHERE mm.idAlumno = ? 
            AND (mm.estado = 'Aprobación por Equivalencia' OR mm.estado = 'Aprobación por Pase')
            ORDER BY mm.fechaMatriculacion DESC";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerEquivalenciasAlumno: " . $conexion->error);
        return [];
    }
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $result = $stmt->get_result();
    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }
    $stmt->close();
    return $registros;
}

/**
 * Obtiene los detalles de una matriculación para su eliminación.
 */
function obtenerDetallesEquivalencia($conn, $idMatriculacionMateria) {
    $sql = "SELECT idAlumno, idMateria FROM matriculacionmateria WHERE idMatriculacionMateria = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar obtenerDetallesEquivalencia: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $idMatriculacionMateria);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();
    return $details;
}

/**
 * Elimina un registro de Equivalencia/Pase (3 tablas).
 * (Debe ser llamado dentro de una transacción)
 *
 * @param mysqli $conn
 * @param int $idMatriculacionMateria
 * @return bool
 * @throws Exception
 */
function eliminarEquivalencia($conn, $idMatriculacionMateria) {
    $details = obtenerDetallesEquivalencia($conn, $idMatriculacionMateria);
    if (!$details) {
        throw new Exception("Registro no encontrado.");
    }
    $idAlumno = $details['idAlumno'];
    $idMateria = $details['idMateria'];

    // 1. Eliminar de resoluciones
    $stmt_r = $conn->prepare("DELETE FROM resoluciones WHERE idReferencia = ? AND tipoResolucion = 'Matriculación Materia'");
    $stmt_r->bind_param("i", $idMatriculacionMateria);
    if (!$stmt_r->execute()) throw new Exception("Error al eliminar de resoluciones.");
    $stmt_r->close();

    // 2. Eliminar de calificacionesterciario
    $stmt_ct = $conn->prepare("DELETE FROM calificacionesterciario WHERE idAlumno = ? AND idMateria = ? AND (estadoCursado = 'Aprobación por Equivalencia' OR estadoCursado = 'Aprobación por Pase')");
    $stmt_ct->bind_param("ii", $idAlumno, $idMateria);
    if (!$stmt_ct->execute()) throw new Exception("Error al eliminar de calificacionesterciario.");
    $stmt_ct->close();

    // 3. Eliminar de matriculacionmateria
    $stmt_mm = $conn->prepare("DELETE FROM matriculacionmateria WHERE idMatriculacionMateria = ?");
    $stmt_mm->bind_param("i", $idMatriculacionMateria);
    if (!$stmt_mm->execute()) throw new Exception("Error al eliminar de matriculacionmateria.");
    $stmt_mm->close();

    return true;
}

/**
 * Actualiza un registro de Equivalencia/Pase (3 tablas).
 * (Debe ser llamado dentro de una transacción)
 *
 * @param mysqli $conn
 * @param array $data
 * @return bool
 * @throws Exception
 */
function actualizarEquivalencia($conn, $data) {
    // Extraer datos
    $idMatriculacionMateria = $data['idMatriculacionMateria'];
    $idResolucion = $data['idResolucion'];
    $idCalificacion = $data['idCalificacion'];
    $idMateria = $data['idMateria'];
    $fecha = $data['fecha'];
    $tipo = $data['tipo'];
    $resolucion = $data['resolucion'];
    $calificacion = $data['calificacion'];
    $procedencia = $data['procedencia'];

    // 1. Actualizar matriculacionmateria
    $sql_mm = "UPDATE matriculacionmateria SET idMateria = ?, fechaMatriculacion = ?, estado = ? 
               WHERE idMatriculacionMateria = ?";
    $stmt_mm = $conn->prepare($sql_mm);
    $stmt_mm->bind_param("issi", $idMateria, $fecha, $tipo, $idMatriculacionMateria);
    if (!$stmt_mm->execute()) throw new Exception("Error al actualizar matriculacionmateria.");
    $stmt_mm->close();

    // 2. Actualizar calificacionesterciario
    // ==========================================================
    // == 🔹 MODIFICACIÓN: Se añade idInscripcionExamen = NULL
    // ==========================================================
    $sql_ct = "UPDATE calificacionesterciario SET idMateria = ?, estadoCursado = ?, examenIntegrador = ?, idInscripcionExamen = NULL 
               WHERE idCalificacion = ?";
    // ==========================================================
               
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("issi", $idMateria, $tipo, $calificacion, $idCalificacion);
    if (!$stmt_ct->execute()) throw new Exception("Error al actualizar calificacionesterciario.");
    $stmt_ct->close();

    // 3. Actualizar resoluciones
    $nombreResolucion = $resolucion . " " . $calificacion;
    $sql_r = "UPDATE resoluciones SET nombre = ?, procedencia = ? WHERE idResolucion = ?";
    $stmt_r = $conn->prepare($sql_r);
    $stmt_r->bind_param("ssi", $nombreResolucion, $procedencia, $idResolucion);
    if (!$stmt_r->execute()) throw new Exception("Error al actualizar resoluciones.");
    $stmt_r->close();

    return true;
}

/**
 * Obtiene Cursos (no predeterminados) por idPlan.
 */
function getCursosPorPlan($conn, $idPlan) {
    $cursos = [];
    $sql = "SELECT idCurso, nombre FROM curso WHERE idPlanEstudio = ? ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar getCursosPorPlan: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $idPlan);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cursos[] = $row;
    }
    $stmt->close();
    return $cursos;
}

/**
 * Obtiene Materias por idCurso.
 */
function getMateriasPorCurso($conn, $idCurso) {
    $materias = [];
    $sql = "SELECT idMateria, nombre FROM materiaterciario WHERE idCurso = ? ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar getMateriasPorCurso: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $idCurso);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }
    $stmt->close();
    return $materias;
}
/**
 * ==========================================================
 * FUNCIONES PARA INSCRIPCIÓN MASIVA A EXAMEN (Secretaría)
 * ==========================================================
 */

/**
 * Busca alumnos para inscripción masiva a examen.
 *
 * Devuelve alumnos que (A) están en matriculacionmateria para esa materia/ciclo
 * y (B) cuyo estado de cursado (estadoCursadoNumero) en calificacionesterciario
 * coincide con el idCondicion proporcionado.
 *
 * @param mysqli $conexion Conexión a la DB.
 * @param int $idMateria
 * @param int $idCiclo
 * @param int $idCondicionEstado El ID de la tabla 'condicion' (que mapea a 'estadoCursadoNumero')
 * @return array Lista de alumnos [idAlumno, apellido, nombre, dni].
 */
function buscarAlumnosParaInscripcionMasiva($conexion, $idMateria, $idCiclo, $idCondicionEstado) {
    $alumnos = [];
    
    // Consulta principal
    // Usamos DISTINCT para asegurar que cada alumno aparezca una sola vez,
    // independientemente de múltiples registros si la lógica de negocio lo permitiera.
    $sql = "SELECT DISTINCT a.idAlumno, p.apellido, p.nombre, p.dni
            FROM alumno a
            JOIN persona p ON a.idPersona = p.idPersona
            JOIN matriculacionmateria mm ON a.idAlumno = mm.idAlumno
            JOIN calificacionesterciario ct ON a.idAlumno = ct.idAlumno AND mm.idMateria = ct.idMateria
            WHERE mm.idMateria = ?
            AND mm.idCicloLectivo = ?
            AND ct.estadoCursadoNumero = ?
            ORDER BY p.apellido, p.nombre";
            
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error al preparar (buscarAlumnosParaInscripcionMasiva): " . $conexion->error);
        return []; // Devuelve vacío en caso de error
    }
    
    // Asumimos todos enteros
    $stmt->bind_param("iii", $idMateria, $idCiclo, $idCondicionEstado);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $alumnos[] = $row;
        }
    } else {
        error_log("Error al ejecutar (buscarAlumnosParaInscripcionMasiva): " . $stmt->error);
    }
    
    $stmt->close();
    return $alumnos;
}
// =================================================================
// FUNCIONES GENERALES
// =================================================================

/**
 * Busca todos los ciclos lectivos disponibles y los devuelve como array asociativo.
 * Función de soporte para la carga inicial de selects.
 * @param mysqli $conexion Conexión a la base de datos.
 * @return array Listado de ciclos lectivos [idciclolectivo, anio].
 */
function buscarCiclosLectivosArray($conexion) {
    $sql = "SELECT idciclolectivo, anio FROM ciclolectivo ORDER BY anio DESC";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (buscarCiclosLectivosArray): " . $conexion->error);
        return [];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}




// =================================================================
// FUNCIONES PARA GESTIÓN DE ACTAS DE EXAMEN (SECRETARÍA)
// =================================================================

/**
 * Busca todos los turnos disponibles en el sistema.
 * @param mysqli $conexion Conexión a la base de datos.
 * @return array Listado de turnos [idTurno, nombre].
 */
function buscarTurnosDisponibles($conexion) {
    $sql = "SELECT idTurno, nombre FROM turno ORDER BY nombre ASC";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (buscarTurnosDisponibles): " . $conexion->error);
        return [];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Busca los planes con materias activas en un ciclo y turno específicos.
 * @param mysqli $conexion Conexión a la base de datos.
 * @param int $idCiclo ID del ciclo lectivo.
 * @param int $idTurno ID del turno.
 * @return array Listado de planes [idPlan, nombre].
 */
function buscarPlanesPorCicloTurno($conexion, $idCiclo, $idTurno) {
    $sql = "SELECT DISTINCT t1.idPlan, t1.nombre \n"
         . "FROM plan t1 \n"
         . "INNER JOIN materiaterciario t2 ON t1.idPlan = t2.idPlan \n"
         . "INNER JOIN curso t3 ON t2.idCurso = t3.idCurso \n"
         . "WHERE t2.idCicloLectivo = ? AND t3.idTurno = ? \n"
         . "ORDER BY t1.nombre ASC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (buscarPlanesPorCicloTurno): " . $conexion->error);
        return [];
    }
    $stmt->bind_param("ii", $idCiclo, $idTurno);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Busca las materias de un curso, plan y ciclo lectivo.
 * Asumo que 'buscarCursosPlanCiclo' ya existe.
 * @param mysqli $conexion Conexión a la base de datos.
 * @param int $idPlan ID del plan.
 * @param int $idCurso ID del curso.
 * @param int $idCiclo ID del ciclo lectivo.
 * @return array Listado de materias [idMateria, nombre].
 */
function buscarMateriasCursoPlanCiclo($conexion, $idPlan, $idCurso, $idCiclo) {
    $sql = "SELECT idMateria, nombre \n"
         . "FROM materiaterciario \n"
         . "WHERE idPlan = ? AND idCurso = ? AND idCicloLectivo = ? \n"
         . "ORDER BY nombre ASC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (buscarMateriasCursoPlanCiclo): " . $conexion->error);
        return [];
    }
    $stmt->bind_param("iii", $idPlan, $idCurso, $idCiclo);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Busca las mesas de examen para una materia y ciclo lectivo.
 * @param mysqli $conexion Conexión a la base de datos.
 * @param int $idMateria ID de la materia.
 * @param int $idCiclo ID del ciclo lectivo.
 * @return array Listado de mesas [idFechaExamen, Fecha, Hora, libro, folio].
 */
function buscarMesasExamenPorMateriaCiclo($conexion, $idMateria, $idCiclo) {
    $sql = "SELECT idFechaExamen, DATE_FORMAT(fecha, '%d/%m/%Y') as Fecha, TIME_FORMAT(hora, '%H:%i') as Hora, libro, folio \n"
         . "FROM fechaexamen \n"
         . "WHERE idMateria = ? AND idCicloLectivo = ? \n"
         . "ORDER BY fecha DESC, hora DESC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (buscarMesasExamenPorMateriaCiclo): " . $conexion->error);
        return [];
    }
    $stmt->bind_param("ii", $idMateria, $idCiclo);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene el listado completo de alumnos inscriptos para un acta de examen, incluyendo su condición.
 * @param mysqli $conexion Conexión a la base de datos.
 * @param int $idFechaExamen ID de la mesa de examen.
 * @return array Listado de alumnos [idAlumno, apellido, nombre, condicion, oral, escrito, calificacion, libro, folio, idCondicion].
 */
function obtenerActaSecretaria($conexion, $idFechaExamen) {
    $sql = "SELECT \n"
         . "    ie.idAlumno, \n"
         . "    p.apellido, \n"
         . "    p.nombre, \n"
         . "    c.condicion, \n" 
         . "    ie.oral, \n"
         . "    ie.escrito, \n"
         . "    ie.calificacion, \n"
         . "    ie.libro, \n"
         . "    ie.folio, \n"
         . "    ie.idCondicion \n" 
         . "FROM inscripcionexamenes ie \n"
         . "JOIN alumno a ON ie.idAlumno = a.idAlumno \n"
         . "JOIN persona p ON a.idPersona = p.idPersona \n"
         . "JOIN condicion c ON ie.idCondicion = c.idCondicion \n"
         . "WHERE ie.idFechaExamen = ? \n"
         . "ORDER BY p.apellido, p.nombre";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (obtenerActaSecretaria): " . $conexion->error);
        return [];
    }
    $stmt->bind_param("i", $idFechaExamen);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Actualiza de forma segura un único campo del acta de examen para un alumno específico.
 * Utiliza una lista blanca de columnas para evitar inyección SQL.
 * @param mysqli $conexion Conexión a la base de datos.
 * @param int $idFechaExamen ID de la mesa de examen.
 * @param int $idAlumno ID del alumno.
 * @param string $columna Nombre de la columna a actualizar (validada).
 * @param string $valor Nuevo valor.
 * @return string Mensaje de estado.
 */
function actualizarActaExamen($conexion, $idFechaExamen, $idAlumno, $columna, $valor) {
    // Lista blanca estricta de columnas para la actualización.
    $columnasPermitidas = ['oral', 'escrito', 'calificacion', 'libro', 'folio'];

    if (!in_array($columna, $columnasPermitidas)) {
        error_log("Intento de actualización de columna no permitida: " . $columna);
        return "Columna no permitida.";
    }

    // La columna es inyectada con seguridad después de la validación.
    $sql = "UPDATE inscripcionexamenes \n"
         . "SET {$columna} = ? \n" 
         . "WHERE idFechaExamen = ? AND idAlumno = ?";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        error_log("Error al preparar (actualizarActaExamen): " . $conexion->error);
        return "Error al preparar la consulta: " . $conexion->error;
    }
    
    // Binding: s (string para el valor), i (int para idFechaExamen), i (int para idAlumno)
    $stmt->bind_param("sii", $valor, $idFechaExamen, $idAlumno);

    if ($stmt->execute()) {
        return "actualizado";
    } else {
        error_log("Error al ejecutar (actualizarActaExamen): " . $stmt->error);
        return "Error al actualizar: " . $stmt->error;
    }
}

/**
 * Busca todas las condiciones de inscripción a examen para el filtro.
 * @param mysqli $conexion Conexión a la base de datos.
 * @return array Listado de condiciones [idCondicion, condicion].
 */
function buscarCondicionesExamen($conexion) {
    $sql = "SELECT idCondicion, condicion FROM condicion ORDER BY condicion ASC";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar (buscarCondicionesExamen): " . $conexion->error);
        return [];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}