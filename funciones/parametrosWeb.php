<?php

$consulta = "SELECT * from colegio";
$colegio = mysqli_query($conn, $consulta);
$datosColegio = array();
$i = 0;

if (!empty($colegio)) {
    while ($data = mysqli_fetch_array($colegio)) 
    {
        $datosColegio[$i]['idTurno'] = $data['iDturnoautoweb'];
        
        $turnoC = "SELECT nombre from turnosexamenes where idTurno = $data[iDturnoautoweb]";
        $turno = mysqli_query($conn, $turnoC);        
        $i = 0;
        
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

        $i++;
    }
}
