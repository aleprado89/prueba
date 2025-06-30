(function () {
  const tiempoInactividad = 15 * 60 * 1000; // 15 minutos de inactividad
  let temporizador;
  const claveSesion = 'usuario_sesion_activa_global'; // Clave en localStorage

  const usuarioActual = window.usuarioActual || "null";

  function cerrarSesion() {
    alert("Sesión finalizada por inactividad o cambio de estado. Serás redirigido al login.");
    // REDIRECCIONAR A cerrarsesion.php (el script general de logout)
    window.location.href = "../funciones/cerrarsesion.php?motivo=inactividad";
  }

  function reiniciarTemporizador() {
    clearTimeout(temporizador);
    temporizador = setTimeout(cerrarSesion, tiempoInactividad);
  }

  // ... (event listeners aquí) ...

  const sesionActivaLocalStorage = localStorage.getItem(claveSesion);

  // Escenario 1: El usuario actual en esta pestaña NO está logueado, pero localStorage sí tiene una sesión activa
  if (usuarioActual === "null" && sesionActivaLocalStorage !== null && sesionActivaLocalStorage !== "null") {
      localStorage.removeItem(claveSesion);
      // REDIRECCIONAR A cerrarsesion.php
      window.location.href = "../funciones/cerrarsesion.php?motivo=sesion_caducada_local";
      return;
  }
  // Escenario 2: Hay un usuario logueado en esta pestaña.
  else if (usuarioActual !== "null") {
      if (sesionActivaLocalStorage === null || sesionActivaLocalStorage !== usuarioActual) {
          if (sesionActivaLocalStorage !== null && sesionActivaLocalStorage !== "null" && sesionActivaLocalStorage !== usuarioActual) {
              alert("Se ha detectado un cambio de usuario en otra pestaña o se ha iniciado sesión con otro usuario. La sesión actual se cerrará.");
              // REDIRECCIONAR A cerrarsesion.php
              window.location.href = "../funciones/cerrarsesion.php?motivo=cambio_usuario_local";
              return;
          }
          localStorage.setItem(claveSesion, usuarioActual);
      }
  }

  // Escenario 3: Escuchar cambios en `localStorage` provenientes de otras pestañas/ventanas
  window.addEventListener('storage', function (e) {
    if (e.key === claveSesion) {
      if (e.newValue === "null" || e.newValue === null) {
        // ... (cierre de sesión externo, inactividad, etc.) ...
      } else if (e.newValue !== usuarioActual && usuarioActual !== "null") {
        // ESTE ES EL CASO: Otro login en otra pestaña mientras esta estaba activa
        alert("Se ha iniciado sesión con otro usuario. Esta pestaña se cerrará.");

        let previousUserType = "general"; // Default para login.php (alumno/docente)
        if (usuarioActual.startsWith("sec_")) {
            previousUserType = "sec"; // Si el usuario activo era un secretario
        }
        // Si tienes otros tipos de usuarios o quieres distinguir alumno vs docente para el login principal, podrías añadir más lógica aquí.
        // Por ahora, 'doc_' o 'alu_' se agrupan en 'general' (login.php)

        window.location.href = "../funciones/cerrarsesion.php?motivo=otro_login_externo&prev_user_type=" + previousUserType;
      }
    }
  });

})();