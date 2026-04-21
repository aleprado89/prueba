<?php

function estado_examen($calificacion, $calificacionExamen)
{
    $estado = true;

    // Determinar calificación obligatoria
    if ($calificacionExamen === null || 
        $calificacionExamen === "" || 
        $calificacionExamen === "S/C") {

        $calificacionObligatoria = 4;
    } else {
        $calificacionObligatoria = (float)$calificacionExamen;
    }

    // Intentar interpretar como número
    if (is_numeric($calificacion)) {

        $calif = (float)$calificacion;

        if ($calif < $calificacionObligatoria) {
            $estado = false;
        }

    } else {

        if ($calificacion !== null && in_array(
            strtolower($calificacion),
            ["apr", "aprobado", "ap", "apto"]
        )) {
            $estado = true;
        } else {
            $estado = false;
        }
    }

    return $estado;
}

function calcularEstadoMateriaAlumno($conn, $idAlumno, $idMateria, $estadoCursadoNumeroActual)
{
    // 1️⃣ Estado no válido
    if (!in_array((int)$estadoCursadoNumeroActual, [0,1,3,4])) {
        return "ESTADO NO VALIDO";
    }

    // 2️⃣ Datos materia
    $sqlMateria = "
        SELECT idUnicoMateria, fechaFin, cantidadTurnosRegular, cantidadTurnosLibre
        FROM materiaterciario
        WHERE idMateria = :idMateria
    ";
    $stmt = $conn->prepare($sqlMateria);
    $stmt->execute(['idMateria' => $idMateria]);
    $materia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$materia) return "ERROR MATERIA";

    $cantRegular = (int)$materia['cantidadTurnosRegular'];
    $cantLibre   = (int)$materia['cantidadTurnosLibre'];

    // 3️⃣ Sin límite
    if (empty($materia['cantidadTurnosRegular']) && empty($materia['cantidadTurnosLibre'])) {
        return "SIN LIMITE";
    }

    // 4️⃣ Contar turnos consumidos
    $sqlTurnos = "
        SELECT COUNT(DISTINCT fe.idTurno)
        FROM materiaterciario mTurno
        JOIN fechasexamenes fe ON fe.idMateria = mTurno.idMateria
        JOIN turnosexamenes te ON te.idTurno = fe.idTurno
        WHERE mTurno.idUnicoMateria = :idUnicoMateria
          AND fe.fecha > :fechaFin
          AND fe.fecha < DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND te.nombre NOT LIKE '%Especial%'
    ";
    $stmt = $conn->prepare($sqlTurnos);
    $stmt->execute([
        'idUnicoMateria' => $materia['idUnicoMateria'],
        'fechaFin'       => $materia['fechaFin']
    ]);
    $turnosConsumidos = (int)$stmt->fetchColumn();

    $esRegularInicial = in_array((int)$estadoCursadoNumeroActual, [1,4]);

    // 5️⃣ Cálculo
    $restantesRegular = 0;
    $restantesLibre   = 0;

    if ($esRegularInicial) {

        if ($turnosConsumidos < $cantRegular) {

            $restantesRegular = $cantRegular - $turnosConsumidos;
            $restantesLibre   = $cantLibre;

        } elseif ($turnosConsumidos < ($cantRegular + $cantLibre)) {

            $restantesRegular = 0;
            $restantesLibre   = ($cantRegular + $cantLibre) - $turnosConsumidos;

        } else {
            return "RECURSA";
        }

    } else {

        if ($turnosConsumidos < $cantLibre) {

            $restantesRegular = 0;
            $restantesLibre   = $cantLibre - $turnosConsumidos;

        } else {
            return "RECURSA";
        }
    }

    return "REGULAR: $restantesRegular | LIBRE: $restantesLibre";
}


