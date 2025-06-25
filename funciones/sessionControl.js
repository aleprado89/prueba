(function () {
  const tiempoInactividad = 15 * 60 * 1000; // 15 minutos de inactividad
  let temporizador;
  const claveSesion = 'usuario_sesion_activa_global'; // Clave en localStorage

  // 'window.usuarioActual' ya es establecido por PHP en verificarSesion.php
  const usuarioActual = window.usuarioActual || "null"; // Default a "null" si PHP no lo pudo establecer

  // Función para cerrar la sesión (redirecciona a logout.php)
  function cerrarSesion() {
    alert("Sesión finalizada por inactividad o cambio de estado. Serás redirigido al login.");
    // Redirige al logout.php centralizado, que a su vez se encargará de la página de login correcta
    window.location.href = "../secretaria/logout.php?motivo=inactividad";
  }

  // Reinicia el contador de inactividad
  function reiniciarTemporizador() {
    clearTimeout(temporizador);
    temporizador = setTimeout(cerrarSesion, tiempoInactividad);
  }

  // Eventos para detectar actividad del usuario
  window.addEventListener('load', reiniciarTemporizador); // Al cargar la página
  document.addEventListener('mousemove', reiniciarTemporizador);
  document.addEventListener('keypress', reiniciarTemporizador);
  document.addEventListener('click', reiniciarTemporizador);
  document.addEventListener('scroll', reiniciarTemporizador);
  document.addEventListener('touchstart', reiniciarTemporizador, {passive: true}); // Para móviles
  document.addEventListener('touchmove', reiniciarTemporizador, {passive: true}); // Para móviles

  // --- Control de sesión cruzada entre pestañas/ventanas usando localStorage ---
  const sesionActivaLocalStorage = localStorage.getItem(claveSesion);

  // Escenario 1: El usuario actual en esta pestaña NO está logueado, pero localStorage sí tiene una sesión activa (ej: sesión expiró aquí pero está activa en otra pestaña)
  if (usuarioActual === "null" && sesionActivaLocalStorage !== null && sesionActivaLocalStorage !== "null") {
      // alert("Tu sesión ha caducado en esta pestaña o ha sido cerrada externamente. Serás redirigido.");
      localStorage.removeItem(claveSesion); // Limpiar localStorage por consistencia
      window.location.href = "../secretaria/logout.php?motivo=sesion_caducada_local"; // Redirigir vía logout
      return; // Detener la ejecución del script
  }
  // Escenario 2: Hay un usuario logueado en esta pestaña.
  else if (usuarioActual !== "null") {
      // Si localStorage está vacío O difiere del usuario actual (esto ocurre al iniciar sesión o cambiar)
      if (sesionActivaLocalStorage === null || sesionActivaLocalStorage !== usuarioActual) {
          // Si localStorage tenía un usuario, pero es DIFERENTE del que acaba de iniciar sesión en esta pestaña
          if (sesionActivaLocalStorage !== null && sesionActivaLocalStorage !== "null" && sesionActivaLocalStorage !== usuarioActual) {
              alert("Se ha detectado un cambio de usuario en otra pestaña o se ha iniciado sesión con otro usuario. La sesión actual se cerrará.");
              window.location.href = "../secretaria/logout.php?motivo=cambio_usuario_local"; // Redirigir vía logout
              return;
          }
          // Si no hubo un cambio de usuario (solo la primera carga o actualización de sesión), actualizamos localStorage.
          localStorage.setItem(claveSesion, usuarioActual);
          // console.log("localStorage actualizado con usuario:", usuarioActual);
      }
  }

  // Escenario 3: Escuchar cambios en `localStorage` provenientes de otras pestañas/ventanas
  window.addEventListener('storage', function (e) {
    if (e.key === claveSesion) {
      if (e.newValue === "null" || e.newValue === null) {
        // Otra pestaña ha cerrado sesión o la sesión ha expirado globalmente.
        if (usuarioActual !== "null") { // Solo si ESTE usuario estaba logueado
          alert("Tu sesión ha sido cerrada en otra pestaña o ha expirado. Serás redirigido.");
          // Usa window.location.href directamente, no a través de cerrarSesion() si no quieres alert extra
          window.location.href = "../secretaria/logout.php?motivo=cierre_externo";
        }
      } else if (e.newValue !== usuarioActual && usuarioActual !== "null") {
        // Otra pestaña ha iniciado sesión con un usuario DIFERENTE mientras ESTA pestaña tenía un usuario activo.
        alert("Se ha iniciado sesión con otro usuario. Esta pestaña se cerrará.");
        window.location.href = "../secretaria/logout.php?motivo=otro_login_externo";
      }
    }
  });

})();