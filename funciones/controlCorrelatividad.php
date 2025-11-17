<?php

        /*
         TABLA DE CORRELATIVIDADES
         CONDICION:         0-REGULAR
                            1-APROBADO
         TIPO INSCRIPCION:  0-CURSADO
                            1-EXAMEN           
        */
$materiasAdeuda;

function controlCorrelatividades($idUnicoMateria, $idAlumno, $tipoInscripcion)
{
    $habilitacion = false;

    $corrRegular = correlatividadRegular($idUnicoMateria,  $idAlumno, $tipoInscripcion);
    $corrRegularGrupal = correlatividadRegularGrupal($idUnicoMateria,  $idAlumno, $tipoInscripcion);
    $corrAprobado = correlatividadAprobado($idUnicoMateria,  $idAlumno, $tipoInscripcion);
    $corrAprobadoGrupal = correlatividadAprobadoGrupal($idUnicoMateria,  $idAlumno, $tipoInscripcion);

    if ($corrRegular && $corrRegularGrupal && $corrAprobado && $corrAprobadoGrupal){
        $habilitacion = true;
    }
    return $habilitacion;
}

function correlatividadRegular($idUnicoMateria, $idAlumno, $tipoInscripcion) {
    global $materiasAdeuda;
    $habilitacion = true;

    // Obtener correlatividades en condición Regular
    $materiasRegular = getCorrelativasIndividual($idUnicoMateria, 0, $tipoInscripcion);

    foreach ($materiasRegular as $rowMateriasRegular) {
        $idUnicoMateriaRegular = (int)$rowMateriasRegular['idUnicoMatCorrelativa'];
        $nombreMateriaRegular = selectNombreXIdUnico($idUnicoMateriaRegular);

        $materiaHabilitada = false;

        // Obtener calificaciones
        $calificacionesMateriaRegular = getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateriaRegular);

        // Verificación directa de registros guardados
        foreach ($calificacionesMateriaRegular as $row) {
            $materiaAprobadaGuardada = filter_var($row['materiaAprobada'], FILTER_VALIDATE_BOOLEAN);
            if ($materiaAprobadaGuardada === true) {
                $materiaHabilitada = true;
                break;
            }

            $estadoNumeroGuardado = (int)$row['estadoCursadoNumero'];
            if (in_array($estadoNumeroGuardado, [1, 4, 11, 13])) {
                $materiaHabilitada = true;
                break;
            }
        }
        
        if (!$materiaHabilitada) {
                $habilitacion = false;
                $materiasAdeuda .= "\n$nombreMateriaRegular (Regular)";
            }
        else{
                $habilitacion = true;
            }
    }

    return $habilitacion;
}

function correlatividadRegularGrupal($idUnicoMateria, $idAlumno, $tipoInscripcion) {
    global $materiasAdeuda;
    $habilitacion = true;

    // Obtener correlatividades en condición Regular Grupal
    $materiasRegular = getCorrelativasGrupal($idUnicoMateria, 0, $tipoInscripcion);

    $cadenaGrupal1 = array_fill(0, 10, null);
    $cadenaGrupal2 = array_fill(0, 10, null);
    $elementos = 0;

    $contarMateriasGrupal = count($materiasRegular);
    for ($a = 0; $a < $contarMateriasGrupal; $a++) {
        $g = $materiasRegular[$a]['grupal'];
        $grupo = substr($g, 0, 1);
        $grupoCantidad = substr($g, 2);

        if ($a == 0) {
            $cadenaGrupal1[$elementos] = $g;
            $cadenaGrupal2[$elementos] = $grupoCantidad;
        } else {
            if ($cadenaGrupal1[$elementos] != $g) {
                $elementos++;
                $cadenaGrupal1[$elementos] = $g;
                $cadenaGrupal2[$elementos] = $grupoCantidad;
            }
        }
    }

    for ($a1 = 0; $a1 < 10; $a1++) {
        if (!is_null($cadenaGrupal1[$a1])) {
            $grupalString = $cadenaGrupal1[$a1];
            $cantidadCorrecta = (int)$cadenaGrupal2[$a1];
            $cantidadActual = 0;

            $grupal = getCorrelativasGrupalDetalle($idUnicoMateria, 0, $tipoInscripcion, $grupalString);

            foreach ($grupal as $rowMateriasRegular) {
                $idUnicoMateriaRegular = (int)$rowMateriasRegular['idUnicoMatCorrelativa'];

                $calificacionesMateriaRegular = getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateriaRegular);

                // Verificación directa de registros guardados
                foreach ($calificacionesMateriaRegular as $row) {
                    $materiaAprobadaGuardada = filter_var($row['materiaAprobada'], FILTER_VALIDATE_BOOLEAN);
                    if ($materiaAprobadaGuardada === true) {
                        $cantidadActual++;
                        break;
                    }

                    $estadoNumeroGuardado = (int)$row['estadoCursadoNumero'];
                    if (in_array($estadoNumeroGuardado, [1, 4, 11, 13])) {
                        $cantidadActual++;
                        break;
                    }
                }                
            }

            if ($cantidadActual < $cantidadCorrecta) {
                $habilitacion = false;
                $materiasAdeuda .= "\n(Cantidad Regular Grupal)";
            }
            else{
                $habilitacion = true;
            }
        }
    }

    return $habilitacion;
}

function correlatividadAprobado($idUnicoMateria, $idAlumno, $tipoInscripcion) {
    global $materiasAdeuda;
    $habilitacion = true;

    // Obtener correlatividades en condición Aprobada
    $materiasAprobada = getCorrelativasIndividual($idUnicoMateria, 1, $tipoInscripcion);

    foreach ($materiasAprobada as $rowMateriasAprobada) {
        $idUnicoMateriaAprobada = (int)$rowMateriasAprobada['idUnicoMatCorrelativa'];
        $nombreMateriaAprobada = selectNombreXIdUnico($idUnicoMateriaAprobada);

        $materiaHabilitada = false;

        // Obtener calificaciones
        $calificacionesMateriaAprobada = getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateriaAprobada);

        // Verificación directa de registros guardados
        foreach ($calificacionesMateriaAprobada as $row) {
            $materiaAprobadaGuardada = filter_var($row['materiaAprobada'], FILTER_VALIDATE_BOOLEAN);
            if ($materiaAprobadaGuardada === true) {
                $materiaHabilitada = true;
                break;
            }            
        }
        
        if (!$materiaHabilitada) {
                $habilitacion = false;
                $materiasAdeuda .= "\n$nombreMateriaAprobada (Aprobada)";
            }
        else{
                $habilitacion = true;
            }
    }

    return $habilitacion;
}

function correlatividadAprobadoGrupal($idUnicoMateria, $idAlumno, $tipoInscripcion) {
    global $materiasAdeuda;
    $habilitacion = true;

    // Obtener correlatividades en condición Aprobada Grupal
    $materiasAprobada = getCorrelativasGrupal($idUnicoMateria, 1, $tipoInscripcion);

    $cadenaGrupal1 = array_fill(0, 10, null);
    $cadenaGrupal2 = array_fill(0, 10, null);
    $elementos = 0;

    $contarMateriasGrupal = count($materiasAprobada);
    for ($a = 0; $a < $contarMateriasGrupal; $a++) {
        $g = $materiasAprobada[$a]['grupal'];
        $grupo = substr($g, 0, 1);
        $grupoCantidad = substr($g, 2);

        if ($a == 0) {
            $cadenaGrupal1[$elementos] = $g;
            $cadenaGrupal2[$elementos] = $grupoCantidad;
        } else {
            if ($cadenaGrupal1[$elementos] != $g) {
                $elementos++;
                $cadenaGrupal1[$elementos] = $g;
                $cadenaGrupal2[$elementos] = $grupoCantidad;
            }
        }
    }

    for ($a1 = 0; $a1 < 10; $a1++) {
        if (!is_null($cadenaGrupal1[$a1])) {
            $grupalString = $cadenaGrupal1[$a1];
            $cantidadCorrecta = (int)$cadenaGrupal2[$a1];
            $cantidadActual = 0;

            $grupal = getCorrelativasGrupalDetalle($idUnicoMateria, 1, $tipoInscripcion, $grupalString);

            foreach ($grupal as $rowMateriasAprobada) {
                $idUnicoMateriaAprobada = (int)$rowMateriasAprobada['idUnicoMatCorrelativa'];

                $calificacionesMateriaAprobada = getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateriaAprobada);

                // Verificación directa de registros guardados
                foreach ($calificacionesMateriaAprobada as $row) {
                    $materiaAprobadaGuardada = filter_var($row['materiaAprobada'], FILTER_VALIDATE_BOOLEAN);
                    if ($materiaAprobadaGuardada === true) {
                        $cantidadActual++;
                        break;
                    }
                }                
            }

            if ($cantidadActual < $cantidadCorrecta) {
                $habilitacion = false;
                $materiasAdeuda .= "\n(Cantidad Aprobada Grupal)";
            }
            else{
                $habilitacion = true;
            }
        }
    }

    return $habilitacion;
}

function inscripcionExamenEstado($inscripcion, $estadoNumero, $nombreMateria) {
    $habilitacion = true;
    $texto = "";
        if ($inscripcion == "Libre" || $inscripcion == "No Regular" || $inscripcion == "Recursa")
        {
            if ($estadoNumero == 0) { }
            if ($estadoNumero == 1)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 2)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad para Coloquio en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 3) { }
            if ($estadoNumero == 4)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 5)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad para Coloquio en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 6)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Calificaciones en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 7) { }
            if ($estadoNumero == 8)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene la Asistencia Requerida en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 9)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Asistencia Registrada en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 10)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 11)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Aprobada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 12)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 13)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Aprobada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 14)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Promocionada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 15)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Promocionada la Materia:" . $nombreMateria;
            }
            }
        if ($inscripcion == "Regular"|| $inscripcion == "Aprobó Cursada")
        {
            if ($estadoNumero == 0)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 1) { }
            if ($estadoNumero == 2)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad para Coloquio en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 3)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 4) { }
            if ($estadoNumero == 5)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad para Coloquio en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 6)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Calificaciones en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 7) { }
            if ($estadoNumero == 8)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene la Asistencia Requerida en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 9)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Asistencia Registrada en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 10)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 11)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Aprobada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 12)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 13)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Aprobada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 14)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Promocionada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 15)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Promocionada la Materia:" . $nombreMateria;
            }
        }
        if ($inscripcion == "Coloquio")
        {
            if ($estadoNumero == 0)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 1)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 2) { }
            if ($estadoNumero == 3)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 4)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 5) { }
            if ($estadoNumero == 6)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Calificaciones en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 7) { }
            if ($estadoNumero == 8)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene la Asistencia Requerida en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 9)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Asistencia Registrada en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 10)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 11)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Aprobada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 12)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 13)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Aprobada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 14)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Promocionada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 15)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Promocionada la Materia:" . $nombreMateria;
            }
        }
        if ($inscripcion == "Promoción")
        {
            if ($estadoNumero == 0)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 1)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 2)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad para Coloquio en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 3)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 4)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 5)
            {
                $habilitacion = false;
                $texto = "El alumno tiene Regularidad para Coloquio en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 6)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Calificaciones en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 7) { }
            if ($estadoNumero == 8)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene la Asistencia Requerida en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 9)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Asistencia Registrada en la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 10)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 11) { }
            if ($estadoNumero == 12)
            {
                $habilitacion = false;
                $texto = "El alumno no tiene Regularizada la Materia:" . $nombreMateria;
            }
            if ($estadoNumero == 13) { }
            if ($estadoNumero == 14) { }
            if ($estadoNumero == 15) { }
        }
        $salida = [ $habilitacion, $texto ];
        return $salida;
}

function inscripcionExamenControl($conexion, $idAlumno, $idUnicoMateria, $inscripcion) {
    global $materiasAdeuda;
    $habilitacionInscripcion = false;
    $existeCursado = false;    
    $tipoInscripcion = 1;

    $consulta = "SELECT *
        from calificacionesterciario c inner join materiaterciario m
        on m.idMateria = c.idMateria
        where m.idUnicoMateria = ? and c.idAlumno = ?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ii", $idUnicoMateria, $idAlumno);
    $stmt->execute();
    $calif = $stmt->get_result();

    $i = 0;
    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {

            $existeCursado = true;

            $estadoCursadoNumero = $data['estadoCursadoNumero'];
            $nombreMateria = $data['nombre'];
    
            $habilitadoEstado = inscripcionExamenEstado($inscripcion, $estadoCursadoNumero, $nombreMateria);
            if ($habilitadoEstado[0]) { break; }
            $i++;
        }
    }
    if ($existeCursado)
    {
        if ($habilitadoEstado[0])
        {
            $habilitacionCorrelatividades = controlCorrelatividades($idUnicoMateria, $idAlumno, $tipoInscripcion);
            if ($habilitacionCorrelatividades) 
                { $habilitacionInscripcion = true; }
            else
                {
                    $habilitacionInscripcion = false; 
                    $salida = $materiasAdeuda;
                }
        } 
        else
        {
            $habilitacionInscripcion = false; 
            $salida = $habilitadoEstado[1];
        }
    }
    else 
    { 
        $habilitacionInscripcion = false; 
        $salida = "El alumno no tiene registro de Cursado.";
    }
    if ($habilitacionInscripcion){ return $habilitacionInscripcion; }
    else { return $salida; }
}


include '../inicio/conexion.php';
function selectNombreXIdUnico($idUnicoMateria) {
    global $conn;
    $stmt = $conn->prepare("SELECT nombre FROM materiaterciario WHERE idUnicoMateria = ?");
    $stmt->bind_param("s", $idUnicoMateria);
    $stmt->execute();
    $stmt->bind_result($nombre);
    $stmt->fetch();
    $stmt->close();
    return $nombre;
}

function getCorrelativasIndividual($idUnicoMateria, $condicion, $tipoInscripcion) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM correlatividadesterciario WHERE idUnicoMateria = ? AND condicionCorrelatividad = ? AND tipoInscripcion = ?");
    $stmt->bind_param("sss", $idUnicoMateria, $condicion, $tipoInscripcion);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function getCorrelativasGrupal($idUnicoMateria, $condicion, $tipoInscripcion) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM correlatividadesterciario WHERE idUnicoMateria = ? AND condicionCorrelatividad = ? AND tipoInscripcion = ? AND grupal <> '' ORDER BY grupal");
    $stmt->bind_param("sss", $idUnicoMateria, $condicion, $tipoInscripcion);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function getCorrelativasGrupalDetalle($idUnicoMateria, $condicion, $tipoInscripcion, $grupalString) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM correlatividadesterciario WHERE idUnicoMateria = ? AND condicionCorrelatividad = ? AND tipoInscripcion = ? AND grupal = ?");
    $stmt->bind_param("ssss", $idUnicoMateria, $condicion, $tipoInscripcion, $grupalString);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateria) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM calificacionesterciario c inner join materiaterciario m
    on c.idMateria = m.idMateria WHERE c.idAlumno = ? AND m.idUnicoMateria = ?");
    $stmt->bind_param("ss", $idAlumno, $idUnicoMateria);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}
?>
