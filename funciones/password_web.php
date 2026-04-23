<?php
/**
 * Contraseñas web: hash bcrypt (PASSWORD_DEFAULT) con compatibilidad texto plano legado.
 * Tablas: passwords, passwords_alumnos, usuarios (clave).
 */

/**
 * Indica si el valor almacenado parece un hash de password_hash() (bcrypt/argon).
 *
 * @param string|null $stored
 */
function password_web_is_hashed($stored) {
    if (!is_string($stored) || $stored === '') {
        return false;
    }
    $info = password_get_info($stored);
    return !empty($info['algo']);
}

/**
 * Verifica contraseña: password_verify si está hasheada; si no, comparación en tiempo constante (legado).
 *
 * @param string $plain
 * @param string|null $stored
 */
function password_web_verify($plain, $stored) {
    if ($stored === null || $stored === '') {
        return false;
    }
    if (password_web_is_hashed($stored)) {
        return password_verify($plain, $stored);
    }
    return hash_equals((string) $stored, (string) $plain);
}

/**
 * Genera hash para almacenar.
 *
 * @param string $plain
 */
function password_web_hash($plain) {
    return password_hash($plain, PASSWORD_DEFAULT);
}

/**
 * Obtiene la capacidad (CHARACTER_MAXIMUM_LENGTH) de una columna.
 *
 * @param mysqli $conn
 * @param string $table
 * @param string $column
 * @return int|null
 */
function password_web_column_capacity($conn, $table, $column) {
    $sql = "SELECT CHARACTER_MAXIMUM_LENGTH
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !isset($row['CHARACTER_MAXIMUM_LENGTH'])) {
        return null;
    }
    return (int) $row['CHARACTER_MAXIMUM_LENGTH'];
}

/**
 * Tras login exitoso con verificación legado: guarda hash en passwords_alumnos.
 *
 * @param mysqli $conn
 * @param int    $idAlumno
 * @param string $plain    Contraseña en texto (la que acaba de validar)
 */
function password_web_upgrade_alumno($conn, $idAlumno, $plain) {
    $hash = password_web_hash($plain);
    $cap = password_web_column_capacity($conn, 'passwords_alumnos', 'password');
    if ($cap !== null && $cap < strlen($hash)) {
        error_log('No se puede hashear passwords_alumnos.password: columna corta');
        return false;
    }
    $sql = 'UPDATE passwords_alumnos SET password = ? WHERE idAlumno = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('si', $hash, $idAlumno);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Tras login exitoso con verificación legado: guarda hash en passwords (docente).
 *
 * @param mysqli $conn
 * @param int    $legajo
 * @param string $plain
 */
function password_web_upgrade_docente($conn, $legajo, $plain) {
    $hash = password_web_hash($plain);
    $cap = password_web_column_capacity($conn, 'passwords', 'password');
    if ($cap !== null && $cap < strlen($hash)) {
        error_log('No se puede hashear passwords.password: columna corta');
        return false;
    }
    $sql = 'UPDATE passwords SET password = ? WHERE legajo = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('si', $hash, $legajo);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Tras login exitoso secretaría con clave en texto plano.
 *
 * @param mysqli $conn
 * @param int    $idUsuario
 * @param string $plain
 */
function password_web_upgrade_usuario_admin($conn, $idUsuario, $plain) {
    $hash = password_web_hash($plain);
    $cap = password_web_column_capacity($conn, 'usuarios', 'clave');
    if ($cap !== null && $cap < strlen($hash)) {
        error_log('No se puede hashear usuarios.clave: columna corta');
        return false;
    }
    $sql = 'UPDATE usuarios SET clave = ? WHERE idusuarios = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('si', $hash, $idUsuario);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
