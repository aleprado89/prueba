<!-- Barra de Navegación Superior -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <?php if (isset($_SESSION['logoSE']) && !empty($_SESSION['logoSE'])): ?>
                <a href="../secretaria/menusecretaria.php"><img src="<?php echo htmlspecialchars($_SESSION['logoSE']); ?>" alt="Logo SE" width="80" class="d-inline-block align-text-top"></a>
            <?php else: ?>
                logo_SE
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Nuevo Menú Inicio -->
                <li class="nav-item">
                    <a class="nav-link" href="../secretaria/menusecretaria.php">
                        <i class="bi bi-house-door-fill"></i> Inicio <!-- Icono de casita para Inicio -->
                    </a>
                </li>

                <!-- Menú Alumnos -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAlumnos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill"></i> Alumnos
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownAlumnos">
                        <li><h6 class="dropdown-header">Gestión de Alumnos</h6></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=legajo">Legajo</a></li>
                        <li><h6 class="dropdown-header">Inscripciones</h6></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=matriculacion">Matriculación (Plan/Curso)</a></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=inscripcionMateria">Inscripción a Materia</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Calificaciones</h6></li>
                        <li><a class="dropdown-item" href="../secretaria/carga_calif_secre_xalu.php">Calificaciones por Alumno</a></li>
                        <li><a class="dropdown-item" href="../secretaria/carga_calif_secretaria.php">Calificaciones por Materia</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Asistencias</h6></li>
                        <li><a class="dropdown-item" href="../secretaria/carga_asist_secretaria.php">Asistencias</a></li>
                    </ul>
                </li>
                
                <!-- Menú Colegio -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownColegio" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building"></i> Colegio
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownColegio">
                        <li><a class="dropdown-item" href="../secretaria/editParametrosWeb.php">Parámetros Web</a></li>
                    </ul>
                </li>
               
                <!-- Menú Funciones Web -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFuncWeb" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-globe"></i> Func. web
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownFuncWeb">
                        <li><h6 class="dropdown-header">Solicitudes de Inscripción</h6></li>
                        <li><a class="dropdown-item" href="../secretaria/solicitudExam.php">Examen</a></li>
                        <li><a class="dropdown-item" href="../secretaria/solicitudCurs.php">Cursado</a></li>
                    </ul>
                </li>
                
            </ul>
            <ul class="navbar-nav ms-auto">
                <!-- Nuevo item de menú para el usuario -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i> <!-- Ícono de usuario -->
                        <?php echo htmlspecialchars($_SESSION['sec_nombreUsuario'] ?? 'Usuario'); ?> <!-- Muestra el nombre de usuario -->
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                        <li><a class="dropdown-item" href="#">Cambiar contraseña</a></li> <!-- Opción de cambiar contraseña -->
                        <!-- Otros ítems relacionados con el usuario pueden ir aquí -->
                    </ul>
                </li>
                <!-- Fin del nuevo item de menú para el usuario -->

                <li class="nav-item">
                    <a class="nav-link" href="../funciones/cerrarsesion.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<link rel="stylesheet" href="../css/estilos.css">