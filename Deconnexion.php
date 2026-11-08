<?php
// Compatibilite : l'ancien bouton AJAX envoie encore une requete GET.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Nouveau bouton sur index.php : POST securise par un jeton de session.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recu = $_POST['csrf_token'] ?? null;
    $attendu = $_SESSION['coreen_logout_csrf'] ?? '';
    if (!is_string($recu) || !is_string($attendu) || $attendu === '' || !hash_equals($attendu, $recu)) {
        http_response_code(403);
        exit('Jeton de déconnexion invalide. Rechargez la page.');
    }
    unset($_SESSION['Connexion_User'], $_SESSION['coreen_logout_csrf']);
    session_regenerate_id(true);
    header('Location: Connexion.php', true, 303);
    exit;
}

// Ancienne compatibilite du formulaire Connexion.php (appel AJAX GET).
unset($_SESSION['Connexion_User'], $_SESSION['coreen_logout_csrf']);
