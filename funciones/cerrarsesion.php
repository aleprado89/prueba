<?php
session_start();

// 1. Determine redirect URL based on the most reliable information
$redirect_url = '../inicio/login.php'; // Default fallback to general login

// Priority 1: Check `prev_user_type` from JS (most reliable for "another login" scenario)
if (isset($_GET['prev_user_type'])) {
    switch ($_GET['prev_user_type']) {
        case 'sec':
            $redirect_url = '../inicio/loginAdmin.php';
            break;
        case 'general': // This will cover alu, doc, (or dual a-d)
            $redirect_url = '../inicio/login.php';
            break;
        // Add more cases if you have other distinct login pages
    }
}
// Priority 2: Fallback to `$_SESSION['redirect_after_logout']` (for manual logout/inactivity, where session is likely still intact)
// This remains important for graceful logouts not triggered by `sessionControl.js`'s storage event.
elseif (isset($_SESSION['redirect_after_logout'])) {
    $redirect_url = $_SESSION['redirect_after_logout'];
}
// Priority 3: Fallback to `$_SESSION['sec_id']` (less reliable as it might have been cleared by another tab, but good as a last resort)
elseif (isset($_SESSION['sec_id'])) {
    $redirect_url = '../inicio/loginAdmin.php';
}

// 2. Construct 'motivo' URL parameter
$motivoUrlParam = '';
if (isset($_GET['motivo'])) {
    $motivoUrlParam = 'motivo=' . urlencode($_GET['motivo']);
}

// 3. Destroy all session variables (AFTER reading necessary info for redirect)
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Clean localStorage and redirect
$finalRedirectUrl = htmlspecialchars($redirect_url);
if (!empty($motivoUrlParam)) {
    if (strpos($finalRedirectUrl, '?') !== false) {
        $finalRedirectUrl .= '&' . $motivoUrlParam;
    } else {
        $finalRedirectUrl .= '?' . $motivoUrlParam;
    }
}

echo '<script type="text/javascript">';
echo 'localStorage.removeItem("usuario_sesion_activa_global");';
echo 'window.location.href = "' . $finalRedirectUrl . '";';
echo '</script>';
exit();
?>