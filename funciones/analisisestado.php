<?php

//Armar tabla de asistencias por materia y alumno
function obtenerAsistencia($conexion, $idAlumno, $idMateria, $idCicloLectivo) {
    // Consulta SQL para obtener los registros de asistencia del alumno
    $consulta = "SELECT d1, d2, d3, d4, d5, d6, d7, d8, d9, d10, d11, d12, d13, d14, d15, 
                        d16, d17, d18, d19, d20, d21, d22, d23, d24, d25, d26, d27, d28, 
                        d29, d30, d31, idAlumno, idAsistenciaTerciario, idCicloLectivo, 
                        idMateria, mes, tmp_apenom 
                 FROM asistenciaterciario 
                 WHERE idAlumno = ? AND idMateria = ? AND idCicloLectivo = ?";

    // Preparar la consulta y vincular parámetros
    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("iii", $idAlumno, $idMateria, $idCicloLectivo);
    
    // Ejecutar la consulta
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Array para almacenar los resultados de la consulta
    $tabla = array();

    // Verificar si se obtuvieron resultados
    if ($resultado->num_rows > 0) {
        // Almacenar los resultados en el array
        while ($data = $resultado->fetch_row()) {
            $tabla[] = $data;  // Cada fila del resultado se agrega al array
        }
    }

    // Retornar la tabla de asistencia
    return $tabla;
}

//Calcular porcentaje asistencia de materia y alumno
function porcentaje($tabla) {
    $salida = "";

    $ausente = 0;
    $presente = 0;
    $total = 0;

    $cantidad = count($tabla); // número de filas

    for ($a = 0; $a < $cantidad; $a++) {
        for ($ubicacion = 0; $ubicacion < 31; $ubicacion++) {           

            $asistencia = strval($tabla[$a][$ubicacion]);

            if ($asistencia != "") {
                $aumenta = 0;

                while ($aumenta < strlen($asistencia)) {
                    $asis = substr($asistencia, $aumenta, 1);
                    $x = strtoupper($asis);

                    if ($x == 'A' || $x == 'J') {
                        $ausente++;
                    } elseif ($x == 'P' || $x == 'S' || $x == 'T') {
                        $presente++;
                    } elseif ($x == 'M') {
                        $ausente += 0.5;
                        $presente += 0.5;
                    }

                    $total++;
                    $aumenta++;
                }
            }
        }
    }

    if ($total != 0) {
        $resultado = round(($presente * 100) / $total, 2);
        $salida = $resultado . "%";
    } else {
        $salida = "S/Asist";
    }

    return $salida;
}

//Actualiza porcentaje de asistencia
function actualizarAsistencia($conexion, $idAlumno, $idMateria, $valor){
    $consulta = "UPDATE calificacionesterciario SET registroModificacion=1, asistencia = ? 
    WHERE idAlumno = ? and idMateria = ?";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "sii", $valor, $idAlumno, $idMateria);
    $resultado = mysqli_stmt_execute($stmt);

    if (!$resultado) {
        $respuesta = "Error: " . mysqli_error($conexion);
    } else {
        $respuesta = "actualizado";
    }
    return $respuesta;
}

function debeMateria($conexion, $idAlumno, $idMateria) {
    $consulta = "
        SELECT COUNT(*) AS total
        FROM correlatividadesterciario cr
        INNER JOIN materiaterciario mt 
            ON cr.idUnicoMatCorrelativa = mt.idUnicoMateria
            AND cr.condicionCorrelatividad = 1
            AND cr.tipoInscripcion = 1
        INNER JOIN calificacionesterciario cl 
            ON cl.idMateria = mt.idMateria 
            AND cl.idAlumno = ?
        WHERE cl.materiaAprobada != 1 
        AND cr.idUnicoMateria = (
            SELECT mt2.idUnicoMateria 
            FROM materiaterciario mt2 
            WHERE mt2.idMateria = ?
        )";

    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "ii", $idAlumno, $idMateria);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);

    return $total; // será 0 o mayor a 0
}


function iniciarAnalisis($conexion, $idMateria, $idAlumno, $idCalificacion)
{
    //DATOS COLEGIO
    $consulta = "SELECT * from colegio where nivel = 'Terciario'";
    $colegio = mysqli_query($conexion, $consulta);

    if (!empty($colegio)) {
        while ($data = mysqli_fetch_array($colegio)) {
            $cod_col = $data['codcol'];

        }
    }

    //DATOS MATERIA
    $consulta = "SELECT * from materiaterciario 
    where materiaterciario.idMateria = $idMateria";

    $datos = mysqli_query($conexion, $consulta);

    if (!empty($datos)) {
        while ($data = mysqli_fetch_array($datos)) {

            $widtipomateria = $data['idTipoMateria'];
            $wasistenciaPromocionRed = $data['asistenciaPromocionRed'];
            $wasistenciaRegularRed = $data['asistenciaRegularRed'];
            $wasistenciaPromocion = $data['asistenciaPromocion'];
            $wasistenciaRegular = $data['asistenciaRegular'];
            $wcalificacionTrabajo = $data['calificacionTrabajo'];
            $wcalificacionPromocion = $data['calificacionPromocion'];
            $wcalificacionRegular = $data['calificacionRegular'];

        }
    }
    //DATOS ALUMNO
    //MatriculacionMateria
    $consulta1 = "SELECT * from matriculacionmateria 
    where matriculacionmateria.idMateria = $idMateria and matriculacionmateria.idAlumno = $idAlumno";

    $matriculacion = mysqli_query($conexion, $consulta1);

    if (!empty($matriculacion)) {
        while ($data = mysqli_fetch_array($matriculacion)) {

            $westadoMatric = $data['estado'];
        }
    }

    //Calificaciones
    $consulta2 = "SELECT calificacionesterciario.* 
    from calificacionesterciario 
    where calificacionesterciario.idMateria = $idMateria and calificacionesterciario.idAlumno = $idAlumno";

    $calif = mysqli_query($conexion, $consulta2);

    $cursadoMateria = array();
    $i = 0;
    if (!empty($calif)) {
        while ($data = mysqli_fetch_array($calif)) {

            $wn1 = $data['n1'];
            $wn2 = $data['n2'];
            $wn3 = $data['n3'];
            $wn4 = $data['n4'];
            $wn5 = $data['n5'];
            $wn6 = $data['n6'];
            $wn7 = $data['n7'];
            $wn8 = $data['n8'];
            $wr1 = $data['r1'];
            $wr2 = $data['r2'];
            $wr3 = $data['r3'];
            $wr4 = $data['r4'];
            $wr5 = $data['r5'];
            $wr6 = $data['r6'];
            $wr7 = $data['r7'];
            $wr8 = $data['r8'];
            $wasistencia = $data['asistencia'];
            $wsinAsistencia = $data['sinAsistencia'];
        }
    }

    //Trabaja
    $consulta3 = "SELECT * from matriculacion
    where matriculacion.idPlanDeEstudio = 
    (Select idPlan from materiaterciario where materiaterciario.idMateria = $idMateria)
    and matriculacion.idAlumno = $idAlumno and matriculacion.anio = 
    (select anio from ciclolectivo where idciclolectivo = 
    (select idCicloLectivo from materiaterciario where idMateria = $idMateria))";

    $trabaja = mysqli_query($conexion, $consulta3);

    if (!empty($trabaja)) {
        while ($data = mysqli_fetch_array($trabaja)) {

            $wtrabaja = $data['certificadoTrabajo'];
        }
    }

    $analisis = analisis_estado(
        $widtipomateria,
        $wasistenciaPromocionRed,
        $wasistenciaRegularRed,
        $wasistenciaPromocion,
        $wasistenciaRegular,
        $wcalificacionTrabajo,
        $wcalificacionPromocion,
        $wcalificacionRegular,
        $westadoMatric,
        $wasistencia,
        $wtrabaja,
        $wsinAsistencia,
        $wn1,
        $wn2,
        $wn3,
        $wn4,
        $wn5,
        $wn6,
        $wn7,
        $wn8,
        $wr1,
        $wr2,
        $wr3,
        $wr4,
        $wr5,
        $wr6,
        $wr7,
        $wr8,
        $cod_col
    );

    $estadoCursadoNumero = $analisis[0];
    $estadoCursado = $analisis[1];

    //Actualizar Estado
    $consulta4 = "update calificacionesterciario
    set registroModificacion=1, estadoCursadoNumero = $estadoCursadoNumero, estadoCursado = '$estadoCursado' 
    where calificacionesterciario.idCalificacion = $idCalificacion";

    $calif = mysqli_query($conexion, $consulta4);

    return $estadoCursado;
}

//Datos necesarios para el analisis
//TipoMateria, AsistPromRed, AsistRegRed, AsistProm, AsistReg, CalifTrabajo, CalifProm,
//CalifReg, EstadoMatriculacion, asistencia, trabaja, sinAsistencia,
//n1,n2,n3,n4,n5,n6,n7,n8,r1,r2,r3,r4,r5,r6,r7,r8

//[glo_codcol] es el nombre del colegio

function analisis_estado(
    $widtipomateria,
    $wasistenciaPromocionRed,
    $wasistenciaRegularRed,
    $wasistenciaPromocion,
    $wasistenciaRegular,
    $wcalificacionTrabajo,
    $wcalificacionPromocion,
    $wcalificacionRegular,
    $westadoMatric,
    $wasistencia,
    $wtrabaja,
    $wsinAsistencia,
    $wn1,
    $wn2,
    $wn3,
    $wn4,
    $wn5,
    $wn6,
    $wn7,
    $wn8,
    $wr1,
    $wr2,
    $wr3,
    $wr4,
    $wr5,
    $wr6,
    $wr7,
    $wr8,
    $cod_col
) {

    //Ajustar variables

    $asistencia = str_replace('%', '', $wasistencia);    // quita el signo de porcentaje
    $asistencia = str_replace(',', '.', $asistencia);    //cambia la coma por el punto

    $trabaja = $wtrabaja;
    $tipoMateria = $widtipomateria;
    if ($wsinAsistencia == 1) {
        $asistencia = "S/Asist";
    }

    $salida = 99;
    $aprobado = "t";
    $regular = "t";
    $coloquio = "t";
    $libre = "f";
    $recursa = "f";
    $asistOblig = "t";
    $asistProm = "t";
    $sinCalif = "f";
    $sinAsist = "f";
    $recuperatorio = "f";

    $p1 = $wn1;
    $p2 = $wn2;
    $p3 = $wn3;
    $p4 = $wn4;
    $p5 = $wn5;
    $p6 = $wn6;
    $p7 = $wn7;
    $p8 = $wn8;
    $r1 = $wr1;
    $r2 = $wr2;
    $r3 = $wr3;
    $r4 = $wr4;
    $r5 = $wr5;
    $r6 = $wr6;
    $r7 = $wr7;
    $r8 = $wr8;

    //Ajustar variables particulares de la materia

    if ($wcalificacionRegular == "S/C") {
        $cReg = 4;
    } else {
        $cReg = $wcalificacionRegular;
    }
    if ($wcalificacionPromocion == "S/C") {
        $cProm = 7;
    } else {
        $cProm = $wcalificacionPromocion;
    }
    if ($wcalificacionTrabajo == "S/C") {
        $cTrab = $cProm;
    } else {
        $cTrab = $wcalificacionTrabajo;
    }
    if ($wasistenciaRegular == "S/A") {
        $aReg = 60;
    } else {
        $aReg = $wasistenciaRegular;
    }
    if ($wasistenciaPromocion == "S/A") {
        $aProm = 70;
    } else {
        $aProm = $wasistenciaPromocion;
    }

    if ($wcalificacionRegular == "S/C" || $wcalificacionRegular == "" || $wcalificacionRegular == null) {
        $cReg = 4;
    }
    if ($wcalificacionRegular == "NS") {
        $cReg = 2;
    }
    if ($wcalificacionRegular == "S") {
        $cReg = 4;
    }
    if ($wcalificacionRegular == "B") {
        $cReg = 6;
    }
    if ($wcalificacionRegular == "MB") {
        $cReg = 8;
    }
    if ($wcalificacionRegular == "E") {
        $cReg = 10;
    }
    if ($cReg == "") {
        $cReg = $wcalificacionRegular;
    }

    if ($wcalificacionPromocion == "S/PR") {
        $cProm = 100;
    }
    if ($wcalificacionPromocion == "S/C" || $wcalificacionPromocion == "" || $wcalificacionPromocion == null) {
        $cProm = 7;
    }
    if ($wcalificacionPromocion == "NS") {
        $cProm = 2;
    }
    if ($wcalificacionPromocion == "S") {
        $cProm = 4;
    }
    if ($wcalificacionPromocion == "B") {
        $cProm = 6;
    }
    if ($wcalificacionPromocion == "MB") {
        $cProm = 8;
    }
    if ($wcalificacionPromocion == "E") {
        $cProm = 10;
    }
    if ($cProm == "") {
        $cReg = $wcalificacionPromocion;
    }

    if ($wcalificacionTrabajo == "S/TP") {
        $cTrab = 100;
    }
    if ($wcalificacionTrabajo == "S/C" || $wcalificacionTrabajo == "" || $wcalificacionTrabajo == null) {
        $cTrab = $cProm;
    }
    if ($wcalificacionTrabajo == "NS") {
        $cTrab = 2;
    }
    if ($wcalificacionTrabajo == "S") {
        $cTrab = 4;
    }
    if ($wcalificacionTrabajo == "B") {
        $cTrab = 6;
    }
    if ($wcalificacionTrabajo == "MB") {
        $cTrab = 8;
    }
    if ($wcalificacionTrabajo == "E") {
        $cTrab = 10;
    }
    if ($cProm == "") {
        $cTrab = $wcalificacionTrabajo;
    }

    if ($wasistenciaRegular == "S/A" || $wasistenciaRegular == "" || $wasistenciaRegular == null) {
        $aReg = 60;
    } else {
        $aReg = $wasistenciaRegular;
    }
    if ($wasistenciaPromocion == "S/A" || $wasistenciaPromocion == "" || $wasistenciaPromocion == null) {
        $aProm = 70;
    } else {
        $aProm = $wasistenciaPromocion;
    }
    if ($wasistenciaRegularRed == "S/A" || $wasistenciaRegularRed == "" || $wasistenciaRegularRed == null) {
        $aRegRed = 60;
    } else {
        $aRegRed = $wasistenciaRegularRed;
    }
    if ($wasistenciaPromocionRed == "S/A" || $wasistenciaPromocionRed == "" || $wasistenciaPromocionRed == null) {
        $aPromRed = 70;
    } else {
        $aPromRed = $wasistenciaPromocionRed;
    }

    //Analisis PreSistema

    $PRS = 0;
    if ($westadoMatric == "Libre PreSistema") {
        $wanalisis = "Desaprobado/Recursa (PS)";
        $salida = 0;
        $PRS = 1;
    }
    if ($westadoMatric == "Regularidad PreSistema") {
        $wanalisis = "Cursada Aprobada (PS)";
        $salida = 1;
        $PRS = 1;
    }
    if ($westadoMatric == "Aprobación PreSistema") {
        $wanalisis = "Aprobación (PS)";
        $salida = 11;
        $PRS = 1;
    }
    if ($westadoMatric == "Aprobación por Equivalencia") {
        $wanalisis = "Aprobación por Equivalencia";
        $salida = 11;
        $PRS = 1;
    }
    if ($westadoMatric == "Aprobación por Pase") {
        $wanalisis = "Aprobación por Pase";
        $salida = 11;
        $PRS = 1;
    }
    if ($westadoMatric == "Desaprob./Recurs. PreSistema") {
        $wanalisis = "Desaprob./Recurs. (PS)";
        $salida = 10;
        $PRS = 1;
    }

    //Pasar calificaciones a numeros si no es presistema

    if ($PRS == 0) {
        if (empty($p1) || $p1 == '-') {
            $p1 = '-1';
        }
        ;
        if ($p1 == 'a' || $p1 == 'A') {
            $p1 = '0';
        }
        ;
        if ($p1 == 'Ap' || $p1 == 'ap' || $p1 == 'AP') {
            $p1 = '8';
        }
        ;
        if ($p1 == 'Na' || $p1 == 'na' || $p1 == 'NA') {
            $p1 = '2';
        }
        ;
        if ($p1 == 'Ns' || $p1 == 'ns' || $p1 == 'NS') {
            $p1 = '2';
        }
        ;
        if ($p1 == 'S' || $p1 == 's') {
            $p1 = '4';
        }
        ;
        if ($p1 == 'B' || $p1 == 'b') {
            $p1 = '6';
        }
        ;
        if ($p1 == 'Mb' || $p1 == 'mb' || $p1 == 'MB') {
            $p1 = '8';
        }
        ;
        if ($p1 == 'E' || $p1 == 'e') {
            $p1 = '10';
        }
        ;

        if (empty($p2) || $p2 == '-') {
            $p2 = '-1';
        }
        ;
        if ($p2 == 'a' || $p2 == 'A') {
            $p2 = '0';
        }
        ;
        if ($p2 == 'Ap' || $p2 == 'ap' || $p2 == 'AP') {
            $p2 = '8';
        }
        ;
        if ($p2 == 'Na' || $p2 == 'na' || $p2 == 'NA') {
            $p2 = '2';
        }
        ;
        if ($p2 == 'Ns' || $p2 == 'ns' || $p2 == 'NS') {
            $p2 = '2';
        }
        ;
        if ($p2 == 'S' || $p2 == 's') {
            $p2 = '4';
        }
        ;
        if ($p2 == 'B' || $p2 == 'b') {
            $p2 = '6';
        }
        ;
        if ($p2 == 'Mb' || $p2 == 'mb' || $p2 == 'MB') {
            $p2 = '8';
        }
        ;
        if ($p2 == 'E' || $p2 == 'e') {
            $p2 = '10';
        }
        ;

        if (empty($p3) || $p3 == '-') {
            $p3 = '-1';
        }
        ;
        if ($p3 == 'a' || $p3 == 'A') {
            $p3 = '0';
        }
        ;
        if ($p3 == 'Ap' || $p3 == 'ap' || $p3 == 'AP') {
            $p3 = '8';
        }
        ;
        if ($p3 == 'Na' || $p3 == 'na' || $p3 == 'NA') {
            $p3 = '2';
        }
        ;
        if ($p3 == 'Ns' || $p3 == 'ns' || $p3 == 'NS') {
            $p3 = '2';
        }
        ;
        if ($p3 == 'S' || $p3 == 's') {
            $p3 = '4';
        }
        ;
        if ($p3 == 'B' || $p3 == 'b') {
            $p3 = '6';
        }
        ;
        if ($p3 == 'Mb' || $p3 == 'mb' || $p3 == 'MB') {
            $p3 = '8';
        }
        ;
        if ($p3 == 'E' || $p3 == 'e') {
            $p3 = '10';
        }
        ;

        if (empty($p4) || $p4 == '-') {
            $p4 = '-1';
        }
        ;
        if ($p4 == 'a' || $p4 == 'A') {
            $p4 = '0';
        }
        ;
        if ($p4 == 'Ap' || $p4 == 'ap' || $p4 == 'AP') {
            $p4 = '8';
        }
        ;
        if ($p4 == 'Na' || $p4 == 'na' || $p4 == 'NA') {
            $p4 = '2';
        }
        ;
        if ($p4 == 'Ns' || $p4 == 'ns' || $p4 == 'NS') {
            $p4 = '2';
        }
        ;
        if ($p4 == 'S' || $p4 == 's') {
            $p4 = '4';
        }
        ;
        if ($p4 == 'B' || $p4 == 'b') {
            $p4 = '6';
        }
        ;
        if ($p4 == 'Mb' || $p4 == 'mb' || $p4 == 'MB') {
            $p4 = '8';
        }
        ;
        if ($p4 == 'E' || $p4 == 'e') {
            $p4 = '10';
        }
        ;

        if (empty($p5) || $p5 == '-') {
            $p5 = '-1';
        }
        ;
        if ($p5 == 'a' || $p5 == 'A') {
            $p5 = '0';
        }
        ;
        if ($p5 == 'Ap' || $p5 == 'ap' || $p5 == 'AP') {
            $p5 = '8';
        }
        ;
        if ($p5 == 'Na' || $p5 == 'na' || $p5 == 'NA') {
            $p5 = '2';
        }
        ;
        if ($p5 == 'Ns' || $p5 == 'ns' || $p5 == 'NS') {
            $p5 = '2';
        }
        ;
        if ($p5 == 'S' || $p5 == 's') {
            $p5 = '4';
        }
        ;
        if ($p5 == 'B' || $p5 == 'b') {
            $p5 = '6';
        }
        ;
        if ($p5 == 'Mb' || $p5 == 'mb' || $p5 == 'MB') {
            $p5 = '8';
        }
        ;
        if ($p5 == 'E' || $p5 == 'e') {
            $p5 = '10';
        }
        ;

        if (empty($p6) || $p6 == '-') {
            $p6 = '-1';
        }
        ;
        if ($p6 == 'a' || $p6 == 'A') {
            $p6 = '0';
        }
        ;
        if ($p6 == 'Ap' || $p6 == 'ap' || $p6 == 'AP') {
            $p6 = '8';
        }
        ;
        if ($p6 == 'Na' || $p6 == 'na' || $p6 == 'NA') {
            $p6 = '2';
        }
        ;
        if ($p6 == 'Ns' || $p6 == 'ns' || $p6 == 'NS') {
            $p6 = '2';
        }
        ;
        if ($p6 == 'S' || $p6 == 's') {
            $p6 = '4';
        }
        ;
        if ($p6 == 'B' || $p6 == 'b') {
            $p6 = '6';
        }
        ;
        if ($p6 == 'Mb' || $p6 == 'mb' || $p6 == 'MB') {
            $p6 = '8';
        }
        ;
        if ($p6 == 'E' || $p6 == 'e') {
            $p6 = '10';
        }
        ;

        if (empty($p7) || $p7 == '-') {
            $p7 = '-1';
        }
        ;
        if ($p7 == 'a' || $p7 == 'A') {
            $p7 = '0';
        }
        ;
        if ($p7 == 'Ap' || $p7 == 'ap' || $p7 == 'AP') {
            $p7 = '8';
        }
        ;
        if ($p7 == 'Na' || $p7 == 'na' || $p7 == 'NA') {
            $p7 = '2';
        }
        ;
        if ($p7 == 'Ns' || $p7 == 'ns' || $p7 == 'NS') {
            $p7 = '2';
        }
        ;
        if ($p7 == 'S' || $p7 == 's') {
            $p7 = '4';
        }
        ;
        if ($p7 == 'B' || $p7 == 'b') {
            $p7 = '6';
        }
        ;
        if ($p7 == 'Mb' || $p7 == 'mb' || $p7 == 'MB') {
            $p7 = '8';
        }
        ;
        if ($p7 == 'E' || $p7 == 'e') {
            $p7 = '10';
        }
        ;

        if (empty($p8) || $p8 == '-') {
            $p8 = '-1';
        }
        ;
        if ($p8 == 'a' || $p8 == 'A') {
            $p8 = '0';
        }
        ;
        if ($p8 == 'Ap' || $p8 == 'ap' || $p8 == 'AP') {
            $p8 = '8';
        }
        ;
        if ($p8 == 'Na' || $p8 == 'na' || $p8 == 'NA') {
            $p8 = '2';
        }
        ;
        if ($p8 == 'Ns' || $p8 == 'ns' || $p8 == 'NS') {
            $p8 = '2';
        }
        ;
        if ($p8 == 'S' || $p8 == 's') {
            $p8 = '4';
        }
        ;
        if ($p8 == 'B' || $p8 == 'b') {
            $p8 = '6';
        }
        ;
        if ($p8 == 'Mb' || $p8 == 'mb' || $p8 == 'MB') {
            $p8 = '8';
        }
        ;
        if ($p8 == 'E' || $p8 == 'e') {
            $p8 = '10';
        }
        ;
        if ($p8 == 'Fe' || $p8 == 'fe' || $p8 == 'FE') {
            $p8 = '-1';
        }
        ;

        if (empty($r1) || $r1 == '-') {
            $r1 = '-1';
        }
        ;
        if ($r1 == 'a' || $r1 == 'A') {
            $r1 = '0';
        }
        ;
        if ($r1 == 'Ap' || $r1 == 'ap' || $r1 == 'AP') {
            $r1 = '8';
        }
        ;
        if ($r1 == 'Na' || $r1 == 'na' || $r1 == 'NA') {
            $r1 = '2';
        }
        ;
        if ($r1 == 'Ns' || $r1 == 'ns' || $r1 == 'NS') {
            $r1 = '2';
        }
        ;
        if ($r1 == 'S' || $r1 == 's') {
            $r1 = '4';
        }
        ;
        if ($r1 == 'B' || $r1 == 'b') {
            $r1 = '6';
        }
        ;
        if ($r1 == 'Mb' || $r1 == 'mb' || $r1 == 'MB') {
            $r1 = '8';
        }
        ;
        if ($r1 == 'E' || $r1 == 'e') {
            $r1 = '10';
        }
        ;

        if (empty($r2) || $r2 == '-') {
            $r2 = '-1';
        }
        ;
        if ($r2 == 'a' || $r2 == 'A') {
            $r2 = '0';
        }
        ;
        if ($r2 == 'Ap' || $r2 == 'ap' || $r2 == 'AP') {
            $r2 = '8';
        }
        ;
        if ($r2 == 'Na' || $r2 == 'na' || $r2 == 'NA') {
            $r2 = '2';
        }
        ;
        if ($r2 == 'Ns' || $r2 == 'ns' || $r2 == 'NS') {
            $r2 = '2';
        }
        ;
        if ($r2 == 'S' || $r2 == 's') {
            $r2 = '4';
        }
        ;
        if ($r2 == 'B' || $r2 == 'b') {
            $r2 = '6';
        }
        ;
        if ($r2 == 'Mb' || $r2 == 'mb' || $r2 == 'MB') {
            $r2 = '8';
        }
        ;
        if ($r2 == 'E' || $r2 == 'e') {
            $r2 = '10';
        }
        ;

        if (empty($r3) || $r3 == '-') {
            $r3 = '-1';
        }
        ;
        if ($r3 == 'a' || $r3 == 'A') {
            $r3 = '0';
        }
        ;
        if ($r3 == 'Ap' || $r3 == 'ap' || $r3 == 'AP') {
            $r3 = '8';
        }
        ;
        if ($r3 == 'Na' || $r3 == 'na' || $r3 == 'NA') {
            $r3 = '2';
        }
        ;
        if ($r3 == 'Ns' || $r3 == 'ns' || $r3 == 'NS') {
            $r3 = '2';
        }
        ;
        if ($r3 == 'S' || $r3 == 's') {
            $r3 = '4';
        }
        ;
        if ($r3 == 'B' || $r3 == 'b') {
            $r3 = '6';
        }
        ;
        if ($r3 == 'Mb' || $r3 == 'mb' || $r3 == 'MB') {
            $r3 = '8';
        }
        ;
        if ($r3 == 'E' || $r3 == 'e') {
            $r3 = '10';
        }
        ;

        if (empty($r4) || $r4 == '-') {
            $r4 = '-1';
        }
        ;
        if ($r4 == 'a' || $r4 == 'A') {
            $r4 = '0';
        }
        ;
        if ($r4 == 'Ap' || $r4 == 'ap' || $r4 == 'AP') {
            $r4 = '8';
        }
        ;
        if ($r4 == 'Na' || $r4 == 'na' || $r4 == 'NA') {
            $r4 = '2';
        }
        ;
        if ($r4 == 'Ns' || $r4 == 'ns' || $r4 == 'NS') {
            $r4 = '2';
        }
        ;
        if ($r4 == 'S' || $r4 == 's') {
            $r4 = '4';
        }
        ;
        if ($r4 == 'B' || $r4 == 'b') {
            $r4 = '6';
        }
        ;
        if ($r4 == 'Mb' || $r4 == 'mb' || $r4 == 'MB') {
            $r4 = '8';
        }
        ;
        if ($r4 == 'E' || $r4 == 'e') {
            $r4 = '10';
        }
        ;

        if (empty($r5) || $r5 == '-') {
            $r5 = '-1';
        }
        ;
        if ($r5 == 'a' || $r5 == 'A') {
            $r5 = '0';
        }
        ;
        if ($r5 == 'Ap' || $r5 == 'ap' || $r5 == 'AP') {
            $r5 = '8';
        }
        ;
        if ($r5 == 'Na' || $r5 == 'na' || $r5 == 'NA') {
            $r5 = '2';
        }
        ;
        if ($r5 == 'Ns' || $r5 == 'ns' || $r5 == 'NS') {
            $r5 = '2';
        }
        ;
        if ($r5 == 'S' || $r5 == 's') {
            $r5 = '4';
        }
        ;
        if ($r5 == 'B' || $r5 == 'b') {
            $r5 = '6';
        }
        ;
        if ($r5 == 'Mb' || $r5 == 'mb' || $r5 == 'MB') {
            $r5 = '8';
        }
        ;
        if ($r5 == 'E' || $r5 == 'e') {
            $r5 = '10';
        }
        ;

        if (empty($r6) || $r6 == '-') {
            $r6 = '-1';
        }
        ;
        if ($r6 == 'a' || $r6 == 'A') {
            $r6 = '0';
        }
        ;
        if ($r6 == 'Ap' || $r6 == 'ap' || $r6 == 'AP') {
            $r6 = '8';
        }
        ;
        if ($r6 == 'Na' || $r6 == 'na' || $r6 == 'NA') {
            $r6 = '2';
        }
        ;
        if ($r6 == 'Ns' || $r6 == 'ns' || $r6 == 'NS') {
            $r6 = '2';
        }
        ;
        if ($r6 == 'S' || $r6 == 's') {
            $r6 = '4';
        }
        ;
        if ($r6 == 'B' || $r6 == 'b') {
            $r6 = '6';
        }
        ;
        if ($r6 == 'Mb' || $r6 == 'mb' || $r6 == 'MB') {
            $r6 = '8';
        }
        ;
        if ($r6 == 'E' || $r6 == 'e') {
            $r6 = '10';
        }
        ;

        if (empty($r7) || $r7 == '-') {
            $r7 = '-1';
        }
        ;
        if ($r7 == 'a' || $r7 == 'A') {
            $r7 = '0';
        }
        ;
        if ($r7 == 'Ap' || $r7 == 'ap' || $r7 == 'AP') {
            $r7 = '8';
        }
        ;
        if ($r7 == 'Na' || $r7 == 'na' || $r7 == 'NA') {
            $r7 = '2';
        }
        ;
        if ($r7 == 'Ns' || $r7 == 'ns' || $r7 == 'NS') {
            $r7 = '2';
        }
        ;
        if ($r7 == 'S' || $r7 == 's') {
            $r7 = '4';
        }
        ;
        if ($r7 == 'B' || $r7 == 'b') {
            $r7 = '6';
        }
        ;
        if ($r7 == 'Mb' || $r7 == 'mb' || $r7 == 'MB') {
            $r7 = '8';
        }
        ;
        if ($r7 == 'E' || $r7 == 'e') {
            $r7 = '10';
        }
        ;

        if (empty($r8) || $r8 == '-') {
            $r8 = '-1';
        }
        ;
        if ($r8 == 'a' || $r8 == 'A') {
            $r8 = '0';
        }
        ;
        if ($r8 == 'Ap' || $r8 == 'ap' || $r8 == 'AP') {
            $r8 = '8';
        }
        ;
        if ($r8 == 'Na' || $r8 == 'na' || $r8 == 'NA') {
            $r8 = '2';
        }
        ;
        if ($r8 == 'Ns' || $r8 == 'ns' || $r8 == 'NS') {
            $r8 = '2';
        }
        ;
        if ($r8 == 'S' || $r8 == 's') {
            $r8 = '4';
        }
        ;
        if ($r8 == 'B' || $r8 == 'b') {
            $r8 = '6';
        }
        ;
        if ($r8 == 'Mb' || $r8 == 'mb' || $r8 == 'MB') {
            $r8 = '8';
        }
        ;
        if ($r8 == 'E' || $r8 == 'e') {
            $r8 = '10';
        }
        ;
    }

    if ($cod_col == "banfield") {			//////////BANFIELD//////////

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Calificaciones Regularidad
        if ($p1 < $cReg && $p1 >= 0) {
            if ($r1 < $cReg) {
                $regular = "f";
            }
        }

        if ($p2 < $cReg && $p2 >= 0) {
            if ($r2 < $cReg) {
                $regular = "f";
            }
        }

        if ($p3 < $cReg && $p3 >= 0) {
            if ($r3 < $cReg) {
                $regular = "f";
            }
        }

        if ($p4 < $cReg && $p4 >= 0) {
            if ($r4 < $cReg) {
                $regular = "f";
            }
        }

        if ($p5 < $cReg && $p5 >= 0) {
            if ($r5 < $cReg) {
                $regular = "f";
            }
        }

        if ($p6 < $cReg && $p6 >= 0) {
            if ($r6 < $cReg) {
                $regular = "f";
            }
        }

        if ($p7 < $cReg && $p7 >= 0) {
            if ($r7 < $cReg) {
                $regular = "f";
            }
        }

        if ($p8 < $cReg && $p8 >= 0) {
            if ($r8 < $cReg) {
                $regular = "f";
            }
        }

        //Analisis Asistencia
        if ($trabaja == 0) {
            if ($asistencia < $aReg) {
                $asistOblig = "f";
            }
            if ($asistencia < $aProm) {
                $asistProm = "f";
            }
        }
        if ($trabaja == 1) {
            if ($asistencia < $aRegRed) {
                $asistOblig = "f";
            }
            if ($asistencia < $aPromRed) {
                $asistProm = "f";
            }
        }

        //Analisis Calificaciones Promocion
        if ($p1 >= $cProm || $p1 == -1) {
        } else {
            if ($r1 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p2 >= $cProm || $p2 == -1) {
        } else {
            if ($r2 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p3 >= $cProm || $p3 == -1) {
        } else {
            if ($r3 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p4 >= $cProm || $p4 == -1) {
        } else {
            if ($r4 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p5 >= $cProm || $p5 == -1) {
        } else {
            if ($r5 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p6 >= $cProm || $p6 == -1) {
        } else {
            if ($r6 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p7 >= $cProm || $p7 == -1) {
        } else {
            if ($r7 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p8 >= $cTrab || $p8 == -1) {
        } else {
            if ($r8 >= $cTrab) {
            } else {
                $aprobado = "f";
            }
        }

        //Resultado Analisis
        if ($regular == "f" || $asistOblig == "f")   //Recursa
        {
            $salida = 10;
            $wanalisis = "Recursa";
        }

        if ($regular == "t" && $asistOblig == "t")    //Aprobó Cursada
        {
            $salida = 1;
            $wanalisis = "Aprobó Cursada";
        }

        if ($regular == "f" && $sinAsist == "t")      //Recursa S/Asist
        {
            $salida = 12;
            $wanalisis = "Recursa - S/Asist";
        }

        if ($regular == "t" && $sinAsist == "t")      //Aprobó Cursada S/Asist
        {
            $salida = 4;
            $wanalisis = "Aprobó Cursada - S/Asist";
        }

        if ($aprobado == "t" && $asistProm == "t")      //Promocionado
        {
            $salida = 11;
            $wanalisis = "Promocionado";
        }

        if ($aprobado == "t" && $sinAsist == "t")      //Promocionado S/Asist
        {
            $salida = 13;
            $wanalisis = "Promocionado - S/Asist";
        }

        if ($sinCalif == "t")       //S/Calif
        {
            $salida = 6;
            $wanalisis = "Sin Calificaciones";
        }


    } elseif ($cod_col == "catolico") {		//////////CATOLICO//////////

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Asistencia
        if ($trabaja == 0) {
            if ($asistencia < $aReg) {
                $asistOblig = "f";
            }
            if ($asistencia < $aProm) {
                $asistProm = "f";
            }
        }
        if ($trabaja == 1) {
            if ($asistencia < $aRegRed) {
                $asistOblig = "f";
            }
            if ($asistencia < $aPromRed) {
                $asistProm = "f";
            }
        }

        //Analisis Calificaciones Tipo Materia 1 2 4 5
        if ($tipoMateria == 1 || $tipoMateria == 2 || $tipoMateria == 4 || $tipoMateria == 5) {
            if ($p1 < $cReg && $p1 >= 0) {
                $aprobado = "f";
            }

            if ($p2 < $cReg && $p2 >= 0) {
                $aprobado = "f";
            }

            if ($p3 < $cReg && $p3 >= 0) {
                $aprobado = "f";
            }

            if ($p4 < $cReg && $p4 >= 0) {
                $aprobado = "f";
            }

            if ($p5 < $cReg && $p5 >= 0) {
                $aprobado = "f";
            }

            if ($p6 < $cReg && $p6 >= 0) {
                $aprobado = "f";
            }

            if ($p7 < $cReg && $p7 >= 0) {
                $aprobado = "f";
            }

            if ($p8 >= $cTrab || $p8 == -1) {
            } else {
                $aprobado = "f";
                if ($p8 >= $cReg) {
                    $coloquio = "t";
                } else {
                    $recuperatorio = "t";
                    $coloquio = "f";
                    if ($r8 >= $cReg) {
                        $coloquio = "t";
                    } else {
                        $coloquio = "f";
                    }
                }
            }
        } else {	//Analisis Calificaciones Tipo Materia 3
            if ($p1 < $cReg && $p1 >= 0) {
                $recuperatorio = "t";
                if ($r1 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p2 < $cReg && $p2 >= 0) {
                $recuperatorio = "t";
                if ($r2 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p3 < $cReg && $p3 >= 0) {
                $recuperatorio = "t";
                if ($r3 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p4 < $cReg && $p4 >= 0) {
                $recuperatorio = "t";
                if ($r4 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p5 < $cReg && $p5 >= 0) {
                $recuperatorio = "t";
                if ($r5 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p6 < $cReg && $p6 >= 0) {
                $recuperatorio = "t";
                if ($r6 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p7 < $cReg && $p7 >= 0) {
                $recuperatorio = "t";
                if ($r7 < $cReg) {
                    $regular = "f";
                }
            }

            if ($p8 < $cReg && $p8 >= 0) {
                $recuperatorio = "t";
                if ($r8 < $cReg) {
                    $regular = "f";
                }
            }

            //Analisis Calificaciones Promocion Tipo Materia 3
            if ($p1 >= $cProm || $p1 == -1) {
            } else {
                if ($r1 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p2 >= $cProm || $p2 == -1) {
            } else {
                if ($r2 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p3 >= $cProm || $p3 == -1) {
            } else {
                if ($r3 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p4 >= $cProm || $p4 == -1) {
            } else {
                if ($r4 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p5 >= $cProm || $p5 == -1) {
            } else {
                if ($r5 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p6 >= $cProm || $p6 == -1) {
            } else {
                if ($r6 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p7 >= $cProm || $p7 == -1) {
            } else {
                if ($r7 >= $cProm) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p8 >= $cTrab || $p8 == -1) {
            } else {
                if ($r8 >= $cTrab) {
                } else {
                    $aprobado = "f";
                }
            }
        }

        //Salida Analisis
        if ($tipoMateria == 1 || $tipoMateria == 2 || $tipoMateria == 4 || $tipoMateria == 5) {
            if ($coloquio == "f" || $asistOblig == "f")   //Recursa
            {
                $salida = 10;
                $wanalisis = "Recursa";
            }

            if ($coloquio == "t" && $asistOblig == "t")    //Coloquio
            {
                $salida = 2;
                $wanalisis = "Coloquio";
            }

            if ($coloquio == "f" && $sinAsist == "t")      //Recursa S/Asist
            {
                $salida = 12;
                $wanalisis = "Recursa - S/Asist";
            }

            if ($coloquio == "t" && $sinAsist == "t")      //Coloquio S/Asist
            {
                $salida = 5;
                $wanalisis = "Coloquio - S/Asist";
            }

            if ($aprobado == "t" && $asistProm == "t" && $recuperatorio == "f")      //Promocional
            {
                $salida = 14;
                $wanalisis = "Promocional";
            }

            if ($aprobado == "t" && $sinAsist == "t" && $recuperatorio == "f")      //Promocional S/Asist
            {
                $salida = 15;
                $wanalisis = "Promocional - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        } else {
            if ($regular == "f" || $asistOblig == "f")   //Libre
            {
                $salida = 0;
                $wanalisis = "Libre";
            }

            if ($regular == "t" && $asistOblig == "t")    //$regular
            {
                $salida = 1;
                $wanalisis = "Regular";
            }

            if ($regular == "f" && $sinAsist == "t")      //Libre S/Asist
            {
                $salida = 3;
                $wanalisis = "Libre - S/Asist";
            }

            if ($regular == "t" && $sinAsist == "t")      //$regular S/Asist
            {
                $salida = 4;
                $wanalisis = "Regular - S/Asist";
            }

            if ($aprobado == "t" && $asistProm == "t" && $recuperatorio == "f")      //Promocional
            {
                $salida = 14;
                $wanalisis = "Promocional";
            }

            if ($aprobado == "t" && $sinAsist == "t" && $recuperatorio == "f")      //Promocional S/Asist
            {
                $salida = 15;
                $wanalisis = "Promocional - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        }



        //////////////////////////////////////////////////////////////////////////////////////////			






        //////////////////////////////////////////////////////////////////////////////////////////
    } elseif ($cod_col == "nssc") {			//////////SAGRADO//////////

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Calificaciones Regularidad
        if ($p1 < $cReg && $p1 >= 0) {
            if ($r1 < $cReg) {
                $regular = "f";
            }
        }

        if ($p2 < $cReg && $p2 >= 0) {
            if ($r2 < $cReg) {
                $regular = "f";
            }
        }

        if ($p3 < $cReg && $p3 >= 0) {
            if ($r3 < $cReg) {
                $regular = "f";
            }
        }

        if ($p4 < $cReg && $p4 >= 0) {
            if ($r4 < $cReg) {
                $regular = "f";
            }
        }

        if ($p5 < $cReg && $p5 >= 0) {
            if ($r5 < $cReg) {
                $regular = "f";
            }
        }

        if ($p6 < $cReg && $p6 >= 0) {
            if ($r6 < $cReg) {
                $regular = "f";
            }
        }

        if ($p7 < $cReg && $p7 >= 0) {
            if ($r7 < $cReg) {
                $regular = "f";
            }
        }

        if ($p8 < $cReg && $p8 >= 0) {
            if ($r8 < $cReg) {
                $regular = "f";
            }
        }

        //Analisis Asistencia
        if ($trabaja == 0) {
            if ($asistencia < $aReg) {
                $asistOblig = "f";
            }
            if ($asistencia < $aProm) {
                $asistProm = "f";
            }
        }
        if ($trabaja == 1) {
            if ($asistencia < $aRegRed) {
                $asistOblig = "f";
            }
            if ($asistencia < $aPromRed) {
                $asistProm = "f";
            }
        }

        //Analisis Calificaciones Promocion
        if ($p1 >= $cProm || $p1 == -1) {
        } else {
            if ($r1 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p2 >= $cProm || $p2 == -1) {
        } else {
            if ($r2 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p3 >= $cProm || $p3 == -1) {
        } else {
            if ($r3 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p4 >= $cProm || $p4 == -1) {
        } else {
            if ($r4 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p5 >= $cProm || $p5 == -1) {
        } else {
            if ($r5 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p6 >= $cProm || $p6 == -1) {
        } else {
            if ($r6 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p7 >= $cProm || $p7 == -1) {
        } else {
            if ($r7 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p8 >= $cTrab) {
        } else {
            if ($r8 >= $cTrab) {
            } else {
                $aprobado = "f";
            }
        }

        //Salida Analisis Tipo Materia 1
        if ($tipoMateria == 1) {
            if ($regular == "f" || $asistOblig == "f")   //Libre
            {
                $salida = 0;
                $wanalisis = "Libre";
            }

            if ($regular == "t" && $asistOblig == "t")    //Regular
            {
                $salida = 1;
                $wanalisis = "Regular";
            }

            if ($regular == "t" && $aprobado == "t" && $asistProm == "t")     //Aprueba
            {
                $salida = 11;
                $wanalisis = "Aprobado";
            }

            if ($regular == "f" && $sinAsist == "t")      //Libre S/Asist
            {
                $salida = 3;
                $wanalisis = "Libre - S/Asist";
            }

            if ($regular == "t" && $sinAsist == "t")      //Regular S/Asist
            {
                $salida = 4;
                $wanalisis = "Regular - S/Asist";
            }

            if ($regular == "t" && $aprobado == "t" && $sinAsist == "t")       //Aprueba S/Asist
            {
                $salida = 13;
                $wanalisis = "Aprobado - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        }

        //Salida Analisis Tipo Materia 2
        if ($tipoMateria == 2) {
            if ($regular == "f" || $asistOblig == "f")       //Recursa
            {
                $salida = 10;
                $wanalisis = "Recursa";
            }

            if ($regular == "t" && $asistOblig == "t")        //Coloquio
            {
                $salida = 2;
                $wanalisis = "Coloquio";
            }

            if ($regular == "t" && $aprobado == "t" && $asistProm == "t")         //Aprueba
            {
                $salida = 11;
                $wanalisis = "Aprueba";
            }

            if ($regular == "f" && $sinAsist == "t")          //Recursa S/Asist
            {
                $salida = 12;
                $wanalisis = "Recursa - S/Asist";
            }

            if ($regular == "t" && $sinAsist == "t")          //Coloquio S/Asist
            {
                $salida = 5;
                $wanalisis = "Coloquio - S/Asist";
            }

            if ($regular == "t" && $aprobado == "t" && $sinAsist == "t")           //Aprueba S/Asist
            {
                $salida = 13;
                $wanalisis = "Aprueba - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        }


    } elseif ($cod_col == "rayuela") {		//////////RAYUELA//////////

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Calificaciones Regularidad
        if ($p1 < $cReg && $p1 >= 0) {
            if ($r1 < $cReg) {
                $regular = "f";
            }
        }

        if ($p2 < $cReg && $p2 >= 0) {
            if ($r2 < $cReg) {
                $regular = "f";
            }
        }

        if ($p3 < $cReg && $p3 >= 0) {
            if ($r3 < $cReg) {
                $regular = "f";
            }
        }

        if ($p4 < $cReg && $p4 >= 0) {
            if ($r4 < $cReg) {
                $regular = "f";
            }
        }

        if ($p5 < $cReg && $p5 >= 0) {
            if ($r5 < $cReg) {
                $regular = "f";
            }
        }

        if ($p6 < $cReg && $p6 >= 0) {
            if ($r6 < $cReg) {
                $regular = "f";
            }
        }

        if ($p7 < $cReg && $p7 >= 0) {
            if ($r7 < $cReg) {
                $regular = "f";
            }
        }

        if ($p8 < $cReg && $p8 >= 0) {
            if ($r8 < $cReg) {
                $regular = "f";
            }
        }

        //Analisis Asistencia
        if ($trabaja == 0) {
            if ($asistencia < $aReg) {
                $asistOblig = "f";
            }
            if ($asistencia < $aProm) {
                $asistProm = "f";
            }
        }
        if ($trabaja == 1) {
            if ($asistencia < $aRegRed) {
                $asistOblig = "f";
            }
            if ($asistencia < $aPromRed) {
                $asistProm = "f";
            }
        }

        //Analisis Calificaciones Promocion
        if ($p1 >= $cProm || $p1 == -1) {
        } else {
            if ($r1 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p2 >= $cProm || $p2 == -1) {
        } else {
            if ($r2 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p3 >= $cProm || $p3 == -1) {
        } else {
            if ($r3 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p4 >= $cProm || $p4 == -1) {
        } else {
            if ($r4 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p5 >= $cProm || $p5 == -1) {
        } else {
            if ($r5 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p6 >= $cProm || $p6 == -1) {
        } else {
            if ($r6 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p7 >= $cProm || $p7 == -1) {
        } else {
            if ($r7 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p8 >= $cTrab || $p8 == -1) {
        } else {
            if ($r8 >= $cTrab) {
            } else {
                $aprobado = "f";
            }
        }

        //Salida Analisis
        if ($regular == "f" || $asistOblig == "f")   //No Regular
        {
            $salida = 0;
            $wanalisis = "No Regular";
        }

        if ($regular == "t" && $asistOblig == "t")    //$regular
        {
            $salida = 1;
            $wanalisis = "Regular";
        }

        if ($regular == "f" && $sinAsist == "t")      //No Regular S/Asist
        {
            $salida = 3;
            $wanalisis = "No Regular - S/Asist";
        }

        if ($regular == "t" && $sinAsist == "t")      //$regular S/Asist
        {
            $salida = 4;
            $wanalisis = "Regular - S/Asist";
        }

        if ($aprobado == "t" && $asistProm == "t")      //Aprueba
        {
            $salida = 11;
            $wanalisis = "Aprueba";
        }

        if ($aprobado == "t" && $sinAsist == "t")      //Aprueba S/Asist
        {
            $salida = 13;
            $wanalisis = "Aprueba - S/Asist";
        }

        if ($sinCalif == "t")       //S/Calif
        {
            $salida = 6;
            $wanalisis = "Sin Calificaciones";
        }


    } elseif ($cod_col == "merc") {			//////////MERCEDARIAS//////////

        //SOLO 2 RECUPERATORIOS

        $primerRec = "f";
        $segundoRec = "f";
        $primerRecUsado = "f";
        $segundoRecUsado = "f";

        if ($r1 >= 0) {
            $primerRec = "t";
        }
        if ($r2 >= 0) {
            $segundoRec = "t";
        }

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //ASIGNATURA

        if ($tipoMateria == 3) {

            //ASISTENCIA

            if ($trabaja == 0) {
                if ($asistencia < $aReg) {
                    $asistOblig = "f";
                }
                if ($asistencia < $aProm) {
                    $asistProm = "f";
                }
            }
            if ($trabaja == 1) {
                if ($asistencia < $aRegRed) {
                    $asistOblig = "f";
                }
                if ($asistencia < $aPromRed) {
                    $asistProm = "f";
                }
            }

            //REGULARIDAD

            if ($p1 < $cReg && $p1 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p2 < $cReg && $p2 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p3 < $cReg && $p3 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p4 < $cReg && $p4 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p5 < $cReg && $p5 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p6 < $cReg && $p6 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p7 < $cReg && $p7 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            if ($p8 < $cReg && $p8 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            //PROMOCION

            if ($p1 < $cProm && $p1 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p2 < $cProm && $p2 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p3 < $cProm && $p3 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p4 < $cProm && $p4 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p5 < $cProm && $p5 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p6 < $cProm && $p6 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p7 < $cProm && $p7 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cProm) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cProm) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cProm) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }

            if ($p8 < $cTrab && $p8 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cTrab) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cTrab) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cTrab) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }
        }

        //TALLER Y SEMINARIO		

        if ($tipoMateria == 1 || $tipoMateria == 2) {

            //ASISTENCIA

            if ($trabaja == 0) {
                if ($asistencia < $aReg) {
                    $asistOblig = "f";
                }
                if ($asistencia < $aProm) {
                    $asistProm = "f";
                }
            }
            if ($trabaja == 1) {
                if ($asistencia < $aRegRed) {
                    $asistOblig = "f";
                }
                if ($asistencia < $aPromRed) {
                    $asistProm = "f";
                }
            }

            //REGULARIDAD

            $instanciasAp = 0;
            if ($p1 >= $cReg && $p1 >= 0) {
                $instanciasAp++;
            }

            if ($p2 >= $cReg && $p2 >= 0) {
                $instanciasAp++;
            }

            if ($p3 >= $cReg && $p3 >= 0) {
                $instanciasAp++;
            }

            if ($p4 >= $cReg && $p4 >= 0) {
                $instanciasAp++;
            }

            if ($p5 >= $cReg && $p5 >= 0) {
                $instanciasAp++;
            }

            if ($p6 >= $cReg && $p6 >= 0) {
                $instanciasAp++;
            }

            if ($p7 >= $cReg && $p7 >= 0) {
                $instanciasAp++;
            }

            if ($instanciasAp <= 0) {
                $regular = "f";
            }

            if ($p8 < $cReg && $p8 >= 0) {
                $recuperatorio = "t";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cReg) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cReg) {
                                $regular = "f";
                            }
                        } else {
                            $regular = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cReg) {
                            $regular = "f";
                        }
                    } else {
                        $regular = "f";
                    }
                }
            }

            //PROMOCION

            if ($p1 < $cProm && $p1 >= 0) {
                $aprobado = "f";
            }

            if ($p2 < $cProm && $p2 >= 0) {
                $aprobado = "f";
            }

            if ($p3 < $cProm && $p3 >= 0) {
                $aprobado = "f";
            }

            if ($p4 < $cProm && $p4 >= 0) {
                $aprobado = "f";
            }

            if ($p5 < $cProm && $p5 >= 0) {
                $aprobado = "f";
            }

            if ($p6 < $cProm && $p6 >= 0) {
                $aprobado = "f";
            }

            if ($p7 < $cProm && $p7 >= 0) {
                $aprobado = "f";
            }

            if ($p8 < $cTrab && $p8 >= 0) {
                $recuperatorio = "t";
                $aprobado = "f";
                if ($primerRec == "t" && $primerRecUsado == "f") {
                    $primerRecUsado = "t";
                    if ($r1 < $cTrab) {
                        if ($segundoRec == "t" && $segundoRecUsado == "f") {
                            $segundoRecUsado = "t";
                            if ($r2 < $cTrab) {
                                $aprobado = "f";
                            }
                        } else {
                            $aprobado = "f";
                        }
                    }
                } else {
                    if ($segundoRec == "t" && $segundoRecUsado == "f") {
                        $segundoRecUsado = "t";
                        if ($r2 < $cTrab) {
                            $aprobado = "f";
                        }
                    } else {
                        $aprobado = "f";
                    }
                }
            }
        }

        //Salida Analisis Tipo Materia 3
        if ($tipoMateria == 3)   //Asignatura
        {

            if ($regular == "f" || $asistOblig == "f")   //Libre
            {
                $salida = 0;
                $wanalisis = "Libre";
            }

            if ($regular == "t" && $asistOblig == "t")    //Regular
            {
                $salida = 1;
                $wanalisis = "Regular";
            }

            if ($regular == "f" && $sinAsist == "t")      //Libre S/Asist
            {
                $salida = 3;
                $wanalisis = "Libre - S/Asist";
            }

            if ($regular == "t" && $sinAsist == "t")      //Regular S/Asist
            {
                $salida = 4;
                $wanalisis = "Regular - S/Asist";
            }

            if ($aprobado == "t" && $asistProm == "t")      //Promoción
            {
                $salida = 14;
                $wanalisis = "Promoción";
            }

            if ($aprobado == "t" && $sinAsist == "t")      //Promoción S/Asist
            {
                $salida = 15;
                $wanalisis = "Promoción - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }

        }

        //Salida Analisis Tipo Materia 1 2
        if ($tipoMateria == 1 || $tipoMateria == 2)   //Taller Seminario
        {

            if ($regular == "f" || $asistOblig == "f")   //Recursa
            {
                $salida = 10;
                $wanalisis = "Recursa";
            }

            if ($regular == "t" && $asistOblig == "t")    //Coloquio
            {
                $salida = 2;
                $wanalisis = "Coloquio";
            }

            if ($regular == "f" && $sinAsist == "t")      //Recursa S/Asist
            {
                $salida = 12;
                $wanalisis = "Recursa - S/Asist";
            }

            if ($regular == "t" && $sinAsist == "t")      //Coloquio S/Asist
            {
                $salida = 5;
                $wanalisis = "Coloquio - S/Asist";
            }

            if ($aprobado == "t" && $asistProm == "t")      //Promoción
            {
                $salida = 14;
                $wanalisis = "Promoción";
            }

            if ($aprobado == "t" && $sinAsist == "t")      //Promoción S/Asist
            {
                $salida = 15;
                $wanalisis = "Promoción - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        }


    } elseif ($cod_col == "iess") {			//////////IESS//////////

        //analisis por codigo

        $cReg = 4;
        $cProm = 7;
        $wasistenciaRegular = 0;
        $wasistenciaRegularRed = 0;

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Calificaciones Tipo Materia 1
        if ($tipoMateria == 1)   //Asignatura
        {

            if ($p1 < 4 && $p1 >= 0) {
                if ($r1 < 4) {
                    $regular = "f";
                }
            }

            if ($p2 < 4 && $p2 >= 0) {
                if ($r2 < 4) {
                    $regular = "f";
                }
            }

            if ($p3 < 4 && $p3 >= 0) {
                if ($r3 < 4) {
                    $regular = "f";
                }
            }

            if ($p4 < 4 && $p4 >= 0) {
                if ($r4 < 4) {
                    $regular = "f";
                }
            }

            if ($p5 < 4 && $p5 >= 0) {
                if ($r5 < 4) {
                    $regular = "f";
                }
            }

            if ($p6 < 4 && $p6 >= 0) {
                if ($r6 < 4) {
                    $regular = "f";
                }
            }

            if ($p7 < 4 && $p7 >= 0) {
                if ($r7 < 4) {
                    $regular = "f";
                }
            }

            if ($p8 < 4 && $p8 >= 0) {
                if ($r8 < 4) {
                    $regular = "f";
                }
            }

            //Analisis Asistencia
            if ($asistencia < 0 && $trabaja == 0) {
                $asistOblig = "f";
            }
            if ($asistencia < 0 && $trabaja == 1) {
                $asistOblig = "f";
            }

            //Analisis Calificaciones Promocion
            if ($p1 >= 7 || $p1 == -1) {
            } else {
                if ($r1 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p2 >= 7 || $p2 == -1) {
            } else {
                if ($r2 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p3 >= 7 || $p3 == -1) {
            } else {
                if ($r3 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p4 >= 7 || $p4 == -1) {
            } else {
                if ($r4 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p5 >= 7 || $p5 == -1) {
            } else {
                if ($r5 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p6 >= 7 || $p6 == -1) {
            } else {
                if ($r6 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p7 >= 7 || $p7 == -1) {
            } else {
                if ($r7 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }

            if ($p8 >= 7 || $p8 == -1) {
            } else {
                if ($r8 >= 7) {
                } else {
                    $coloquio = "f";
                }
            }
        }

        //Analisis Calificaciones Tipo Materia 2 3
        if ($tipoMateria == 2 || $tipoMateria == 3)   //Taller, Seminario
        {
            if ($p1 < 4 && $p1 >= 0) {
                if ($r1 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p2 < 4 && $p2 >= 0) {
                if ($r2 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p3 < 4 && $p3 >= 0) {
                if ($r3 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p4 < 4 && $p4 >= 0) {
                if ($r4 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p5 < 4 && $p5 >= 0) {
                if ($r5 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p6 < 4 && $p6 >= 0) {
                if ($r6 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p7 < 4 && $p7 >= 0) {
                if ($r7 < 4) {
                    $coloquio = "f";
                }
            }

            if ($p8 < 4 && $p8 >= 0) {
                if ($r8 < 4) {
                    $coloquio = "f";
                }
            }

            //Analisis Asistencia
            if ($asistencia < 0 && $trabaja == 0) {
                $asistOblig = "f";
            }
            if ($asistencia < 0 && $trabaja == 1) {
                $asistOblig = "f";
            }

            //Analisis Calificaciones Promocion
            if ($p1 >= 7 || $p1 == -1) {
            } else {
                if ($r1 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p2 >= 7 || $p2 == -1) {
            } else {
                if ($r2 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p3 >= 7 || $p3 == -1) {
            } else {
                if ($r3 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p4 >= 7 || $p4 == -1) {
            } else {
                if ($r4 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p5 >= 7 || $p5 == -1) {
            } else {
                if ($r5 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p6 >= 7 || $p6 == -1) {
            } else {
                if ($r6 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p7 >= 7 || $p7 == -1) {
            } else {
                if ($r7 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }

            if ($p8 >= 7 || $p8 == -1) {
            } else {
                if ($r8 >= 7) {
                } else {
                    $aprobado = "f";
                }
            }
        }

        //Analisis Calificaciones Tipo Materia 4
        if ($tipoMateria == 4)   //Ateneo
        {
            if ($asistencia < 0 && $trabaja == 0) {
                $asistOblig = "f";
            }
            if ($asistencia < 0 && $trabaja == 1) {
                $asistOblig = "f";
            }
        }

        //Salida Analisis Tipo Materia 1
        if ($tipoMateria == 1) {
            if ($regular == "f" || $asistOblig == "f")   //Libre
            {
                $salida = 0;
                $wanalisis = "Libre";
            }

            if ($regular == "t" && $asistOblig == "t")    //Regular
            {
                $salida = 1;
                $wanalisis = "Regular";
            }

            if ($regular == "t" && $coloquio == "t" && $asistOblig == "t")     //Coloquio
            {
                $salida = 2;
                $wanalisis = "Coloquio";
            }

            if ($regular == "f" && $sinAsist == "t")      //Libre S/Asist
            {
                $salida = 3;
                $wanalisis = "Libre - S/Asist";
            }

            if ($regular == "t" && $sinAsist == "t")      //Regular S/Asist
            {
                $salida = 4;
                $wanalisis = "Regular - S/Asist";
            }

            if ($regular == "t" && $coloquio == "t" && $sinAsist == "t")       //Coloquio S/Asist
            {
                $salida = 5;
                $wanalisis = "Coloquio - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        }

        //Salida Analisis Tipo Materia 2 3
        if ($tipoMateria == 2 || $tipoMateria == 3) {
            if ($coloquio == "f" || $asistOblig == "f")       //Recursa
            {
                $salida = 10;
                $wanalisis = "Recursa";
            }

            if ($coloquio == "t" && $asistOblig == "t")        //Coloquio
            {
                $salida = 2;
                $wanalisis = "Coloquio";
            }

            if ($coloquio == "t" && $aprobado == "t" && $asistOblig == "t")         //Aprueba
            {
                $salida = 11;
                $wanalisis = "Aprueba";
            }

            if ($coloquio == "f" && $sinAsist == "t")          //Recursa S/Asist
            {
                $salida = 12;
                $wanalisis = "Recursa - S/Asist";
            }

            if ($coloquio == "t" && $sinAsist == "t")          //Coloquio S/Asist
            {
                $salida = 5;
                $wanalisis = "Coloquio - S/Asist";
            }

            if ($coloquio == "t" && $aprobado == "t" && $sinAsist == "t")           //Aprueba S/Asist
            {
                $salida = 13;
                $wanalisis = "Aprueba - S/Asist";
            }

            if ($sinCalif == "t")       //S/Calif
            {
                $salida = 6;
                $wanalisis = "Sin Calificaciones";
            }
        }

        //Salida Analisis Tipo Materia 4
        if ($tipoMateria == 4) {
            if ($asistOblig == "t")     //$asistencia Requerida
            {
                $salida = 7;
                $wanalisis = "Asistencia Requerida";
            }

            if ($asistOblig == "f")    //$asistencia Incompleta
            {
                $salida = 8;
                $wanalisis = "Asistencia Incompleta";
            }

            if ($sinAsist == "t")       //S/Asist
            {
                $salida = 9;
                $wanalisis = "S/Asist";
            }
        }


    } elseif ($cod_col == "houssay") {		//////////HOUSSAY//////////

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Calificaiones Regularidad
        if ($p1 < $cReg && $p1 >= 0) {
            $recursa = "t";
            if ($r1 < $cReg) {
                $regular = "f";
            }
        }

        if ($p2 < $cReg && $p2 >= 0) {
            $recursa = "t";
            if ($r2 < $cReg) {
                $regular = "f";
            }
        }

        if ($p3 < $cReg && $p3 >= 0) {
            $recursa = "t";
            if ($r3 < $cReg) {
                $regular = "f";
            }
        }

        if ($p4 < $cReg && $p4 >= 0) {
            $recursa = "t";
            if ($r4 < $cReg) {
                $regular = "f";
            }
        }

        if ($p5 < $cReg && $p5 >= 0) {
            $recursa = "t";
            if ($r5 < $cReg) {
                $regular = "f";
            }
        }

        if ($p6 < $cReg && $p6 >= 0) {
            $recursa = "t";
            if ($r6 < $cReg) {
                $regular = "f";
            }
        }

        if ($p7 < $cReg && $p7 >= 0) {
            $recursa = "t";
            if ($r7 < $cReg) {
                $regular = "f";
            }
        }

        if ($p8 < $cReg && $p8 >= 0) {
            $recursa = "t";
            if ($r8 < $cReg) {
                $regular = "f";
            }
        }

        //Analisis Asistencia
        if ($trabaja == 0) {
            if ($asistencia < $aReg) {
                $asistOblig = "f";
            }
            if ($asistencia < $aProm) {
                $asistProm = "f";
            }
        }
        if ($trabaja == 1) {
            if ($asistencia < $aRegRed) {
                $asistOblig = "f";
            }
            if ($asistencia < $aPromRed) {
                $asistProm = "f";
            }
        }

        //Analisis Calificaciones Promocion
        if ($p1 >= $cProm || $p1 == -1) {
        } else {
            if ($r1 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p2 >= $cProm || $p2 == -1) {
        } else {
            if ($r2 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p3 >= $cProm || $p3 == -1) {
        } else {
            if ($r3 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p4 >= $cProm || $p4 == -1) {
        } else {
            if ($r4 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p5 >= $cProm || $p5 == -1) {
        } else {
            if ($r5 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p6 >= $cProm || $p6 == -1) {
        } else {
            if ($r6 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p7 >= $cProm || $p7 == -1) {
        } else {
            if ($r7 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p8 >= $cTrab || $p8 == -1) {
        } else {
            if ($r8 >= $cTrab) {
            } else {
                $aprobado = "f";
            }
        }

        //Salida Analisis
        if ($regular == "f" || $asistOblig == "f")   //Libre
        {
            $salida = 0;
            $wanalisis = "Libre";
        }

        if ($regular == "t" && $asistOblig == "t")    //$regular
        {
            $salida = 1;
            $wanalisis = "Regular";
        }

        if ($regular == "f" && $sinAsist == "t")      //Libre S/Asist
        {
            $salida = 3;
            $wanalisis = "Libre - S/Asist";
        }

        if ($regular == "t" && $sinAsist == "t")      //$regular S/Asist
        {
            $salida = 4;
            $wanalisis = "Regular - S/Asist";
        }

        if ($aprobado == "t" && $asistProm == "t" && $recursa = "f")      //Promocional
        {
            $salida = 14;
            $wanalisis = "Promocional";
        }

        if ($aprobado == "t" && $sinAsist == "t" && $recursa = "f")      //Promocional S/Asist
        {
            $salida = 15;
            $wanalisis = "Promocional - S/Asist";
        }

        if ($sinCalif == "t")       //S/Calif
        {
            $salida = 6;
            $wanalisis = "Sin Calificaciones";
        }


    } else {									//////////GENERICO//////////

        //Sin Calif o Sin Asist
        if ($p1 == -1 && $p2 == -1 && $p3 == -1 && $p4 == -1 && $p5 == -1 && $p6 == -1 && $p7 == -1 && $p8 == -1 && $r1 == -1 && $r2 == -1 && $r3 == -1 && $r4 == -1 && $r5 == -1 && $r6 == -1 && $r7 == -1 && $r8 == -1) {
            $sinCalif = "t";
        }

        if ($asistencia == "S/Asist") {
            $sinAsist = "t";
        }

        //Analisis Calificaciones Regularidad
        if ($p1 < $cReg && $p1 >= 0) {
            if ($r1 < $cReg) {
                $regular = "f";
            }
        }

        if ($p2 < $cReg && $p2 >= 0) {
            if ($r2 < $cReg) {
                $regular = "f";
            }
        }

        if ($p3 < $cReg && $p3 >= 0) {
            if ($r3 < $cReg) {
                $regular = "f";
            }
        }

        if ($p4 < $cReg && $p4 >= 0) {
            if ($r4 < $cReg) {
                $regular = "f";
            }
        }

        if ($p5 < $cReg && $p5 >= 0) {
            if ($r5 < $cReg) {
                $regular = "f";
            }
        }

        if ($p6 < $cReg && $p6 >= 0) {
            if ($r6 < $cReg) {
                $regular = "f";
            }
        }

        if ($p7 < $cReg && $p7 >= 0) {
            if ($r7 < $cReg) {
                $regular = "f";
            }
        }

        if ($p8 < $cReg && $p8 >= 0) {
            if ($r8 < $cReg) {
                $regular = "f";
            }
        }

        //Analisis Asistencia
        if ($trabaja == 0) {
            if ($asistencia < $aReg) {
                $asistOblig = "f";
            }
            if ($asistencia < $aProm) {
                $asistProm = "f";
            }
        }
        if ($trabaja == 1) {
            if ($asistencia < $aRegRed) {
                $asistOblig = "f";
            }
            if ($asistencia < $aPromRed) {
                $asistProm = "f";
            }
        }

        //Analisis Calificaciones Promocion
        if ($p1 >= $cProm || $p1 == -1) {
        } else {
            if ($r1 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p2 >= $cProm || $p2 == -1) {
        } else {
            if ($r2 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p3 >= $cProm || $p3 == -1) {
        } else {
            if ($r3 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p4 >= $cProm || $p4 == -1) {
        } else {
            if ($r4 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p5 >= $cProm || $p5 == -1) {
        } else {
            if ($r5 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p6 >= $cProm || $p6 == -1) {
        } else {
            if ($r6 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p7 >= $cProm || $p7 == -1) {
        } else {
            if ($r7 >= $cProm) {
            } else {
                $aprobado = "f";
            }
        }

        if ($p8 >= $cTrab || $p8 == -1) {
        } else {
            if ($r8 >= $cTrab) {
            } else {
                $aprobado = "f";
            }
        }

        //Salida Analisis
        if ($regular == "f" || $asistOblig == "f")   //No Regular
        {
            $salida = 0;
            $wanalisis = "No Regular";
        }

        if ($regular == "t" && $asistOblig == "t")    //$regular
        {
            $salida = 1;
            $wanalisis = "Regular";
        }

        if ($regular == "f" && $sinAsist == "t")      //No Regular S/Asist
        {
            $salida = 3;
            $wanalisis = "No Regular - S/Asist";
        }

        if ($regular == "t" && $sinAsist == "t")      //$regular S/Asist
        {
            $salida = 4;
            $wanalisis = "Regular - S/Asist";
        }

        if ($aprobado == "t" && $asistProm == "t")      //Aprueba
        {
            $salida = 11;
            $wanalisis = "Aprueba";
        }

        if ($aprobado == "t" && $sinAsist == "t")      //Aprueba S/Asist
        {
            $salida = 13;
            $wanalisis = "Aprueba - S/Asist";
        }

        if ($sinCalif == "t")       //S/Calif
        {
            $salida = 6;
            $wanalisis = "Sin Calificaciones";
        }
    }

    //Salida (numeroEstado, textoEstado)
    return array($salida, $wanalisis);
}
