<?php

$consulta = "SELECT * from colegio where codnivel = 6";
$colegio = mysqli_query($conn, $consulta);
$datosColegio = array();
$i = 0;

if (!empty($colegio)) {
    while ($data = mysqli_fetch_array($colegio)) 
    {
        $datosColegio[$i]['idTurno'] = $data['iDturnoautoweb'];
        
        // Preparar la consulta
        $turnoC = "SELECT nombre from turnosexamenes where idTurno = ?";
        $stmt = $conn->prepare($turnoC);
        
        // Vincular el parámetro
        $stmt->bind_param("i", $data['iDturnoautoweb']);
        
        // Ejecutar la consulta
        $stmt->execute();
        
        // Obtener el resultado
        $turno = $stmt->get_result();        
      
        if (!empty($turno)) {
            while ($dataT = mysqli_fetch_array($turno)) 
            {
                $datosColegio[$i]['nombreTurno'] = $dataT['nombre'];
            }
        }

        $datosColegio[$i]['examenDesde'] = $data['inscExamDesde'];
        $datosColegio[$i]['examenHasta'] = $data['inscExamHasta'];
        $datosColegio[$i]['cursadoDesde'] = $data['inscCursDesde'];
        $datosColegio[$i]['cursadoHasta'] = $data['inscCursHasta'];
        $datosColegio[$i]['examenLectDesde'] = $data['inscExamLectDesde'];
        $datosColegio[$i]['cursadoLectDesde'] = $data['inscCursLectDesde'];
        $datosColegio[$i]['actaDesde'] = $data['cargaActaVolDesde'];
        $datosColegio[$i]['actaHasta'] = $data['cargaActaVolHasta'];
        $datosColegio[$i]['idTurnoActa'] = $data['cargaActaVolTurno'];
        $datosColegio[$i]['anioautoweb'] = $data['anioautoweb'];
        $datosColegio[$i]['anioCargaNotas'] = $data['anio_carga_notas'];
        $datosColegio[$i]['cargaActaVolTurno'] = $data['cargaActaVolTurno'];
        $datosColegio[$i]['nombreColegio'] = $data['nombreColegio'];
        $datosColegio[$i]['localidad'] = $data['localidad'];

        $i++;
    }
}