<?php
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function limpiarTexto($valor) {
    $valor = trim((string)$valor);
    return $valor === '' ? null : $valor;
}

function limpiarFecha($valor) {
    $valor = trim((string)$valor);
    return $valor === '' ? null : $valor;
}

function normalizarTipoTitulo($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '0000';
    }
    if (preg_match('/^[01]{4}$/', $valor)) {
        return $valor;
    }
    return '0000';
}

function tipoTituloDesdePost($post) {
    $checks = isset($post['tipoTitulo']) && is_array($post['tipoTitulo']) ? $post['tipoTitulo'] : [];
    $flags = ['terciario', 'universitario', 'magister', 'doctorado'];
    $valor = '';
    foreach ($flags as $flag) {
        $valor .= in_array($flag, $checks, true) ? '1' : '0';
    }
    return $valor;
}

$legajo = isset($_GET['legajo']) ? (int)$_GET['legajo'] : 0;
$mode = $legajo > 0 ? 'edit' : 'new';

$personalData = [
    'legajo' => null,
    'idPersona' => null,
    'apellido' => '',
    'nombre' => '',
    'dni' => '',
    'sexo' => '',
    'fechaNacimiento' => '',
    'nacionalidadNacimiento' => '',
    'provinciaNacimiento' => '',
    'localidadNacimiento' => '',
    'domicilio' => '',
    'cp' => '',
    'email' => '',
    'telefono' => '',
    'celular' => '',
    'cuilPre' => '',
    'cuilPost' => '',
    'fotoURL' => '',
    'telefonoEmergencia' => '',
    'estadoCivil' => '',
    'tipoCargo' => '',
    'cargo' => '',
    'titulo' => '',
    'tipoTitulo' => '',
    'legJunta' => '',
    'legEscuela' => '',
    'escalafD' => '',
    'escalafE' => '',
    'numReg' => '',
    'apto' => '',
    'certArt28' => '',
    'incapac' => '',
    'actual' => 1,
    'nivel' => 6,
    'fechaBaja' => '',
    'mailInst' => ''
];

if ($mode === 'edit') {
    $loaded = obtenerDatosPersonal($conn, $legajo);
    if (!$loaded) {
        $_SESSION['message_legajo_personal'] = ['text' => 'Legajo de personal no encontrado.', 'type' => 'danger'];
        header('Location: buscarPersonal.php');
        exit;
    }
    $personalData = array_merge($personalData, $loaded);
    $personalData['tipoTitulo'] = normalizarTipoTitulo($personalData['tipoTitulo'] ?? '');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $postMode = $_POST['mode'] ?? $mode;
    $idPersona = isset($_POST['idPersona']) ? (int)$_POST['idPersona'] : 0;
    $legajoPost = isset($_POST['legajo']) ? (int)$_POST['legajo'] : 0;

    $form = [
        'apellido' => trim($_POST['apellido'] ?? ''),
        'nombre' => trim($_POST['nombre'] ?? ''),
        'dni' => trim($_POST['dni'] ?? ''),
        'sexo' => trim($_POST['sexo'] ?? ''),
        'fechaNacimiento' => limpiarFecha($_POST['fechaNacimiento'] ?? null),
        'nacionalidadNacimiento' => limpiarTexto($_POST['nacionalidadNacimiento'] ?? null),
        'provinciaNacimiento' => limpiarTexto($_POST['provinciaNacimiento'] ?? null),
        'localidadNacimiento' => limpiarTexto($_POST['localidadNacimiento'] ?? null),
        'domicilio' => limpiarTexto($_POST['domicilio'] ?? null),
        'cp' => limpiarTexto($_POST['cp'] ?? null),
        'email' => limpiarTexto($_POST['email'] ?? null),
        'telefono' => limpiarTexto($_POST['telefono'] ?? null),
        'celular' => limpiarTexto($_POST['celular'] ?? null),
        'cuilPre' => limpiarTexto($_POST['cuilPre'] ?? null),
        'cuilPost' => limpiarTexto($_POST['cuilPost'] ?? null),
        'fotoURL' => limpiarTexto($_POST['fotoURL'] ?? null),
        'telefonoEmergencia' => limpiarTexto($_POST['telefonoEmergencia'] ?? null),
        'estadoCivil' => limpiarTexto($_POST['estadoCivil'] ?? null),
        'tipoCargo' => limpiarTexto($_POST['tipoCargo'] ?? null),
        'cargo' => limpiarTexto($_POST['cargo'] ?? null),
        'titulo' => limpiarTexto($_POST['titulo'] ?? null),
        'tipoTitulo' => tipoTituloDesdePost($_POST),
        'legJunta' => limpiarTexto($_POST['legJunta'] ?? null),
        'legEscuela' => limpiarTexto($_POST['legEscuela'] ?? null),
        'escalafD' => limpiarFecha($_POST['escalafD'] ?? null),
        'escalafE' => limpiarFecha($_POST['escalafE'] ?? null),
        'numReg' => limpiarTexto($_POST['numReg'] ?? null),
        'apto' => limpiarFecha($_POST['apto'] ?? null),
        'certArt28' => limpiarTexto($_POST['certArt28'] ?? null),
        'incapac' => limpiarTexto($_POST['incapac'] ?? null),
        'actual' => isset($_POST['actual']) ? 1 : 0,
        'nivel' => isset($_POST['nivel']) && $_POST['nivel'] !== '' ? (int)$_POST['nivel'] : 6,
        'fechaBaja' => limpiarFecha($_POST['fechaBaja'] ?? null),
        'mailInst' => limpiarTexto($_POST['mailInst'] ?? null)
    ];

    if ($form['actual'] === 1) {
        $form['fechaBaja'] = null;
    } elseif ($form['fechaBaja'] === null) {
        $form['fechaBaja'] = date('Y-m-d');
    }

    if ($form['apellido'] === '' || $form['nombre'] === '' || $form['dni'] === '') {
        $_SESSION['message_legajo_personal'] = ['text' => 'Apellido, nombre y DNI son obligatorios.', 'type' => 'danger'];
        $personalData = array_merge($personalData, $form);
    } elseif (dniExiste($conn, $form['dni'], $idPersona > 0 ? $idPersona : null)) {
        $_SESSION['message_legajo_personal'] = ['text' => 'El DNI ingresado ya existe.', 'type' => 'danger'];
        $personalData = array_merge($personalData, $form);
    } else {
        $conn->begin_transaction();
        try {
            if ($postMode === 'new') {
                $nuevoIdPersona = insertPersona($conn, [
                    'apellido' => $form['apellido'],
                    'nombre' => $form['nombre'],
                    'dni' => $form['dni'],
                    'sexo' => $form['sexo'],
                    'fechaNacimiento' => $form['fechaNacimiento'],
                    'nacionalidadNacimiento' => $form['nacionalidadNacimiento'],
                    'provinciaNacimiento' => $form['provinciaNacimiento'],
                    'localidadNacimiento' => $form['localidadNacimiento'],
                    'email' => $form['email'],
                    'telefono' => $form['telefono'],
                    'celular' => $form['celular'],
                    'cuilPre' => $form['cuilPre'],
                    'cuilPost' => $form['cuilPost'],
                    'telefonoEmergencia' => $form['telefonoEmergencia'],
                    'domicilio' => $form['domicilio'],
                    'cp' => $form['cp'],
                    'fotoURL' => $form['fotoURL']
                ]);

                if ($nuevoIdPersona === false) {
                    throw new Exception('No se pudo insertar persona.');
                }

                $nuevoLegajo = insertPersonal($conn, $nuevoIdPersona, $form);
                if ($nuevoLegajo === false) {
                    throw new Exception('No se pudo insertar personal.');
                }

                $conn->commit();
                $_SESSION['message_legajo_personal'] = ['text' => 'Legajo creado correctamente.', 'type' => 'success'];
                header('Location: legajoPersonal.php?legajo=' . $nuevoLegajo . '&mode=edit');
                exit;
            }

            $okPersona = updatePersona($conn, $idPersona, [
                'apellido' => $form['apellido'],
                'nombre' => $form['nombre'],
                'dni' => $form['dni'],
                'sexo' => $form['sexo'],
                'fechaNacimiento' => $form['fechaNacimiento'],
                'nacionalidadNacimiento' => $form['nacionalidadNacimiento'],
                'provinciaNacimiento' => $form['provinciaNacimiento'],
                'localidadNacimiento' => $form['localidadNacimiento'],
                'email' => $form['email'],
                'telefono' => $form['telefono'],
                'celular' => $form['celular'],
                'cuilPre' => $form['cuilPre'],
                'cuilPost' => $form['cuilPost'],
                'telefonoEmergencia' => $form['telefonoEmergencia'],
                'domicilio' => $form['domicilio'],
                'cp' => $form['cp'],
                'fotoURL' => $form['fotoURL']
            ]);

            if ($okPersona === false) {
                throw new Exception('No se pudo actualizar persona.');
            }

            $okPersonal = updatePersonal($conn, $legajoPost, $form);
            if ($okPersonal === false) {
                throw new Exception('No se pudo actualizar personal.');
            }

            $conn->commit();
            $_SESSION['message_legajo_personal'] = ['text' => 'Legajo actualizado correctamente.', 'type' => 'success'];
            header('Location: legajoPersonal.php?legajo=' . $legajoPost . '&mode=edit');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message_legajo_personal'] = ['text' => 'Error al guardar: ' . $e->getMessage(), 'type' => 'danger'];
            $personalData = array_merge($personalData, $form);
        }
    }
}

$message = '';
$message_type = '';
if (isset($_SESSION['message_legajo_personal'])) {
    $message = $_SESSION['message_legajo_personal']['text'];
    $message_type = $_SESSION['message_legajo_personal']['type'];
    unset($_SESSION['message_legajo_personal']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'new' ? 'Nuevo legajo de personal' : 'Legajo de personal'; ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
    <br>
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="buscarPersonal.php">Personal</a></li>
            <li class="breadcrumb-item active"><?php echo $mode === 'new' ? 'Nuevo legajo' : 'Editar legajo'; ?></li>
        </ol>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>
                    <?php if ($mode === 'new'): ?>
                        Nuevo legajo de personal
                    <?php else: ?>
                        Datos de personal
                    <?php endif; ?>
                </h5>
                <a href="buscarPersonal.php" class="btn btn-outline-secondary text-dark">Volver</a>
            </div>

            <form method="POST" action="legajoPersonal.php<?php echo $mode === 'edit' ? '?legajo=' . urlencode((string)$personalData['legajo']) . '&mode=edit' : '?mode=new'; ?>">
                <input type="hidden" name="mode" value="<?php echo htmlspecialchars($mode); ?>">
                <input type="hidden" name="idPersona" value="<?php echo htmlspecialchars((string)($personalData['idPersona'] ?? '')); ?>">
                <input type="hidden" name="legajo" value="<?php echo htmlspecialchars((string)($personalData['legajo'] ?? '')); ?>">
                <input type="hidden" name="fotoURL" value="<?php echo htmlspecialchars((string)($personalData['fotoURL'] ?? '')); ?>">

                <fieldset class="border p-3 mb-4">
                    <legend class="float-none w-auto px-2">Datos personales</legend>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Estado civil</label>
                            <input type="text" class="form-control" name="estadoCivil" value="<?php echo htmlspecialchars((string)$personalData['estadoCivil']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-control" name="apellido" required value="<?php echo htmlspecialchars((string)$personalData['apellido']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" required value="<?php echo htmlspecialchars((string)$personalData['nombre']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">DNI *</label>
                            <input type="text" class="form-control" name="dni" required value="<?php echo htmlspecialchars((string)$personalData['dni']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sexo</label>
                            <select class="form-select" name="sexo">
                                <option value="" <?php echo empty($personalData['sexo']) ? 'selected' : ''; ?>>Seleccionar</option>
                                <option value="M" <?php echo $personalData['sexo'] === 'M' ? 'selected' : ''; ?>>M</option>
                                <option value="F" <?php echo $personalData['sexo'] === 'F' ? 'selected' : ''; ?>>F</option>
                                <option value="O" <?php echo $personalData['sexo'] === 'O' ? 'selected' : ''; ?>>O</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha nacimiento</label>
                            <input type="date" class="form-control" name="fechaNacimiento" value="<?php echo htmlspecialchars((string)$personalData['fechaNacimiento']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CUIL Pre</label>
                            <input type="text" class="form-control" name="cuilPre" value="<?php echo htmlspecialchars((string)$personalData['cuilPre']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CUIL Post</label>
                            <input type="text" class="form-control" name="cuilPost" value="<?php echo htmlspecialchars((string)$personalData['cuilPost']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <input type="text" class="form-control" name="nacionalidadNacimiento" value="<?php echo htmlspecialchars((string)$personalData['nacionalidadNacimiento']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Provincia</label>
                            <input type="text" class="form-control" name="provinciaNacimiento" value="<?php echo htmlspecialchars((string)$personalData['provinciaNacimiento']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Localidad</label>
                            <input type="text" class="form-control" name="localidadNacimiento" value="<?php echo htmlspecialchars((string)$personalData['localidadNacimiento']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Domicilio</label>
                            <input type="text" class="form-control" name="domicilio" value="<?php echo htmlspecialchars((string)$personalData['domicilio']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">CP</label>
                            <input type="text" class="form-control" name="cp" value="<?php echo htmlspecialchars((string)$personalData['cp']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars((string)$personalData['email']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars((string)$personalData['telefono']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Celular</label>
                            <input type="text" class="form-control" name="celular" value="<?php echo htmlspecialchars((string)$personalData['celular']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono emergencia</label>
                            <input type="text" class="form-control" name="telefonoEmergencia" value="<?php echo htmlspecialchars((string)$personalData['telefonoEmergencia']); ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border p-3 mb-4">
                    <legend class="float-none w-auto px-2">Datos laborales</legend>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tipo cargo</label>
                            <select class="form-select" name="tipoCargo">
                                <option value="" <?php echo empty($personalData['tipoCargo']) ? 'selected' : ''; ?>>Seleccionar</option>
                                <option value="Profesor" <?php echo ($personalData['tipoCargo'] ?? '') === 'Profesor' ? 'selected' : ''; ?>>Profesor</option>
                                <option value="Personal Docente" <?php echo ($personalData['tipoCargo'] ?? '') === 'Personal Docente' ? 'selected' : ''; ?>>Personal Docente</option>
                                <option value="Personal No Docente" <?php echo ($personalData['tipoCargo'] ?? '') === 'Personal No Docente' ? 'selected' : ''; ?>>Personal No Docente</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cargo</label>
                            <input type="text" class="form-control" name="cargo" value="<?php echo htmlspecialchars((string)$personalData['cargo']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Titulo</label>
                            <input type="text" class="form-control" name="titulo" value="<?php echo htmlspecialchars((string)$personalData['titulo']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo titulo</label>
                            <?php $tipoTituloValor = normalizarTipoTitulo($personalData['tipoTitulo'] ?? '0000'); ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipoTituloTerciario" name="tipoTitulo[]" value="terciario" <?php echo (isset($tipoTituloValor[0]) && $tipoTituloValor[0] === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tipoTituloTerciario">Terciario</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipoTituloUniversitario" name="tipoTitulo[]" value="universitario" <?php echo (isset($tipoTituloValor[1]) && $tipoTituloValor[1] === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tipoTituloUniversitario">Universitario</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipoTituloMagister" name="tipoTitulo[]" value="magister" <?php echo (isset($tipoTituloValor[2]) && $tipoTituloValor[2] === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tipoTituloMagister">Magister</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipoTituloDoctorado" name="tipoTitulo[]" value="doctorado" <?php echo (isset($tipoTituloValor[3]) && $tipoTituloValor[3] === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tipoTituloDoctorado">Doctorado</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mail institucional</label>
                            <input type="email" class="form-control" name="mailInst" value="<?php echo htmlspecialchars((string)$personalData['mailInst']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Leg. Junta</label>
                            <input type="text" class="form-control" name="legJunta" value="<?php echo htmlspecialchars((string)$personalData['legJunta']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Leg. Escuela</label>
                            <input type="text" class="form-control" name="legEscuela" value="<?php echo htmlspecialchars((string)$personalData['legEscuela']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Escalafon D</label>
                            <input type="date" class="form-control" name="escalafD" value="<?php echo htmlspecialchars((string)$personalData['escalafD']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Escalafon E</label>
                            <input type="date" class="form-control" name="escalafE" value="<?php echo htmlspecialchars((string)$personalData['escalafE']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Num. Registro</label>
                            <input type="text" class="form-control" name="numReg" value="<?php echo htmlspecialchars((string)$personalData['numReg']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Apto</label>
                            <input type="date" class="form-control" name="apto" value="<?php echo htmlspecialchars((string)$personalData['apto']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cert. Art. 28</label>
                            <input type="text" class="form-control" name="certArt28" value="<?php echo htmlspecialchars((string)$personalData['certArt28']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Incapacidad</label>
                            <input type="text" class="form-control" name="incapac" value="<?php echo htmlspecialchars((string)$personalData['incapac']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha baja</label>
                            <input type="date" class="form-control" name="fechaBaja" value="<?php echo htmlspecialchars((string)$personalData['fechaBaja']); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="actual" name="actual" value="1" <?php echo ((int)$personalData['actual'] === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="actual">
                                    Activo
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy"></i> Guardar
                    </button>
                    <a href="buscarPersonal.php" class="btn btn-outline-secondary text-dark">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../funciones/footer.html'; ?>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>
</body>
</html>
