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
    $habilitacion = false;

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
    $habilitacion = false;

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
    $habilitacion = false;

    // Obtener correlatividades en condición Aprobada
    $materiasAprobada = getCorrelativasIndividual($idUnicoMateria, 0, $tipoInscripcion);

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
    $habilitacion = false;

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






function selectNombreXIdUnico($idUnicoMateria) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nombre FROM materiaterciario WHERE idUnicoMateria = ?");
    $stmt->execute([$idUnicoMateria]);
    return $stmt->fetchColumn();
}

function getCorrelativasIndividual($idUnicoMateria, $condicion, $tipoInscripcion) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM correlatividadesterciario WHERE idUnicoMateria = ? AND condicion = ? AND tipoInscripcion = ?");
    $stmt->execute([$idUnicoMateria, $condicion, $tipoInscripcion]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCorrelativasGrupal($idUnicoMateria, $condicion, $tipoInscripcion) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM correlatividadesterciario WHERE idUnicoMateria = ? AND condicion = ? AND tipoInscripcion = ? AND grupal <> '' order by grupal");
    $stmt->execute([$idUnicoMateria, $condicion, $tipoInscripcion]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCorrelativasGrupalDetalle($idUnicoMateria, $condicion, $tipoInscripcion, $grupalString) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM correlatividadesterciario WHERE idUnicoMateria = ? AND condicion = ? AND tipoInscripcion = ? AND grupal = ?");
    $stmt->execute([$idUnicoMateria, $condicion, $tipoInscripcion, $grupalString]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCalificacionesPorAlumnoMateria($idAlumno, $idUnicoMateria) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM calificacionesterciario WHERE idAlumno = ? AND idUnicoMateria = ?");
    $stmt->execute([$idAlumno, $idUnicoMateria]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>