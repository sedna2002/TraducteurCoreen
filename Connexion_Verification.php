<?php
/** Authentification compatible avec l’ancien navigateur (MD5 transmis), migration vers password_hash. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/Methodes_Connexions.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('NOK'); }
$login = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
$pwMd5 = is_string($_POST['pw'] ?? null) ? strtolower($_POST['pw']) : '';
if ($login === '' || strlen($login) > 100 || !preg_match('/^[a-f0-9]{32}$/D', $pwMd5)) {
    http_response_code(400); exit('NOK');
}
try {
    $requete = $bdd->prepare('SELECT * FROM `utilisateurs` WHERE `Login` = :login LIMIT 1');
    $requete->execute([':login' => $login]);
    $compte = $requete->fetch(PDO::FETCH_ASSOC);
    $stored = is_array($compte) ? (string)$compte['Password'] : '';
    $legacy = (bool)preg_match('/^[a-f0-9]{32}$/iD', $stored);
    $ok = $legacy ? hash_equals(strtolower($stored), $pwMd5) :
        password_verify('md5:' . $pwMd5, $stored);
    if (!$compte || !$ok) { http_response_code(401); exit('NOK'); }
    if (!(int)$compte['Valider']) { http_response_code(403); exit('NON_VALIDE'); }

    // Convertit progressivement les empreintes MD5 brutes en hash moderne salé.
    if ($legacy || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        $newHash = password_hash('md5:' . $pwMd5, PASSWORD_DEFAULT);
        $migrate = $bdd->prepare('UPDATE `utilisateurs` SET `Password` = :new WHERE `User_id` = :id AND `Password` = :old');
        $migrate->execute([':new' => $newHash, ':id' => $compte['User_id'], ':old' => $stored]);
    }
    $q = $bdd->prepare('SELECT `Date` FROM `historique` WHERE `User_id` = :id ORDER BY `Date` DESC LIMIT 1');
    $q->execute([':id' => $compte['User_id']]);
    $last = $q->fetch(PDO::FETCH_ASSOC);
    $insert = $bdd->prepare('INSERT INTO `historique` (`User_id`,`Date`,`AdresseIp`,`Host`) VALUES (:id,NOW(),:ip,:agent)');
    $insert->execute([':id' => $compte['User_id'], ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 2000)]);
    session_regenerate_id(true);
    unset($compte['Password']);
    $_SESSION['Connexion_User'] = $compte;
    $_SESSION['Connexion_User']['LastConnexion'] = $last['Date'] ?? null;
    echo 'OK';
} catch (Throwable $e) {
    error_log('[coreen] Erreur authentification : ' . $e->getMessage());
    http_response_code(503); echo 'NOK';
}
