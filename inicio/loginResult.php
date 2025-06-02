<?php
session_start(); 
include 'conexion.php';
include '../funciones/parametrosWeb.php';
//include '../funciones/sesionInactiva.php';


// Lógica principal
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');



if (verificarAccesoAlumnoYDocente($username, $password, $conn, $datosColegio)) {
  // No hacer nada, ya que se redirigirá a la página de selección de rol
} elseif (verificarAccesoDocente($username, $password, $conn, $datosColegio)) {
  // Redirigir al menú de docentes
  header('Location: ../docentes/menudocentes.php');
  exit;
} elseif (verificarAccesoAlumno($username, $password, $conn, $datosColegio)) {
  // Redirigir al menú de alumnos
  header('Location: ../alumnos/menualumnos.php');
  exit;
} else {
  // Setear la variable de sesión con el mensaje de error
  $_SESSION['login_message'] = "Usuario o contraseña incorrectos.";
  // Redirigir al usuario de vuelta a login.php
  header('Location: login.php');
  exit;
}



//FUNCIONES

function verificarAccesoDocente($username, $password, $conn, $datosColegio) {
  $sql = "SELECT * FROM persona INNER JOIN personal ON persona.idPersona = personal.idPersona WHERE dni = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar si el docente ya tiene un registro en la tabla passwords
    $sql = "SELECT * FROM passwords INNER JOIN personal ON passwords.legajo = personal.legajo WHERE personal.idPersona = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $row['idPersona']);
    $stmt->execute();
    $result_passwords = $stmt->get_result();

    if ($result_passwords->num_rows > 0) {
      $row_passwords = $result_passwords->fetch_assoc();
      if ($password == $row_passwords['password']) {
        // Crear las variables de sesión y procesos que hace cuando el acceso es correcto
        $_SESSION['doc_nombre'] = $row["nombre"];
        $_SESSION['doc_apellido'] = $row["apellido"];
        $_SESSION['doc_dni'] = $row["dni"];
        $_SESSION['doc_legajo'] = $row["legajo"];
        $_SESSION['doc_idPersona'] = $row["idPersona"];

        // Verificar si la contraseña es igual a la variable de sesión
        if ($password == $_SESSION['claveDocente']) {
          // Actualizar la variable de sesión con la contraseña de la variable de sesión
          $_SESSION['cambiarClave'] = 1;}

        // Obtener los datos del colegio
        foreach ($datosColegio as $colegio) {
            $_SESSION['anioPlataformaDoc'] = $colegio['anioCargaNotas'];
            $_SESSION['nombreColegio'] = $colegio['nombreColegio'];
            //$_SESSION['idCiclo'] = $colegio['idciclolectivo'];
          
        }

        return true;
      } else {
        return false;
      }
    } else {
      // Si no tiene un registro en la tabla passwords, verificar si la contraseña es igual a la variable de sesión
      if ($password == $_SESSION['claveDocente']) {
        // Crear un registro en la tabla passwords con el legajo y la contraseña de la variable de sesión
        $sql = "INSERT INTO passwords (legajo, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $row['legajo'], $_SESSION['claveDocente']);
        $stmt->execute();

        // Crear las variables de sesión y procesos que hace cuando el acceso es correcto
        $_SESSION['doc_nombre'] = $row["nombre"];
        $_SESSION['doc_apellido'] = $row["apellido"];
        $_SESSION['doc_dni'] = $row["dni"];
        $_SESSION['doc_legajo'] = $row["legajo"];
        $_SESSION['doc_idPersona'] = $row["idPersona"];
        $_SESSION['cambiarClave'] = 1;

        // Obtener los datos del colegio
        foreach ($datosColegio as $colegio) {
            $_SESSION['anioPlataformaDoc'] = $colegio['anioCargaNotas'];
            $_SESSION['nombreColegio'] = $colegio['nombreColegio'];
            //$_SESSION['idCiclo'] = $colegio['idciclolectivo'];
          
        }

        return true;
      } else {
        return false;
      }
    }
  }

  return false;
}

function verificarAccesoAlumno($username, $password, $conn, $datosColegio) {
  $sql = "SELECT p.*, a.idAlumno FROM persona p INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona WHERE p.dni = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar si el alumno ya tiene un registro en la tabla passwords_alumnos
    $sql = "SELECT * FROM passwords_alumnos WHERE idAlumno = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $row['idAlumno']);
    $stmt->execute();
    $result_passwords = $stmt->get_result();

    if ($result_passwords->num_rows > 0) {
      $row_passwords = $result_passwords->fetch_assoc();
      if ($password == $row_passwords['password']) {
        // Crear las variables de sesión y procesos que hace cuando el acceso es correcto
        $_SESSION['alu_nombre'] = $row["nombre"];
        $_SESSION['alu_apellido'] = $row["apellido"];
        $_SESSION['alu_dni'] = $row["dni"];
        $_SESSION['alu_idAlumno'] = $row["idAlumno"];
        $_SESSION['alu_idPersona'] = $row["idPersona"];

        // Verificar si la contraseña es igual al usuario
        if ($password == $username) {
          $_SESSION['cambiarClave'] = 1;
        }

        // Obtener los datos del colegio
        foreach ($datosColegio as $colegio) {
            $_SESSION['anioPlataformaAlu'] = $colegio['anioautoweb'];
            $_SESSION['nombreColegio'] = $colegio['nombreColegio'];
           // $_SESSION['idCiclo'] = $colegio['idciclolectivo'];
          
        }

        return true;
      } else {
        return false;
      }
    } else {
      // Si no tiene un registro en la tabla passwords_alumnos, verificar si la contraseña es igual al DNI
      if ($password == $username) {
        // Crear un registro en la tabla passwords_alumnos con el idAlumno y de password el DNI
        $sql = "INSERT INTO passwords_alumnos (idAlumno, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $row['idAlumno'], $username);
        $stmt->execute();

        // Crear las variables de sesión y procesos que hace cuando el acceso es correcto
        $_SESSION['alu_nombre'] = $row["nombre"];
        $_SESSION['alu_apellido'] = $row["apellido"];
        $_SESSION['alu_dni'] = $row["dni"];
        $_SESSION['alu_idAlumno'] = $row["idAlumno"];
        $_SESSION['alu_idPersona'] = $row["idPersona"];
        $_SESSION['cambiarClave'] = 1;


        // Obtener los datos del colegio
        foreach ($datosColegio as $colegio) {
            $_SESSION['anioPlataformaAlu'] = $colegio['anioautoweb'];
            $_SESSION['nombreColegio'] = $colegio['nombreColegio'];
           // $_SESSION['idCiclo'] = $colegio['idciclolectivo'];
          
        }

        return true;
      } else {
        return false;
      }
    }
  }

  return false;
}
function verificarAccesoAlumnoYDocente($username, $password, $conn, $datosColegio) {
  $sql = "SELECT p.*, a.idAlumno, per.legajo FROM persona p 
          INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona 
          INNER JOIN personal per ON p.idPersona = per.idPersona 
          WHERE p.dni = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar si el alumno/docente ya tiene un registro en la tabla passwords_alumnos o passwords
    $sql = "SELECT * FROM passwords_alumnos WHERE idAlumno = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $row['idAlumno']);
    $stmt->execute();
    $result_passwords_alumno = $stmt->get_result();

    $sql = "SELECT * FROM passwords WHERE legajo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $row['legajo']);
    $stmt->execute();
    $result_passwords_docente = $stmt->get_result();

    if ($result_passwords_alumno->num_rows > 0 || $result_passwords_docente->num_rows > 0) {
      if ($password == $row['dni'] || $password == $_SESSION['claveDocente']) {
        // Crear un registro en la tabla passwords_alumnos o passwords si no existe
        if ($result_passwords_alumno->num_rows == 0) {
          $sql = "INSERT INTO passwords_alumnos (idAlumno, password) VALUES (?, ?)";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("is", $row['idAlumno'], $row['dni']);
          $stmt->execute();
        }
        if ($result_passwords_docente->num_rows == 0) {
          $sql = "INSERT INTO passwords (legajo, password) VALUES (?, ?)";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("ss", $row['legajo'], $row['dni']);
          $stmt->execute();
        }

         // Crear variables de sesión específicas para alumnos
  $_SESSION['alu_nombre'] = $row['nombre'];
  $_SESSION['alu_apellido'] = $row['apellido'];
  $_SESSION['alu_dni'] = $row['dni'];
  $_SESSION['alu_idAlumno'] = $row['idAlumno'];
  $_SESSION['alu_idPersona'] = $row['idPersona'];

  // Crear variables de sesión específicas para docentes
  $_SESSION['doc_nombre'] = $row['nombre'];
  $_SESSION['doc_apellido'] = $row['apellido'];
  $_SESSION['doc_dni'] = $row['dni'];
  $_SESSION['doc_legajo'] = $row['legajo'];
  $_SESSION['doc_idPersona'] = $row['idPersona'];

  // Verificar si la contraseña es igual a la variable de sesión para docentes o al DNI para alumnos
  if ($password == $_SESSION['claveDocente'] || $password == $username) {
    $_SESSION['cambiarClave'] = 1;
  }

  // Cargar datos del colegio
  foreach ($datosColegio as $colegio) {
    $_SESSION['nombreColegio'] = $colegio['nombreColegio'];
      $_SESSION['anioPlataformaAlu'] = $colegio['anioautoweb'];
      $_SESSION['anioPlataformaDoc'] = $colegio['anioCargaNotas'];
      }

        // Redirigir a la página de selección de rol
        $_SESSION['es_alumno_y_docente'] = true;
        header('Location: seleccionar_rol.php');
        exit;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  return false;
}
?>