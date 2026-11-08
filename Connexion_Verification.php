<?php
// Vérification de la connexion : compatible avec les mots de passe MD5 historiques.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/Methodes_Connexions.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('NOK');
}

$login = isset($_POST['login']) && is_string($_POST['login']) ? trim($_POST['login']) : '';
$pwMd5 = isset($_POST['pw']) && is_string($_POST['pw']) ? $_POST['pw'] : '';
if ($login === '' || strlen($login) > 100 || !preg_match('/^[a-f0-9]{32}$/i', $pwMd5)) {
    http_response_code(400);
    exit('NOK');
}

try {
    // Requête préparée pour éviter l'injection SQL sur le login.
    $requete = $bdd->prepare('SELECT * FROM `utilisateurs` WHERE `Login` = :login AND `Password` = :pw LIMIT 1');
    $requete->execute([':login' => $login, ':pw' => strtolower($pwMd5)]);
    $compte = $requete->fetch(PDO::FETCH_ASSOC);
    $requete->closeCursor();

    if (!$compte) {
        http_response_code(401);
        exit('NOK');
    }
    if (empty($compte['Valider'])) {
        http_response_code(403);
        exit('NON_VALIDE');
    }

    $historique = $bdd->prepare('SELECT `Date` FROM `historique` WHERE `User_id` = :id ORDER BY `Date` DESC LIMIT 1');
    $historique->execute([':id' => $compte['User_id']]);
    $precedente = $historique->fetch(PDO::FETCH_ASSOC);
    $historique->closeCursor();

    $ajout = $bdd->prepare('INSERT INTO `historique` (`User_id`, `Date`, `AdresseIp`, `Host`)
                            VALUES (:id, :date, :ip, :agent)');
    $ajout->execute([
        ':id' => $compte['User_id'],
        ':date' => date('Y-m-d H:i:s'),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    session_regenerate_id(true);
    $_SESSION['Connexion_User'] = $compte;
    $_SESSION['Connexion_User']['LastConnexion'] = $precedente['Date'] ?? null;
    echo 'OK';
} catch (Throwable $e) {
    error_log('[coreen] Erreur lors de l’authentification : ' . $e->getMessage());
    http_response_code(503);
    echo 'NOK';
}
