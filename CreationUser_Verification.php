<?php
/** Création et édition du compte : requêtes préparées et stockage salé moderne. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/Methodes_Connexions.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('NOK'); }
$csrf = $_POST['csrf_token'] ?? null;
if (!is_string($csrf) || empty($_SESSION['coreen_account_csrf']) || !hash_equals($_SESSION['coreen_account_csrf'], $csrf)) {
    http_response_code(403); exit('Jeton de sécurité invalide. Rechargez la page.');
}
$update = isset($_GET['update']);
$login = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
$nom = is_string($_POST['nom'] ?? null) ? trim($_POST['nom']) : '';
$prenom = is_string($_POST['prenom'] ?? null) ? trim($_POST['prenom']) : '';
$pw = is_string($_POST['pw'] ?? null) ? strtolower($_POST['pw']) : '';
$mail1 = is_string($_POST['mail1'] ?? null) ? trim($_POST['mail1']) : '';
$mail2 = is_string($_POST['mail2'] ?? null) ? trim($_POST['mail2']) : '';
if (!$login || strlen($login) > 100 || strlen($nom) < 2 || strlen($prenom) < 2 ||
    strlen($nom) > 150 || strlen($prenom) > 150 ||
    (!$update && !preg_match('/^[a-f0-9]{32}$/D', $pw)) ||
    ($mail1 !== '' && !filter_var($mail1, FILTER_VALIDATE_EMAIL)) ||
    ($mail2 !== '' && !filter_var($mail2, FILTER_VALIDATE_EMAIL))) {
    http_response_code(400); exit('Vérifiez les informations saisies.');
}
$cols = [
    'Nom' => $nom, 'Prenom' => $prenom, 'Mail1' => $mail1 ?: null, 'Mail2' => $mail2 ?: null,
    'Telephone' => substr((string)($_POST['telephone'] ?? ''), 0, 40),
    'loginServiceSMSFree' => substr((string)($_POST['telephone_login'] ?? ''), 0, 255),
    'clefServiceSMSFree' => substr((string)($_POST['telephone_pw'] ?? ''), 0, 255),
    'KeyAPI' => (string)($_POST['key_api'] ?? ''),
    'SecretAPI' => (string)($_POST['secret_api'] ?? ''),
    'mail1Enable' => ($_POST['mail1_check'] ?? '') === '1' ? 1 : 0,
    'mail2Enable' => ($_POST['mail2_check'] ?? '') === '1' ? 1 : 0,
    'ServiceSMSFreeEnable' => ($_POST['telephone_check'] ?? '') === '1' ? 1 : 0,
];
try {
    if ($update) {
        $userId = filter_var($_SESSION['Connexion_User']['User_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId || !($_SESSION['Connexion_User']['Valider'] ?? false)) {
            http_response_code(403); exit('Vous devez être connecté.');
        }
        $parts = [];
        foreach ($cols as $column => $value) $parts[] = '`' . $column . '`=:' . $column;
        $sql = 'UPDATE `utilisateurs` SET ' . implode(', ', $parts) . ', `Date_upd`=NOW() WHERE `User_id`=:User_id';
        $stmt = $bdd->prepare($sql);
        $stmt->execute($cols + ['User_id' => $userId]);
        $read = $bdd->prepare('SELECT * FROM `utilisateurs` WHERE `User_id`=:id LIMIT 1');
        $read->execute([':id' => $userId]);
        $new = $read->fetch(PDO::FETCH_ASSOC);
        if ($new) { unset($new['Password']); $_SESSION['Connexion_User'] = array_merge($_SESSION['Connexion_User'], $new); }
    } else {
        $cols['Login'] = $login;
        $cols['Password'] = password_hash('md5:' . $pw, PASSWORD_DEFAULT);
        $cols['Administrateur'] = 0;
        $cols['SAdministrateur'] = 0;
        $cols['Valider'] = 0;
        $columns = array_keys($cols);
        $stmt = $bdd->prepare('INSERT INTO `utilisateurs` (`' . implode('`,`', $columns) . '`, `Date_cre`)
            VALUES (:' . implode(',:', $columns) . ', NOW())');
        $stmt->execute($cols);
    }
    echo 'OK';
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { http_response_code(409); exit('Cet identifiant est déjà utilisé.'); }
    error_log('[coreen] Enregistrement utilisateur : ' . $e->getMessage());
    http_response_code(503); echo 'Enregistrement temporairement indisponible.';
} catch (Throwable $e) {
    error_log('[coreen] Erreur utilisateur : ' . $e->getMessage());
    http_response_code(503); echo 'Enregistrement temporairement indisponible.';
}
