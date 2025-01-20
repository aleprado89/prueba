<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml('Hola, mundo!');
$dompdf->render();
$dompdf->stream();
?>