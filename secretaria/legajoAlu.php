<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos y consultas
include '../inicio/conexion.php';
include '../funciones/consultas.php'; // Asegúrate de que aquí estén las nuevas funciones

// Función auxiliar para calcular CUIL en PHP (para la carga inicial si hay DNI y Sexo)
// Mantenida aquí ya que es lógica de CUIL, no consulta de DB
function calculateCuilPHP($dni, $sexo) {
    if (empty($dni) || empty($sexo)) {
        return ['pre' => '', 'post' => ''];
    }

    $base = 0;
    if ($sexo === 'M') {
        $base = 20;
    } elseif ($sexo === 'F') {
        $base = 27;
    } else {
        return ['pre' => '', 'post' => '']; // Sexo inválido
    }

    $cuil = (string)$base . (string)$dni;
    $multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2]; // Para DNI más largos, los multiplicadores se usarían en los primeros 10 dígitos.
    $sum = 0;
    // Iterar sobre los 10 dígitos base del CUIL (prefijo + DNI)
    for ($i = 0; $i < 10; $i++) {
        // Asegurarse de que el índice existe para evitar errores con DNI incompletos
        if (!isset($cuil[$i])) {
            return ['pre' => '', 'post' => ''];
        }
        $sum += (int)$cuil[$i] * $multipliers[$i];
    }

    $remainder = $sum % 11;
    $checksum = 11 - $remainder;

    if ($checksum === 11) {
        $checksum = 0;
    } elseif ($checksum === 10) {
        // Según las reglas de AFIP, if DV is 10, prefijos 23/24 deberían ser usados en vez de 20/27.
        // Por la simplicidad si es 10 convertiremos a 9 en vez de intentar cambiar el prefijo.
        // En un sistema robusto real, este escenario implicaría cambiar el prefijo (base) y recalcular,
        // o marcar un error si el sexo/DNI elegido no puede formar un CUIL estándar.
        $checksum = 9;
    }
    return ['pre' => (string)$base, 'post' => (string)$checksum];
}


// --- PROCESAMIENTO AJAX PARA DNI UNIQUE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'check_dni_unique') {
    $dni_to_check = $_POST['dni'] ?? '';
    // En modo edición, debemos excluir el propio ID de persona del alumno
    $exclude_idPersona = $_POST['idPersonaActual'] ?? null;
    $exists = dniExiste($conn, $dni_to_check, $exclude_idPersona);
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit;
}

// --- LÓGICA PHP PARA CARGAR / INICIALIZAR DATOS DEL FORMULARIO ---
$idAlumno = $_GET['idAlumno'] ?? null;
$mode = $idAlumno ? 'edit' : 'new'; // 'edit' o 'new'

$alumnoData = []; // Datos de la persona y alumno a cargar/editar
$message = ''; // Mensaje de éxito/error después de guardar
$message_type = '';

// Array de etiquetas para los checkboxes de documentación (orden fijo)
$documentacion_labels = [
    "Fotocopia DNI",
    "Título habilitante",
    "Ficha médica",
    "Título en trámite",
    "Fotos 4x4",
    "Partida de nacimiento",
    "Libreta"
];


// Si es modo edición, cargar datos existentes
if ($mode == 'edit') {
    $alumnoData = obtenerDatosAlumno($conn, $idAlumno);
    if (!$alumnoData) {
        // Si no se encuentra el alumno para edición, redirigir o mostrar error
        $_SESSION['message_legajo'] = ['text' => 'Alumno no encontrado para edición.', 'type' => 'danger'];
        header('Location: buscarAlumno.php');
        exit;
    }
} else {
    // Inicializar datos para el formulario de nuevo alumno
    $alumnoData = [
        'idPersona' => null, 'apellido' => '', 'nombre' => '', 'dni' => '', 'sexo' => '',
        'fechaNacimiento' => '', 'nacionalidadNacimiento' => '', 'provinciaNacimiento' => '',
        'localidadNacimiento' => '', 'domicilio' => '', 'cp' => '',
        'telefono' => '', 'celular' => '', 'email' => '',
        'cuilPre' => '', 'cuilPost' => '', 'telefonoEmergencia' => '',
        'idAlumno' => null, 'vivePadre' => 0, 'viveMadre' => 0, 'egresado' => 0,
        'retiroBiblioteca' => 0, 'observacionesAlumno' => '', 'fotoURL' => '', 'mailInstitucional' => '',
        'documentacion' => '0000000', // Valor inicial para los 7 checkboxes
        'materiasAdeuda' => '',
        'idFamilia' => 0 // Valor inicial para idFamilia
    ];
}

// Recalcular CUIL para la visualización inicial si es edición y DNI/Sexo están presentes
if ($mode == 'edit' && !empty($alumnoData['dni']) && !empty($alumnoData['sexo'])) {
    $cuilParts = calculateCuilPHP($alumnoData['dni'], $alumnoData['sexo']);
    $alumnoData['cuilPre'] = $cuilParts['pre'];
    $alumnoData['cuilPost'] = $cuilParts['post'];
}


// --- PROCESAMIENTO DEL FORMULARIO POST (Guarda de datos) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['action']) || $_POST['action'] != 'check_dni_unique')) { // Excluir la petición AJAX
    // 1. Recoger datos del POST
    $formData = $_POST;
    $idPersona = $formData['idPersona'] ?? null; // Si existe, es una operación de edición

    // 2. Validar DNI único (solo para nuevas inserciones o si el DNI cambia en modo edición)
    $dni = $formData['dni'] ?? '';
    // Obtener el DNI original para comparar en modo edición
    $originalDni = '';
    if ($mode == 'edit' && $alumnoData) {
        $originalDni = $alumnoData['dni'];
    }

    if ($mode == 'new' || ($mode == 'edit' && $dni != $originalDni)) {
        if (dniExiste($conn, $dni, $idPersona)) {
            $message = "Error: El DNI ingresado ya existe.";
            $message_type = 'danger';
            // Vuelve a poblar $alumnoData con los datos del POST para que el usuario no pierda lo ingresado
            $alumnoData = array_merge($alumnoData, $formData);
            goto end_post_processing; // Saltamos al final del procesamiento POST
        }
    }

    // Validar campos requeridos
    if (empty($formData['apellido']) || empty($formData['nombre']) || empty($formData['dni']) || empty($formData['sexo'])) {
        $message = "Error: Apellido, Nombre, DNI y Sexo son campos requeridos.";
        $message_type = 'danger';
        $alumnoData = array_merge($alumnoData, $formData);
        goto end_post_processing;
    }

    // 3. Calcular CUIL (cuilPre y cuilPost)
    $calculatedCuilParts = calculateCuilPHP($dni, $formData['sexo']);
    $formData['cuilPre'] = $calculatedCuilParts['pre'];
    $formData['cuilPost'] = $calculatedCuilParts['post'];

    // 3.5: Convertir fechaNacimiento a NULL si está vacío
    if (empty($formData['fechaNacimiento'])) {
        $formData['fechaNacimiento'] = null;
    }


    // 4. Procesar la subida de la foto (campo FotoCarnet de la tabla `persona`)
    // Si es una edición, mantener la URL de la foto existente a menos que se suba una nueva
    $fotoURL_db = $alumnoData['fotoURL'] ?? ''; // El alias fotoURL es para p.FotoCarnet
    if (isset($_FILES['fotoCarnet']) && $_FILES['fotoCarnet']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'fotosPersonas/'; // DIRECTORIO DE actuaDatosAlu.php
        // Asegurarse de que el directorio de subida existe
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0755, true);
        }
        
        $allowedFormats = ['jpg', 'jpeg', 'png'];
        $maxSizeMB = 5;
        $maxSizeBytes = $maxSizeMB * 1024 * 1024; // 5 MB en bytes

        $fileExtension = strtolower(pathinfo($_FILES['fotoCarnet']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedFormats)) {
            $message = "Formato de archivo no permitido. Los formatos permitidos son: " . implode(", ", $allowedFormats) . ".";
            $message_type = 'danger';
            $alumnoData = array_merge($alumnoData, $formData);
            goto end_post_processing;
        }

        if ($_FILES['fotoCarnet']['size'] > $maxSizeBytes) {
            $message = "El archivo es demasiado grande. El tamaño máximo permitido es de " . $maxSizeMB . " MB.";
            $message_type = 'danger';
            $alumnoData = array_merge($alumnoData, $formData);
            goto end_post_processing;
        }

        // Generar un nombre único para el archivo, usando parte del DNI y timestamp para mayor unicidad e información
        $fileName = $dni . '_' . date('Y-m-d_H-i-s') . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName; // Ruta para guardar en la base de datos
        // Ruta completa del servidor para move_uploaded_file
        $targetFilePath = '../' . $filePath; // Asumiendo que este script está en un subdirectorio como 'vistas'

        if (move_uploaded_file($_FILES['fotoCarnet']['tmp_name'], $targetFilePath)) {
            $fotoURL_db = $filePath; // Guardar la ruta relativa en la base de datos
            // Opcional: eliminar la foto antigua si existe y es diferente a la nueva
            if ($mode == 'edit' && !empty($alumnoData['fotoURL']) && $alumnoData['fotoURL'] !== $fotoURL_db && file_exists('../' . $alumnoData['fotoURL'])) {
                unlink('../' . $alumnoData['fotoURL']);
            }
        } else {
            $message = "Error al mover el archivo de la foto del carnet.";
            $message_type = 'warning';
        }
    } else if (isset($_FILES['fotoCarnet']) && $_FILES['fotoCarnet']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Manejar otros errores de subida distintos a "no se seleccionó archivo"
        $message = "Error de subida de archivo: " . $_FILES['fotoCarnet']['error'];
        $message_type = 'danger';
        $alumnoData = array_merge($alumnoData, $formData);
        goto end_post_processing;
    }
    $formData['fotoURL'] = $fotoURL_db; // Actualizar con la nueva URL o conservar la existente

    // 5. Coleccionar los datos de documentación (checkboxes)
    $documentacion_flags_string = '';
    // Iterar sobre las etiquetas para construir la cadena en el orden correcto
    for ($i = 0; $i < count($documentacion_labels); $i++) {
        // En el HTML, los checkboxes tienen name="documentacion_flag_X".
        // Si el checkbox está marcado, $_POST['documentacion_flag_X'] será "1".
        // Si no está marcado, nuestro JS añade un input hidden con el mismo nombre y valor "0".
        // Así nos aseguramos de que el valor exista en $_POST y sea "0" o "1".
        $documentacion_flags_string .= ($formData['documentacion_flag_' . $i] ?? '0');
    }
    $formData['documentacion'] = $documentacion_flags_string;
    $formData['idFamilia'] = $formData['idFamilia'] ?? 0; // Asegura que idFamilia siempre es 0 si no viene del form


    // 6. Insertar/Actualizar en tablas 'persona' y 'alumnosterciario' usando funciones
    $conn->begin_transaction();
    try {
        if ($mode == 'new') {
            // INSERTAR en persona
            $idPersona = insertPersona($conn, [
                'apellido' => $formData['apellido'],
                'nombre' => $formData['nombre'],
                'dni' => $formData['dni'],
                'sexo' => $formData['sexo'],
                'fechaNacimiento' => $formData['fechaNacimiento'],
                'nacionalidadNacimiento' => $formData['nacionalidadNacimiento'],
                'provinciaNacimiento' => $formData['provinciaNacimiento'],
                'localidadNacimiento' => $formData['localidadNacimiento'],
                'email' => $formData['email'],
                'telefono' => $formData['telefono'],
                'celular' => $formData['celular'],
                'cuilPre' => $formData['cuilPre'],
                'cuilPost' => $formData['cuilPost'],
                'telefonoEmergencia' => $formData['telefonoEmergencia'],
                'domicilio' => $formData['domicilio'], // Este es 'direccion' en la DB
                'cp' => $formData['cp'],             // Este es 'codigoPostal' en la DB
                'fotoURL' => $formData['fotoURL']    // Este es 'FotoCarnet' en la DB
            ]);

            if ($idPersona === false) {
                throw new Exception("Error al insertar en la tabla persona.");
            }

            // INSERTAR en alumnosterciario
            $idAlumno = insertAlumnoTerciario($conn, $idPersona, [
                'vivePadre' => $formData['vivePadre'],
                'viveMadre' => $formData['viveMadre'],
                'egresado' => $formData['egresado'],
                'retiroBiblioteca' => $formData['retiroBiblioteca'],
                'observacionesAlumno' => $formData['observacionesAlumno'], // Este es 'observaciones' en la DB
                'mailInstitucional' => $formData['mailInstitucional'],
                'documentacion' => $formData['documentacion'],
                'materiasAdeuda' => $formData['materiasAdeuda'],
                'idFamilia' => $formData['idFamilia']
            ]);

            if ($idAlumno === false) {
                throw new Exception("Error al insertar en la tabla alumnosterciario.");
            }

            $conn->commit();
            $message = "Alumno insertado correctamente.";
            $message_type = 'success';
            // Redirigir a la misma página en modo edición del alumno recién creado
            $_SESSION['message_legajo'] = ['text' => $message, 'type' => $message_type];
            header('Location: legajoAlu.php?idAlumno=' . $idAlumno);
            exit;

        } else { // mode == 'edit'
            // ACTUALIZAR persona
            $personaUpdated = updatePersona($conn, $idPersona, [
                'apellido' => $formData['apellido'],
                'nombre' => $formData['nombre'],
                'dni' => $formData['dni'],
                'sexo' => $formData['sexo'],
                'fechaNacimiento' => $formData['fechaNacimiento'],
                'nacionalidadNacimiento' => $formData['nacionalidadNacimiento'],
                'provinciaNacimiento' => $formData['provinciaNacimiento'],
                'localidadNacimiento' => $formData['localidadNacimiento'],
                'email' => $formData['email'],
                'telefono' => $formData['telefono'],
                'celular' => $formData['celular'],
                'cuilPre' => $formData['cuilPre'],
                'cuilPost' => $formData['cuilPost'],
                'telefonoEmergencia' => $formData['telefonoEmergencia'],
                'domicilio' => $formData['domicilio'], // Este es 'direccion' en la DB
                'cp' => $formData['cp'],             // Este es 'codigoPostal' en la DB
                'fotoURL' => $formData['fotoURL']    // Este es 'FotoCarnet' en la DB
            ]);

             if ($personaUpdated === false) {
                throw new Exception("Error al actualizar la tabla persona.");
            }

            // ACTUALIZAR alumnosterciario
            $alumnoUpdated = updateAlumnoTerciario($conn, $idPersona, [
                'vivePadre' => $formData['vivePadre'],
                'viveMadre' => $formData['viveMadre'],
                'egresado' => $formData['egresado'],
                'retiroBiblioteca' => $formData['retiroBiblioteca'],
                'observacionesAlumno' => $formData['observacionesAlumno'], // Este es 'observaciones' en la DB
                'mailInstitucional' => $formData['mailInstitucional'],
                'documentacion' => $formData['documentacion'],
                'materiasAdeuda' => $formData['materiasAdeuda'], // Este es 'materiasAdeuda' en la DB
                'idFamilia' => $formData['idFamilia']
            ]);

            if ($alumnoUpdated === false) {
                throw new Exception("Error al actualizar la tabla alumnosterciario.");
            }

            $conn->commit();
            $message = "Alumno actualizado correctamente.";
            $message_type = 'success';
            $_SESSION['message_legajo'] = ['text' => $message, 'type' => $message_type];
            header('Location: legajoAlu.php?idAlumno=' . $idAlumno); // Mantener en modo edición
            exit;
        }

    } catch (Throwable $e) {
        $conn->rollback();
        $message = "Error al guardar el alumno: " . $e->getMessage();
        $message_type = 'danger';
        // En caso de error, volvemos a poblar $alumnoData con lo que venía del POST
        $alumnoData = array_merge($alumnoData, $formData);
    }
}

end_post_processing: // Etiqueta para saltar aquí en caso de validación fallida sin error de base de datos
// Recuperar mensaje de sesión si existe
if (isset($_SESSION['message_legajo'])) {
    $message = $_SESSION['message_legajo']['text'];
    $message_type = $_SESSION['message_legajo']['type'];
    unset($_SESSION['message_legajo']);
}

// Ruta de la foto para mostrar en el formulario
// La fotoURL en alumnoData viene de p.FotoCarnet y ya debería tener la ruta completa relativa a la raíz del proyecto (ej: 'fotosPersonas/xyz.jpg')
$fotoDisplayPath = !empty($alumnoData['fotoURL']) && file_exists('../' . $alumnoData['fotoURL']) ? '../' . $alumnoData['fotoURL'] : '../img/placeholder.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Legajo Alumno - Secretaría</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .cuil-display {
      font-weight: bold;
      color: #007bff;
    }
  </style>
</head>
<body>

<!-- CRÍTICO: Inyecta window.usuarioActual aquí, justo después de abrir <body> -->
<script>
    window.usuarioActual = "<?php echo htmlspecialchars($_SESSION['active_user_identifier'] ?? 'null'); ?>";
    console.log("INJECTED window.usuarioActual (en legajoAlu):", window.usuarioActual);
</script>

<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="buscarAlumno.php">Buscar Alumno</a></li>
      <li class="breadcrumb-item active"><?php echo ($mode == 'edit' ? 'Editar' : 'Nuevo'); ?> Alumno</li>
    </ol>

    <?php if ($message): ?>
      <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card padding col-12">
      <h5><?php echo ($mode == 'edit' ? 'Editar Datos del Alumno' : 'Ingresar Nuevo Alumno'); ?></h5>
      <br>

      <form method="POST" action="legajoAlu.php<?php echo ($mode == 'edit' ? '?idAlumno=' . htmlspecialchars($idAlumno) : ''); ?>" enctype="multipart/form-data">
        <!-- Campos Ocultos para control -->
        <input type="hidden" name="idAlumno" value="<?php echo htmlspecialchars($idAlumno ?? ''); ?>">
        <input type="hidden" name="idPersona" value="<?php echo htmlspecialchars($alumnoData['idPersona'] ?? ''); ?>">
        <input type="hidden" name="mode" value="<?php echo htmlspecialchars($mode); ?>">

        <!-- Datos de Persona -->
        <fieldset class="mb-4 p-3 border rounded">
          <legend class="float-none w-auto px-2">Datos Personales</legend>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($alumnoData['apellido'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($alumnoData['nombre'] ?? ''); ?>" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="dni" class="form-label">DNI <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="dni" name="dni" value="<?php echo htmlspecialchars($alumnoData['dni'] ?? ''); ?>" required oninput="checkDniUnique(); calculateCuil()">
              <div id="dniFeedback" class="form-text"></div>
            </div>
            <div class="col-md-6">
              <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
              <select class="form-select" id="sexo" name="sexo" required onchange="calculateCuil()">
                <option value="">Seleccione...</option>
                <option value="F" <?php echo (($alumnoData['sexo'] ?? '') == 'F' ? 'selected' : ''); ?>>Femenino</option>
                <option value="M" <?php echo (($alumnoData['sexo'] ?? '') == 'M' ? 'selected' : ''); ?>>Masculino</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
                <label for="fechaNacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="fechaNacimiento" name="fechaNacimiento" value="<?php echo htmlspecialchars($alumnoData['fechaNacimiento'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email (recuperación clave)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($alumnoData['email'] ?? ''); ?>">
            </div>
          </div>

          <div class="row mb-3">
             <div class="col-md-4">
                <label for="nacionalidadNacimiento" class="form-label">Nacionalidad</label>
                <input type="text" class="form-control" id="nacionalidadNacimiento" name="nacionalidadNacimiento" value="<?php echo htmlspecialchars($alumnoData['nacionalidadNacimiento'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label for="provinciaNacimiento" class="form-label">Provincia de Nacimiento</label>
                <input type="text" class="form-control" id="provinciaNacimiento" name="provinciaNacimiento" value="<?php echo htmlspecialchars($alumnoData['provinciaNacimiento'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label for="localidadNacimiento" class="form-label">Localidad de Nacimiento</label>
                <input type="text" class="form-control" id="localidadNacimiento" name="localidadNacimiento" value="<?php echo htmlspecialchars($alumnoData['localidadNacimiento'] ?? ''); ?>">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-8">
                <label for="domicilio" class="form-label">Domicilio</label>
                <input type="text" class="form-control" id="domicilio" name="domicilio" value="<?php echo htmlspecialchars($alumnoData['domicilio'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label for="cp" class="form-label">Código Postal</label>
                <input type="text" class="form-control" id="cp" name="cp" value="<?php echo htmlspecialchars($alumnoData['cp'] ?? ''); ?>">
            </div>
          </div>

          <div class="row mb-3">
             <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono Fijo</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($alumnoData['telefono'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" class="form-control" id="celular" name="celular" value="<?php echo htmlspecialchars($alumnoData['celular'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                 <label for="telefonoEmergencia" class="form-label">Teléfono de Emergencia</label>
                 <input type="text" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia" value="<?php echo htmlspecialchars($alumnoData['telefonoEmergencia'] ?? ''); ?>">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="vivePadre" name="vivePadre" value="1" <?php echo (($alumnoData['vivePadre'] ?? 0) == 1 ? 'checked' : ''); ?>>
                <label class="form-check-label" for="vivePadre">Vive Padre</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="viveMadre" name="viveMadre" value="1" <?php echo (($alumnoData['viveMadre'] ?? 0) == 1 ? 'checked' : ''); ?>>
                <label class="form-check-label" for="viveMadre">Vive Madre</label>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label for="cuil" class="form-label">CUIL:</label>
              <span id="cuilDisplay" class="cuil-display">
                <?php
                if (!empty($alumnoData['dni']) && !empty($alumnoData['sexo'])) {
                    echo htmlspecialchars($alumnoData['cuilPre'] . '-' . $alumnoData['dni'] . '-' . $alumnoData['cuilPost']);
                } else {
                    echo "Calcular al ingresar DNI y Sexo";
                }
                ?>
              </span>
            </div>
          </div>

          <!-- Sección de Foto Carnet -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="fotoCarnet" class="form-label">Foto Carnet:</label>
              <input class="form-control" type="file" id="fotoCarnet" name="fotoCarnet" accept="image/jpeg, image/png">
            </div>
            <div class="col-md-6">
                <?php if (!empty($fotoDisplayPath) && $fotoDisplayPath != '../img/placeholder.png'): // Solo muestra la imagen si hay una ruta válida y no es el placeholder ?>
                    <img src="<?php echo htmlspecialchars($fotoDisplayPath); ?>" alt="Foto del Alumno" class="img-thumbnail mt-2" style="max-width: 150px; display: block;">
                <?php else: ?>
                    <img src="../img/placeholder.png" alt="Sin Foto" class="img-thumbnail mt-2" style="max-width: 150px; display: block;">
                <?php endif; ?>
            </div>
          </div>
        
        </fieldset>

        <!-- Student Data -->
        <fieldset class="mb-4 p-3 border rounded">
          <legend class="float-none w-auto px-2">Datos de Alumno</legend>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="egresado" name="egresado" value="1" <?php echo (($alumnoData['egresado'] ?? 0) == 1 ? 'checked' : ''); ?>>
                <label class="form-check-label" for="egresado">Egresado</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="retiroBiblioteca" name="retiroBiblioteca" value="1" <?php echo (($alumnoData['retiroBiblioteca'] ?? 0) == 1 ? 'checked' : ''); ?>>
                <label class="form-check-label" for="retiroBiblioteca">Retira Material de Biblioteca</label>
              </div>
            </div>
          </div>

           <div class="mb-3 col-md-3">
              <label for="mailInstitucional" class="form-label">Mail Institucional</label>
              <input type="email" class="form-control" id="mailInstitucional" name="mailInstitucional" value="<?php echo htmlspecialchars($alumnoData['mailInstitucional'] ?? ''); ?>">
           </div>
           
           <div class="row mb-3">
            <h6>Documentación presentada:</h6>
            <?php
            // Obtener la cadena de flags de documentación, o un string de 7 ceros si no existe
            $doc_flags_db = $alumnoData['documentacion'] ?? '0000000';
            // Asegurarse de que la cadena tenga al menos 7 caracteres
            $doc_flags = str_pad($doc_flags_db, 7, '0', STR_PAD_RIGHT); 

            foreach ($documentacion_labels as $index => $label) {
                // Verificar si el bit correspondiente es '1'
                $checked = (isset($doc_flags[$index]) && $doc_flags[$index] == '1') ? 'checked' : '';
                echo '<div class="col-md-6 col-lg-4">'; // Usar columnas para organizar en la grilla
                echo '<div class="form-check">';
                echo '<input class="form-check-input" type="checkbox" id="documentacion_flag_' . $index . '" name="documentacion_flag_' . $index . '" value="1" ' . $checked . '>';
                echo '<label class="form-check-label" for="documentacion_flag_' . $index . '">' . htmlspecialchars($label) . '</label>';
                echo '</div>';
                echo '</div>';
            }
            ?>
           </div>

           <div class="mb-3">
              <label for="materiasAdeuda" class="form-label">Materias Adeudadas (Opcional)</label>
              <textarea class="form-control" id="materiasAdeuda" name="materiasAdeuda" rows="3"><?php echo htmlspecialchars($alumnoData['materiasAdeuda'] ?? ''); ?></textarea>
           </div>


           <div class="mb-3">
              <label for="observacionesAlumno" class="form-label">Observaciones</label>
              <textarea class="form-control" id="observacionesAlumno" name="observacionesAlumno" rows="3"><?php echo htmlspecialchars($alumnoData['observacionesAlumno'] ?? ''); ?></textarea>
           </div>

           <input type="hidden" name="idFamilia" value="<?php echo htmlspecialchars($alumnoData['idFamilia'] ?? 0); ?>">


        </fieldset>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      </form>

    </div>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>

<script>
var mode = "<?php echo htmlspecialchars($mode); ?>"; // 'new' o 'edit'
var currentIdPersona = "<?php echo htmlspecialchars($alumnoData['idPersona'] ?? ''); ?>"; // ID de Persona actual en modo edición
var originalDni = "<?php echo htmlspecialchars($alumnoData['dni'] ?? ''); ?>"; // DNI original para verificar cambios en modo edición

// Función para calcular CUIL
function calculateCuil() {
    var dni = $('#dni').val().trim();
    var sexo = $('#sexo').val();
    var cuilDisplay = $('#cuilDisplay');

    if (dni.length < 7 || !sexo) { // El DNI debe tener al menos 7 dígitos para el cálculo
        cuilDisplay.text("Calcular al ingresar DNI y Sexo");
        return;
    }

    var base = 0;
    if (sexo === 'M') {
        base = 20;
    } else if (sexo === 'F') {
        base = 27;
    } else {
        cuilDisplay.text("Sexo inválido para CUIL.");
        return;
    }

    var cuil = String(base) + String(dni);
    var checksum = calculateCuilChecksum(cuil);

    // Update display span
    cuilDisplay.text(base + '-' + dni + '-' + checksum);
}

// Algoritmo de cálculo del dígito verificador del CUIL/CUIT (Módulo 11)
function calculateCuilChecksum(cuilWithoutChecksum) {
    var multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    var sum = 0;
    for (var i = 0; i < 10; i++) {
         if (i >= cuilWithoutChecksum.length) { // Asegurarse de no exceder la longitud de la cadena
            return ''; // O manejar el error de otra forma, por ejemplo, lanzar una excepción
        }
        sum += parseInt(cuilWithoutChecksum[i], 10) * multipliers[i];
    }

    var remainder = sum % 11;
    var checksum = 11 - remainder;

    if (checksum === 11) {
        checksum = 0;
    } else if (checksum === 10) {
        checksum = 9; // Fallback para este contexto, en la práctica los CUILs con dígito verificador 10 suelen usar prefijos 23/24
    }
    return checksum;
}


// Función para verificar si el DNI ya existe (solo para modo "new" o si el DNI cambia en "edit")
function checkDniUnique() {
    var dni = $('#dni').val().trim();
    var dniFeedback = $('#dniFeedback');
    dniFeedback.text(''); // Limpiar mensaje anterior

    // No verificar si es el DNI original en modo edición
    if (mode === 'edit' && dni === originalDni) {
        dniFeedback.text(''); // No mostrar mensaje, es el DNI original
        $('#dni').get(0).setCustomValidity(''); // Validar el campo
        return;
    }

    if (dni.length >= 7) { // Verificar a partir de una longitud razonable
        $.ajax({
            type: 'POST',
            url: 'legajoAlu.php', // Apunta a este mismo archivo
            data: {
                action: 'check_dni_unique',
                dni: dni,
                idPersonaActual: currentIdPersona // Para excluirlo en edición
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    dniFeedback.text('Este DNI ya existe.').css('color', 'red');
                    $('#dni').get(0).setCustomValidity('Este DNI ya existe.'); // Invalidar el campo
                } else {
                    dniFeedback.text('DNI disponible.').css('color', 'green');
                    $('#dni').get(0).setCustomValidity(''); // Validar el campo
                }
            },
            error: function() {
                dniFeedback.text('Error al verificar DNI.').css('color', 'orange');
            }
        });
    } else {
        dniFeedback.text('El DNI debe tener al menos 7 dígitos.').css('color', 'orange');
        $('#dni').get(0).setCustomValidity('El DNI debe tener al menos 7 dígitos.');
    }
}


$(document).ready(function() {
    // Si estamos en modo edición, calcular CUIL al cargar la página
    if (mode === 'edit') {
        calculateCuil();
    }
    // Para modo nuevo, siempre calcular si se escribe
    $('#dni, #sexo').on('input change', function() {
        calculateCuil();
    });

    // Manejar checkboxes: para los no marcados, enviar explícitamente '0'
    $('form').on('submit', function() {
      // Eliminar cualquier input oculto añadido previamente para evitar duplicados en re-envíos
      // Asegurarse de que solo se remuevan los hidden inputs de fix, y no otros fields hidden
      $('.hidden_checkbox_fix').remove();

      $('input[type="checkbox"]').each(function() {
          var checkboxName = $(this).attr('name');
          // Si el checkbox no está marcado, añadir un input oculto con valor '0'
          if (!this.checked) {
              // Usar un nombre único para el input oculto para evitar conflictos
              $(this).after('<input type="hidden" name="' + checkboxName + '" value="0" class="hidden_checkbox_fix">');
          }
      });
    });
});
</script>

</body>
</html>