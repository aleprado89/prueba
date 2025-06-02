(function () {
  const tiempoInactividad = 15 * 60 * 1000; // 15 minutos
  let temporizador;
  const claveSesion = 'usuario_sesion_activa';

  const usuarioActual = window.usuarioActual || "";

  function cerrarSesionPorInactividad() {
    alert("Sesión cerrada por inactividad");
    window.location.href = "../inicio/login.php?inactividad=1";
  }

  function reiniciarTemporizador() {
    clearTimeout(temporizador);
    temporizador = setTimeout(cerrarSesionPorInactividad, tiempoInactividad);
  }

  window.onload = reiniciarTemporizador;
  document.onmousemove = reiniciarTemporizador;
  document.onkeypress = reiniciarTemporizador;
  document.onclick = reiniciarTemporizador;
  document.onscroll = reiniciarTemporizador;

  const sesionAnterior = localStorage.getItem(claveSesion);

  // Si el usuario cambió, avisar y salir
  if (sesionAnterior && sesionAnterior !== usuarioActual) {
    alert("Se ha iniciado sesión con otro usuario. Esta pestaña se cerrará.");
    window.location.href = "../inicio/login.php?cambioSesion=1";
  }

  // Actualizar sesión activa en localStorage
  localStorage.setItem(claveSesion, usuarioActual);

  // Escuchar cambios en otras pestañas
  window.addEventListener('storage', function (e) {
    if (e.key === claveSesion && e.newValue !== usuarioActual) {
      alert("Se ha iniciado sesión con otro usuario. Esta pestaña se cerrará.");
      window.location.href = "../inicio/login.php?cambioSesion=1";
    }
  });
})();
