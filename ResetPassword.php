<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/Methodes_Connexions.php';
require_once __DIR__ . '/PasswordReset_lib.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
if (empty($_SESSION['coreen_reset_csrf'])) $_SESSION['coreen_reset_csrf'] = bin2hex(random_bytes(32));
$token = is_string($_POST['token'] ?? null) ? $_POST['token'] : (is_string($_GET['token'] ?? null) ? $_GET['token'] : '');
$valid = null;
$error = '';
$success = (($_GET['done'] ?? '') === '1' && !empty($_SESSION['coreen_reset_success']));
if ($success) unset($_SESSION['coreen_reset_success']);
if (!$success) {
    try {
        $config = coreenResetSettings();
        if (!coreenResetIsSecureRequest($config)) throw new RuntimeException('Un accès HTTPS est nécessaire.');
        $valid = coreenResetFindValid($bdd, $token);
    } catch (Throwable $e) {
        error_log('[coreen-reset] Validation lien : ' . $e->getMessage());
        $error = 'La réinitialisation est indisponible. Vérifiez la configuration HTTPS/SMTP.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['coreen_reset_csrf'], $csrf)) {
        http_response_code(403); exit('Jeton de formulaire invalide.');
    }
    $pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';
    if (!$valid) {
        $error = 'Ce lien est invalide, expiré ou déjà utilisé.';
    } elseif (strlen($pass) < 12 || strlen($pass) > 128 || $pass !== $confirm) {
        $error = 'Choisissez un mot de passe de 12 à 128 caractères et confirmez-le à l’identique.';
    } else {
        try {
            $bdd->beginTransaction();
            $locked = coreenResetFindValid($bdd, $token, true);
            if (!$locked) throw new RuntimeException('Lien utilisé ou expiré.');
            // Compatibilité avec le client de connexion actuel (qui envoie MD5(mot de passe)).
            // Le stockage est bcrypt/argon via password_hash, et non plus du MD5 brut.
            $stored = password_hash('md5:' . md5($pass), PASSWORD_DEFAULT);
            $q = $bdd->prepare('UPDATE `utilisateurs` SET `Password`=:password, `Date_upd`=NOW() WHERE `User_id`=:id AND `Valider`=1');
            $q->execute([':password' => $stored, ':id' => $locked['User_id']]);
            if ($q->rowCount() !== 1) throw new RuntimeException('Compte indisponible.');
            $delete = $bdd->prepare('DELETE FROM `password_reset_tokens` WHERE `User_id`=:id');
            $delete->execute([':id' => $locked['User_id']]);
            $bdd->commit();
            unset($_SESSION['Connexion_User']);
            session_regenerate_id(true);
            $_SESSION['coreen_reset_success'] = true;
            header('Location: ResetPassword.php?done=1', true, 303);
            exit;
        } catch (Throwable $e) {
            if ($bdd->inTransaction()) $bdd->rollBack();
            $error = 'Ce lien est invalide, expiré ou déjà utilisé. Demandez un nouveau lien.';
            error_log('[coreen-reset] Enregistrement mot de passe : ' . $e->getMessage());
        }
    }
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Nouveau mot de passe · Vocabulaire coréen</title><link rel="icon" href="image/logo.jpeg" type="image/jpeg"><link rel="stylesheet" href="connexion.css?v=3"></head><body class="login-page"><div class="login-shell">
<header class="login-topbar"><a class="login-brand" href="Connexion.php"><span class="login-brand__icon">가</span><span>Vocabulaire <strong>coréen</strong></span></a></header>
<main class="login-layout"><section class="login-story"><div class="login-story__inner"><div class="login-eyebrow"><span class="login-eyebrow__dot"></span> APPRENDRE LE CORÉEN</div><h1>Un nouveau départ<span class="login-title-dot">.</span></h1><p>Choisissez un nouveau mot de passe pour retrouver votre espace d'apprentissage.</p></div><div class="login-story__symbol" aria-hidden="true">가</div></section>
<section class="login-panel"><div class="login-panel__mark" aria-hidden="true">안녕!</div><div class="login-panel__eyebrow">SÉCURITÉ DU COMPTE</div><h2><?= $success ? 'Mot de passe modifié' : 'Choisir un mot de passe' ?></h2>
<?php if ($success): ?><p class="login-panel__subtitle" role="status">Votre mot de passe a été modifié. Le lien de récupération ne peut plus être utilisé.</p><a class="reset-link-button" href="Connexion.php">Se connecter →</a>
<?php elseif (!$valid): ?><div class="login-feedback login-feedback--error" role="alert"><?= htmlspecialchars($error ?: 'Ce lien est invalide, expiré ou déjà utilisé.', ENT_QUOTES, 'UTF-8') ?></div><p class="login-signup"><a href="MotDePasseOublie.php">Demander un nouveau lien</a></p>
<?php else: ?>
<p class="login-panel__subtitle">12 caractères minimum. Évitez les mots de passe déjà utilisés.</p>
<?php if ($error): ?><div class="login-feedback login-feedback--error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post" class="reset-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['coreen_reset_csrf'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"><div class="login-field"><label for="password">Nouveau mot de passe</label><input id="password" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required autofocus></div><div class="login-field"><label for="confirmation">Confirmer le mot de passe</label><input id="confirmation" name="confirmation" type="password" minlength="12" maxlength="128" autocomplete="new-password" required></div><button type="submit" class="login-submit">Enregistrer le nouveau mot de passe →</button></form>
<?php endif; ?>
<div class="login-panel__separator"><span>ou</span></div><p class="login-signup"><a href="Connexion.php">← Retour à la connexion</a></p>
</section></main><footer class="login-footer">한국어 <span>·</span> Un peu de vocabulaire chaque jour fait toute la différence.</footer></div></body></html>
