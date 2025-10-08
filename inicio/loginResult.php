<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php'; // Conexión a la base de datos
include 'variablesParticulares.php'; 

// Verificar que la conexión a la base de datos sea válida
if (!isset($conn) || !($conn instanceof mysqli)) {
    $_SESSION['login_message'] = "Error crítico: No se pudo establecer la conexión a la base de datos.";
    $_SESSION['login_message_type'] = 'alert-danger';
    header('Location: login.php');
    exit();
}

// ------ NUEVA LÓGICA: Obtener el valor de docenteModifica al principio ------
$docenteModificaGlobal = 0; // Por defecto, no pueden modificar
$sql_docente_modifica = "SELECT docenteModifica FROM colegio WHERE codnivel=6 LIMIT 1";
$result_docente_modifica = $conn->query($sql_docente_modifica);
if ($result_docente_modifica && $result_docente_modifica->num_rows > 0) {
    $row_docente_modifica = $result_docente_modifica->fetch_assoc();
    $docenteModificaGlobal = $row_docente_modifica['docenteModifica'];
    $result_docente_modifica->free();
} else {
    error_log("No se encontró la configuración 'docenteModifica' en la tabla 'colegio' con codnivel = 6.");
}
// -------------------------------------------------------------------------

// Recoger el DNI (username) y la contraseña del formulario
$username = trim($_POST['username'] ?? ''); // Aquí 'username' es el DNI
$password = trim($_POST['password'] ?? '');

$CLAVE_DOCENTE_POR_DEFECTO = $_SESSION['claveDocente'] ?? 'defaultdocpass'; 

$loggedInUser = null;
$userType = null;
$redirectPath = null;

// --- Lógica de verificación de acceso ---

$alumnoDocenteData = verificarAccesoAlumnoYDocente($username, $password, $conn, $CLAVE_DOCENTE_POR_DEFECTO);
if ($alumnoDocenteData) {
    $loggedInUser = $alumnoDocenteData;
    $userType = 'alumno_docente';
    $redirectPath = '../inicio/seleccionar_rol.php';
}
elseif (($docenteData = verificarAccesoDocente($username, $password, $conn, $CLAVE_DOCENTE_POR_DEFECTO)) !== false) {
    $loggedInUser = $docenteData;
    $userType = 'docente';
    $redirectPath = '../docentes/menudocentes.php';
}
elseif (($alumnoData = verificarAccesoAlumno($username, $password, $conn)) !== false) {
    $loggedInUser = $alumnoData;
    $userType = 'alumno';
    $redirectPath = '../alumnos/menualumnos.php';
}

// --- Procesar resultado del login ---
if ($loggedInUser) {
    // --- LOGIN EXITOSO: GESTIÓN DE SESIÓN CENTRALIZADA ---

    $_SESSION = array();
    session_destroy();
    session_start();
    session_regenerate_id(true);

    include 'variablesParticulares.php'; 

    $sql_colegio = "SELECT nombreColegio FROM colegio WHERE codnivel=6 LIMIT 1";
    $result_colegio = $conn->query($sql_colegio);
    if ($result_colegio && $result_colegio->num_rows > 0) {
        $row_colegio = $result_colegio->fetch_assoc();
        $_SESSION['nombreColegio'] = $row_colegio['nombreColegio'];
        $result_colegio->free();
    } else {
        error_log("Error: No se encontró nombreColegio para codnivel=6 en DB o la consulta falló en loginResult.php.");
        $_SESSION['nombreColegio'] = "Instituto Superior";
    }

    // Establecer variables de sesión específicas según el tipo de usuario
    if ($userType === 'alumno' || $userType === 'alumno_docente') {
        $_SESSION['alu_nombre'] = $loggedInUser['alu_nombre'];
        $_SESSION['alu_apellido'] = $loggedInUser['alu_apellido'];
        $_SESSION['alu_dni'] = $loggedInUser['alu_dni'];
        $_SESSION['alu_idAlumno'] = $loggedInUser['alu_idAlumno'];
        $_SESSION['alu_idPersona'] = $loggedInUser['alu_idPersona'];
        $_SESSION['anioPlataformaAlu'] = $loggedInUser['anioPlataformaAlu'];
        if ($loggedInUser['force_clave_change_alu'] ?? false) {
             $_SESSION['cambiarClave'] = 1;
        }
    }
    if ($userType === 'docente' || $userType === 'alumno_docente') {
        $_SESSION['doc_nombre'] = $loggedInUser['doc_nombre'];
        $_SESSION['doc_apellido'] = $loggedInUser['doc_apellido'];
        $_SESSION['doc_dni'] = $loggedInUser['doc_dni'];
        $_SESSION['doc_legajo'] = $loggedInUser['doc_legajo'];
        $_SESSION['doc_idPersona'] = $loggedInUser['doc_idPersona'];
        $_SESSION['anioPlataformaDoc'] = $loggedInUser['anioPlataformaDoc'];
        if ($loggedInUser['force_clave_change_doc'] ?? false) {
             $_SESSION['cambiarClave'] = 1;
        }
        // ------ NUEVA LÓGICA: Establecer $_SESSION['profeModCalifAsis'] para docentes ------
        $_SESSION['profeModCalifAsis'] = $docenteModificaGlobal;
        // ----------------------------------------------------------------------------------
    }

    // Establecer el identificador de usuario activo global para el control de sesiones
    if ($userType === 'alumno_docente') {
        $_SESSION['active_user_identifier'] = 'ad_' . $loggedInUser['alu_idAlumno'] . '_' . $loggedInUser['doc_legajo'];
        $_SESSION['es_alumno_y_docente'] = true;
    } elseif ($userType === 'alumno') {
        $_SESSION['active_user_identifier'] = 'alu_' . $_SESSION['alu_idAlumno'];
    } elseif ($userType === 'docente') {
        $_SESSION['active_user_identifier'] = 'doc_' . $_SESSION['doc_legajo'];
    }
    
    $_SESSION['redirect_after_logout'] = '../inicio/login.php';

    header('Location: ' . $redirectPath);
    exit;

} else {
    // --- LOGIN FALLIDO ---
    $_SESSION['login_message'] = "Usuario (" . htmlspecialchars($username) . ") o contraseña incorrectos.";
    $_SESSION['login_message_type'] = 'alert-danger';
    header('Location: login.php');
    exit;
}

if (isset($conn)) {
    $conn->close();
}

/**
 * Verifica el acceso de un DOCENTE.
 * ... (Esta función permanece igual, ya la modificamos para `per.nivel = 6`) ...
 */
function verificarAccesoDocente($dni, $passwordInput, $conn, $CLAVE_DOCENTE_POR_DEFECTO) {
    if (empty($dni) || empty($passwordInput)) return false;

    $sql = "SELECT p.nombre, p.apellido, p.dni, p.idPersona, per.legajo
            FROM persona p INNER JOIN personal per ON p.idPersona = per.idPersona
            WHERE p.dni = ? AND per.nivel = 6";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log("Error preparando stmt docente: " . $conn->error); return false; }
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $sqlPass = "SELECT password FROM passwords WHERE legajo = ?";
        $stmtPass = $conn->prepare($sqlPass);
        if (!$stmtPass) { error_log("Error preparando stmtPass docente: " . $conn->error); return false; }
        $stmtPass->bind_param("i", $row['legajo']);
        $stmtPass->execute();
        $resultPass = $stmtPass->get_result();
        if($resultPass) {
            $rowPass = $resultPass->fetch_assoc();
            $resultPass->free();
        }
        $stmtPass->close();

        $forceClaveChange = false;
        if ($rowPass) {
            if ($passwordInput === $rowPass['password']) {
                $forceClaveChange = (strtolower($passwordInput) === strtolower($CLAVE_DOCENTE_POR_DEFECTO));
                $colegioData = getColegioConfig($conn);
                return [
                    'doc_nombre' => $row["nombre"],
                    'doc_apellido' => $row["apellido"],
                    'doc_dni' => $row["dni"],
                    'doc_legajo' => $row["legajo"],
                    'doc_idPersona' => $row["idPersona"],
                    'anioPlataformaDoc' => $colegioData['anioCargaNotas'],
                    'force_clave_change_doc' => $forceClaveChange
                ];
            } else {
                return false;
            }
        } else {
            if ($passwordInput === $CLAVE_DOCENTE_POR_DEFECTO) {
                $sqlInsertPass = "INSERT INTO passwords (legajo, password) VALUES (?, ?)";
                $stmtInsertPass = $conn->prepare($sqlInsertPass);
                if (!$stmtInsertPass) { error_log("Error preparando insert pass docente: " . $conn->error); return false; }
                $stmtInsertPass->bind_param("is", $row['legajo'], $CLAVE_DOCENTE_POR_DEFECTO);
                if (!$stmtInsertPass->execute()) {
                    error_log("Error al insertar contraseña de docente: " . $stmtInsertPass->error);
                    return false;
                }
                $stmtInsertPass->close();
                $forceClaveChange = true;

                $colegioData = getColegioConfig($conn);
                return [
                    'doc_nombre' => $row["nombre"],
                    'doc_apellido' => $row["apellido"],
                    'doc_dni' => $row["dni"],
                    'doc_legajo' => $row["legajo"],
                    'doc_idPersona' => $row["idPersona"],
                    'anioPlataformaDoc' => $colegioData['anioCargaNotas'],
                    'force_clave_change_doc' => $forceClaveChange
                ];
            } else {
                return false;
            }
        }
    }
    return false;
}

/**
 * Verifica el acceso de un ALUMNO.
 * ... (Esta función permanece igual) ...
 */
function verificarAccesoAlumno($dni, $passwordInput, $conn) {
    if (empty($dni) || empty($passwordInput)) return false;

    $sql = "SELECT p.nombre, p.apellido, p.dni, p.idPersona, a.idAlumno
            FROM persona p INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona
            WHERE p.dni = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log("Error preparando stmt alumno: " . $conn->error); return false; }
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $sqlPass = "SELECT password FROM passwords_alumnos WHERE idAlumno = ?";
        $stmtPass = $conn->prepare($sqlPass);
        if (!$stmtPass) { error_log("Error preparando stmtPass alumno: " . $conn->error); return false; }
        $stmtPass->bind_param("i", $row['idAlumno']);
        $stmtPass->execute();
        $resultPass = $stmtPass->get_result();
        if($resultPass) {
            $rowPass = $resultPass->fetch_assoc();
            $resultPass->free();
        }
        $stmtPass->close();

        $forceClaveChange = false;
        if ($rowPass) {
            if ($passwordInput === $rowPass['password']) {
                $forceClaveChange = ($passwordInput === $dni);
                $colegioData = getColegioConfig($conn);
                return [
                    'alu_nombre' => $row["nombre"],
                    'alu_apellido' => $row["apellido"],
                    'alu_dni' => $row["dni"],
                    'alu_idAlumno' => $row["idAlumno"],
                    'alu_idPersona' => $row["idPersona"],
                    'anioPlataformaAlu' => $colegioData['anioautoweb'],
                    'force_clave_change_alu' => $forceClaveChange
                ];
            } else {
                return false;
            }
        } else {
            if ($passwordInput === $dni) {
                $sqlInsertPass = "INSERT INTO passwords_alumnos (idAlumno, password) VALUES (?, ?)";
                $stmtInsertPass = $conn->prepare($sqlInsertPass);
                if (!$stmtInsertPass) { error_log("Error preparando insert pass alumno: " . $conn->error); return false; }
                $stmtInsertPass->bind_param("is", $row['idAlumno'], $dni);
                if (!$stmtInsertPass->execute()) {
                    error_log("Error al insertar contraseña de alumno: " . $stmtInsertPass->error);
                    return false;
                }
                $stmtInsertPass->close();
                $forceClaveChange = true;

                $colegioData = getColegioConfig($conn);
                return [
                    'alu_nombre' => $row["nombre"],
                    'alu_apellido' => $row["apellido"],
                    'alu_dni' => $row["dni"],
                    'alu_idAlumno' => $row["idAlumno"],
                    'alu_idPersona' => $row["idPersona"],
                    'anioPlataformaAlu' => $colegioData['anioautoweb'],
                    'force_clave_change_alu' => $forceClaveChange
                ];
            } else {
                return false;
            }
        }
    }
    return false;
}

/**
 * Verifica si es un Alumno Y Docente.
 * ... (Esta función permanece igual, ya la modificamos para `per.nivel = 6`) ...
 */
function verificarAccesoAlumnoYDocente($dni, $passwordInput, $conn, $CLAVE_DOCENTE_POR_DEFECTO) {
    // 1. Obtener todas las personas con ese DNI
    $stmt = $conn->prepare("SELECT idPersona FROM persona WHERE dni = ?");
    if (!$stmt) { error_log("Error preparando stmt persona: " . $conn->error); return false; }
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows < 1) return false;

    $personas = [];
    while ($row = $res->fetch_assoc()) {
        $personas[] = $row['idPersona'];
    }
    $stmt->close();

    $alumnoData = null;
    $docenteData = null;

    // 2. Buscar si alguna persona es alumno
    $in = str_repeat('?,', count($personas) - 1) . '?';
    $types = str_repeat('i', count($personas));
    $sqlAlumno = "SELECT p.nombre, p.apellido, p.dni, p.idPersona, a.idAlumno
                  FROM persona p 
                  INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona
                  WHERE p.idPersona IN ($in)";
    $stmt = $conn->prepare($sqlAlumno);
    $stmt->bind_param($types, ...$personas);
    $stmt->execute();
    $resAlumno = $stmt->get_result();
    $esAlumno = ($resAlumno && $resAlumno->num_rows >= 1);
    if ($esAlumno) $alumnoData = $resAlumno->fetch_assoc();
    $stmt->close();

    // 3. Buscar si alguna persona es docente (nivel 6)
    $sqlDocente = "SELECT p.nombre, p.apellido, p.dni, p.idPersona, per.legajo
                   FROM persona p 
                   INNER JOIN personal per ON p.idPersona = per.idPersona
                   WHERE p.idPersona IN ($in) AND per.nivel = 6";
    $stmt = $conn->prepare($sqlDocente);
    $stmt->bind_param($types, ...$personas);
    $stmt->execute();
    $resDocente = $stmt->get_result();
    $esDocente = ($resDocente && $resDocente->num_rows >= 1);
    if ($esDocente) $docenteData = $resDocente->fetch_assoc();
    $stmt->close();

    // Necesita ser ambos para aplicar esta función
    if (!$esAlumno || !$esDocente) return false;

    // ------------------------------------------
    // 4. Verificar clave del ALUMNO
    $claveAlumnoCorrecta = false;
    $forceClaveChangeAlu = false;

    if ($alumnoData) {
        $stmt = $conn->prepare("SELECT password FROM passwords_alumnos WHERE idAlumno = ?");
        $stmt->bind_param("i", $alumnoData['idAlumno']);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = ($res && $res->num_rows === 1) ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            if ($passwordInput === $row['password']) {
                $claveAlumnoCorrecta = true;
                $forceClaveChangeAlu = ($passwordInput === $dni);
            }
        } else if ($passwordInput === $dni) {
            // Crear clave por defecto
            $stmt = $conn->prepare("INSERT INTO passwords_alumnos (idAlumno, password) VALUES (?, ?)");
            $stmt->bind_param("is", $alumnoData['idAlumno'], $dni);
            if ($stmt->execute()) {
                $claveAlumnoCorrecta = true;
                $forceClaveChangeAlu = true;
            }
            $stmt->close();
        }
    }

    // ------------------------------------------
    // 5. Verificar clave del DOCENTE
    $claveDocenteCorrecta = false;
    $forceClaveChangeDoc = false;

    if ($docenteData) {
        $stmt = $conn->prepare("SELECT password FROM passwords WHERE legajo = ?");
        $stmt->bind_param("i", $docenteData['legajo']);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = ($res && $res->num_rows === 1) ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            if ($passwordInput === $row['password']) {
                $claveDocenteCorrecta = true;
                $forceClaveChangeDoc = (strtolower($passwordInput) === strtolower($CLAVE_DOCENTE_POR_DEFECTO));
            }
        } else if ($passwordInput === $CLAVE_DOCENTE_POR_DEFECTO) {
            // Crear clave por defecto
            $stmt = $conn->prepare("INSERT INTO passwords (legajo, password) VALUES (?, ?)");
            $stmt->bind_param("is", $docenteData['legajo'], $CLAVE_DOCENTE_POR_DEFECTO);
            if ($stmt->execute()) {
                $claveDocenteCorrecta = true;
                $forceClaveChangeDoc = true;
            }
            $stmt->close();
        }
    }

    // ------------------------------------------
    // 6. ¿Alguna clave fue válida?
    if (!$claveAlumnoCorrecta && !$claveDocenteCorrecta) {
        return false;
    }

    // 7. Devolver datos completos
    $colegioData = getColegioConfig($conn);
    return [
        'alu_nombre' => $alumnoData['nombre'],
        'alu_apellido' => $alumnoData['apellido'],
        'alu_dni' => $alumnoData['dni'],
        'alu_idAlumno' => $alumnoData['idAlumno'],
        'alu_idPersona' => $alumnoData['idPersona'],
        'doc_nombre' => $docenteData['nombre'],
        'doc_apellido' => $docenteData['apellido'],
        'doc_dni' => $docenteData['dni'],
        'doc_legajo' => $docenteData['legajo'],
        'doc_idPersona' => $docenteData['idPersona'],
        'anioPlataformaAlu' => $colegioData['anioautoweb'],
        'anioPlataformaDoc' => $colegioData['anioCargaNotas'],
        'force_clave_change_alu' => $forceClaveChangeAlu,
        'force_clave_change_doc' => $forceClaveChangeDoc
    ];
}



function existeAlumno($conn, $idPersona) {
    $sql = "SELECT 1 FROM alumnosterciario WHERE idPersona = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function existeDocente($conn, $idPersona) {
    $sql = "SELECT 1 FROM personal WHERE idPersona = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}


/**
 * Función auxiliar para obtener la configuración del colegio.
 */
function getColegioConfig($conn) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        error_log("Error: La conexión a la base de datos no es válida para getColegioConfig.");
        return ['anioautoweb' => date('Y'), 'anioCargaNotas' => date('Y')]; // Fallback
    }

    $colegioConfig = [];
    $sql = "SELECT anioautoweb, anio_carga_notas FROM colegio WHERE codnivel=6 LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $colegioConfig = $result->fetch_assoc();
        $result->free();
    } else {
        error_log("No se encontraron datos de colegio para codnivel=6 en DB o consulta falló en getColegioConfig.");
    }
    return [
        'anioautoweb' => $colegioConfig['anioautoweb'] ?? date('Y'),
        'anioCargaNotas' => $colegioConfig['anio_carga_notas'] ?? date('Y')
    ];
}
?>