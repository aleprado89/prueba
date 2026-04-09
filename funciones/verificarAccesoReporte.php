<?php
/**
 * Controles de autorizacion para reportes PDF.
 * Incluir despues de verificarSesion.php y conexion.php cuando $conn exista.
 */

/**
 * Solo personal de secretaria (usuarios administrativos).
 */
function assertReporteSecretaria() {
    if (empty($_SESSION['sec_nombreUsuario'])) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acceso denegado');
    }
}

/**
 * Secretaria o docente (no alumnos).
 */
function assertReporteSecretariaODocente() {
    if (!empty($_SESSION['sec_nombreUsuario'])) {
        return;
    }
    if (!empty($_SESSION['doc_legajo'])) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die('Acceso denegado');
}

/**
 * Secretaria puede ver cualquier alumno; alumno solo su propio idAlumno.
 */
function assertReporteAlumnoOsecretariaPorIdAlumno($idAlumno) {
    $idAlumno = (int) $idAlumno;
    if ($idAlumno <= 0) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acceso denegado');
    }
    if (!empty($_SESSION['sec_nombreUsuario'])) {
        return;
    }
    if (!empty($_SESSION['alu_idAlumno']) && (int) $_SESSION['alu_idAlumno'] === $idAlumno) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die('Acceso denegado');
}

/**
 * Solo sesion de alumno (reportes generados desde el area alumno).
 */
function assertReporteSoloAlumno() {
    if (empty($_SESSION['alu_idAlumno'])) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acceso denegado');
    }
}

/**
 * Docente asignado a la materia o secretaria.
 *
 * @param mysqli $conn
 * @param int    $idMateria
 */
function assertReporteDocenteOsecretariaPorIdMateria($conn, $idMateria) {
    $idMateria = (int) $idMateria;
    if ($idMateria <= 0) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acceso denegado');
    }
    if (!empty($_SESSION['sec_nombreUsuario'])) {
        return;
    }
    if (empty($_SESSION['doc_legajo'])) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acceso denegado');
    }
    $legajo = (int) $_SESSION['doc_legajo'];
    $sql = 'SELECT 1 FROM profesorxmateria WHERE idMateria = ? AND idPersonal = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        die('Error de autorizacion');
    }
    $stmt->bind_param('ii', $idMateria, $legajo);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if (!$ok) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acceso denegado');
    }
}
