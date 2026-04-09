<?php
if (isset($conn) && $conn instanceof mysqli && !function_exists('usuarioTieneAccesoFormularioSecretaria')) {
    require_once __DIR__ . '/consultas.php';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="../secretaria/menusecretaria.php">
            <?php if (isset($_SESSION['logoSE']) && !empty($_SESSION['logoSE'])): ?>
                <img src="<?php echo htmlspecialchars($_SESSION['logoSE']); ?>" alt="Logo SE" width="80" class="d-inline-block align-text-top">
            <?php else: ?>
                logo_SE
            <?php endif; ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                
                <li class="nav-item">
                    <a class="nav-link" href="../secretaria/menusecretaria.php">
                        <i class="bi bi-house-door-fill"></i> Inicio
                    </a>
                </li>

             <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAlumnos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-fill"></i> Alumnos
    </a>
    <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownAlumnos">
        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Datos de alumnos</h6></li>
        <li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=legajo">Legajo / Búsqueda</a></li>
        <li><hr class="dropdown-divider"></li>

        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Inscripciones manuales</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 1)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=matriculacion">Matriculación (Plan/Curso)</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 2)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=inscripcionMateria">Inscripción a Materia</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 7)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=presistema">Registros Presistema</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 9)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=equivalencias">Equivalencias</a></li><?php endif; ?>
        <li><hr class="dropdown-divider"></li>

        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Calificaciones</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 5)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=califxalumno">Por Alumno</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 5)): ?><li><a class="dropdown-item ps-4" href="../secretaria/carga_calif_secretaria.php">Por Materia</a></li><?php endif; ?>
        <li><hr class="dropdown-divider"></li>

        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Asistencias</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 6)): ?><li><a class="dropdown-item ps-4" href="../secretaria/carga_asist_secretaria.php">Carga de Asistencias</a></li><?php endif; ?>
    </ul>
</li>
                
                <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownExamen" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-calendar-check"></i> Examen
    </a>
    <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownExamen">
        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Fechas de examen</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 10)): ?><li><a class="dropdown-item ps-4" href="mesasExamen.php">Mesas de Examen</a></li><?php endif; ?>
        <li><hr class="dropdown-divider"></li>

        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Inscripción examen manual</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 11)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=inscribirExamen">Inscripción Individual</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 11)): ?><li><a class="dropdown-item ps-4" href="inscripcionExamenMasivo.php">Inscripción Masiva</a></li><?php endif; ?>
        <li><hr class="dropdown-divider"></li>

        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Carga e impresión de actas</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 12)): ?><li><a class="dropdown-item ps-4" href="actas.php">Actas</a></li><?php endif; ?>
    </ul>
</li>

                <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownInscWeb" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-cloud-arrow-up"></i> Inscripciones Web
    </a>
    <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownInscWeb">
        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Recepción de Solicitudes</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 70)): ?><li><a class="dropdown-item ps-4" href="solicitudesExamen.php">Examen</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 71)): ?><li><a class="dropdown-item ps-4" href="solicitudesCursado.php">Cursado</a></li><?php endif; ?>
    </ul>
</li>

               <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownPersonal" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-vcard"></i> Personal
    </a>
    <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownPersonal">
        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Datos de Personal</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 20)): ?><li><a class="dropdown-item ps-4" href="buscarPersonal.php">Legajos</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 24)): ?><li><a class="dropdown-item ps-4" href="asignarDocenteMateria.php">Asignar docente a materia</a></li><?php endif; ?>
        <li><hr class="dropdown-divider"></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 41)): ?><li><a class="dropdown-item ps-4" href="verClaves.php"><i class="bi bi-key"></i> Consultar claves (web)</a></li><?php endif; ?>
    </ul>
</li>

               <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownListados" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-list-check"></i> Listados
    </a>
    <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownListados">
        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Filtrado</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 3)): ?><li><a class="dropdown-item ps-4" href="listadoPorCurso.php">Listado por Curso</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 3)): ?><li><a class="dropdown-item ps-4" href="listadoPorMateria.php">Listado por Materia</a></li><?php endif; ?>
        <?php if (secretariaMenuMuestraFormulario($conn, 22)): ?><li><a class="dropdown-item ps-4" href="listadoPersonal.php">Listado de Personal</a></li><?php endif; ?>
        
        <li><hr class="dropdown-divider"></li>
        
        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Documentación</h6></li>
        <?php if (secretariaMenuMuestraFormulario($conn, 8)): ?><li><a class="dropdown-item ps-4" href="buscarAlumno.php?origin=certificadoRegular">Certificado Alumno Regular</a></li><?php endif; ?>
    </ul>
</li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownColegio" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building"></i> Instituto
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownColegio">
                        <?php if (secretariaMenuMuestraFormulario($conn, 33)): ?><li><a class="dropdown-item" href="../secretaria/editParametrosWeb.php">Parámetros Web</a></li><?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-dark fw-bold" style="font-size: inherit;"><i class="bi bi-dot"></i> Parámetros</h6></li>
                        <?php if (secretariaMenuMuestraFormulario($conn, 33)): ?><li><a class="dropdown-item ps-4" href="../secretaria/parametrosPlanesEstudio.php">Planes de estudio</a></li><?php endif; ?>
                        <?php if (secretariaMenuMuestraFormulario($conn, 33)): ?><li><a class="dropdown-item ps-4" href="../secretaria/parametrosCursos.php">Cursos</a></li><?php endif; ?>
                        <?php if ((int)($_SESSION['sec_tipoPermiso'] ?? 0) === 7): ?>
                            <li><a class="dropdown-item" href="../secretaria/usuarios.php">Usuarios y Permisos</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['sec_nombreUsuario'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                        <li><a class="dropdown-item" href="#">Cambiar contraseña</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../funciones/cerrarsesion.php">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
