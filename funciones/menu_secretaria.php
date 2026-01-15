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
                <li class="nav-item">
                    <a class="nav-link" href="../secretaria/menusecretaria.php">
                        <i class="bi bi-house-door-fill"></i> Inicio </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAlumnos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill"></i> Alumnos
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownAlumnos">
                        <li><h6 class="dropdown-header">Gestión de Alumnos</h6></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=legajo">Legajo</a></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=presistema">Registros Presistema</a></li>
                        <li> <a class="dropdown-item" href="buscarAlumno.php?origin=equivalencias">Equivalencias</a></li>                        
                        <li><h6 class="dropdown-header">Inscripciones</h6></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=matriculacion">Matriculación (Plan/Curso)</a></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=inscripcionMateria">Inscripción a Materia</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Calificaciones</h6></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=califxalumno">Calificaciones por Alumno</a></li>
                        <li><a class="dropdown-item" href="../secretaria/carga_calif_secretaria.php">Calificaciones por Materia</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Asistencias</h6></li>
                        <li><a class="dropdown-item" href="../secretaria/carga_asist_secretaria.php">Asistencias</a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownExamen" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-calendar-check"></i> Examen
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownExamen">
                        <li><a class="dropdown-item" href="mesasExamen.php">Mesas de Examen</a></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=inscribirExamen">Inscripción a Examen (individual)</a></li>
                        <li><a class="dropdown-item" href="inscripcionExamenMasivo.php">Inscripción a Examen (Masivo)</a></li>
                        <li><a class="dropdown-item" href="actas.php">Actas</a></li>
                    </ul>
                </li>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Inscripciones Web</a>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="solicitudesExamen.php">Examen</a>
                <a class="dropdown-item" href="solicitudesCursado.php">Cursado</a>

        </div>
</li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownListados" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Listados
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownListados">
                        <li><a class="dropdown-item" href="listadoPorCurso.php">Listado por Curso</a></li>
                        <li><a class="dropdown-item" href="listadoPorMateria.php">Listado por Materia</a></li> 
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="buscarAlumno.php?origin=certificadoRegular">Certificado Alumno Regular</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownColegio" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building"></i> Instituto
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownColegio">
                        <li><a class="dropdown-item" href="../secretaria/editParametrosWeb.php">Parámetros Web</a></li>
                    </ul>
                </li>
               
                </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['sec_nombreUsuario'] ?? 'Usuario'); ?> </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                        <li><a class="dropdown-item" href="#">Cambiar contraseña</a></li> </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="../funciones/cerrarsesion.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<link rel="stylesheet" href="../css/estilos.css">