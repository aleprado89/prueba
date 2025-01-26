
<nav class="navbar navbar-expand-lg " data-bs-theme="light">
    <div class="container ">
      <div class="d-flex flex-column align-items-center centrarlogomenu ">
        <img src="<?php echo $_SESSION['logo']; ?>" class="est-logo img-fluid mx-auto" alt="logo">
      </div>
      <button class="navbar-toggler margenbottom ms-auto" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarColor01">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link " href="menudocentes.php">Inicio

            </a>
          </li>
          <li class="nav-item active">
          <a class="nav-link" href="#" onclick="cargarParametro('carga_calif.php')">Carga de calificaciones</a>              <span class="visually-hidden">(current)</span>
            </a>

          </li>
          <li class="nav-item">
          <a class="nav-link" href="#" onclick="cargarParametro('carga_asist.php')">Carga de asistencias</a>          </li>
          <li class="nav-item">
            <a class="nav-link" href="examenes_planes.php">Carga de actas</a>
          </li>
        </ul>
        <ul class="ms-auto" style="list-style-type: none;">
          <li class="nav-item">
            <a class="nav-link" href="../funciones/cerrarsesion.php" style="display: flex; flex-direction: column; align-items: center;"
              onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i
                class="bi bi-power"></i>Cerrar Sesión</a>
          </li>

        </ul>
      </div>
    </div>
  </nav>
  <script> //cargo el parametro en el hipervinculo
    function cargarParametro(parametro) {
  // Establece la cookie con el valor de la variable de sesión
  document.cookie = "parametro=" + parametro + "; expires=" + new Date(Date.now() + 3600000).toUTCString();

  $.ajax({
    type: 'POST',
    url: 'materiaxdocente.php',
    data: {parametro: parametro},
    success: function(data) {
      console.log('Respuesta del servidor:', data);
      window.location.href = 'materiaxdocente.php';
    }
  });
}</script>